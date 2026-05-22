<?php

/**
 * Copyright (C) InnoCraft Ltd - All rights reserved.
 *
 * NOTICE:  All information contained herein is, and remains the property of InnoCraft Ltd.
 * The intellectual and technical concepts contained herein are protected by trade secret or copyright law.
 * Redistribution of this information or reproduction of this material is strictly forbidden
 * unless prior written permission is obtained from InnoCraft Ltd.
 *
 * You shall use this code only in accordance with the license agreement obtained from InnoCraft Ltd.
 *
 * @link https://www.innocraft.com/
 * @license For license details see https://www.innocraft.com/license
 */

declare(strict_types=1);

namespace Piwik\Plugins\AIProviders\Provider;

use Exception;
use Piwik\Http;
use Piwik\Plugins\AIProviders\AIProviderException;

abstract class AIProvider
{
    private const TRANSIENT_ERROR_RETRY_DELAYS = [1, 2];

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var bool
     */
    private $supportsCustomEndpoint;

    public function __construct(
        string $id,
        string $name,
        string $description,
        bool $supportsCustomEndpoint = false
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->supportsCustomEndpoint = $supportsCustomEndpoint;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function supportsCustomEndpoint(): bool
    {
        return $this->supportsCustomEndpoint;
    }

    public function getDefaultEndpointUrl(): string
    {
        return '';
    }

    public function getDefaultModel(): string
    {
        return '';
    }

    /**
     * Completes the given prompt using the provider.
     *
     * @param array<string, string> $configuration
     */
    public function completePrompt(array $configuration, string $prompt): string
    {
        throw new AIProviderException(sprintf('%s does not support prompt completion.', $this->getName()));
    }

    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     description: string,
     *     supportsCustomEndpoint: bool,
     *     defaultModel: string
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'supportsCustomEndpoint' => $this->supportsCustomEndpoint(),
            'defaultModel' => $this->getDefaultModel(),
        ];
    }

    /**
     * @param array<string, string> $configuration
     */
    protected function getApiKey(array $configuration): string
    {
        $apiKey = trim($configuration['apiKey'] ?? '');

        if ($apiKey === '') {
            throw new AIProviderException(sprintf('No API key is configured for %s.', $this->getName()));
        }

        return $apiKey;
    }

    /**
     * @param array<string, string> $configuration
     */
    protected function getEndpointUrl(array $configuration): string
    {
        $endpointUrl = $this->supportsCustomEndpoint()
            ? trim($configuration['endpointUrl'] ?? '')
            : '';

        if ($endpointUrl === '') {
            $endpointUrl = $this->getDefaultEndpointUrl();
        }

        if ($endpointUrl === '') {
            throw new AIProviderException(sprintf('No endpoint URL is configured for %s.', $this->getName()));
        }

        $parsedUrl = parse_url($endpointUrl);
        $scheme = is_array($parsedUrl) ? ($parsedUrl['scheme'] ?? '') : '';

        if (
            !filter_var($endpointUrl, FILTER_VALIDATE_URL)
            || !in_array($scheme, ['http', 'https'], true)
        ) {
            throw new AIProviderException(sprintf('The endpoint URL for %s is invalid.', $this->getName()));
        }

        return $endpointUrl;
    }

    /**
     * Sends a JSON request to the provider and returns the decoded JSON object.
     *
     * @param array<string, string> $headers
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    protected function sendJsonRequest(string $url, array $headers, array $payload): array
    {
        $requestBody = json_encode($payload);

        if (!is_string($requestBody)) {
            throw new AIProviderException(sprintf('Could not encode request body for %s.', $this->getName()));
        }

        $requestHeaders = ['Content-Type: application/json'];
        foreach ($headers as $name => $value) {
            $requestHeaders[] = $name . ': ' . $value;
        }

        $retryDelays = self::TRANSIENT_ERROR_RETRY_DELAYS;

        for ($attempt = 0; $attempt <= count($retryDelays); $attempt++) {
            try {
                $response = Http::sendHttpRequestBy(
                    Http::getTransportMethod(),
                    $url,
                    30,
                    null,
                    null,
                    null,
                    0,
                    false,
                    false,
                    false,
                    true,
                    'POST',
                    null,
                    null,
                    $requestBody,
                    $requestHeaders
                );
            } catch (Exception $e) {
                throw new AIProviderException(sprintf(
                    'Could not connect to %s: %s',
                    $this->getName(),
                    $e->getMessage()
                ), 0, $e);
            }

            if (!is_array($response)) {
                throw new AIProviderException(sprintf('%s returned an invalid response.', $this->getName()));
            }

            $status = (int) ($response['status'] ?? 0);
            $body = is_string($response['data'] ?? null) ? $response['data'] : '';
            $decoded = $body !== '' ? json_decode($body, true) : null;

            if ($status >= 200 && $status < 300) {
                if (!is_array($decoded)) {
                    throw new AIProviderException(sprintf('%s returned invalid JSON.', $this->getName()));
                }

                return $decoded;
            }

            $providerError = $this->getProviderErrorMessage(is_array($decoded) ? $decoded : []);

            if ($this->isAuthenticationError($providerError)) {
                throw new AIProviderException(sprintf(
                    '%s rejected the API key. Check the key and try again.',
                    $this->getName()
                ));
            }

            if ($this->shouldRetryTransientError($status, $attempt, $retryDelays)) {
                sleep($retryDelays[$attempt]);
                continue;
            }

            $errorSuffix = $providerError !== '' ? ': ' . substr($providerError, 0, 300) : '';
            throw new AIProviderException(sprintf('%s request failed%s.', $this->getName(), $errorSuffix));
        }

        throw new AIProviderException(sprintf('%s request failed.', $this->getName()));
    }

    /**
     * @param array<string, mixed> $response
     */
    private function getProviderErrorMessage(array $response): string
    {
        $message = '';

        if (isset($response['error']) && is_string($response['error'])) {
            $message = $response['error'];
        } elseif (isset($response['error']) && is_array($response['error'])) {
            $error = $response['error'];
            if (isset($error['message']) && is_string($error['message'])) {
                $message = $error['message'];
            } elseif (isset($error['type']) && is_string($error['type'])) {
                $message = $error['type'];
            }
        }

        return $message;
    }

    private function isAuthenticationError(string $message): bool
    {
        $message = strtolower($message);

        return strpos($message, 'api key') !== false
            || strpos($message, 'x-api-key') !== false
            || strpos($message, 'authentication') !== false
            || strpos($message, 'unauthorized') !== false
            || strpos($message, 'invalid key') !== false;
    }

    /**
     * @param int[] $retryDelays
     */
    private function shouldRetryTransientError(int $status, int $attempt, array $retryDelays): bool
    {
        return in_array($status, [500, 503], true)
            && array_key_exists($attempt, $retryDelays);
    }
}

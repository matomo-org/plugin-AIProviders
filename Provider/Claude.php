<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\AIProviders\Provider;

use Piwik\Plugins\AIProviders\AIProviderResponse;
use Piwik\Plugins\AIProviders\AIRequest;

class Claude extends AIProvider
{
    private const DEFAULT_MODEL = 'claude-haiku-4-5';
    private const ANTHROPIC_VERSION = '2023-06-01';

    public function __construct()
    {
        parent::__construct('claude', 'Claude', 'AIProviders_ClaudeDescription');
    }

    public function getDefaultEndpointUrl(): string
    {
        return 'https://api.anthropic.com/v1/messages';
    }

    public function getDefaultModel(): string
    {
        return self::DEFAULT_MODEL;
    }

    /**
     * Custom Claude chat completion method.
     * @see https://platform.claude.com/docs/en/api/messages/create
     * @param array<string, string> $configuration
     */
    public function complete(AIRequest $request, array $configuration): AIProviderResponse
    {
        $model = $this->resolveModel($request);

        $payload = [
            'model' => $model,
            'max_tokens' => $request->getMaxTokens(),
            'temperature' => $request->getTemperature(),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $request->getUserPrompt(),
                ],
            ],
        ];

        $systemPrompt = $this->getSystemPrompt($request);
        if ($systemPrompt !== null && $systemPrompt !== '') {
            $payload['system'] = $systemPrompt;
        }

        $response = $this->sendJsonRequest(
            $this->getEndpointUrl($configuration),
            [
                'anthropic-version' => self::ANTHROPIC_VERSION,
                'x-api-key' => $this->getApiKey($configuration),
            ],
            $payload
        );

        $text = $response['content'][0]['text'] ?? '';

        return $this->buildResponse(
            $request,
            $model,
            is_string($text) ? $text : '',
            isset($response['usage']['input_tokens']) ? (int) $response['usage']['input_tokens'] : null,
            isset($response['usage']['output_tokens']) ? (int) $response['usage']['output_tokens'] : null
        );
    }

    /**
     * Validates credentials and reachability with a cheap models listing
     * (`GET /v1/models`) instead of spending generation tokens.
     *
     * @see https://platform.claude.com/docs/en/api/models-list
     * @param array<string, string> $configuration
     */
    public function verifyConnection(array $configuration): void
    {
        $modelsEndpoint = preg_replace(
            '#/messages/?$#',
            '/models',
            $this->getEndpointUrl($configuration)
        ) ?? $this->getEndpointUrl($configuration);

        $this->sendGetRequest(
            $modelsEndpoint,
            [
                'anthropic-version' => self::ANTHROPIC_VERSION,
                'x-api-key' => $this->getApiKey($configuration),
            ]
        );
    }
}

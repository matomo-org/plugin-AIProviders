<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\AIProviders\Provider;

use Exception;
use Piwik\Http;
use Piwik\Plugins\AIProviders\AIProviderResponse;
use Piwik\Plugins\AIProviders\AIRequest;
use RuntimeException;

abstract class AIProvider
{
    private const TRANSIENT_ERROR_RETRY_DELAYS = [1, 2];

    private const JSON_RESPONSE_INSTRUCTION = 'Respond with a single valid JSON object and nothing else. Do not wrap it in Markdown code fences.';

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

    /**
     * @var int|null
     */
    private $lastRequestExecutionTimeMs = null;

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
     * Completes the given request using the provider.
     *
     * Implementations should honor the request's system prompt, model,
     * max tokens, and temperature, and populate token usage on the response
     * when the provider reports it.
     *
     * @param array<string, string> $configuration
     */
    abstract public function complete(AIRequest $request, array $configuration): AIProviderResponse;

    /**
     * Returns whether the provider has everything it needs to run completions.
     *
     * The default implementation requires an API key, plus a custom endpoint
     * URL for providers that support one. Providers whose credentials are
     * supplied by the environment (rather than stored configuration) should
     * override this method.
     *
     * @param array<string, string> $configuration
     */
    public function isConfigured(array $configuration): bool
    {
        if (trim($configuration['apiKey'] ?? '') === '') {
            return false;
        }

        return !$this->supportsCustomEndpoint() || trim($configuration['endpointUrl'] ?? '') !== '';
    }

    /**
     * Returns the model to use for the request, falling back to the provider default.
     */
    protected function resolveModel(AIRequest $request): string
    {
        $model = $request->getModel();

        // TODO: Map capability levels to provider-specific models once the
        // model choices are confirmed. Callers can still override the model
        // explicitly per request for now.
        return $model !== null && $model !== '' ? $model : $this->getDefaultModel();
    }

    /**
     * Returns the system prompt for the request, augmented with a JSON-output
     * instruction when JSON mode is requested. Providers should use this rather
     * than reading the request's system prompt directly, so JSON mode works even
     * for providers without a native JSON option (and so the word "JSON" is
     * present, which some providers require to enable their JSON mode).
     */
    protected function getSystemPrompt(AIRequest $request): ?string
    {
        $systemPrompt = $request->getSystemPrompt();

        if (!$request->isJsonResponse()) {
            return $systemPrompt;
        }

        if ($systemPrompt === null || trim($systemPrompt) === '') {
            return self::JSON_RESPONSE_INSTRUCTION;
        }

        return rtrim($systemPrompt) . "\n\n" . self::JSON_RESPONSE_INSTRUCTION;
    }

    /**
     * @param int|null $inputTokens  Prompt tokens reported by the provider, if any.
     * @param int|null $outputTokens Completion tokens reported by the provider, if any.
     * @param array<string, mixed>|null $rawResponse Decoded provider response, if available.
     */
    protected function buildResponse(
        AIRequest $request,
        string $model,
        string $text,
        ?int $inputTokens = null,
        ?int $outputTokens = null,
        ?array $rawResponse = null
    ): AIProviderResponse {
        return new AIProviderResponse(
            $this->getId(),
            $this->getName(),
            $model,
            trim($text),
            $inputTokens,
            $outputTokens,
            $rawResponse,
            $this->getReasoningLevelUsed($request),
            $this->isWebSearchUsed($request),
            $this->lastRequestExecutionTimeMs
        );
    }

    protected function getReasoningLevelUsed(AIRequest $request): string
    {
        // TODO: Map AIRequest::getReasoningLevel() and getThinkingBudget() to
        // provider-specific request fields once the supported models/formats
        // are confirmed.
        return AIRequest::REASONING_NONE;
    }

    protected function isWebSearchUsed(AIRequest $request): bool
    {
        // TODO: Implement provider-specific web search/tool configuration for
        // OpenAI, Gemini, Claude, and managed providers separately.
        return false;
    }

    /**
     * Completes a request against an OpenAI-compatible Chat Completions endpoint.
     *
     * Shared by providers that speak the OpenAI `/chat/completions` wire format
     * (system + user messages, `max_tokens`/`temperature`, and a `usage` object
     * with `prompt_tokens`/`completion_tokens`).
     *
     * @param array<string, string> $headers Additional request headers, such as authentication.
     */
    protected function completeChatCompletion(AIRequest $request, string $endpointUrl, array $headers): AIProviderResponse
    {
        $messages = [];

        $systemPrompt = $this->getSystemPrompt($request);
        if ($systemPrompt !== null && $systemPrompt !== '') {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }

        $messages[] = ['role' => 'user', 'content' => $request->getUserPrompt()];

        $model = $this->resolveModel($request);

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $request->getMaxTokens(),
            'temperature' => $request->getTemperature(),
        ];

        if ($request->isJsonResponse()) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $response = $this->sendJsonRequest($endpointUrl, $headers, $payload);

        $text = $response['choices'][0]['message']['content'] ?? '';

        return $this->buildResponse(
            $request,
            $model,
            is_string($text) ? $text : '',
            isset($response['usage']['prompt_tokens']) ? (int) $response['usage']['prompt_tokens'] : null,
            isset($response['usage']['completion_tokens']) ? (int) $response['usage']['completion_tokens'] : null,
            $response
        );
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
            throw new RuntimeException(sprintf('No API key is configured for %s.', $this->getName()));
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
            throw new RuntimeException(sprintf('No endpoint URL is configured for %s.', $this->getName()));
        }

        $parsedUrl = parse_url($endpointUrl);
        $scheme = is_array($parsedUrl) ? ($parsedUrl['scheme'] ?? '') : '';

        if (
            !filter_var($endpointUrl, FILTER_VALIDATE_URL)
            || !in_array($scheme, ['http', 'https'], true)
        ) {
            throw new RuntimeException(sprintf('The endpoint URL for %s is invalid.', $this->getName()));
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
            throw new RuntimeException(sprintf('Could not encode request body for %s.', $this->getName()));
        }

        $requestHeaders = ['Content-Type: application/json'];
        foreach ($headers as $name => $value) {
            $requestHeaders[] = $name . ': ' . $value;
        }

        $retryDelays = self::TRANSIENT_ERROR_RETRY_DELAYS;
        $startedAt = microtime(true);

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
                throw new RuntimeException(sprintf(
                    'Could not connect to %s: %s',
                    $this->getName(),
                    $e->getMessage()
                ), 0, $e);
            }

            if (!is_array($response)) {
                throw new RuntimeException(sprintf('%s returned an invalid response.', $this->getName()));
            }

            $status = (int) ($response['status'] ?? 0);
            $body = is_string($response['data'] ?? null) ? $response['data'] : '';
            $decoded = $body !== '' ? json_decode($body, true) : null;

            if ($status >= 200 && $status < 300) {
                if (!is_array($decoded)) {
                    throw new RuntimeException(sprintf('%s returned invalid JSON.', $this->getName()));
                }

                $this->lastRequestExecutionTimeMs = (int) round((microtime(true) - $startedAt) * 1000);

                return $decoded;
            }

            $providerError = $this->getProviderErrorMessage(is_array($decoded) ? $decoded : []);

            if ($this->isAuthenticationError($providerError)) {
                throw new RuntimeException(sprintf(
                    '%s rejected the API key. Check the key and try again.',
                    $this->getName()
                ));
            }

            if ($this->shouldRetryTransientError($status, $attempt, $retryDelays)) {
                sleep($retryDelays[$attempt]);
                continue;
            }

            $errorSuffix = $providerError !== '' ? ': ' . substr($providerError, 0, 300) : '';
            throw new RuntimeException(sprintf('%s request failed%s.', $this->getName(), $errorSuffix));
        }

        throw new RuntimeException(sprintf('%s request failed.', $this->getName()));
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

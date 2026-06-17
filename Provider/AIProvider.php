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
use Piwik\Plugins\AIProviders\AIConversationRequest;
use Piwik\Plugins\AIProviders\AIConversationResponse;
use Piwik\Plugins\AIProviders\AIProviderResponse;
use Piwik\Plugins\AIProviders\AIRequest;
use Piwik\Plugins\AIProviders\CanonicalMessage;
use Piwik\Plugins\AIProviders\Exception\AIProviderClientException;
use Piwik\Plugins\AIProviders\Exception\AIProviderException;
use Piwik\Plugins\AIProviders\Exception\AIProviderServerException;

/**
 * @phpstan-import-type CanonicalMessageArray from CanonicalMessage
 * @phpstan-import-type CanonicalContentBlockArray from CanonicalMessage
 * @phpstan-import-type ToolCatalogEntryArray from AIConversationRequest
 */
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
     * Returns whether the provider implements {@link converse()}: multi-turn
     * conversations with tool calling. Callers should check
     * {@link \Piwik\Plugins\AIProviders\AIProviderService::canConverse()}
     * before offering conversational features.
     */
    public function supportsConversations(): bool
    {
        return false;
    }

    /**
     * Runs one conversational round-trip and returns the assistant's turn in
     * the canonical shape (see {@link \Piwik\Plugins\AIProviders\CanonicalMessage}).
     *
     * Implementations must translate the canonical messages and the tool
     * catalogue to their wire format, honour the request's system prompt,
     * model, max tokens, temperature, and timeout, and map their stop-reason
     * vocabulary to the AIConversationResponse::STOP_* constants where a
     * mapping exists. Providers that override this method must also override
     * {@link supportsConversations()} to return true.
     *
     * @param array<string, string> $configuration
     */
    public function converse(AIConversationRequest $request, array $configuration): AIConversationResponse
    {
        throw new AIProviderClientException(
            sprintf('%s does not support multi-turn conversations.', $this->getName())
        );
    }

    /**
     * Validates the given connection settings, throwing on failure.
     *
     * Used by the admin "test connection" flow before a configuration is
     * stored. The default implementation proves the full round-trip with a
     * tiny completion; providers that expose a cheaper auth/health endpoint
     * (e.g. a models listing) should override this to avoid spending
     * generation tokens. Returns normally when the connection works.
     *
     * @param array<string, string> $configuration
     * @throws AIProviderException when the connection cannot be established
     */
    public function verifyConnection(array $configuration): void
    {
        $request = (new AIRequest('Reply with the single word: OK', 'AIProviders'))
            ->withFeatureKey('test-connection')
            ->withMaxTokens(16);

        $response = $this->complete($request, $configuration);

        if (trim($response->getText()) === '') {
            throw new AIProviderServerException(sprintf('%s returned an empty response.', $this->getName()));
        }
    }

    /**
     * Returns the list of model identifiers the provider can serve with the
     * given configuration, used to populate the model picker in the admin UI.
     * The default implementation returns no models; providers that can discover
     * them (e.g. an OpenAI-compatible `/models` listing) should override this.
     *
     * @param array<string, string> $configuration
     * @return list<string>
     */
    public function listModels(array $configuration): array
    {
        return [];
    }

    /**
     * Returns whether the provider has everything it needs to run completions.
     *
     * Providers that talk to a fixed hosted API require an API key. Providers
     * that support a custom endpoint instead require the endpoint URL; their
     * API key is optional, because local LLM servers (Ollama, LM Studio,
     * llama.cpp, vLLM, …) commonly run with authentication disabled. Providers
     * whose credentials are supplied by the environment (rather than stored
     * configuration) should override this method.
     *
     * @param array<string, string> $configuration
     */
    public function isConfigured(array $configuration): bool
    {
        if ($this->supportsCustomEndpoint()) {
            return trim($configuration['endpointUrl'] ?? '') !== '';
        }

        return trim($configuration['apiKey'] ?? '') !== '';
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
     * Returns the model to use for the conversation request, falling back to
     * the provider default.
     */
    protected function resolveConversationModel(AIConversationRequest $request): string
    {
        $model = $request->getModel();

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
     */
    protected function buildResponse(
        AIRequest $request,
        string $model,
        string $text,
        ?int $inputTokens = null,
        ?int $outputTokens = null
    ): AIProviderResponse {
        return new AIProviderResponse(
            $this->getId(),
            $this->getName(),
            $model,
            trim($text),
            $inputTokens,
            $outputTokens,
            $this->getReasoningLevelUsed($request),
            $this->isWebSearchUsed($request),
            $this->lastRequestExecutionTimeMs
        );
    }

    /**
     * @param list<CanonicalContentBlockArray> $content canonical assistant content blocks
     * @param int|null $inputTokens  Prompt tokens reported by the provider, if any.
     * @param int|null $outputTokens Completion tokens reported by the provider, if any.
     */
    protected function buildConversationResponse(
        string $model,
        array $content,
        string $stopReason,
        ?int $inputTokens = null,
        ?int $outputTokens = null
    ): AIConversationResponse {
        return new AIConversationResponse(
            $this->getId(),
            $this->getName(),
            $model,
            $content,
            $stopReason,
            $inputTokens,
            $outputTokens,
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
            isset($response['usage']['completion_tokens']) ? (int) $response['usage']['completion_tokens'] : null
        );
    }

    /**
     * Runs one conversational round-trip against an OpenAI-compatible Chat
     * Completions endpoint.
     *
     * Shared by providers that speak the OpenAI `/chat/completions` wire format
     * for multi-turn, tool-calling conversations. The canonical message shape
     * is translated to OpenAI `messages` (system/user/assistant/tool roles,
     * assistant `tool_calls` with JSON-string arguments, and one `tool` message
     * per tool result), the tool catalogue to `tools`, and the response's
     * `choices[0]` back to canonical content blocks with stop reasons mapped
     * onto the AIConversationResponse::STOP_* constants.
     *
     * @see https://platform.openai.com/docs/api-reference/chat/create
     * @param array<string, string> $headers Additional request headers, such as authentication.
     */
    protected function converseChatCompletion(AIConversationRequest $request, string $endpointUrl, array $headers): AIConversationResponse
    {
        $model = $this->resolveConversationModel($request);

        $payload = [
            'model' => $model,
            'messages' => $this->canonicalMessagesToOpenAI($request->getMessages(), $request->getSystemPrompt()),
            'max_tokens' => $request->getMaxTokens(),
            'temperature' => $request->getTemperature(),
        ];

        $tools = $this->toolCatalogToOpenAI($request->getTools());
        if ($tools !== null) {
            $payload['tools'] = $tools;
        }

        $response = $this->sendJsonRequest($endpointUrl, $headers, $payload, $request->getTimeoutSeconds());

        $message = is_array($response['choices'][0]['message'] ?? null) ? $response['choices'][0]['message'] : [];
        $finishReason = is_string($response['choices'][0]['finish_reason'] ?? null)
            ? $response['choices'][0]['finish_reason']
            : '';

        return $this->buildConversationResponse(
            $model,
            $this->openAIMessageToCanonical($message),
            $this->mapOpenAIFinishReason($finishReason),
            isset($response['usage']['prompt_tokens']) ? (int) $response['usage']['prompt_tokens'] : null,
            isset($response['usage']['completion_tokens']) ? (int) $response['usage']['completion_tokens'] : null
        );
    }

    /**
     * @param list<CanonicalMessageArray> $messages canonical messages
     * @return list<array<string, mixed>>
     */
    private function canonicalMessagesToOpenAI(array $messages, ?string $systemPrompt): array
    {
        $openAIMessages = [];

        if ($systemPrompt !== null && $systemPrompt !== '') {
            $openAIMessages[] = ['role' => 'system', 'content' => $systemPrompt];
        }

        foreach ($messages as $message) {
            if ($message['role'] === 'assistant') {
                $openAIMessages[] = $this->canonicalAssistantToOpenAI($message['content']);
                continue;
            }

            if ($message['role'] === 'tool') {
                // OpenAI expects one message per tool result, so a single
                // canonical 'tool' message fans out into N 'tool' messages.
                foreach ($this->canonicalToolResultsToOpenAI($message['content']) as $toolMessage) {
                    $openAIMessages[] = $toolMessage;
                }
                continue;
            }

            // Canonical 'user' messages carry only text blocks.
            $openAIMessages[] = ['role' => 'user', 'content' => $this->textBlocksToString($message['content'])];
        }

        return $openAIMessages;
    }

    /**
     * @param list<CanonicalContentBlockArray> $content canonical assistant content blocks
     * @return array<string, mixed>
     */
    private function canonicalAssistantToOpenAI(array $content): array
    {
        $toolCalls = [];
        foreach (CanonicalMessage::toolUseBlocks($content) as $block) {
            // tool_call.arguments must be a JSON string holding an object even
            // when empty; json_encode collapses `[]` to `[]`, so coerce empty
            // inputs to stdClass so the arguments string is `{}`.
            $arguments = json_encode($block['input'] === [] ? new \stdClass() : $block['input']);
            $toolCalls[] = [
                'id' => $block['id'],
                'type' => 'function',
                'function' => [
                    'name' => $block['name'],
                    'arguments' => $arguments === false ? '{}' : $arguments,
                ],
            ];
        }

        // OpenAI requires the content key to be present even when tool_calls
        // carry the turn; an empty string keeps it valid.
        $message = ['role' => 'assistant', 'content' => $this->textBlocksToString($content)];

        if ($toolCalls !== []) {
            $message['tool_calls'] = $toolCalls;
        }

        return $message;
    }

    /**
     * @param list<CanonicalContentBlockArray> $content canonical tool_result blocks
     * @return list<array<string, mixed>>
     */
    private function canonicalToolResultsToOpenAI(array $content): array
    {
        $messages = [];
        foreach ($content as $block) {
            if (($block['type'] ?? null) !== 'tool_result') {
                continue;
            }
            $toolUseId = $block['tool_use_id'] ?? null;
            if (!is_string($toolUseId)) {
                continue;
            }
            $structured = is_array($block['structuredContent'] ?? null) ? $block['structuredContent'] : null;
            $mcpContent = is_array($block['content'] ?? null) ? $block['content'] : [];

            $messages[] = [
                'role' => 'tool',
                'tool_call_id' => $toolUseId,
                'content' => $this->toolResultContentToOpenAI($structured, $mcpContent),
            ];
        }

        return $messages;
    }

    /**
     * OpenAI tool messages carry a single string content. The tool's
     * structured output, when present, is serialised; otherwise MCP text
     * blocks are concatenated, with non-text blocks JSON-stringified so their
     * data still reaches the model.
     *
     * @param array<string, mixed>|null $structured
     * @param list<array<string, mixed>> $mcpContent
     */
    private function toolResultContentToOpenAI(?array $structured, array $mcpContent): string
    {
        if ($structured !== null) {
            $serialised = json_encode($structured);

            return $serialised === false ? '' : $serialised;
        }

        $parts = [];
        foreach ($mcpContent as $block) {
            if (!is_array($block)) {
                continue;
            }
            if (($block['type'] ?? null) === 'text' && is_string($block['text'] ?? null)) {
                $parts[] = $block['text'];
                continue;
            }
            $serialised = json_encode($block);
            if ($serialised !== false) {
                $parts[] = $serialised;
            }
        }

        return implode("\n", $parts);
    }

    /**
     * Concatenates the text blocks of a canonical content list, ignoring any
     * non-text blocks. Returns '' when there are none.
     *
     * @param list<CanonicalContentBlockArray> $content
     */
    private function textBlocksToString(array $content): string
    {
        $parts = [];
        foreach ($content as $block) {
            if (($block['type'] ?? null) === 'text' && is_string($block['text'] ?? null)) {
                $parts[] = $block['text'];
            }
        }

        return implode("\n", $parts);
    }

    /**
     * @param list<ToolCatalogEntryArray> $tools
     * @return list<array{type: string, function: array<string, mixed>}>|null
     */
    private function toolCatalogToOpenAI(array $tools): ?array
    {
        if ($tools === []) {
            return null;
        }

        $openAITools = [];
        foreach ($tools as $tool) {
            $openAITools[] = [
                'type' => 'function',
                'function' => [
                    'name' => $tool['name'],
                    'description' => $tool['description'],
                    'parameters' => $this->toToolParametersObjectSchema($tool['inputSchema']),
                ],
            ];
        }

        return $openAITools;
    }

    /**
     * Makes a tool's parameter schema acceptable to OpenAI and Gemini.
     *
     * Both reject certain keywords at the top level of the schema
     * (`oneOf`/`anyOf`/`allOf`/`not`/`enum`/`const`), so this method strips them and
     * forces a plain top-level `type: object`.
     *
     * These are only validation hints, and the tool server re-validates arguments
     * on the actual call, so nothing is really loosened. Nested property schemas
     * are left untouched, and `additionalProperties` are kept on purpose
     * (OpenAI's strict mode requires it).
     *
     * Claude and Bedrock accept the original schema and skip this entirely.
     * Gemini is stricter and adds a deeper recursive strip on top, in
     * {@see Gemini::geminiParameterSchema()}.
     *
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    protected function toToolParametersObjectSchema(array $schema): array
    {
        unset(
            $schema['oneOf'],
            $schema['anyOf'],
            $schema['allOf'],
            $schema['not'],
            $schema['enum'],
            $schema['const']
        );

        if (($schema['type'] ?? null) !== 'object') {
            $schema['type'] = 'object';
        }

        return $schema;
    }

    /**
     * @param array<string, mixed> $message OpenAI assistant message
     * @return list<CanonicalContentBlockArray> canonical assistant content blocks
     */
    private function openAIMessageToCanonical(array $message): array
    {
        $canonical = [];

        $text = $message['content'] ?? null;
        if (is_string($text) && $text !== '') {
            $canonical[] = ['type' => 'text', 'text' => $text];
        }

        $toolCalls = is_array($message['tool_calls'] ?? null) ? $message['tool_calls'] : [];
        foreach ($toolCalls as $toolCall) {
            if (!is_array($toolCall)) {
                continue;
            }
            $id = $toolCall['id'] ?? null;
            $name = $toolCall['function']['name'] ?? null;
            if (!is_string($id) || !is_string($name)) {
                continue;
            }

            $arguments = $toolCall['function']['arguments'] ?? null;
            $decoded = is_string($arguments) && $arguments !== '' ? json_decode($arguments, true) : [];
            $input = [];
            if (is_array($decoded)) {
                foreach ($decoded as $key => $value) {
                    if (is_string($key)) {
                        $input[$key] = $value;
                    }
                }
            }

            $canonical[] = [
                'type' => 'tool_use',
                'id' => $id,
                'name' => $name,
                'input' => $input,
            ];
        }

        return $canonical;
    }

    /**
     * Maps an OpenAI `finish_reason` onto the canonical stop reasons, passing
     * unknown values through unchanged.
     */
    private function mapOpenAIFinishReason(string $finishReason): string
    {
        switch ($finishReason) {
            case 'tool_calls':
                return AIConversationResponse::STOP_TOOL_USE;
            case 'stop':
                return AIConversationResponse::STOP_END_TURN;
            case 'length':
                return AIConversationResponse::STOP_MAX_TOKENS;
            case 'content_filter':
                return AIConversationResponse::STOP_GUARDRAIL_INTERVENED;
            default:
                return $finishReason;
        }
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
            throw new AIProviderClientException(sprintf('No API key is configured for %s.', $this->getName()));
        }

        return $apiKey;
    }

    /**
     * Builds the bearer Authorization header for OpenAI-compatible providers,
     * omitting it entirely when no API key is configured. This lets custom
     * endpoints point at local LLM servers that run without authentication
     * while still sending the key when one is provided.
     *
     * @param array<string, string> $configuration
     * @return array<string, string>
     */
    protected function getBearerAuthorizationHeaders(array $configuration): array
    {
        $apiKey = trim($configuration['apiKey'] ?? '');

        return $apiKey === '' ? [] : ['Authorization' => 'Bearer ' . $apiKey];
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
            throw new AIProviderClientException(sprintf('No endpoint URL is configured for %s.', $this->getName()));
        }

        $parsedUrl = parse_url($endpointUrl);
        $scheme = is_array($parsedUrl) ? ($parsedUrl['scheme'] ?? '') : '';

        if (
            !filter_var($endpointUrl, FILTER_VALIDATE_URL)
            || !in_array($scheme, ['http', 'https'], true)
        ) {
            throw new AIProviderClientException(sprintf('The endpoint URL for %s is invalid.', $this->getName()));
        }

        return $endpointUrl;
    }

    /**
     * Derives the OpenAI-compatible models-listing endpoint from a chat
     * completions endpoint, e.g. `.../v1/chat/completions` or a bare `.../v1`
     * base both become `.../v1/models` — the standard `GET {base}/models`
     * probe used by the "test connection" flow.
     */
    protected function openAiCompatibleModelsEndpoint(string $chatEndpointUrl): string
    {
        $base = preg_replace('#/chat/completions/?$#', '', $chatEndpointUrl) ?? $chatEndpointUrl;

        return rtrim($base, '/') . '/models';
    }

    /**
     * Sends a JSON request to the provider and returns the decoded JSON object.
     *
     * Transient errors (HTTP 500/503) are retried with backoff. Failures are
     * classified: {@link AIProviderClientException} for authentication and
     * 4xx responses, {@link AIProviderServerException} for 5xx responses
     * after retries, {@link AIProviderException} for transport and protocol
     * failures.
     *
     * @param array<string, string> $headers
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    protected function sendJsonRequest(string $url, array $headers, array $payload, int $timeoutSeconds = 60): array
    {
        $requestBody = json_encode($payload);

        if (!is_string($requestBody)) {
            throw new AIProviderException(sprintf('Could not encode request body for %s.', $this->getName()));
        }

        return $this->sendRequest('POST', $url, $headers, $requestBody, $timeoutSeconds);
    }

    /**
     * Sends a GET request to the provider and returns the decoded JSON object,
     * used by the "test connection" probe. Failures are classified exactly as
     * in {@link sendJsonRequest()}.
     *
     * @param array<string, string> $headers
     * @return array<string, mixed>
     */
    protected function sendGetRequest(string $url, array $headers, int $timeoutSeconds = 10): array
    {
        return $this->sendRequest('GET', $url, $headers, null, $timeoutSeconds);
    }

    /**
     * Performs the actual HTTP exchange, retrying transient errors and
     * classifying failures. Shared by {@link sendJsonRequest()} and
     * {@link sendGetRequest()}.
     *
     * @param array<string, string> $headers
     * @return array<string, mixed>
     */
    private function sendRequest(
        string $method,
        string $url,
        array $headers,
        ?string $requestBody,
        int $timeoutSeconds
    ): array {
        $requestHeaders = [];
        if ($requestBody !== null) {
            $requestHeaders[] = 'Content-Type: application/json';
        }
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
                    max(1, $timeoutSeconds),
                    null,
                    null,
                    null,
                    0,
                    false,
                    false,
                    false,
                    true,
                    $method,
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

                $this->lastRequestExecutionTimeMs = (int) round((microtime(true) - $startedAt) * 1000);

                return $decoded;
            }

            $providerError = $this->getProviderErrorMessage(is_array($decoded) ? $decoded : []);

            if ($this->isAuthenticationError($providerError)) {
                throw new AIProviderClientException(sprintf(
                    '%s rejected the API key. Check the key and try again.',
                    $this->getName()
                ));
            }

            if ($this->shouldRetryTransientError($status, $attempt, $retryDelays)) {
                sleep($retryDelays[$attempt]);
                continue;
            }

            $errorSuffix = $providerError !== '' ? ': ' . substr($providerError, 0, 300) : '';
            $message = sprintf('%s request failed%s.', $this->getName(), $errorSuffix);

            if ($status >= 500) {
                throw new AIProviderServerException($message);
            }

            if ($status >= 400) {
                throw new AIProviderClientException($message);
            }

            throw new AIProviderException($message);
        }

        throw new AIProviderServerException(sprintf('%s request failed.', $this->getName()));
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

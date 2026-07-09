<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\AIProviders\Provider;

use Piwik\Plugins\AIProviders\AIConversationRequest;
use Piwik\Plugins\AIProviders\AIConversationResponse;
use Piwik\Plugins\AIProviders\AIProviderResponse;
use Piwik\Plugins\AIProviders\AIRequest;
use Piwik\Plugins\AIProviders\CanonicalMessage;
use Piwik\Plugins\AIProviders\Exception\AIProviderClientException;

/**
 * AWS Bedrock provider using the Converse HTTP API.
 *
 * Authenticates with an Amazon Bedrock API key sent as a bearer token, so no
 * AWS SDK or SigV4 signing is needed. The key must be a long-term API key
 * (short-term keys expire within 12 hours and cannot serve a stored
 * configuration).
 *
 * The AWS region is configured directly; runtime and control-plane endpoints
 * are derived internally so Matomo never sends the key to arbitrary hosts.
 *
 * @see https://docs.aws.amazon.com/bedrock/latest/userguide/api-keys.html
 * @phpstan-import-type CanonicalMessageArray from CanonicalMessage
 * @phpstan-import-type CanonicalContentBlockArray from CanonicalMessage
 * @phpstan-import-type ToolCatalogEntryArray from AIConversationRequest
 */
class Bedrock extends AIProvider
{
    public const ID = 'bedrock';

    private const DEFAULT_REGION = 'us-east-1';
    private const DEFAULT_MODEL = 'openai.gpt-oss-120b-1:0';

    /** Timeout for single-shot completions; conversations use the request's own budget. */
    private const COMPLETE_TIMEOUT_SECONDS = 30;

    public function __construct()
    {
        parent::__construct(
            self::ID,
            'AWS Bedrock',
            'AIProviders_BedrockDescription',
            true
        );
    }

    public function getDefaultEndpointUrl(): string
    {
        return self::DEFAULT_REGION;
    }

    public function getEndpointFieldTitle(): string
    {
        return 'AIProviders_BedrockEndpointTitle';
    }

    public function getEndpointFieldPlaceholder(): string
    {
        return 'AIProviders_BedrockEndpointPlaceholder';
    }

    public function supportsFipsEndpoint(): bool
    {
        return true;
    }

    public function endpointFieldRequiresUrl(): bool
    {
        return false;
    }

    public function getDefaultModel(): string
    {
        return self::DEFAULT_MODEL;
    }

    /**
     * The endpoint has a working default (it only carries the region), so the
     * API key is the one required piece.
     *
     * @param array<string, string> $configuration
     */
    public function isConfigured(array $configuration): bool
    {
        return trim($configuration['apiKey'] ?? '') !== '';
    }

    /**
     * Matomo's HTTP layer blocks `*.amazonaws.com` as SSRF protection (see
     * `http.blocklist.hosts`). Only the genuine Bedrock service hostnames are
     * trusted; other AWS services and custom endpoints stay blocked.
     */
    protected function isTrustedRequestHost(string $host): bool
    {
        return preg_match('/^bedrock(-runtime)?(-fips)?\.[a-z]{2}(?:-[a-z0-9]+)+-[0-9]+\.amazonaws\.com$/i', $host) === 1;
    }

    /**
     * @param array<string, string> $configuration
     */
    public function complete(AIRequest $request, array $configuration): AIProviderResponse
    {
        $model = $this->resolveConfiguredModel($request->getModel(), $configuration);

        $payload = [
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        ['text' => $request->getUserPrompt()],
                    ],
                ],
            ],
            'inferenceConfig' => [
                'maxTokens' => $request->getMaxTokens(),
                'temperature' => $request->getTemperature(),
            ],
        ];

        $systemPrompt = $this->getSystemPrompt($request);
        if ($systemPrompt !== null && $systemPrompt !== '') {
            $payload['system'] = [
                ['text' => $systemPrompt],
            ];
        }

        $response = $this->sendConverseRequest($model, $payload, self::COMPLETE_TIMEOUT_SECONDS, $configuration);

        $stopReason = is_string($response['stopReason'] ?? null) ? $response['stopReason'] : null;

        return $this->buildResponse(
            $request,
            $model,
            $this->extractText($response),
            isset($response['usage']['inputTokens']) ? (int) $response['usage']['inputTokens'] : null,
            isset($response['usage']['outputTokens']) ? (int) $response['usage']['outputTokens'] : null,
            $stopReason
        );
    }

    public function supportsConversations(): bool
    {
        return true;
    }

    /**
     * Runs one conversational round-trip, translating the canonical message
     * shape to and from the Converse wire format.
     *
     * @see https://docs.aws.amazon.com/bedrock/latest/APIReference/API_runtime_Converse.html
     * @param array<string, string> $configuration
     */
    public function converse(AIConversationRequest $request, array $configuration): AIConversationResponse
    {
        $model = $this->resolveConfiguredModel($request->getModel(), $configuration);

        $payload = [
            'messages' => $this->canonicalMessagesToBedrock($request->getMessages()),
            'inferenceConfig' => [
                'maxTokens' => $request->getMaxTokens(),
                'temperature' => $request->getTemperature(),
            ],
        ];

        $systemPrompt = $request->getSystemPrompt();
        if ($systemPrompt !== null && $systemPrompt !== '') {
            $payload['system'] = [['text' => $systemPrompt]];
        }

        $toolConfig = $this->toolCatalogToBedrock($request->getTools());
        if ($toolConfig !== null) {
            $payload['toolConfig'] = $toolConfig;
        }

        $response = $this->sendConverseRequest($model, $payload, $request->getTimeoutSeconds(), $configuration);

        $rawContent = $response['output']['message']['content'] ?? null;
        $stopReason = is_string($response['stopReason'] ?? null) ? $response['stopReason'] : '';

        return $this->buildConversationResponse(
            $model,
            $this->bedrockContentToCanonical(is_array($rawContent) ? $rawContent : []),
            $stopReason,
            isset($response['usage']['inputTokens']) ? (int) $response['usage']['inputTokens'] : null,
            isset($response['usage']['outputTokens']) ? (int) $response['usage']['outputTokens'] : null
        );
    }

    /**
     * Probes with the model listing instead of spending generation tokens.
     *
     * @param array<string, string> $configuration
     */
    public function verifyConnection(array $configuration): void
    {
        $this->listModels($configuration);
    }

    /**
     * Discovers invokable models via the control-plane listing (host
     * `bedrock.`, not the runtime `bedrock-runtime.`, same region).
     * `byInferenceType=ON_DEMAND` drops models that need an inference profile.
     *
     * @see https://docs.aws.amazon.com/bedrock/latest/APIReference/API_ListFoundationModels.html
     * @param array<string, string> $configuration
     * @return list<string>
     */
    public function listModels(array $configuration): array
    {
        $response = $this->sendGetRequest(
            $this->getModelListingEndpoint($this->getRegion($configuration), $configuration),
            $this->getAuthorizationHeader($configuration)
        );

        $models = [];
        foreach ($response['modelSummaries'] ?? [] as $summary) {
            if (is_array($summary) && isset($summary['modelId']) && is_string($summary['modelId']) && $summary['modelId'] !== '') {
                $models[] = $summary['modelId'];
            }
        }

        sort($models);

        return $models;
    }

    /**
     * Normalizes the AWS region. Bedrock endpoints are derived internally so
     * user/config input never decides the request host directly.
     */
    public function normalizeEndpointUrl(string $endpointUrl): string
    {
        return $this->normalizeRegion($endpointUrl);
    }

    public function normalizeRegion(string $region): string
    {
        $region = strtolower(trim($region));

        if ($region === '') {
            return self::DEFAULT_REGION;
        }

        if (preg_match('/^[a-z]{2}(?:-[a-z0-9]+)+-[0-9]+$/', $region) !== 1) {
            throw new AIProviderClientException(sprintf('The AWS region for %s is invalid.', $this->getName()));
        }

        return $region;
    }

    /**
     * Applies region normalization on use as well: config-file credentials
     * bypass the admin-side normalization.
     *
     * @param array<string, mixed> $configuration
     */
    protected function getEndpointUrl(array $configuration): string
    {
        return $this->getRuntimeEndpoint($this->getRegion($configuration), $configuration);
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private function getRegion(array $configuration): string
    {
        return $this->normalizeRegion((string) ($configuration['endpointUrl'] ?? ''));
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private function getRuntimeEndpoint(string $region, array $configuration): string
    {
        $service = $this->useFipsEndpoint($configuration) ? 'bedrock-runtime-fips' : 'bedrock-runtime';

        return sprintf('https://%s.%s.amazonaws.com', $service, $region);
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private function getControlPlaneEndpoint(string $region, array $configuration): string
    {
        $service = $this->useFipsEndpoint($configuration) ? 'bedrock-fips' : 'bedrock';

        return sprintf('https://%s.%s.amazonaws.com', $service, $region);
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private function useFipsEndpoint(array $configuration): bool
    {
        return !empty($configuration['useFipsEndpoint']);
    }

    /**
     * Resolves the model: per-request, then saved configuration, then default.
     *
     * @param array<string, string> $configuration
     */
    protected function resolveConfiguredModel(?string $requestModel, array $configuration): string
    {
        if ($requestModel !== null && $requestModel !== '') {
            return $requestModel;
        }

        $configuredModel = trim($configuration['model'] ?? '');

        return $configuredModel !== '' ? $configuredModel : $this->getDefaultModel();
    }

    /**
     * POSTs the Converse payload to the regional runtime endpoint with
     * bearer-key auth.
     *
     * @param array<string, mixed> $payload Converse request body, without the model
     * @param array<string, string> $configuration
     * @return array<string, mixed>
     */
    protected function sendConverseRequest(string $model, array $payload, int $timeoutSeconds, array $configuration): array
    {
        return $this->sendJsonRequest(
            $this->getConverseEndpoint($this->getEndpointUrl($configuration), $model),
            $this->getAuthorizationHeader($configuration),
            $payload,
            $timeoutSeconds
        );
    }

    /**
     * The model travels in the URL path, not the payload. Inference-profile
     * IDs and ARNs contain `:` and `/`, so the model must be encoded as a
     * single path segment.
     */
    private function getConverseEndpoint(string $endpointUrl, string $model): string
    {
        return rtrim($endpointUrl, '/') . '/model/' . rawurlencode($model) . '/converse';
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private function getModelListingEndpoint(string $region, array $configuration): string
    {
        return $this->getControlPlaneEndpoint($region, $configuration) . '/foundation-models?byInferenceType=ON_DEMAND';
    }

    /**
     * @param array<string, string> $configuration
     * @return array<string, string>
     */
    private function getAuthorizationHeader(array $configuration): array
    {
        return ['Authorization' => 'Bearer ' . $this->getApiKey($configuration)];
    }

    /**
     * Translates canonical messages into Bedrock Converse messages.
     *
     * Converse requires turns to alternate user/assistant, and a canonical
     * 'tool' row folds into a 'user' turn. A tool-result turn followed by the
     * user's next message would emit two consecutive 'user' turns, which
     * Bedrock rejects, so runs of same-role messages are merged into one turn
     * (concatenating their content blocks is valid Converse input).
     *
     * @param list<CanonicalMessageArray> $messages canonical messages
     * @return list<array{role: string, content: list<array<string, mixed>>}>
     */
    private function canonicalMessagesToBedrock(array $messages): array
    {
        $bedrockMessages = [];
        foreach ($messages as $message) {
            // Converse uses two roles ('user', 'assistant') and folds tool
            // results into a 'user' message body so the toolUse/toolResult
            // pairing survives a round trip.
            $role = $message['role'] === 'assistant' ? 'assistant' : 'user';

            $blocks = [];
            foreach ($message['content'] as $block) {
                $translated = $this->canonicalBlockToBedrock($block);
                if ($translated !== null) {
                    $blocks[] = $translated;
                }
            }

            $lastIndex = count($bedrockMessages) - 1;
            if ($lastIndex >= 0 && $bedrockMessages[$lastIndex]['role'] === $role) {
                $bedrockMessages[$lastIndex]['content'] = array_merge(
                    $bedrockMessages[$lastIndex]['content'],
                    $blocks
                );
                continue;
            }

            $bedrockMessages[] = ['role' => $role, 'content' => $blocks];
        }

        // array_values keeps this a list after the index-keyed coalescing
        // merges above.
        return array_values($bedrockMessages);
    }

    /**
     * @param array<string, mixed> $block
     * @return array<string, mixed>|null null when the block shape is unrecognised
     */
    private function canonicalBlockToBedrock(array $block): ?array
    {
        $type = $block['type'] ?? null;

        if ($type === 'text' && is_string($block['text'] ?? null)) {
            return ['text' => $block['text']];
        }

        if ($type === 'tool_use') {
            $id = $block['id'] ?? null;
            $name = $block['name'] ?? null;
            $input = $block['input'] ?? [];
            if (!is_string($id) || !is_string($name) || !is_array($input)) {
                return null;
            }

            // Bedrock requires toolUse.input to be a JSON object, even when
            // empty. `json_decode($body, true)` collapses `{}` to `[]`; if
            // that round-trips back to Bedrock it would emit `"[]"` and the
            // next turn rejects it with a 400. Coerce empty inputs back to
            // stdClass so json_encode produces `{}`.
            $inputForWire = $input === [] ? new \stdClass() : $input;

            return ['toolUse' => ['toolUseId' => $id, 'name' => $name, 'input' => $inputForWire]];
        }

        if ($type === 'tool_result') {
            $toolUseId = $block['tool_use_id'] ?? null;
            if (!is_string($toolUseId)) {
                return null;
            }
            $structured = is_array($block['structuredContent'] ?? null) ? $block['structuredContent'] : null;
            $mcpContent = is_array($block['content'] ?? null) ? $block['content'] : [];

            return [
                'toolResult' => [
                    'toolUseId' => $toolUseId,
                    'content' => $this->toolResultContentToBedrock($structured, $mcpContent),
                    'status' => !empty($block['is_error']) ? 'error' : 'success',
                ],
            ];
        }

        return null;
    }

    /**
     * Chooses the optimal Bedrock `toolResult.content` shape:
     *
     * - structuredContent → `[{json: <object>}]`. The model receives the
     *   tool's structured output natively without re-parsing an escaped JSON
     *   string from a text block.
     * - Otherwise each MCP content block is translated to its Bedrock
     *   counterpart, with non-text blocks JSON-stringified so the model
     *   still sees the data.
     *
     * @param array<string, mixed>|null $structured
     * @param list<array<string, mixed>> $mcpContent
     * @return list<array<string, mixed>>
     */
    private function toolResultContentToBedrock(?array $structured, array $mcpContent): array
    {
        if ($structured !== null) {
            return [['json' => $structured]];
        }

        $bedrockBlocks = [];
        foreach ($mcpContent as $block) {
            if (!is_array($block)) {
                continue;
            }
            if (($block['type'] ?? null) === 'text' && is_string($block['text'] ?? null)) {
                $bedrockBlocks[] = ['text' => $block['text']];
                continue;
            }
            $serialised = json_encode($block);
            if ($serialised !== false) {
                $bedrockBlocks[] = ['text' => $serialised];
            }
        }

        if ($bedrockBlocks === []) {
            $bedrockBlocks[] = ['text' => ''];
        }

        return $bedrockBlocks;
    }

    /**
     * Translates the tool catalogue into the Converse `toolConfig` shape, or
     * null for an empty catalogue so `toolConfig` is omitted entirely.
     *
     * @param list<ToolCatalogEntryArray> $tools
     * @return array{tools: list<array<string, mixed>>}|null
     */
    private function toolCatalogToBedrock(array $tools): ?array
    {
        if ($tools === []) {
            return null;
        }

        $bedrockTools = [];
        foreach ($tools as $tool) {
            $bedrockTools[] = [
                'toolSpec' => [
                    'name' => $tool['name'],
                    'description' => $tool['description'],
                    'inputSchema' => ['json' => $tool['inputSchema']],
                ],
            ];
        }

        return ['tools' => $bedrockTools];
    }

    /**
     * @param list<mixed> $content Bedrock assistant content blocks
     * @return list<CanonicalContentBlockArray> canonical assistant content blocks
     */
    private function bedrockContentToCanonical(array $content): array
    {
        $canonical = [];
        foreach ($content as $block) {
            if (!is_array($block)) {
                continue;
            }

            if (is_string($block['text'] ?? null)) {
                $canonical[] = ['type' => 'text', 'text' => $block['text']];
                continue;
            }

            if (is_array($block['toolUse'] ?? null)) {
                $toolUse = $block['toolUse'];
                $id = $toolUse['toolUseId'] ?? null;
                $name = $toolUse['name'] ?? null;
                $input = $toolUse['input'] ?? [];
                if (!is_string($id) || !is_string($name) || !is_array($input)) {
                    continue;
                }
                $normalizedInput = [];
                foreach ($input as $key => $value) {
                    if (is_string($key)) {
                        $normalizedInput[$key] = $value;
                    }
                }
                $canonical[] = [
                    'type' => 'tool_use',
                    'id' => $id,
                    'name' => $name,
                    'input' => $normalizedInput,
                ];
                continue;
            }

            // Unknown Bedrock block shapes (reasoningContent, etc.) are
            // dropped until a canonical block type exists for them.
        }

        return $canonical;
    }

    /**
     * @param array<string, mixed> $response
     */
    private function extractText(array $response): string
    {
        $content = $response['output']['message']['content'] ?? [];

        if (is_array($content)) {
            foreach ($content as $block) {
                if (isset($block['text']) && is_string($block['text'])) {
                    return $block['text'];
                }
            }
        }

        return '';
    }
}

<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\AIProviders;

class AIProviderResponse
{
    /**
     * @var string
     */
    private $providerId;

    /**
     * @var string
     */
    private $providerName;

    /**
     * @var string
     */
    private $model;

    /**
     * @var string
     */
    private $text;

    /**
     * Number of input (prompt) tokens reported by the provider, or null when
     * the provider does not report token usage.
     *
     * @var int|null
     */
    private $inputTokens;

    /**
     * Number of output (completion) tokens reported by the provider, or null
     * when the provider does not report token usage.
     *
     * @var int|null
     */
    private $outputTokens;

    /**
     * Decoded provider response, retained for trusted server-side callers that
     * need to persist or re-parse provider-specific payloads.
     *
     * @var array<string, mixed>|null
     */
    private $rawResponse;

    /**
     * Provider reasoning level that was actually applied.
     *
     * @var string
     */
    private $reasoningLevel;

    /**
     * Whether provider-side web search was actually applied.
     *
     * @var bool
     */
    private $webSearchEnabled;

    /**
     * Total provider request time in milliseconds, including retries.
     *
     * @var int|null
     */
    private $executionTimeMs;

    /**
     * @param array<string, mixed>|null $rawResponse
     */
    public function __construct(
        string $providerId,
        string $providerName,
        string $model,
        string $text,
        ?int $inputTokens = null,
        ?int $outputTokens = null,
        ?array $rawResponse = null,
        string $reasoningLevel = AIRequest::REASONING_NONE,
        bool $webSearchEnabled = false,
        ?int $executionTimeMs = null
    ) {
        $this->providerId = $providerId;
        $this->providerName = $providerName;
        $this->model = $model;
        $this->text = $text;
        $this->inputTokens = $inputTokens;
        $this->outputTokens = $outputTokens;
        $this->rawResponse = $rawResponse;
        $this->reasoningLevel = $reasoningLevel;
        $this->webSearchEnabled = $webSearchEnabled;
        $this->executionTimeMs = $executionTimeMs;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getInputTokens(): ?int
    {
        return $this->inputTokens;
    }

    public function getOutputTokens(): ?int
    {
        return $this->outputTokens;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRawResponse(): ?array
    {
        return $this->rawResponse;
    }

    public function getReasoningLevel(): string
    {
        return $this->reasoningLevel;
    }

    public function isWebSearchEnabled(): bool
    {
        return $this->webSearchEnabled;
    }

    public function getExecutionTimeMs(): ?int
    {
        return $this->executionTimeMs;
    }

    /**
     * Returns the response text decoded as a JSON array/object, or null when the
     * text is not valid JSON. Intended for requests made with
     * {@link AIRequest::withJsonResponse()}.
     *
     * @return array<mixed>|null
     */
    public function getJsonData(): ?array
    {
        $decoded = json_decode($this->text, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'providerId' => $this->providerId,
            'providerName' => $this->providerName,
            'model' => $this->model,
            'text' => $this->text,
            'inputTokens' => $this->inputTokens,
            'outputTokens' => $this->outputTokens,
            'rawResponse' => $this->rawResponse,
            'reasoningLevel' => $this->reasoningLevel,
            'webSearchEnabled' => $this->webSearchEnabled,
            'executionTimeMs' => $this->executionTimeMs,
        ];
    }
}

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

    public function __construct(
        string $providerId,
        string $providerName,
        string $model,
        string $text,
        ?int $inputTokens = null,
        ?int $outputTokens = null
    ) {
        $this->providerId = $providerId;
        $this->providerName = $providerName;
        $this->model = $model;
        $this->text = $text;
        $this->inputTokens = $inputTokens;
        $this->outputTokens = $outputTokens;
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
        ];
    }
}

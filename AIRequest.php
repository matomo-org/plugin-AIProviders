<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\AIProviders;

/**
 * Immutable description of a single AI completion request.
 *
 * Trusted Matomo plugins build an {@link AIRequest} and pass it to
 * {@link AIProviderService::complete()} to run an AI feature through the
 * configured provider. Optional values are set with the fluent `with*()`
 * methods, each of which returns a new instance:
 *
 *     $response = $service->complete(
 *         (new AIRequest('Summarise this report.', 'Goals'))
 *             ->withSystemPrompt('You are a concise web analytics assistant.')
 *             ->withCapabilityLevel(Configuration::CAPABILITY_THINKING)
 *             ->withIdSite($idSite)
 *     );
 *
 * The selected `providerId` is treated as a hint: on a managed environment
 * (for example Matomo Cloud) it may be overridden by the forced default
 * provider. See {@link AIProviderService::complete()}.
 */
class AIRequest
{
    public const DEFAULT_MAX_TOKENS = 1024;
    public const DEFAULT_TEMPERATURE = 0.2;

    /**
     * @var string
     */
    private $userPrompt;

    /**
     * Name of the plugin issuing the request, used for accountability and
     * future usage accounting (for example `'Goals'`).
     *
     * @var string
     */
    private $callerPluginName;

    /**
     * @var string|null
     */
    private $systemPrompt = null;

    /**
     * @var string|null
     */
    private $providerId = null;

    /**
     * @var string|null
     */
    private $model = null;

    /**
     * Requested model capability level (see Configuration::CAPABILITY_*).
     * Advisory for now: providers do not yet map this to a specific model.
     *
     * @var string|null
     */
    private $capabilityLevel = null;

    /**
     * Optional identifier of the feature issuing the request (for example
     * `'goal-recommendation'`), used for future usage accounting.
     *
     * @var string|null
     */
    private $featureKey = null;

    /**
     * @var int|null
     */
    private $idSite = null;

    /**
     * @var int
     */
    private $maxTokens = self::DEFAULT_MAX_TOKENS;

    /**
     * @var float
     */
    private $temperature = self::DEFAULT_TEMPERATURE;

    public function __construct(string $userPrompt, string $callerPluginName)
    {
        $this->userPrompt = $userPrompt;
        $this->callerPluginName = $callerPluginName;
    }

    public function withSystemPrompt(?string $systemPrompt): self
    {
        $request = clone $this;
        $request->systemPrompt = $systemPrompt;

        return $request;
    }

    public function withProviderId(?string $providerId): self
    {
        $request = clone $this;
        $request->providerId = $providerId;

        return $request;
    }

    public function withModel(?string $model): self
    {
        $request = clone $this;
        $request->model = $model;

        return $request;
    }

    public function withCapabilityLevel(?string $capabilityLevel): self
    {
        $request = clone $this;
        $request->capabilityLevel = $capabilityLevel;

        return $request;
    }

    public function withFeatureKey(?string $featureKey): self
    {
        $request = clone $this;
        $request->featureKey = $featureKey;

        return $request;
    }

    public function withIdSite(?int $idSite): self
    {
        $request = clone $this;
        $request->idSite = $idSite;

        return $request;
    }

    public function withMaxTokens(int $maxTokens): self
    {
        $request = clone $this;
        $request->maxTokens = $maxTokens;

        return $request;
    }

    public function withTemperature(float $temperature): self
    {
        $request = clone $this;
        $request->temperature = $temperature;

        return $request;
    }

    public function getUserPrompt(): string
    {
        return $this->userPrompt;
    }

    public function getCallerPluginName(): string
    {
        return $this->callerPluginName;
    }

    public function getSystemPrompt(): ?string
    {
        return $this->systemPrompt;
    }

    public function getProviderId(): ?string
    {
        return $this->providerId;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function getCapabilityLevel(): ?string
    {
        return $this->capabilityLevel;
    }

    public function getFeatureKey(): ?string
    {
        return $this->featureKey;
    }

    public function getIdSite(): ?int
    {
        return $this->idSite;
    }

    public function getMaxTokens(): int
    {
        return $this->maxTokens;
    }

    public function getTemperature(): float
    {
        return $this->temperature;
    }
}

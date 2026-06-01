<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\AIProviders;

use InvalidArgumentException;
use Piwik\Plugins\AIProviders\Model\Configuration;
use Piwik\Plugins\AIProviders\Provider\AIProvider;

/**
 * Entry point for Matomo plugins that want to run AI features.
 *
 * Obtain the service from the dependency injection container and pass an
 * {@link AIRequest}:
 *
 *     $service  = \Piwik\Container\StaticContainer::get(AIProviderService::class);
 *     $response = $service->complete(new AIRequest($prompt, 'Goals'));
 *     $text     = $response->getText();
 */
class AIProviderService
{
    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Completes the given request.
     *
     * The provider is resolved in this order: the provider forced by a managed
     * environment (for example Matomo Cloud), then the provider requested by
     * the caller, then the configured default provider. This means a managed
     * environment always wins, even when the caller requests another provider.
     */
    public function complete(AIRequest $request): AIProviderResponse
    {
        $providers = AIProviders::getAvailableProviders();

        $forcedProviderId = $this->configuration->getForcedProviderId();
        $requestedProviderId = $request->getProviderId();

        if ($forcedProviderId !== null) {
            $providerId = $forcedProviderId;
        } elseif ($requestedProviderId !== null && $requestedProviderId !== '') {
            $providerId = $requestedProviderId;
        } else {
            $providerId = $this->configuration->getDefaultProviderId($providers);
        }

        $provider = $providers->getProvider($providerId);

        if ($provider === null) {
            throw new InvalidArgumentException(sprintf('Unknown AI provider "%s".', $providerId));
        }

        $configuration = $this->configuration->getProviderConfiguration($provider->getId());

        $response = $this->runWithProvider($provider, $configuration, $request);

        /**
         * TODO: record usage telemetry here once a usage-logging table exists.
         * Everything needed is already available: $request->getCallerPluginName(),
         * $request->getFeatureKey(), $request->getIdSite(), $provider->getId(),
         * and the token counts on $response (getInputTokens()/getOutputTokens()).
         * Telemetry is not implemented yet.
         */
        return $response;
    }

    /**
     * Runs a request against a specific provider and configuration, with no
     * provider resolution. Restricted to the admin "test connection" flow, which
     * needs to test an unsaved provider/configuration before it is stored.
     *
     * Callers must enforce their own access control (the admin API gates this
     * behind super-user access). Because it bypasses resolution — including the
     * provider forced by a managed environment — it must not be used as a general
     * completion entry point; use {@link complete()} for that.
     *
     * @param array<string, string> $configuration
     */
    public function testProviderConnection(AIProvider $provider, array $configuration, AIRequest $request): AIProviderResponse
    {
        return $this->runWithProvider($provider, $configuration, $request);
    }

    /**
     * Executes the request against the given provider and guards against an empty
     * completion. Shared by {@link complete()} and {@link testProviderConnection()}.
     *
     * @param array<string, string> $configuration
     */
    private function runWithProvider(AIProvider $provider, array $configuration, AIRequest $request): AIProviderResponse
    {
        $response = $provider->complete($request, $configuration);

        if (trim($response->getText()) === '') {
            throw new \RuntimeException(sprintf('%s returned an empty response.', $provider->getName()));
        }

        return $response;
    }

    /**
     * Returns the provider that completions run through by default, honouring a
     * provider forced by a managed environment.
     */
    public function getDefaultProvider(): AIProvider
    {
        $providers = AIProviders::getAvailableProviders();
        $forcedProviderId = $this->configuration->getForcedProviderId();
        $providerId = $forcedProviderId ?? $this->configuration->getDefaultProviderId($providers);
        $provider = $providers->getProvider($providerId);

        if ($provider === null) {
            throw new InvalidArgumentException(sprintf('Unknown AI provider "%s".', $providerId));
        }

        return $provider;
    }

    /**
     * Returns the server-side connection config for the default provider.
     *
     * The returned array may include secrets and must not be exposed in API
     * responses, logs, or browser-rendered output.
     *
     * @return array<string, string>
     */
    public function getDefaultProviderConfiguration(): array
    {
        $provider = $this->getDefaultProvider();

        return $this->configuration->getProviderConfiguration($provider->getId());
    }

    /**
     * Returns the configured default model capability level.
     */
    public function getDefaultCapabilityLevel(): string
    {
        return $this->configuration->getDefaultCapabilityLevel();
    }

    /**
     * Returns provider status metadata for trusted PHP callers.
     *
     * @return array<int, array{
     *     id: string,
     *     name: string,
     *     description: string,
     *     defaultModel: string,
     *     isDefault: bool,
     *     isConfigured: bool,
     *     supportsCustomEndpoint: bool,
     *     endpointUrl: string
     * }>
     */
    public function getAvailableProviderStatuses(): array
    {
        $providers = AIProviders::getAvailableProviders();
        $defaultProviderId = $this->configuration->getForcedProviderId()
            ?? $this->configuration->getDefaultProviderId($providers);

        return array_map(function (AIProvider $provider) use ($defaultProviderId): array {
            $configuration = $this->configuration->getProviderConfiguration($provider->getId());

            return [
                'id' => $provider->getId(),
                'name' => $provider->getName(),
                'description' => $provider->getDescription(),
                'defaultModel' => $provider->getDefaultModel(),
                'isDefault' => $provider->getId() === $defaultProviderId,
                'isConfigured' => $provider->isConfigured($configuration),
                'supportsCustomEndpoint' => $provider->supportsCustomEndpoint(),
                'endpointUrl' => $configuration['endpointUrl'] ?? '',
            ];
        }, $providers->getProviders());
    }
}

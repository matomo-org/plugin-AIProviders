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
     * environment, then the provider requested by
     * the caller, then the configured default provider. A managed environment
     * wins even when the caller requests another provider, except for callers
     * on the `providerSelectionAllowlist` of the managed config, whose
     * requested provider and model are honoured (see below).
     *
     * Allowlisted callers (for example a plugin that must query several AI
     * engines because comparing the engines is the feature itself) get no
     * silent fallback: an unknown requested provider throws, and an
     * unconfigured one fails in the provider. Falling back to the forced
     * provider would silently produce answers from the wrong engine, which is
     * worse than a clear error.
     *
     * The requested model is forwarded for allowlisted callers and on
     * unmanaged instances, and stripped otherwise.
     */
    public function complete(AIRequest $request): AIProviderResponse
    {
        $providers = AIProviders::getAvailableProviders();
        $resolution = $this->resolveProviderId($request->getProviderId(), $request->getCallerPluginName(), $providers);

        if ($resolution['stripRequestedModel']) {
            $request = $request->withProviderId($resolution['providerId'])->withModel(null);
        }

        $provider = $this->requireProvider($providers, $resolution['providerId']);
        $configuration = $this->configuration->getProviderConfiguration($provider->getId());

        $response = $this->runWithProvider($provider, $configuration, $request);

        /**
         * TODO: publish an observability event here so billing or monitoring can
         * hook into AI usage without this plugin depending on them. Emit basic
         * data, for example:
         *
         *     Piwik::postEvent('AIProviders.usage', [[
         *         'caller'    => $request->getCallerPluginName(),
         *         'feature'   => $request->getFeatureKey(),
         *         'idSite'    => $request->getIdSite(),
         *         'login'     => Piwik::getCurrentUserLogin(),
         *         'provider'  => $provider->getId(),
         *         'model'     => $response->getModel(),
         *         'tokensIn'  => $response->getInputTokens(),
         *         'tokensOut' => $response->getOutputTokens(),
         *     ]]);
         *
         * Not implemented yet.
         */
        return $response;
    }

    /**
     * Resolves which provider a request runs through: the provider forced by
     * a managed environment, then the caller's requested provider (always on
     * unmanaged instances; on managed instances only for callers on the
     * `providerSelectionAllowlist` — the caller name is self-declared, see
     * {@link Configuration::isPluginAllowedToSelectProvider()} for why this
     * is a policy gate, not a sandbox), then the configured default.
     *
     * `stripRequestedModel` is true when the forced provider overrode the
     * request, in which case the caller must also drop the requested model
     * because the model decides cost on managed instances.
     *
     * @return array{providerId: string, stripRequestedModel: bool}
     */
    private function resolveProviderId(
        ?string $requestedProviderId,
        string $callerPluginName,
        AIProvidersList $providers
    ): array {
        $forcedProviderId = $this->configuration->getForcedProviderId();
        $hasRequestedProvider = $requestedProviderId !== null && $requestedProviderId !== '';

        if (
            $forcedProviderId !== null
            && $hasRequestedProvider
            && $this->configuration->isPluginAllowedToSelectProvider($callerPluginName)
        ) {
            // TODO: consider validating the requested model against a
            // per-provider `allowedModels` list from the managed config as a
            // cost backstop, once the planned `AIProviders.usage` event shows
            // whether actual token usage needs it.
            return ['providerId' => $requestedProviderId, 'stripRequestedModel' => false];
        }

        if ($forcedProviderId !== null) {
            return ['providerId' => $forcedProviderId, 'stripRequestedModel' => true];
        }

        if ($hasRequestedProvider) {
            return ['providerId' => $requestedProviderId, 'stripRequestedModel' => false];
        }

        return [
            'providerId' => $this->configuration->getDefaultProviderId($providers),
            'stripRequestedModel' => false,
        ];
    }

    private function requireProvider(AIProvidersList $providers, string $providerId): AIProvider
    {
        $provider = $providers->getProvider($providerId);

        if ($provider === null) {
            throw new InvalidArgumentException(sprintf('Unknown AI provider "%s".', $providerId));
        }

        return $provider;
    }

    /**
     * Validates a specific provider and configuration, with no provider
     * resolution. Restricted to the admin "test connection" flow, which needs
     * to test an unsaved provider/configuration before it is stored. Delegates
     * to the provider's lightweight connection probe and throws on failure.
     *
     * Callers must enforce their own access control (the admin API gates this
     * behind super-user access). Because it bypasses resolution — including the
     * provider forced by a managed environment — it must not be used as a general
     * completion entry point; use {@link complete()} for that.
     *
     * @param array<string, string> $configuration
     */
    public function testProviderConnection(AIProvider $provider, array $configuration): void
    {
        $provider->verifyConnection($configuration);
    }

    /**
     * Executes the request against the given provider and guards against an empty
     * completion. Used by {@link complete()}.
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

        return $this->requireProvider($providers, $providerId);
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
     * Restricted providers (registered as non-selectable by a managed
     * environment) are excluded so they stay invisible outside the
     * allowlisted completion flow.
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
                'endpointUrl' => $configuration['endpointUrl'],
            ];
        }, $providers->getSelectableProviders());
    }
}

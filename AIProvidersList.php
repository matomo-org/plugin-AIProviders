<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\AIProviders;

use Piwik\Container\StaticContainer;
use Piwik\Plugins\AIProviders\Provider\AIProvider;
use Psr\Log\LoggerInterface;

class AIProvidersList
{
    /**
     * @var array<string, AIProvider>
     */
    private $providers = [];

    public function addProvider(AIProvider $provider): void
    {
        $providerId = $provider->getId();

        if (isset($this->providers[$providerId])) {
            /**
             * Overriding an existing provider is currently allowed: the last
             * registration for a given ID wins. Matomo logs it so an accidental or
             * unexpected override of a built-in provider is visible.
             * TODO: decide whether built-in provider IDs should be protected
             * from being overridden by other plugins.
             */
            StaticContainer::get(LoggerInterface::class)->warning(
                'AI provider "{id}" was overridden: {old} replaced by {new}.',
                [
                    'id' => $providerId,
                    'old' => get_class($this->providers[$providerId]),
                    'new' => get_class($provider),
                ]
            );
        }

        $this->providers[$providerId] = $provider;
    }

    public function removeProvider(string $providerId): void
    {
        unset($this->providers[$providerId]);
    }

    public function hasProvider(string $providerId): bool
    {
        return isset($this->providers[$providerId]);
    }

    public function getProvider(string $providerId): ?AIProvider
    {
        return $this->providers[$providerId] ?? null;
    }

    /**
     * @return array<int, AIProvider>
     */
    public function getProviders(): array
    {
        return array_values($this->providers);
    }
}

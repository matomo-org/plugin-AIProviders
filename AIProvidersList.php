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
             * Provider IDs are unique and cannot be overwritten: the first
             * registration for a given ID wins and later registrations are
             * ignored. This protects the built-in providers (and a provider that
             * is centrally forced in a managed multi-tenant environment such as
             * Matomo Cloud) from being shadowed by another plugin. The ignored
             * registration is logged so the collision is visible.
             */
            StaticContainer::get(LoggerInterface::class)->warning(
                'AI provider "{id}" is already registered as {existing}; ignoring duplicate registration of {ignored}.',
                [
                    'id' => $providerId,
                    'existing' => get_class($this->providers[$providerId]),
                    'ignored' => get_class($provider),
                ]
            );

            return;
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

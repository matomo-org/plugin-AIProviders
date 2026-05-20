<?php

/**
 * Copyright (C) InnoCraft Ltd - All rights reserved.
 *
 * NOTICE:  All information contained herein is, and remains the property of InnoCraft Ltd.
 * The intellectual and technical concepts contained herein are protected by trade secret or copyright law.
 * Redistribution of this information or reproduction of this material is strictly forbidden
 * unless prior written permission is obtained from InnoCraft Ltd.
 *
 * You shall use this code only in accordance with the license agreement obtained from InnoCraft Ltd.
 *
 * @link https://www.innocraft.com/
 * @license For license details see https://www.innocraft.com/license
 */

declare(strict_types=1);

namespace Piwik\Plugins\AIProviders;

use Piwik\Plugins\AIProviders\Provider\AIProvider;

class AIProvidersList
{
    /**
     * @var array<string, AIProvider>
     */
    private $providers = [];

    public function addProvider(AIProvider $provider): void
    {
        $this->providers[$provider->getId()] = $provider;
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
     * @return AIProvider[]
     */
    public function getProviders(): array
    {
        return array_values($this->providers);
    }
}

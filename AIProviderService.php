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

use InvalidArgumentException;
use Piwik\Plugins\AIProviders\Model\Configuration;
use Piwik\Plugins\AIProviders\Provider\AIProvider;

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
     * Returns the configured default provider for server-side plugin use.
     */
    public function getDefaultProvider(): AIProvider
    {
        $providers = AIProviders::getAvailableProviders();
        $providerId = $this->configuration->getDefaultProviderId($providers);
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
}

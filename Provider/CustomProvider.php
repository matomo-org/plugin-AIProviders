<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\AIProviders\Provider;

use Piwik\Plugins\AIProviders\AIProviderResponse;
use Piwik\Plugins\AIProviders\AIRequest;

class CustomProvider extends AIProvider
{
    private const DEFAULT_MODEL = 'gpt-4.1-mini';

    public function __construct()
    {
        parent::__construct(
            'custom-provider',
            'Custom Provider',
            'AIProviders_CustomProviderDescription',
            true
        );
    }

    public function getDefaultModel(): string
    {
        return self::DEFAULT_MODEL;
    }

    /**
     * Custom servers are expected to be OpenAI-compatible. Callers can override
     * the model via the request for servers that expose a different alias.
     *
     * @param array<string, string> $configuration
     */
    public function complete(AIRequest $request, array $configuration): AIProviderResponse
    {
        return $this->completeChatCompletion(
            $request,
            $this->getChatCompletionsEndpoint($this->getEndpointUrl($configuration)),
            ['Authorization' => 'Bearer ' . $this->getApiKey($configuration)]
        );
    }

    private function getChatCompletionsEndpoint(string $endpointUrl): string
    {
        if (preg_match('#/chat/completions/?$#', $endpointUrl)) {
            return $endpointUrl;
        }

        return rtrim($endpointUrl, '/') . '/chat/completions';
    }
}

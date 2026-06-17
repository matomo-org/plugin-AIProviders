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

class OpenAI extends AIProvider
{
    private const DEFAULT_MODEL = 'gpt-4.1-mini';

    public function __construct()
    {
        parent::__construct('openai', 'OpenAI', 'AIProviders_OpenAIDescription');
    }

    public function getDefaultEndpointUrl(): string
    {
        return 'https://api.openai.com/v1/chat/completions';
    }

    public function getDefaultModel(): string
    {
        return self::DEFAULT_MODEL;
    }

    /**
     * @param array<string, string> $configuration
     */
    public function complete(AIRequest $request, array $configuration): AIProviderResponse
    {
        return $this->completeChatCompletion(
            $request,
            $this->getEndpointUrl($configuration),
            ['Authorization' => 'Bearer ' . $this->getApiKey($configuration)]
        );
    }

    /**
     * Validates credentials and reachability with a cheap models listing
     * (`GET /v1/models`) instead of spending generation tokens.
     *
     * @param array<string, string> $configuration
     */
    public function verifyConnection(array $configuration): void
    {
        $this->sendGetRequest(
            $this->openAiCompatibleModelsEndpoint($this->getEndpointUrl($configuration)),
            ['Authorization' => 'Bearer ' . $this->getApiKey($configuration)]
        );
    }
}

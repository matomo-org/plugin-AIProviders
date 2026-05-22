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

namespace Piwik\Plugins\AIProviders\Provider;

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
    public function completePrompt(array $configuration, string $prompt): string
    {
        $response = $this->sendJsonRequest(
            $this->getEndpointUrl($configuration),
            [
                'Authorization' => 'Bearer ' . $this->getApiKey($configuration),
            ],
            [
                'model' => $this->getDefaultModel(),
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'max_tokens' => 80,
                'temperature' => 0.2,
            ]
        );

        $text = $response['choices'][0]['message']['content'] ?? '';

        return is_string($text) ? trim($text) : '';
    }
}

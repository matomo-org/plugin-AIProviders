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

class Gemini extends AIProvider
{
    private const DEFAULT_MODEL = 'gemini-2.5-flash';

    public function __construct()
    {
        parent::__construct('gemini', 'Gemini', 'AIProviders_GeminiDescription');
    }

    public function getDefaultEndpointUrl(): string
    {
        return sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
            $this->getDefaultModel()
        );
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
                'x-goog-api-key' => $this->getApiKey($configuration),
            ],
            [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $prompt,
                            ],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'maxOutputTokens' => 80,
                    'temperature' => 0.2,
                ],
            ]
        );

        $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';

        return is_string($text) ? trim($text) : '';
    }
}

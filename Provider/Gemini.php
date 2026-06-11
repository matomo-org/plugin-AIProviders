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

class Gemini extends AIProvider
{
    private const DEFAULT_MODEL = 'gemini-2.5-flash';

    public function __construct()
    {
        parent::__construct('gemini', 'Gemini', 'AIProviders_GeminiDescription');
    }

    public function getDefaultEndpointUrl(): string
    {
        return $this->getEndpointUrlForModel($this->getDefaultModel());
    }

    public function getDefaultModel(): string
    {
        return self::DEFAULT_MODEL;
    }

    /**
     * Custom Gemini chat completion method.
     * @see https://ai.google.dev/gemini-api/docs/text-generation
     * @param array<string, string> $configuration
     */
    public function complete(AIRequest $request, array $configuration): AIProviderResponse
    {
        $model = $this->resolveModel($request);

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $request->getUserPrompt(),
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'maxOutputTokens' => $request->getMaxTokens(),
                'temperature' => $request->getTemperature(),
            ],
        ];

        if ($request->isJsonResponse()) {
            $payload['generationConfig']['responseMimeType'] = 'application/json';
        }

        $systemPrompt = $this->getSystemPrompt($request);
        if ($systemPrompt !== null && $systemPrompt !== '') {
            $payload['systemInstruction'] = [
                'parts' => [
                    [
                        'text' => $systemPrompt,
                    ],
                ],
            ];
        }

        $response = $this->sendJsonRequest(
            $this->getEndpointUrlForModel($model),
            [
                'x-goog-api-key' => $this->getApiKey($configuration),
            ],
            $payload
        );

        $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';

        return $this->buildResponse(
            $request,
            $model,
            is_string($text) ? $text : '',
            isset($response['usageMetadata']['promptTokenCount']) ? (int) $response['usageMetadata']['promptTokenCount'] : null,
            isset($response['usageMetadata']['candidatesTokenCount']) ? (int) $response['usageMetadata']['candidatesTokenCount'] : null,
            $response
        );
    }

    private function getEndpointUrlForModel(string $model): string
    {
        return sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
            $model
        );
    }
}

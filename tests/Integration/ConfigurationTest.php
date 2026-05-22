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

namespace Piwik\Plugins\AIProviders\tests\Integration;

use Piwik\Container\StaticContainer;
use Piwik\Common;
use Piwik\Option;
use Piwik\Piwik;
use Piwik\Plugins\AIProviders\API;
use Piwik\Plugins\AIProviders\AIProviderService;
use Piwik\Plugins\AIProviders\Model\Configuration;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group AIProviders
 * @group AIProvidersConfiguration
 * @group Plugins
 */
class ConfigurationTest extends IntegrationTestCase
{
    /**
     * @var API
     */
    private $api;

    public function setUp(): void
    {
        parent::setUp();

        Option::delete(Configuration::OPTION_DEFAULT_PROVIDER_ID);
        Option::delete(Configuration::OPTION_DEFAULT_CAPABILITY_LEVEL);
        Option::delete(Configuration::OPTION_PROVIDER_CONFIGURATIONS);

        Fixture::createSuperUser();
        $this->setSuperUser();

        $this->api = API::getInstance();
    }

    public function testGetSettingsReturnsDefaultProviderConfiguration(): void
    {
        $settings = $this->api->getSettings();

        $this->assertSame('', $settings['defaultProviderId']);
        $this->assertSame(Configuration::CAPABILITY_INSTANT, $settings['defaultCapabilityLevel']);
        $this->assertTrue($settings['canEditProviderConfiguration']);
        $this->assertTrue($settings['canEditCapabilityLevel']);
        $this->assertCount(4, $settings['providers']);

        $customProvider = $this->getProvider($settings, 'custom-provider');
        $this->assertSame('Custom Provider', $customProvider['name']);
        $this->assertTrue($customProvider['supportsCustomEndpoint']);
    }

    public function testSaveSettingsStoresApiKeyWithoutReturningIt(): void
    {
        $settings = $this->api->saveSettings(
            'claude',
            Configuration::CAPABILITY_THINKING,
            (string) json_encode([
                'claude' => [
                    'apiKey' => 'secret-claude-key',
                    'endpointUrl' => '',
                ],
            ])
        );

        $this->assertSame('claude', $settings['defaultProviderId']);
        $this->assertSame(Configuration::CAPABILITY_THINKING, $settings['defaultCapabilityLevel']);

        $claude = $this->getProvider($settings, 'claude');
        $this->assertTrue($claude['configuration']['hasApiKey']);
        $this->assertTrue($claude['configuration']['isUsable']);
        $this->assertArrayNotHasKey('apiKey', $claude['configuration']);
        $this->assertStringNotContainsString('secret-claude-key', (string) json_encode($settings));
    }

    public function testSaveSettingsAcceptsSanitizedJsonFromApiRequests(): void
    {
        $settings = $this->api->saveSettings(
            'claude',
            Configuration::CAPABILITY_THINKING,
            Common::sanitizeInputValue((string) json_encode([
                'claude' => [
                    'apiKey' => 'secret-claude-key',
                    'endpointUrl' => '',
                ],
            ]))
        );

        $claude = $this->getProvider($settings, 'claude');
        $this->assertTrue($claude['configuration']['hasApiKey']);
    }

    public function testSaveSettingsPreservesExistingApiKeyWhenInputIsEmpty(): void
    {
        $this->api->saveSettings(
            'claude',
            Configuration::CAPABILITY_THINKING,
            (string) json_encode([
                'claude' => [
                    'apiKey' => 'secret-claude-key',
                    'endpointUrl' => '',
                ],
            ])
        );

        $settings = $this->api->saveSettings(
            'claude',
            Configuration::CAPABILITY_INSTANT,
            (string) json_encode([
                'claude' => [
                    'apiKey' => '',
                    'endpointUrl' => '',
                ],
            ])
        );

        $claude = $this->getProvider($settings, 'claude');
        $this->assertTrue($claude['configuration']['hasApiKey']);
    }

    public function testServiceReturnsServerSideDefaultProviderConfiguration(): void
    {
        $this->api->saveSettings(
            'claude',
            Configuration::CAPABILITY_THINKING,
            (string) json_encode([
                'claude' => [
                    'apiKey' => 'secret-claude-key',
                    'endpointUrl' => '',
                ],
            ])
        );

        $service = StaticContainer::get(AIProviderService::class);

        $this->assertSame('claude', $service->getDefaultProvider()->getId());
        $this->assertSame(Configuration::CAPABILITY_THINKING, $service->getDefaultCapabilityLevel());
        $this->assertSame('secret-claude-key', $service->getDefaultProviderConfiguration()['apiKey']);
    }

    /**
     * This test requires the OpenAI provider to be configured.
     * It tests the service's ability to complete a prompt using the configured default provider.
     * TODO: determine if it is fine to call the actual endpoints or if those tests should be mocked.
     * @return void
     * @throws \Piwik\Exception\DI\DependencyException
     * @throws \Piwik\Exception\DI\NotFoundException
     */
    public function testServiceCompletesPromptUsingConfiguredDefaultProvider(): void
    {
        $this->api->saveSettings(
            'openai',
            Configuration::CAPABILITY_INSTANT,
            (string) json_encode([
                'openai' => [
                    'apiKey' => 'secret-openai-key',
                    'endpointUrl' => '',
                ],
            ])
        );
        $this->mockAIProviderResponse('Because molecules scatter blue light more strongly.');

        $response = StaticContainer::get(AIProviderService::class)
            ->completePrompt('why is the sky blue, answer in 7 words');

        $this->assertSame('openai', $response->toArray()['providerId']);
        $this->assertSame('Because molecules scatter blue light more strongly.', $response->getText());
    }

    public function testApiTestsConnectionWithUnsavedProviderConfiguration(): void
    {
        $this->mockAIProviderResponse('Because molecules scatter blue light more strongly.');

        $response = $this->api->testConnection(
            'openai',
            Common::sanitizeInputValue((string) json_encode([
                'apiKey' => 'secret-openai-key',
                'endpointUrl' => '',
            ]))
        );

        $this->assertSame('openai', $response['providerId']);
        $this->assertSame('Because molecules scatter blue light more strongly.', $response['text']);
    }

    public function testApiRetriesTransientProviderErrors(): void
    {
        $requests = 0;
        Piwik::addAction('Http.sendHttpRequest', function (
            string $url,
            array $httpEventParams,
            ?string &$response,
            ?int &$status,
            array &$headers
        ) use (&$requests): void {
            $this->assertSame(
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent',
                $url
            );
            $this->assertSame('POST', $httpEventParams['httpMethod']);

            $requests++;

            if ($requests === 1) {
                $response = (string) json_encode([
                    'error' => [
                        'code' => 503,
                        'message' => 'This model is currently experiencing high demand.',
                        'status' => 'UNAVAILABLE',
                    ],
                ]);
                $status = 503;
                $headers = ['Content-Type' => 'application/json'];
                return;
            }

            $response = (string) json_encode([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => 'Because molecules scatter blue light more strongly.',
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
            $status = 200;
            $headers = ['Content-Type' => 'application/json'];
        });

        $response = $this->api->testConnection(
            'gemini',
            Common::sanitizeInputValue((string) json_encode([
                'apiKey' => 'secret-gemini-key',
                'endpointUrl' => '',
            ]))
        );

        $this->assertSame(2, $requests);
        $this->assertSame('gemini', $response['providerId']);
        $this->assertSame('Because molecules scatter blue light more strongly.', $response['text']);
    }

    public function testDisconnectProviderRemovesStoredApiKey(): void
    {
        $this->api->saveSettings(
            'openai',
            Configuration::CAPABILITY_INSTANT,
            (string) json_encode([
                'openai' => [
                    'apiKey' => 'secret-openai-key',
                    'endpointUrl' => '',
                ],
            ])
        );

        $settings = $this->api->disconnectProvider('openai');
        $openAI = $this->getProvider($settings, 'openai');

        $this->assertSame('', $settings['defaultProviderId']);
        $this->assertFalse($openAI['configuration']['hasApiKey']);
    }

    public function testSaveSettingsRejectsUnconfiguredDefaultProvider(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('AI provider "openai" is not configured.');

        $this->api->saveSettings(
            'openai',
            Configuration::CAPABILITY_INSTANT,
            '{}'
        );
    }

    public function testSaveSettingsRejectsUnknownProvider(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown AI provider');

        $this->api->saveSettings(
            'unknown-provider',
            Configuration::CAPABILITY_INSTANT,
            '{}'
        );
    }

    public function testGetSettingsRequiresSuperUserAccess(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('checkUserHasSuperUserAccess');

        $this->setAnonymousUser();
        $this->api->getSettings();
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function getProvider(array $settings, string $providerId): array
    {
        foreach ($settings['providers'] as $provider) {
            if ($provider['id'] === $providerId) {
                return $provider;
            }
        }

        $this->fail(sprintf('Provider "%s" was not found.', $providerId));
    }

    private function setSuperUser(): void
    {
        FakeAccess::clearAccess(true);
    }

    private function setAnonymousUser(): void
    {
        FakeAccess::clearAccess();
        FakeAccess::$identity = 'anonymous';
    }

    private function mockAIProviderResponse(string $text): void
    {
        Piwik::addAction('Http.sendHttpRequest', function (
            string $url,
            array $httpEventParams,
            ?string &$response,
            ?int &$status,
            array &$headers
        ) use ($text): void {
            $this->assertSame('https://api.openai.com/v1/chat/completions', $url);
            $this->assertSame('POST', $httpEventParams['httpMethod']);

            $response = (string) json_encode([
                'choices' => [
                    [
                        'message' => [
                            'content' => $text,
                        ],
                    ],
                ],
            ]);
            $status = 200;
            $headers = ['Content-Type' => 'application/json'];
        });
    }

    public function provideContainerConfig(): array
    {
        return [
            'Piwik\Access' => new FakeAccess(),
        ];
    }
}

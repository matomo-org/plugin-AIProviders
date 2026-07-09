<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\AIProviders\tests\Integration;

use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\Piwik;
use Piwik\Plugins\AIProviders\AIProvidersList;
use Piwik\Plugins\AIProviders\AIRequest;
use Piwik\Plugins\AIProviders\API;
use Piwik\Plugins\AIProviders\AIProviderService;
use Piwik\Plugins\AIProviders\Controller;
use Piwik\Plugins\AIProviders\Exception\AIProviderClientException;
use Piwik\Plugins\AIProviders\Model\Configuration;
use Piwik\Plugins\AIProviders\Provider\OpenAI;
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

        // Ensure no forced configuration leaks between tests.
        Config::getInstance()->AIProviders = [];

        Fixture::createSuperUser();
        $this->setSuperUser();

        $this->api = API::getInstance();

        // Reset stored settings to their defaults between tests.
        $this->api->saveSettings('', Configuration::CAPABILITY_INSTANT, '{}');
    }

    public function tearDown(): void
    {
        Config::getInstance()->AIProviders = [];
        putenv('MATOMO_AIPROVIDERS_OPENAI_API_KEY');
        StaticContainer::getContainer()->set('AIProviders.openaiApiKey', '');
        StaticContainer::getContainer()->set('AIProviders.openaiEndpointUrl', '');
        StaticContainer::getContainer()->set('AIProviders.openaiModel', '');

        parent::tearDown();
    }

    public function testGetSettingsReturnsDefaultProviderConfiguration(): void
    {
        $settings = $this->api->getSettings();

        $this->assertSame('', $settings['defaultProviderId']);
        $this->assertSame(Configuration::CAPABILITY_INSTANT, $settings['defaultCapabilityLevel']);
        $this->assertTrue($settings['canEditProviderConfiguration']);
        $this->assertTrue($settings['canEditCapabilityLevel']);
        $this->assertCount(5, $settings['providers']);

        $customProvider = $this->getProvider($settings, 'custom-provider');
        $this->assertSame('Custom Provider', $customProvider['name']);
        $this->assertTrue($customProvider['supportsCustomEndpoint']);

        $bedrock = $this->getProvider($settings, 'bedrock');
        $this->assertSame('AWS Bedrock', $bedrock['name']);
        $this->assertTrue($bedrock['supportsCustomEndpoint']);
    }

    public function testSaveSettingsCanBeCalledWithDefaultsBeforeAnyProviderIsConnected(): void
    {
        // Saving the form with nothing configured must not throw: an empty
        // default provider and capability level are valid (see saveSettings()).
        $settings = $this->api->saveSettings();

        $this->assertSame('', $settings['defaultProviderId']);
        $this->assertSame(Configuration::CAPABILITY_INSTANT, $settings['defaultCapabilityLevel']);
    }

    public function testSaveSettingsWithEmptyCapabilityLevelKeepsStoredValue(): void
    {
        $this->api->saveSettings('', Configuration::CAPABILITY_THINKING, '{}');

        // An empty capability level leaves the stored value untouched rather
        // than resetting it, so the capability can be saved independently.
        $settings = $this->api->saveSettings('', '', '{}');

        $this->assertSame(Configuration::CAPABILITY_THINKING, $settings['defaultCapabilityLevel']);
    }

    public function testSaveSettingsStoresApiKeyWithoutReturningIt(): void
    {
        $settings = $this->api->saveSettings(
            'anthropic',
            Configuration::CAPABILITY_THINKING,
            (string) json_encode([
                'anthropic' => [
                    'apiKey' => 'secret-claude-key',
                    'endpointUrl' => '',
                ],
            ])
        );

        $this->assertSame('anthropic', $settings['defaultProviderId']);
        $this->assertSame(Configuration::CAPABILITY_THINKING, $settings['defaultCapabilityLevel']);

        $claude = $this->getProvider($settings, 'anthropic');
        $this->assertTrue($claude['configuration']['hasApiKey']);
        $this->assertTrue($claude['configuration']['isUsable']);
        $this->assertArrayNotHasKey('apiKey', $claude['configuration']);
        $this->assertStringNotContainsString('secret-claude-key', (string) json_encode($settings));
    }

    public function testSaveSettingsKeepsApiKeyVerbatimWithoutSanitizing(): void
    {
        // The API disables automatic input sanitization ($autoSanitizeInputParams
        // = false), so a key containing characters that sanitization would
        // HTML-encode must be stored byte-for-byte intact.
        $apiKey = 'secret&claude<key>"123"';

        $settings = $this->api->saveSettings(
            'anthropic',
            Configuration::CAPABILITY_THINKING,
            (string) json_encode([
                'anthropic' => [
                    'apiKey' => $apiKey,
                    'endpointUrl' => '',
                ],
            ])
        );

        $claude = $this->getProvider($settings, 'anthropic');
        $this->assertTrue($claude['configuration']['hasApiKey']);

        $stored = StaticContainer::get(Configuration::class)->getProviderConfiguration('anthropic');
        $this->assertSame($apiKey, $stored['apiKey']);
    }

    public function testSaveSettingsPreservesExistingApiKeyWhenInputIsEmpty(): void
    {
        $this->api->saveSettings(
            'anthropic',
            Configuration::CAPABILITY_THINKING,
            (string) json_encode([
                'anthropic' => [
                    'apiKey' => 'secret-claude-key',
                    'endpointUrl' => '',
                ],
            ])
        );

        $settings = $this->api->saveSettings(
            'anthropic',
            Configuration::CAPABILITY_INSTANT,
            (string) json_encode([
                'anthropic' => [
                    'apiKey' => '',
                    'endpointUrl' => '',
                ],
            ])
        );

        $claude = $this->getProvider($settings, 'anthropic');
        $this->assertTrue($claude['configuration']['hasApiKey']);
    }

    public function testServiceReturnsServerSideDefaultProviderConfiguration(): void
    {
        $this->api->saveSettings(
            'anthropic',
            Configuration::CAPABILITY_THINKING,
            (string) json_encode([
                'anthropic' => [
                    'apiKey' => 'secret-claude-key',
                    'endpointUrl' => '',
                ],
            ])
        );

        $service = StaticContainer::get(AIProviderService::class);

        $this->assertSame('anthropic', $service->getDefaultProvider()->getId());
        $this->assertSame(Configuration::CAPABILITY_THINKING, $service->getDefaultCapabilityLevel());
        // The stored credentials stay internal to the plugin; the service does
        // not expose them. They are only resolvable through the Configuration.
        $configuration = StaticContainer::get(Configuration::class);
        $this->assertSame('secret-claude-key', $configuration->getProviderConfiguration('anthropic')['apiKey']);
        $this->assertFalse(method_exists($service, 'getDefaultProviderConfiguration'));
    }

    public function testServiceCompletesRequestUsingConfiguredDefaultProvider(): void
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
            ->complete(new AIRequest('why is the sky blue, answer in 7 words', 'Test'));

        $this->assertSame('openai', $response->toArray()['providerId']);
        $this->assertSame('Because molecules scatter blue light more strongly.', $response->getText());
        $this->assertSame(12, $response->getInputTokens());
        $this->assertSame(7, $response->getOutputTokens());
        $this->assertSame(AIRequest::REASONING_NONE, $response->getReasoningLevel());
        $this->assertFalse($response->isWebSearchEnabled());
        $this->assertIsInt($response->getExecutionTimeMs());
    }

    public function testRequestForwardsSystemPromptModelAndOptionsToProvider(): void
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

        $capturedBody = null;
        Piwik::addAction('Http.sendHttpRequest', function (
            string $url,
            array $httpEventParams,
            ?string &$response,
            ?int &$status,
            array &$headers
        ) use (&$capturedBody): void {
            $capturedBody = json_decode((string) $httpEventParams['body'], true);
            $response = (string) json_encode([
                'choices' => [['message' => ['content' => 'Blue light scatters most.']]],
            ]);
            $status = 200;
            $headers = ['Content-Type' => 'application/json'];
        });

        $request = (new AIRequest('why is the sky blue', 'Test'))
            ->withSystemPrompt('You are concise.')
            ->withModel('gpt-4o-mini')
            ->withMaxTokens(64)
            ->withTemperature(0.5)
            ->withReasoningLevel('low')
            ->withWebSearchEnabled(true);

        $response = StaticContainer::get(AIProviderService::class)->complete($request);

        $this->assertSame('Blue light scatters most.', $response->getText());
        $this->assertSame(AIRequest::REASONING_NONE, $response->getReasoningLevel());
        $this->assertFalse($response->isWebSearchEnabled());
        $this->assertIsArray($capturedBody);
        $this->assertSame('gpt-4o-mini', $capturedBody['model']);
        $this->assertSame(64, $capturedBody['max_completion_tokens']);
        $this->assertArrayNotHasKey('max_tokens', $capturedBody);
        $this->assertArrayNotHasKey('temperature', $capturedBody);
        $this->assertSame('none', $capturedBody['reasoning_effort']);
        $this->assertSame(
            [
                ['role' => 'system', 'content' => 'You are concise.'],
                ['role' => 'user', 'content' => 'why is the sky blue'],
            ],
            $capturedBody['messages']
        );
    }

    public function testJsonResponseModeAsksProviderForJsonAndDecodesIt(): void
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

        $capturedBody = null;
        Piwik::addAction('Http.sendHttpRequest', function (
            string $url,
            array $httpEventParams,
            ?string &$response,
            ?int &$status,
            array &$headers
        ) use (&$capturedBody): void {
            $capturedBody = json_decode((string) $httpEventParams['body'], true);
            $response = (string) json_encode([
                'choices' => [['message' => ['content' => '{"goals":[]}']]],
            ]);
            $status = 200;
            $headers = ['Content-Type' => 'application/json'];
        });

        $response = StaticContainer::get(AIProviderService::class)
            ->complete((new AIRequest('Recommend goals for this site', 'Goals'))->withJsonResponse());

        // The provider is asked for JSON natively …
        $this->assertSame(['type' => 'json_object'], $capturedBody['response_format']);
        // … and via a system instruction that mentions JSON (required by some providers).
        $this->assertSame('system', $capturedBody['messages'][0]['role']);
        $this->assertStringContainsString('JSON', $capturedBody['messages'][0]['content']);
        // The response exposes the decoded object.
        $this->assertSame(['goals' => []], $response->getJsonData());
    }

    public function testServiceUsesForcedProviderAndIgnoresRequestedProvider(): void
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

        // Simulate a managed environment forcing the provider from config.ini.php.
        Config::getInstance()->AIProviders = ['defaultProvider' => 'openai'];
        $this->mockAIProviderResponse('Because molecules scatter blue light more strongly.');

        $response = StaticContainer::get(AIProviderService::class)
            ->complete((new AIRequest('why is the sky blue', 'Test'))->withProviderId('anthropic'));

        $this->assertSame('openai', $response->toArray()['providerId']);
    }

    public function testServiceIgnoresRequestedModelWhenProviderIsForced(): void
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

        Config::getInstance()->AIProviders = ['defaultProvider' => 'openai'];

        $capturedBody = null;
        Piwik::addAction('Http.sendHttpRequest', function (
            string $url,
            array $httpEventParams,
            ?string &$response,
            ?int &$status,
            array &$headers
        ) use (&$capturedBody): void {
            $capturedBody = json_decode((string) $httpEventParams['body'], true);
            $response = (string) json_encode([
                'choices' => [['message' => ['content' => 'Forced provider default model used.']]],
            ]);
            $status = 200;
            $headers = ['Content-Type' => 'application/json'];
        });

        StaticContainer::get(AIProviderService::class)
            ->complete((new AIRequest('why is the sky blue', 'Test'))->withModel('claude-haiku-4-5'));

        $this->assertIsArray($capturedBody);
        $this->assertSame('gpt-5.4-mini', $capturedBody['model']);
    }

    public function testAllowlistedCallerMaySelectProviderAndModelWhenProviderIsForced(): void
    {
        $this->saveOpenAiAndClaudeKeys();

        Config::getInstance()->AIProviders = [
            'defaultProvider' => 'openai',
            'providerSelectionAllowlist' => ['ExamplePlugin'],
        ];

        $capturedUrl = null;
        $capturedBody = null;
        $this->mockClaudeResponse('Brand X is a well-known brand.', $capturedUrl, $capturedBody);

        $response = StaticContainer::get(AIProviderService::class)->complete(
            (new AIRequest('What do you know about brand X?', 'ExamplePlugin'))
                ->withProviderId('anthropic')
                ->withModel('claude-sonnet-4-5')
        );

        $this->assertSame('anthropic', $response->toArray()['providerId']);
        $this->assertSame('https://api.anthropic.com/v1/messages', $capturedUrl);
        $this->assertIsArray($capturedBody);
        $this->assertSame('claude-sonnet-4-5', $capturedBody['model']);
    }

    public function testNonSuperUserServiceCallDoesNotForceDefaultProviderOnUnmanagedInstance(): void
    {
        $this->saveOpenAiAndClaudeKeys();
        $this->setAnonymousUser();

        $capturedUrl = null;
        $capturedBody = null;
        $this->mockClaudeResponse('Brand X is a well-known brand.', $capturedUrl, $capturedBody);

        $response = StaticContainer::get(AIProviderService::class)->complete(
            (new AIRequest('What do you know about brand X?', 'ExamplePlugin'))
                ->withProviderId('anthropic')
                ->withModel('claude-sonnet-4-5')
        );

        $this->assertFalse(StaticContainer::get(AIProviderService::class)->isManaged());
        $this->assertSame('anthropic', $response->toArray()['providerId']);
        $this->assertSame('https://api.anthropic.com/v1/messages', $capturedUrl);
        $this->assertIsArray($capturedBody);
        $this->assertSame('claude-sonnet-4-5', $capturedBody['model']);
    }

    public function testAllowlistOnlyAppliesToTheNamedCallerPlugin(): void
    {
        $this->saveOpenAiAndClaudeKeys();

        Config::getInstance()->AIProviders = [
            'defaultProvider' => 'openai',
            'providerSelectionAllowlist' => ['ExamplePlugin'],
        ];

        // mockAIProviderResponse() asserts the OpenAI endpoint is called, so a
        // request to Anthropic would fail this test.
        $this->mockAIProviderResponse('Because molecules scatter blue light more strongly.');

        $response = StaticContainer::get(AIProviderService::class)->complete(
            (new AIRequest('why is the sky blue', 'Goals'))
                ->withProviderId('anthropic')
                ->withModel('claude-sonnet-4-5')
        );

        $this->assertSame('openai', $response->toArray()['providerId']);
    }

    public function testAllowlistedCallerRequestingUnknownProviderGetsClearError(): void
    {
        $this->saveOpenAiAndClaudeKeys();

        Config::getInstance()->AIProviders = [
            'defaultProvider' => 'openai',
            'providerSelectionAllowlist' => ['ExamplePlugin'],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown AI provider "no-such-provider".');

        StaticContainer::get(AIProviderService::class)->complete(
            (new AIRequest('What do you know about brand X?', 'ExamplePlugin'))
                ->withProviderId('no-such-provider')
        );
    }

    public function testAllowlistedCallerRequestingUnconfiguredProviderFailsInsteadOfFallingBack(): void
    {
        // Only OpenAI is configured; Anthropic is requested. Falling back to the
        // forced provider would silently answer from the wrong engine, so a
        // clear error is expected instead.
        $this->api->saveSettings(
            'openai',
            Configuration::CAPABILITY_INSTANT,
            (string) json_encode([
                'openai' => ['apiKey' => 'secret-openai-key', 'endpointUrl' => ''],
            ])
        );

        Config::getInstance()->AIProviders = [
            'defaultProvider' => 'openai',
            'providerSelectionAllowlist' => ['ExamplePlugin'],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No API key is configured for Anthropic.');

        StaticContainer::get(AIProviderService::class)->complete(
            (new AIRequest('What do you know about brand X?', 'ExamplePlugin'))
                ->withProviderId('anthropic')
        );
    }

    public function testConfigFileApiKeyMakesProviderUsableWithoutStoredCredentials(): void
    {
        Config::getInstance()->AIProviders = ['openaiApiKey' => 'config-openai-key'];

        $this->mockAIProviderResponse('Because molecules scatter blue light more strongly.');

        $response = StaticContainer::get(AIProviderService::class)
            ->complete(new AIRequest('why is the sky blue', 'Test'));

        $this->assertSame('openai', $response->toArray()['providerId']);
    }

    public function testConfigFileApiKeyWinsOverStoredApiKey(): void
    {
        $this->api->saveSettings(
            'openai',
            Configuration::CAPABILITY_INSTANT,
            (string) json_encode([
                'openai' => ['apiKey' => 'stored-db-key', 'endpointUrl' => ''],
            ])
        );

        Config::getInstance()->AIProviders = ['openaiApiKey' => 'config-openai-key'];

        $configuration = StaticContainer::get(Configuration::class);

        $this->assertSame('config-openai-key', $configuration->getProviderConfiguration('openai')['apiKey']);
    }

    public function testEnvironmentVariableSuppliesApiKey(): void
    {
        putenv('MATOMO_AIPROVIDERS_OPENAI_API_KEY=env-openai-key');

        $configuration = StaticContainer::get(Configuration::class);

        $this->assertSame('env-openai-key', $configuration->getProviderConfiguration('openai')['apiKey']);

        // The config file wins over the environment variable.
        Config::getInstance()->AIProviders = ['openaiApiKey' => 'config-openai-key'];

        $this->assertSame('config-openai-key', $configuration->getProviderConfiguration('openai')['apiKey']);
    }

    public function testDiValueSuppliesApiKey(): void
    {
        StaticContainer::getContainer()->set('AIProviders.openaiApiKey', 'di-openai-key');

        $configuration = StaticContainer::get(Configuration::class);

        $this->assertSame('di-openai-key', $configuration->getProviderConfiguration('openai')['apiKey']);
    }

    public function testDiValueWinsOverConfigFileAndEnvironmentVariable(): void
    {
        putenv('MATOMO_AIPROVIDERS_OPENAI_API_KEY=env-openai-key');
        Config::getInstance()->AIProviders = ['openaiApiKey' => 'config-openai-key'];
        StaticContainer::getContainer()->set('AIProviders.openaiApiKey', 'di-openai-key');

        $configuration = StaticContainer::get(Configuration::class);

        $this->assertSame('di-openai-key', $configuration->getProviderConfiguration('openai')['apiKey']);
    }

    public function testManagedFlowServesAllowlistedPluginThroughRestrictedProviderWithConfigCredentials(): void
    {
        // Full managed-environment setup: a forced provider, restricted basic providers,
        // config-file credentials, and an allowlisted plugin targeting one of
        // the restricted providers.
        Config::getInstance()->AIProviders = [
            'defaultProvider' => 'openai',
            'openaiApiKey' => 'config-openai-key',
            'anthropicApiKey' => 'config-claude-key',
            'providerSelectionAllowlist' => ['ExamplePlugin'],
        ];
        $this->restrictSelectableProvidersTo('openai');

        $capturedUrl = null;
        $capturedBody = null;
        $capturedHeaders = null;
        $this->mockClaudeResponse('Brand X is a well-known brand.', $capturedUrl, $capturedBody, $capturedHeaders);

        $response = StaticContainer::get(AIProviderService::class)->complete(
            (new AIRequest('What do you know about brand X?', 'ExamplePlugin'))
                ->withProviderId('anthropic')
                ->withModel('claude-sonnet-4-5')
        );

        $this->assertSame('anthropic', $response->toArray()['providerId']);
        $this->assertSame('Brand X is a well-known brand.', $response->getText());
        $this->assertSame('https://api.anthropic.com/v1/messages', $capturedUrl);
        $this->assertSame('claude-sonnet-4-5', $capturedBody['model']);
        $this->assertContains('x-api-key: config-claude-key', $capturedHeaders);
    }

    public function testRestrictedProvidersAreHiddenFromAdminSurfaces(): void
    {
        $this->restrictSelectableProvidersTo('openai');

        $settings = $this->api->getSettings();
        $settingsProviderIds = array_column($settings['providers'], 'id');

        $statuses = StaticContainer::get(AIProviderService::class)->getAvailableProviderStatuses();
        $statusProviderIds = array_column($statuses, 'id');

        $this->assertSame(['openai'], $settingsProviderIds);
        $this->assertSame(['openai'], $statusProviderIds);
    }

    public function testAllowlistedCallerSeesRestrictedProvidersWithTheirConfiguredState(): void
    {
        Config::getInstance()->AIProviders = [
            'defaultProvider' => 'openai',
            'anthropicApiKey' => 'config-claude-key',
            'providerSelectionAllowlist' => ['ExamplePlugin'],
        ];
        $this->restrictSelectableProvidersTo('openai');

        $statuses = StaticContainer::get(AIProviderService::class)
            ->getProviderStatusesForCaller('ExamplePlugin');
        $statusesById = array_column($statuses, null, 'id');

        // The full registered list, matching what complete() can serve.
        $this->assertGreaterThan(1, count($statusesById));
        $this->assertArrayHasKey('openai', $statusesById);
        $this->assertArrayHasKey('anthropic', $statusesById);
        $this->assertTrue($statusesById['anthropic']['isConfigured']);
        $this->assertFalse($statusesById['google']['isConfigured']);
        $this->assertSame(
            ['id', 'name', 'isConfigured'],
            array_keys($statusesById['anthropic'])
        );
    }

    public function testNonAllowlistedCallerOnlySeesTheForcedProvider(): void
    {
        Config::getInstance()->AIProviders = [
            'defaultProvider' => 'openai',
            'providerSelectionAllowlist' => ['ExamplePlugin'],
        ];
        $this->restrictSelectableProvidersTo('openai');

        $service = StaticContainer::get(AIProviderService::class);

        $this->assertSame(['openai'], array_column($service->getProviderStatusesForCaller('OtherPlugin'), 'id'));
        $this->assertSame(['openai'], array_column($service->getProviderStatusesForCaller(''), 'id'));
    }

    public function testUnregisteredForcedProviderYieldsAnEmptyListForLockedCallers(): void
    {
        Config::getInstance()->AIProviders = ['defaultProvider' => 'not-a-provider'];

        $statuses = StaticContainer::get(AIProviderService::class)
            ->getProviderStatusesForCaller('OtherPlugin');

        $this->assertSame([], $statuses);
    }

    public function testAnyCallerSeesAllProvidersOnAnUnmanagedInstance(): void
    {
        // The allowlist is irrelevant when no provider is forced.
        Config::getInstance()->AIProviders = ['providerSelectionAllowlist' => ['ExamplePlugin']];

        $service = StaticContainer::get(AIProviderService::class);

        foreach (['ExamplePlugin', 'OtherPlugin'] as $caller) {
            $ids = array_column($service->getProviderStatusesForCaller($caller), 'id');
            $this->assertContains('anthropic', $ids);
            $this->assertContains('openai', $ids);
        }
    }

    public function testSavingBedrockWithRegionStoresTheNormalizedRegionAndFipsSetting(): void
    {
        $this->api->saveSettings(
            '',
            '',
            (string) json_encode([
                'bedrock' => [
                    'apiKey' => 'bedrock-long-term-key',
                    'endpointUrl' => ' EU-Central-1 ',
                    'useFipsEndpoint' => true,
                ],
            ])
        );

        $stored = StaticContainer::get(Configuration::class)->getProviderConfiguration('bedrock');

        $this->assertSame('eu-central-1', $stored['endpointUrl']);
        $this->assertTrue($stored['useFipsEndpoint']);
    }

    public function testSavingBedrockWithOnlyTheFipsSettingIsPersisted(): void
    {
        // Semi-managed setups get the API key from the config file, so the
        // admin may submit nothing but the FIPS toggle.
        $this->api->saveSettings(
            '',
            '',
            (string) json_encode([
                'bedrock' => ['useFipsEndpoint' => true],
            ])
        );

        $stored = StaticContainer::get(Configuration::class)->getProviderConfiguration('bedrock');

        $this->assertTrue($stored['useFipsEndpoint']);
    }

    public function testSavingBedrockWithAnUnrecognizedRegionValueStillFails(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The AWS region for AWS Bedrock is invalid.');

        $this->api->saveSettings(
            '',
            '',
            (string) json_encode([
                'bedrock' => ['apiKey' => 'bedrock-long-term-key', 'endpointUrl' => 'not a url'],
            ])
        );
    }

    public function testRestrictedProviderCannotBeTested(): void
    {
        $this->restrictSelectableProvidersTo('openai');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown AI provider "anthropic".');

        $this->api->testConnection(
            'anthropic',
            (string) json_encode([
                'apiKey' => 'secret-claude-key',
                'endpointUrl' => '',
            ])
        );
    }

    public function testRestrictedProviderCannotBeDisconnected(): void
    {
        $this->restrictSelectableProvidersTo('openai');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown AI provider "anthropic".');

        $this->api->disconnectProvider('anthropic');
    }

    public function testRestrictedProviderCannotBeSavedAsDefault(): void
    {
        $this->restrictSelectableProvidersTo('openai');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown AI provider "anthropic".');

        $this->api->saveSettings(
            'anthropic',
            Configuration::CAPABILITY_INSTANT,
            (string) json_encode([
                'anthropic' => ['apiKey' => 'secret-claude-key', 'endpointUrl' => ''],
            ])
        );
    }

    public function testRestrictedProviderIsNotEligibleAsDefault(): void
    {
        $this->api->saveSettings(
            'openai',
            Configuration::CAPABILITY_INSTANT,
            (string) json_encode([
                'openai' => ['apiKey' => 'secret-openai-key', 'endpointUrl' => ''],
            ])
        );

        // After the saved default provider becomes restricted, resolution must
        // not pick it (or any other restricted provider) as the default.
        $this->restrictSelectableProvidersTo('anthropic');

        $settings = $this->api->getSettings();

        $this->assertSame('', $settings['defaultProviderId']);
    }

    public function testDuplicateProviderRegistrationKeepsFirstSelectableFlag(): void
    {
        $providers = new AIProvidersList();
        $providers->addProvider(new OpenAI(), false);
        $providers->addProvider(new OpenAI());

        $this->assertCount(1, $providers->getProviders());
        $this->assertFalse($providers->isSelectable('openai'));
        $this->assertSame([], $providers->getSelectableProviders());
    }

    public function testForcedDefaultProviderFromConfigLocksConfiguration(): void
    {
        Config::getInstance()->AIProviders = ['defaultProvider' => 'openai'];

        $settings = $this->api->getSettings();

        $this->assertSame('openai', $settings['defaultProviderId']);
        $this->assertFalse($settings['canEditProviderConfiguration']);
        $this->assertFalse($settings['canEditCapabilityLevel']);
    }

    public function testManagedModeMakesSettingsPageUnavailable(): void
    {
        Config::getInstance()->AIProviders = ['defaultProvider' => 'openai'];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('managed');

        StaticContainer::get(Controller::class)->index();
    }

    public function testProvidersCannotBeOverwritten(): void
    {
        $providers = new AIProvidersList();
        $first = new OpenAI();

        $this->assertTrue($providers->addProvider($first));
        // A duplicate ID is rejected and reported via the return value.
        $this->assertFalse($providers->addProvider(new OpenAI()));

        $this->assertCount(1, $providers->getProviders());
        $this->assertSame($first, $providers->getProvider('openai'));
    }

    public function testApiTestsConnectionWithUnsavedProviderConfiguration(): void
    {
        Piwik::addAction('Http.sendHttpRequest', function (
            string $url,
            array $httpEventParams,
            ?string &$response,
            ?int &$status,
            array &$headers
        ): void {
            $this->assertSame('https://api.openai.com/v1/models', $url);
            $this->assertSame('GET', $httpEventParams['httpMethod']);

            $response = (string) json_encode(['data' => [['id' => 'gpt-4.1-mini']]]);
            $status = 200;
            $headers = ['Content-Type' => 'application/json'];
        });

        $response = $this->api->testConnection(
            'openai',
            (string) json_encode([
                'apiKey' => 'secret-openai-key',
                'endpointUrl' => '',
            ])
        );

        $this->assertSame('openai', $response['providerId']);
        $this->assertSame('OpenAI', $response['providerName']);
    }

    /**
     * @dataProvider getTransientErrorStatusCodes
     */
    public function testApiRetriesTransientProviderErrors(int $transientStatus): void
    {
        $requests = 0;
        Piwik::addAction('Http.sendHttpRequest', function (
            string $url,
            array $httpEventParams,
            ?string &$response,
            ?int &$status,
            array &$headers
        ) use (
            &$requests,
            $transientStatus
): void {
            $this->assertSame(
                'https://generativelanguage.googleapis.com/v1beta/models',
                $url
            );
            $this->assertSame('GET', $httpEventParams['httpMethod']);

            $requests++;

            if ($requests === 1) {
                $response = (string) json_encode([
                    'error' => [
                        'code' => $transientStatus,
                        'message' => 'This model is currently experiencing high demand.',
                        'status' => 'UNAVAILABLE',
                    ],
                ]);
                $status = $transientStatus;
                $headers = ['Content-Type' => 'application/json'];
                return;
            }

            $response = (string) json_encode(['models' => [['name' => 'models/gemini-2.5-flash']]]);
            $status = 200;
            $headers = ['Content-Type' => 'application/json'];
        });

        $response = $this->api->testConnection(
            'google',
            (string) json_encode([
                'apiKey' => 'secret-gemini-key',
                'endpointUrl' => '',
            ])
        );

        $this->assertSame(2, $requests);
        $this->assertSame('google', $response['providerId']);
        $this->assertSame('Google', $response['providerName']);
    }

    /**
     * @return array<array{int}>
     */
    public function getTransientErrorStatusCodes(): array
    {
        return [
            'request timeout (408)' => [408],
            'rate limited (429)' => [429],
            'internal server error (500)' => [500],
            'bad gateway (502)' => [502],
            'service unavailable (503)' => [503],
            'gateway timeout (504)' => [504],
            'overloaded (529)' => [529],
        ];
    }

    /**
     * @dataProvider getNonRetryableErrorStatusCodes
     */
    public function testApiDoesNotRetryPermanentProviderErrors(int $permanentStatus): void
    {
        $requests = 0;
        Piwik::addAction('Http.sendHttpRequest', function (
            string $url,
            array $httpEventParams,
            ?string &$response,
            ?int &$status,
            array &$headers
        ) use (
            &$requests,
            $permanentStatus
): void {
            $requests++;

            $response = (string) json_encode([
                'error' => [
                    'code' => $permanentStatus,
                    'message' => 'The request body is malformed.',
                    'status' => 'INVALID_ARGUMENT',
                ],
            ]);
            $status = $permanentStatus;
            $headers = ['Content-Type' => 'application/json'];
        });

        try {
            $this->api->testConnection(
                'google',
                (string) json_encode([
                    'apiKey' => 'secret-gemini-key',
                    'endpointUrl' => '',
                ])
            );
            $this->fail('Expected a permanent provider error to be thrown.');
        } catch (AIProviderClientException $e) {
            // expected: permanent client errors are surfaced, not retried
        }

        $this->assertSame(1, $requests);
    }

    /**
     * @return array<array{int}>
     */
    public function getNonRetryableErrorStatusCodes(): array
    {
        return [
            'bad request (400)' => [400],
            'forbidden (403)' => [403],
            'not found (404)' => [404],
            'request too large (413)' => [413],
        ];
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

    /**
     * Stores credentials for OpenAI and Anthropic while the instance is still
     * unmanaged, so managed-mode tests can then force a provider via config.
     */
    private function saveOpenAiAndClaudeKeys(): void
    {
        $this->api->saveSettings(
            'openai',
            Configuration::CAPABILITY_INSTANT,
            (string) json_encode([
                'openai' => ['apiKey' => 'secret-openai-key', 'endpointUrl' => ''],
                'anthropic' => ['apiKey' => 'secret-claude-key', 'endpointUrl' => ''],
            ])
        );
    }

    /**
     * Demotes every provider except the given one to non-selectable, the same
     * way a managed environment restricts providers.
     */
    private function restrictSelectableProvidersTo(string $selectableProviderId): void
    {
        Piwik::addAction(
            'AIProviders.filterAIProviders',
            function (AIProvidersList $providers) use ($selectableProviderId): void {
                foreach ($providers->getProviders() as $provider) {
                    if ($provider->getId() !== $selectableProviderId) {
                        $providers->setSelectable($provider->getId(), false);
                    }
                }
            }
        );
    }

    /**
     * @param string|null $capturedUrl Set to the requested URL.
     * @param array<string, mixed>|null $capturedBody Set to the decoded request body.
     * @param array<int, string>|null $capturedHeaders Set to the sent request headers.
     */
    private function mockClaudeResponse(
        string $text,
        ?string &$capturedUrl,
        ?array &$capturedBody,
        ?array &$capturedHeaders = null
    ): void {
        Piwik::addAction('Http.sendHttpRequest', function (
            string $url,
            array $httpEventParams,
            ?string &$response,
            ?int &$status,
            array &$headers
        ) use (
            $text,
            &$capturedUrl,
            &$capturedBody,
            &$capturedHeaders
): void {
            $capturedUrl = $url;
            $capturedBody = json_decode((string) $httpEventParams['body'], true);
            $capturedHeaders = $httpEventParams['headers'];

            $response = (string) json_encode([
                'content' => [['type' => 'text', 'text' => $text]],
                'usage' => ['input_tokens' => 15, 'output_tokens' => 9],
            ]);
            $status = 200;
            $headers = ['Content-Type' => 'application/json'];
        });
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
                'usage' => [
                    'prompt_tokens' => 12,
                    'completion_tokens' => 7,
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

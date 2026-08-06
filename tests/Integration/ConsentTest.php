<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\AIProviders\tests\Integration;

use Exception;
use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\Plugins\AIProviders\AIDataScope;
use Piwik\Plugins\AIProviders\AIProviderService;
use Piwik\Plugins\AIProviders\SystemSettings;
use Piwik\Plugins\CorePluginsAdmin\API as CorePluginsAdminAPI;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * How the instance-wide AI consent is offered, changed, and read back.
 *
 * @group AIProviders
 * @group AIProvidersConsent
 * @group Plugins
 */
class ConsentTest extends IntegrationTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Config::getInstance()->AIProviders = [];

        Fixture::createSuperUser();
        FakeAccess::clearAccess(true);
    }

    public function tearDown(): void
    {
        Config::getInstance()->AIProviders = [];

        parent::tearDown();
    }

    public function testConsentIsOfferedOnTheGeneralSettingsPage(): void
    {
        $sections = CorePluginsAdminAPI::getInstance()->getSystemSettings();

        $settings = [];
        foreach ($sections as $section) {
            if ($section['pluginName'] === 'AIProviders') {
                $settings = $section['settings'];
            }
        }

        $this->assertCount(1, $settings);
        $this->assertSame('dataConsent', $settings[0]['name']);
        $this->assertSame(AIDataScope::NONE, $settings[0]['value']);
        $this->assertSame(['plugin' => 'AIProviders', 'name' => 'FieldAIConsent'], $settings[0]['component']);

        // least sensitive first, each with a title and an explanation
        $this->assertSame(
            [AIDataScope::GENERAL, AIDataScope::AGGREGATED, AIDataScope::RAW],
            array_keys((array) $settings[0]['availableValues'])
        );
        foreach ((array) $settings[0]['availableValues'] as $key => $tier) {
            $this->assertSame($key, $tier['key']);
            $this->assertNotEmpty($tier['value']);
            $this->assertNotEmpty($tier['description']);
        }
    }

    public function testOnlyKnownDataScopesCanBeStored(): void
    {
        $dataConsent = StaticContainer::get(SystemSettings::class)->dataConsent;

        // ticking nothing stays storable even though it is not an offered tier
        $dataConsent->setValue(AIDataScope::NONE);
        $this->assertSame(AIDataScope::NONE, $dataConsent->getValue());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unknown AI data consent value "everything".');

        $dataConsent->setValue('everything');
    }

    public function testNothingIsAllowedUntilATierIsGranted(): void
    {
        $service = StaticContainer::get(AIProviderService::class);

        $this->assertFalse($service->hasConsentFor(AIDataScope::GENERAL));
        $this->assertFalse($service->hasConsentFor(AIDataScope::RAW));

        $this->grantConsent(AIDataScope::AGGREGATED);

        $this->assertTrue($service->hasConsentFor(AIDataScope::GENERAL));
        $this->assertFalse($service->hasConsentFor(AIDataScope::RAW));
    }

    public function testConsentIsReadableByANonSuperUser(): void
    {
        $this->grantConsent(AIDataScope::GENERAL);

        // features gate their UI for every user, so reading needs no access rights
        FakeAccess::clearAccess(false, [], [1], 'viewUser');

        $service = StaticContainer::get(AIProviderService::class);

        $this->assertTrue($service->hasConsentFor(AIDataScope::GENERAL));
        $this->assertFalse($service->hasConsentFor(AIDataScope::RAW));
    }

    public function testConsentIsNotSettableFromTheConfigFile(): void
    {
        $this->grantConsent(AIDataScope::GENERAL);
        Config::getInstance()->AIProviders = ['dataConsent' => AIDataScope::RAW];

        $service = StaticContainer::get(AIProviderService::class);

        $this->assertTrue($service->hasConsentFor(AIDataScope::GENERAL));
        $this->assertFalse($service->hasConsentFor(AIDataScope::RAW));

        // the field stays offered, so the account owner can still decide
        $this->assertTrue(
            StaticContainer::get(SystemSettings::class)->dataConsent->isWritableByCurrentUser()
        );
    }

    private function grantConsent(string $dataScope): void
    {
        $systemSettings = StaticContainer::get(SystemSettings::class);
        $systemSettings->dataConsent->setValue($dataScope);
        $systemSettings->dataConsent->save();
    }

    public function provideContainerConfig(): array
    {
        return [
            'Piwik\Access' => new FakeAccess(),
        ];
    }
}

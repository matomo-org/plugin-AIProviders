<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\AIProviders\tests\Unit;

use PHPUnit\Framework\TestCase;
use Piwik\Plugins\AIProviders\AIDataScope;

/**
 * The sensitivity hierarchy behind the consent setting.
 *
 * @group AIProviders
 * @group AIProvidersConsent
 * @group Plugins
 */
class AIDataScopeTest extends TestCase
{
    /**
     * @dataProvider getCoversScenarios
     */
    public function testCovers(string $consented, string $required, bool $expected): void
    {
        $this->assertSame($expected, AIDataScope::covers($consented, $required));
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public function getCoversScenarios(): iterable
    {
        yield 'no consent denies the narrowest scope' => [AIDataScope::NONE, AIDataScope::GENERAL, false];
        yield 'a tier covers itself' => [AIDataScope::AGGREGATED, AIDataScope::AGGREGATED, true];
        yield 'a tier covers the narrower ones' => [AIDataScope::AGGREGATED, AIDataScope::GENERAL, true];
        yield 'a tier denies the wider ones' => [AIDataScope::AGGREGATED, AIDataScope::RAW, false];

        // both sides fail closed
        yield 'unknown consent grants nothing' => ['aggregate', AIDataScope::GENERAL, false];
        yield 'unknown requirement needs raw consent' => [AIDataScope::AGGREGATED, 'everything', false];
    }
}

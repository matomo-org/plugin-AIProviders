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
use Piwik\Plugins\AIProviders\AIProvidersList;
use Piwik\Plugins\AIProviders\Provider\Claude;
use Piwik\Plugins\AIProviders\Provider\OpenAI;

/**
 * @group AIProviders
 * @group AIProvidersList
 * @group Plugins
 */
class AIProvidersListTest extends TestCase
{
    public function testProvidersAreSelectableByDefault(): void
    {
        $providers = new AIProvidersList();
        $providers->addProvider(new OpenAI());

        $this->assertTrue($providers->isSelectable('openai'));
        $this->assertCount(1, $providers->getSelectableProviders());
    }

    public function testProviderCanBeRegisteredAsRestricted(): void
    {
        $providers = new AIProvidersList();
        $providers->addProvider(new OpenAI(), false);

        $this->assertTrue($providers->hasProvider('openai'));
        $this->assertFalse($providers->isSelectable('openai'));
        $this->assertSame([], $providers->getSelectableProviders());
        $this->assertCount(1, $providers->getProviders());
    }

    public function testSetSelectableDemotesAndPromotesRegisteredProviders(): void
    {
        $providers = new AIProvidersList();
        $providers->addProvider(new OpenAI());
        $providers->addProvider(new Claude());

        $providers->setSelectable('claude', false);

        $this->assertFalse($providers->isSelectable('claude'));
        $this->assertTrue($providers->isSelectable('openai'));
        $this->assertCount(2, $providers->getProviders());
        $this->assertCount(1, $providers->getSelectableProviders());
        $this->assertSame('openai', $providers->getSelectableProviders()[0]->getId());

        $providers->setSelectable('claude', true);

        $this->assertTrue($providers->isSelectable('claude'));
        $this->assertCount(2, $providers->getSelectableProviders());
    }

    public function testSetSelectableIgnoresUnknownProviders(): void
    {
        $providers = new AIProvidersList();

        $providers->setSelectable('unknown', true);

        $this->assertFalse($providers->isSelectable('unknown'));
        $this->assertFalse($providers->hasProvider('unknown'));
    }

    public function testRemoveProviderForgetsSelectableFlag(): void
    {
        $providers = new AIProvidersList();
        $providers->addProvider(new OpenAI());

        $providers->removeProvider('openai');

        $this->assertFalse($providers->hasProvider('openai'));
        $this->assertFalse($providers->isSelectable('openai'));
        $this->assertSame([], $providers->getSelectableProviders());
    }
}

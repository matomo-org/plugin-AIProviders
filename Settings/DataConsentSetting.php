<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\AIProviders\Settings;

use Piwik\Piwik;
use Piwik\Settings\Plugin\SystemSetting;

/**
 * A SystemSetting that ignores a value of the same name in the `[AIProviders]`
 * config section.
 *
 * Consent is the account owner's decision, so the hosting environment
 * must not be able to overwrite it, or to hide the field by setting it. Both
 * overrides come from {@link SystemSetting} by default.
 */
class DataConsentSetting extends SystemSetting
{
    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->storage->getValue($this->name, $this->defaultValue, $this->type);
    }

    public function isWritableByCurrentUser(): bool
    {
        return Piwik::hasUserSuperUserAccess();
    }
}

<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\AIProviders;

use Piwik\Piwik;
use Piwik\Plugin\ControllerAdmin;

class Controller extends ControllerAdmin
{
    public function index(): string
    {
        Piwik::checkUserHasSuperUserAccess();

        return $this->renderTemplate('index');
    }
}

<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\AIProviders;

use Exception;
use Piwik\Piwik;
use Piwik\Plugins\AIProviders\Settings\DataConsentSetting;
use Piwik\Settings\FieldConfig;

/**
 * Instance-wide AI data processing consent setting.
 *
 * Super-user only, password confirmed on save, logged by ActivityLog. Features
 * read it through {@link AIProviderService::hasConsentFor()}. Always the
 * account owner's decision, never the hosting environment's, hence
 * {@link DataConsentSetting}.
 */
class SystemSettings extends \Piwik\Settings\Plugin\SystemSettings
{
    private const FIELD_COMPONENT = ['plugin' => 'AIProviders', 'name' => 'FieldAIConsent'];

    /**
     * Widest scope AI features may send, an {@link AIDataScope} constant.
     *
     * @var DataConsentSetting
     */
    public $dataConsent;

    protected function init(): void
    {
        $this->title = Piwik::translate('AIProviders_DataConsentTitle');

        $this->dataConsent = new DataConsentSetting(
            'dataConsent',
            AIDataScope::NONE,
            FieldConfig::TYPE_STRING,
            $this->pluginName
        );
        $this->dataConsent->setConfigureCallback(
            function (FieldConfig $field): void {
                $field->title = Piwik::translate('AIProviders_DataConsent');
                // only lays the field out as a checkbox group, the control is FIELD_COMPONENT
                $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
                $field->customFieldComponent = self::FIELD_COMPONENT;
                $field->inlineHelp = Piwik::translate('AIProviders_DataConsentHelp');
                $field->availableValues = $this->getSelectableDataScopes();
                // availableValues holds the offered tiers only, so NONE needs accepting here
                $field->validate = function ($value): void {
                    $allowedValues = array_merge(
                        [AIDataScope::NONE],
                        array_keys($this->getSelectableDataScopes())
                    );

                    if (!in_array($value, $allowedValues, true)) {
                        throw new Exception(sprintf('Unknown AI data consent value "%s".', $value));
                    }
                };
            }
        );
        $this->addSetting($this->dataConsent);
    }

    /**
     * Tiers offered in the UI, least sensitive first. NONE is not among them, it
     * is what an empty checklist means.
     *
     * @return array<string, array{key: string, value: string, description: string}>
     */
    private function getSelectableDataScopes(): array
    {
        return [
            AIDataScope::GENERAL => [
                'key' => AIDataScope::GENERAL,
                'value' => Piwik::translate('AIProviders_DataConsentGeneral'),
                'description' => Piwik::translate('AIProviders_DataConsentGeneralHelp'),
            ],
            AIDataScope::AGGREGATED => [
                'key' => AIDataScope::AGGREGATED,
                'value' => Piwik::translate('AIProviders_DataConsentAggregated'),
                'description' => Piwik::translate('AIProviders_DataConsentAggregatedHelp'),
            ],
            AIDataScope::RAW => [
                'key' => AIDataScope::RAW,
                'value' => Piwik::translate('AIProviders_DataConsentRaw'),
                'description' => Piwik::translate('AIProviders_DataConsentRawHelp'),
            ],
        ];
    }

    /**
     * Returns the consented scope, {@link AIDataScope::NONE} when unset. Unknown
     * values are denied by {@link AIDataScope::covers()}.
     */
    public function getDataConsent(): string
    {
        $dataScope = $this->dataConsent->getValue();

        return is_string($dataScope) && $dataScope !== '' ? $dataScope : AIDataScope::NONE;
    }
}

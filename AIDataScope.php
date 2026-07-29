<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\AIProviders;

/**
 * Categories of data an AI feature may send to a provider. Consent for one
 * always includes the narrower ones.
 */
final class AIDataScope
{
    /** Nothing may be sent. Only a consent value, features never ask for it. */
    public const NONE = 'none';

    /** Publicly available information only, no data from this Matomo. */
    public const GENERAL = 'general';

    /** Aggregated report data, for example report rows and metrics. */
    public const AGGREGATED = 'aggregated';

    /** Visitor level data, for example the visits log or session recordings. */
    public const RAW = 'raw';

    /** Least to most sensitive, the position is the rank {@link covers()} compares. */
    private const ORDERED = [self::NONE, self::GENERAL, self::AGGREGATED, self::RAW];

    /**
     * Returns whether the consented scope covers the scope a feature needs.
     * Unknown values fail closed on both sides.
     */
    public static function covers(string $consented, string $required): bool
    {
        $ranks = array_flip(self::ORDERED);

        return ($ranks[$consented] ?? $ranks[self::NONE]) >= ($ranks[$required] ?? $ranks[self::RAW]);
    }
}

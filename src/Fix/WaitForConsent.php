<?php

declare(strict_types=1);

namespace Contenir\Extension\Tracking\Fix;

use Contenir\Extension\Tracking\TrackingSection;
use Override;

/**
 * Holds the tags back until visitors consent.
 *
 * @api
 */
final class WaitForConsent extends AbstractSettingFix
{
    #[Override]
    protected function done(): string
    {
        return 'The tags now wait for consent.';
    }

    #[Override]
    protected function values(): array
    {
        return [TrackingSection::WAIT_FOR_CONSENT => true];
    }
}

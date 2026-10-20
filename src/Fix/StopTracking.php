<?php

declare(strict_types=1);

namespace Contenir\Extension\Tracking\Fix;

use Contenir\Extension\Tracking\TrackingSection;
use Override;

/**
 * Switches tracking off on this environment.
 *
 * @api
 */
final class StopTracking extends AbstractSettingFix
{
    #[Override]
    protected function done(): string
    {
        return 'Tracking is off on this environment.';
    }

    #[Override]
    protected function values(): array
    {
        return [TrackingSection::TRACK_ENVIRONMENT => false];
    }
}

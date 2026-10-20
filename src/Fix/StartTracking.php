<?php

declare(strict_types=1);

namespace Contenir\Extension\Tracking\Fix;

use Contenir\Extension\Tracking\TrackingSection;
use Override;

/**
 * Switches tracking on, for production.
 *
 * @api
 */
final class StartTracking extends AbstractSettingFix
{
    #[Override]
    protected function done(): string
    {
        return 'Production is tracked again.';
    }

    #[Override]
    protected function values(): array
    {
        return [TrackingSection::TRACK_ENVIRONMENT => true];
    }
}

<?php

declare(strict_types=1);

namespace Contenir\Extension\Tracking\Check;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Diagnostic\CheckInterface;
use Contenir\Extension\Api\Diagnostic\CheckResult;
use Contenir\Extension\Api\Diagnostic\GuidedFix;
use Contenir\Extension\Api\Diagnostic\OneClickFix;
use Contenir\Extension\Tracking\Environment;
use Contenir\Extension\Tracking\Fix\StartTracking;
use Contenir\Extension\Tracking\Fix\StopTracking;
use Contenir\Extension\Tracking\TrackingSection;
use Override;

use function sprintf;

/**
 * Warns when tracking is on outside production, where a copy of the site
 * would report visits to the live accounts, and when production has tags
 * but doesn't track, so no visits are counted.
 *
 * @api
 */
final readonly class EnvironmentCheck implements CheckInterface
{
    private Environment $environment;

    public function __construct(?Environment $environment = null)
    {
        $this->environment = $environment ?? new Environment();
    }

    #[Override]
    public function check(ExtensionContextInterface $context): CheckResult
    {
        $config = TrackingSection::config($context->settings());
        if ([] === $config->tags) {
            return CheckResult::skipped('No tags are set.');
        }

        if ($config->track && ! $this->environment->isProduction()) {
            return CheckResult::warning(
                sprintf(
                    'Tracking is on outside production: this %s site reports its visits to the live accounts.',
                    $this->environment->name,
                ),
                new GuidedFix(
                    label: 'Stop tracking here',
                    explanation: sprintf(
                        'Visits to this %s copy of the site are counted with the live site\'s, which skews its '
                            . 'reports. Stopping tracking here changes this environment only: production keeps tracking.',
                        $this->environment->name,
                    ),
                    handler: StopTracking::class,
                ),
            );
        }

        if (! $config->track && $this->environment->isProduction()) {
            return CheckResult::warning(
                'Tags are set, but tracking is off on production, so no visits are counted.',
                new OneClickFix('Track on production', StartTracking::class),
            );
        }

        return CheckResult::success(
            $config->track
                ? 'Production is tracked.'
                : sprintf(
                    'This %s site is not tracked.',
                    $this->environment->name,
                ),
        );
    }

    #[Override]
    public function label(): string
    {
        return 'Tracking outside production';
    }
}

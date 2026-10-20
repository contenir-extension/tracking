<?php

declare(strict_types=1);

namespace Contenir\Extension\Tracking\Check;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Diagnostic\CheckInterface;
use Contenir\Extension\Api\Diagnostic\CheckResult;
use Contenir\Extension\Api\Diagnostic\GuidedFix;
use Contenir\Extension\Tracking\Fix\WaitForConsent;
use Contenir\Extension\Tracking\TrackingSection;
use Override;

/**
 * Warns while a site's tags run before visitors consent (TRK-1): sites that
 * tracked before the add-on keep doing so until they have a consent banner.
 *
 * @api
 */
final class ConsentCheck implements CheckInterface
{
    #[Override]
    public function check(ExtensionContextInterface $context): CheckResult
    {
        $config = TrackingSection::config($context->settings());
        if (! $config->writesTags()) {
            return CheckResult::skipped('This environment writes no tags.');
        }

        if ($config->consent->waitForConsent) {
            return CheckResult::success('The tags wait for consent.');
        }

        return CheckResult::warning(
            'The tags run before visitors consent, which the law requires for visitors from the EU and UK.',
            new GuidedFix(
                label: 'Wait for consent',
                explanation: 'Put a cookie banner on the site first: one that fires contenir:consent, such as the '
                    . 'Consent add-on. Then hold the tags back until visitors accept them. Without a banner, nothing '
                    . 'is counted once the tags wait.',
                handler: WaitForConsent::class,
            ),
        );
    }

    #[Override]
    public function label(): string
    {
        return 'Tracking and consent';
    }
}

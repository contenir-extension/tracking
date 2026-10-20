<?php

declare(strict_types=1);

namespace Contenir\Extension\Tracking\Tests\Unit\Check;

use Contenir\Extension\Api\Diagnostic\CheckResult;
use Contenir\Extension\Api\Diagnostic\GuidedFix;
use Contenir\Extension\Tracking\Check\ConsentCheck;
use Contenir\Extension\Tracking\EnabledAddOns;
use Contenir\Extension\Tracking\Fix\WaitForConsent;
use Contenir\Extension\Tracking\Tests\TestAsset\Settings\ArraySettings;
use Contenir\Extension\Tracking\Tests\TestAsset\Settings\SettingsContext;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ConsentCheckTest extends TestCase
{
    #[Test]
    public function namesWhatItLooksAt(): void
    {
        static::assertSame('Tracking and consent', (new ConsentCheck())->label());
    }

    #[Test]
    public function passesTagsHeldBackByTheConsentAddOn(): void
    {
        static::assertEquals(
            CheckResult::success('The Consent add-on holds the tags back until visitors consent.'),
            $this->check(
                wait: false,
                addOns: new EnabledAddOns(['tracking', 'consent']),
            ),
        );
    }

    #[Test]
    public function passesTagsThatWaitForConsent(): void
    {
        static::assertEquals(CheckResult::success('The tags wait for consent.'), $this->check(wait: true));
    }

    #[Test]
    public function skipsAnEnvironmentThatWritesNoTags(): void
    {
        $result = (new ConsentCheck(new EnabledAddOns()))->check(new SettingsContext(new ArraySettings([
            'google_analytics' => 'G-ABC12345',
            'wait_for_consent' => false,
        ])));

        static::assertEquals(CheckResult::skipped('This environment writes no tags.'), $result);
    }

    #[Test]
    public function skipsAnEnvironmentThatWritesNoTagsEvenWithTheConsentAddOn(): void
    {
        $result = (new ConsentCheck(new EnabledAddOns(['consent'])))->check(new SettingsContext(new ArraySettings([
            'google_analytics' => 'G-ABC12345',
        ])));

        static::assertEquals(CheckResult::skipped('This environment writes no tags.'), $result);
    }

    #[Test]
    public function warnsWhileTagsRunBeforeConsent(): void
    {
        static::assertEquals(
            CheckResult::warning(
                'The tags run before visitors consent, which the law requires for visitors from the EU and UK.',
                new GuidedFix(
                    label: 'Wait for consent',
                    explanation: 'Put a cookie banner on the site first: one that fires contenir:consent, such as the '
                        . 'Consent add-on. Then hold the tags back until visitors accept them. Without a banner, nothing '
                        . 'is counted once the tags wait.',
                    handler: WaitForConsent::class,
                ),
            ),
            $this->check(wait: false),
        );
    }

    private function check(bool $wait, EnabledAddOns $addOns = new EnabledAddOns()): CheckResult
    {
        return (new ConsentCheck($addOns))->check(new SettingsContext(new ArraySettings([
            'google_analytics'  => 'G-ABC12345',
            'wait_for_consent'  => $wait,
            'track_environment' => true,
        ])));
    }
}

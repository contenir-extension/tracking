<?php

declare(strict_types=1);

namespace Contenir\Extension\Tracking\Tests\Unit\Check;

use Contenir\Extension\Api\Diagnostic\CheckResult;
use Contenir\Extension\Api\Diagnostic\GuidedFix;
use Contenir\Extension\Api\Diagnostic\OneClickFix;
use Contenir\Extension\Api\Diagnostic\Status;
use Contenir\Extension\Tracking\Check\EnvironmentCheck;
use Contenir\Extension\Tracking\Environment;
use Contenir\Extension\Tracking\Fix\StartTracking;
use Contenir\Extension\Tracking\Fix\StopTracking;
use Contenir\Extension\Tracking\Tests\TestAsset\Settings\ArraySettings;
use Contenir\Extension\Tracking\Tests\TestAsset\Settings\SettingsContext;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class EnvironmentCheckTest extends TestCase
{
    #[Test]
    public function namesWhatItLooksAt(): void
    {
        static::assertSame('Tracking outside production', (new EnvironmentCheck())->label());
    }

    #[Test]
    public function offersToStopTrackingOutsideProduction(): void
    {
        static::assertEquals(
            CheckResult::warning(
                'Tracking is on outside production: this staging site reports its visits to the live accounts.',
                new GuidedFix(
                    label: 'Stop tracking here',
                    explanation: 'Visits to this staging copy of the site are counted with the live site\'s, which '
                        . 'skews its reports. Stopping tracking here changes this environment only: production keeps '
                        . 'tracking.',
                    handler: StopTracking::class,
                ),
            ),
            $this->check('staging', track: true),
        );
    }

    #[Test]
    public function offersToTrackProductionThatHasTagsButDoesNotTrack(): void
    {
        static::assertEquals(
            CheckResult::warning(
                'Tags are set, but tracking is off on production, so no visits are counted.',
                new OneClickFix('Track on production', StartTracking::class),
            ),
            $this->check('production', track: false),
        );
    }

    #[Test]
    public function passesAnotherEnvironmentThatDoesNotTrack(): void
    {
        static::assertEquals(
            CheckResult::success('This development site is not tracked.'),
            $this->check('development', track: false),
        );
    }

    #[Test]
    public function passesProductionThatTracks(): void
    {
        static::assertEquals(CheckResult::success('Production is tracked.'), $this->check('production', track: true));
    }

    #[Test]
    public function readsTheCmssEnvironmentWhenGivenNone(): void
    {
        $result = (new EnvironmentCheck())->check(new SettingsContext(new ArraySettings([
            'google_analytics'  => 'G-ABC12345',
            'track_environment' => (new Environment())->isProduction(),
        ])));

        static::assertSame(Status::Success, $result->status);
    }

    #[Test]
    public function skipsASiteWithNoTags(): void
    {
        $result = (new EnvironmentCheck(new Environment('staging')))->check(new SettingsContext(new ArraySettings([
            'track_environment' => true,
        ])));

        static::assertEquals(CheckResult::skipped('No tags are set.'), $result);
    }

    private function check(string $environment, bool $track): CheckResult
    {
        return (new EnvironmentCheck(new Environment($environment)))->check(new SettingsContext(new ArraySettings([
            'google_analytics'  => 'G-ABC12345',
            'track_environment' => $track,
        ])));
    }
}

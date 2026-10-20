<?php

declare(strict_types=1);

namespace Contenir\Extension\Tracking\Tests\Unit\Fix;

use Contenir\Extension\Api\Diagnostic\FixInterface;
use Contenir\Extension\Api\Diagnostic\FixResult;
use Contenir\Extension\Api\Event\Outcome;
use Contenir\Extension\Tracking\Fix\StartTracking;
use Contenir\Extension\Tracking\Fix\StopTracking;
use Contenir\Extension\Tracking\Fix\WaitForConsent;
use Contenir\Extension\Tracking\Tests\TestAsset\Settings\ArraySettings;
use Contenir\Extension\Tracking\Tests\TestAsset\Settings\SettingsContext;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class SettingFixTest extends TestCase
{
    /**
     * @return array<string, array{FixInterface, array<string, bool>, string}>
     */
    public static function fixes(): array
    {
        return [
            'stop tracking'    => [
                new StopTracking(),
                ['track_environment' => false],
                'Tracking is off on this environment.',
            ],
            'start tracking'   => [new StartTracking(), ['track_environment' => true], 'Production is tracked again.'],
            'wait for consent' => [
                new WaitForConsent(),
                ['wait_for_consent' => true],
                'The tags now wait for consent.',
            ],
        ];
    }

    #[Test]
    public function passesOnTheSettingsMessageWhenTheSaveFails(): void
    {
        $settings = new ArraySettings(
            outcome: Outcome::Stopped,
            message: 'Saving is switched off',
        );

        static::assertEquals(
            FixResult::failed('Saving is switched off'),
            (new StopTracking())->apply([], new SettingsContext($settings)),
        );
    }

    /**
     * @param array<string, bool> $saved
     */
    #[Test]
    #[DataProvider('fixes')]
    public function savesItsSwitchAsTheSettingsScreenWould(FixInterface $fix, array $saved, string $message): void
    {
        $settings = new ArraySettings();

        static::assertEquals(
            [FixResult::applied($message), $saved],
            [$fix->apply(['ignored' => 'x'], new SettingsContext($settings)), $settings->saved],
        );
    }

    #[Test]
    public function saysTheSettingWasNotSavedWhenTheHostGivesNoReason(): void
    {
        $settings = new ArraySettings(outcome: Outcome::Failed);

        static::assertEquals(
            FixResult::failed('The setting could not be saved.'),
            (new WaitForConsent())->apply([], new SettingsContext($settings)),
        );
    }
}

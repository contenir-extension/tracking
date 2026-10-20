<?php

declare(strict_types=1);

namespace Contenir\Extension\Tracking\Tests\Unit\Script;

use Contenir\Extension\Api\Script\ConsentCategory;
use Contenir\Extension\Api\Script\Placement;
use Contenir\Extension\Api\Script\Script;
use Contenir\Extension\Tracking\Script\TrackingScripts;
use Contenir\Extension\Tracking\Tests\TestAsset\Settings\ArraySettings;
use Contenir\Extension\Tracking\Tests\TestAsset\Settings\SettingsContext;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_map;

#[Group('unit')]
final class TrackingScriptsTest extends TestCase
{
    #[Test]
    public function leavesTheNoscriptFallbacksToTheSiteAdapters(): void
    {
        $scripts = (new TrackingScripts())->scripts(new SettingsContext(new ArraySettings([
            'google_tag_manager' => 'GTM-ABCD123',
            'wait_for_consent'   => false,
            'track_environment'  => true,
        ])));

        static::assertSame(
            [Placement::Head, Placement::Head],
            array_map(static fn(Script $script): Placement => $script->placement, $scripts),
        );
    }

    #[Test]
    public function offersEveryTagWithItsConsentCategoryInOrder(): void
    {
        $scripts = (new TrackingScripts())->scripts(new SettingsContext(new ArraySettings([
            'google_analytics'   => 'G-ABC12345',
            'google_tag_manager' => 'GTM-ABCD123',
            'facebook_pixel'     => '123456789012345',
            'linkedin_insight'   => '1234567',
            'track_environment'  => true,
        ])));

        static::assertSame(
            [
                ['inline', ConsentCategory::Necessary, []],
                [
                    'https://www.googletagmanager.com/gtag/js?id=G-ABC12345',
                    ConsentCategory::Analytics,
                    ['async' => true],
                ],
                ['inline', ConsentCategory::Analytics, []],
                ['inline', ConsentCategory::Analytics, []],
                ['inline', ConsentCategory::Marketing, []],
                ['inline', ConsentCategory::Marketing, []],
            ],
            array_map(
                static fn(Script $script): array => [
                    $script->src ?? (null === $script->inline ? 'neither' : 'inline'),
                    $script->consent,
                    $script->attributes,
                ],
                $scripts,
            ),
        );
    }

    #[Test]
    public function offersNothingWhereTheEnvironmentDoesNotTrack(): void
    {
        static::assertSame(
            [],
            (new TrackingScripts())->scripts(new SettingsContext(new ArraySettings([
                'google_analytics' => 'G-ABC12345',
            ]))),
        );
    }

    #[Test]
    public function passesTheCodeThrough(): void
    {
        $scripts = (new TrackingScripts())->scripts(new SettingsContext(new ArraySettings([
            'google_analytics'  => 'G-ABC12345',
            'track_environment' => true,
        ])));

        static::assertSame("gtag('js', new Date());\ngtag('config', \"G-ABC12345\");", $scripts[2]->inline ?? null);
    }
}

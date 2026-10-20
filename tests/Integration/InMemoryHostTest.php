<?php

declare(strict_types=1);

namespace Contenir\Extension\Tracking\Tests\Integration;

use Contenir\Extension\Api\Diagnostic\CheckResult;
use Contenir\Extension\Api\Diagnostic\Status;
use Contenir\Extension\Api\Script\ConsentCategory;
use Contenir\Extension\Api\Script\Script;
use Contenir\Extension\Api\Testing\FrozenClock;
use Contenir\Extension\Api\Testing\InMemoryHost;
use Contenir\Extension\Tracking\Check\EnvironmentCheck;
use Contenir\Extension\Tracking\Environment;
use Contenir\Extension\Tracking\Script\TrackingScripts;
use Contenir\Extension\Tracking\Tests\Trait\TempDirectoryTrait;
use Contenir\Extension\Tracking\TrackingExtension;
use DateTimeImmutable;
use Nyholm\Psr7\Factory\Psr17Factory;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_map;

/**
 * The add-on on the API's in-memory host: settings saved as the settings
 * screen saves them, its checks and fixes in the update check, and its
 * scripts for public pages.
 */
#[Group('integration')]
final class InMemoryHostTest extends TestCase
{
    use TempDirectoryTrait;

    #[Test]
    public function offersTheSavedTagsAsScripts(): void
    {
        $host = $this->host('production');
        $host->context('tracking')
            ->settings()
            ->set([
                'google_analytics'  => 'G-ABC12345',
                'facebook_pixel'    => '123456789012345',
                'track_environment' => true,
            ]);

        static::assertSame(
            [
                ConsentCategory::Necessary,
                ConsentCategory::Analytics,
                ConsentCategory::Analytics,
                ConsentCategory::Marketing,
            ],
            array_map(
                static fn(Script $script): ConsentCategory => $script->consent,
                (new TrackingScripts())->scripts($host->context('tracking')),
            ),
        );
    }

    #[Test]
    public function passesTheConsentCheckOnceTheSiteEnablesTheConsentAddOn(): void
    {
        $this->makeTempDirectory();
        $this->writeConfig('config/autoload/extensions.global.php', ['extensions' => ['enabled' => ['tracking']]]);
        $this->writeConfig('config/autoload/extensions.local.php', [
            'extensions' => ['enabled' => ['tracking', 'consent']],
        ]);

        $host = $this->host('production', $this->directory);
        $host->context('tracking')
            ->settings()
            ->set([
                'google_analytics'  => 'G-ABC12345',
                'wait_for_consent'  => false,
                'track_environment' => true,
            ]);

        static::assertEquals(
            CheckResult::success('The Consent add-on holds the tags back until visitors consent.'),
            $host->check('tracking', 'consent'),
        );
    }

    #[Test]
    public function stopsTrackingAStagingCopyThroughItsFix(): void
    {
        $host = $this->host('staging');
        $host->context('tracking')->settings()->set(['google_analytics' => 'G-ABC12345', 'track_environment' => true]);

        static::assertSame(
            [Status::Warning, Status::Success, false],
            [
                $host->check('tracking', 'environment')->status,
                $host->fix('tracking', 'environment', [], ['extension.tracking.fix'])?->status,
                $host->context('tracking')->settings()->get('track_environment'),
            ],
        );
    }

    #[Test]
    public function waitsForConsentThroughItsFix(): void
    {
        $host = $this->host('production');
        $host->context('tracking')
            ->settings()
            ->set([
                'google_analytics'  => 'G-ABC12345',
                'wait_for_consent'  => false,
                'track_environment' => true,
            ]);

        static::assertSame(
            [Status::Warning, Status::Success],
            [
                $host->check('tracking', 'consent')->status,
                $host->fix('tracking', 'consent', [], ['extension.tracking.fix'])?->status,
            ],
        );
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->removeTempDirectory();
    }

    private function host(string $environment, string $sitePath = '/site'): InMemoryHost
    {
        $http = new Psr17Factory();
        $host = new InMemoryHost(
            $http,
            $http,
            new FrozenClock(new DateTimeImmutable('2026-10-20T09:00:00+00:00')),
            sitePath: $sitePath,
        );
        $host->boot(
            ['tracking' => new TrackingExtension()],
            ['tracking'],
            [
                EnvironmentCheck::class => new EnvironmentCheck(new Environment($environment)),
            ],
        );

        return $host;
    }
}

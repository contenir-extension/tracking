<?php

declare(strict_types=1);

namespace Contenir\Extension\Tracking\Tests\Unit;

use Contenir\Extension\Api\Diagnostic\DiagnosticDeclaration;
use Contenir\Extension\Api\Field\FieldTypeDeclaration;
use Contenir\Extension\Api\Registration;
use Contenir\Extension\Api\Registry;
use Contenir\Extension\Api\Script\ScriptDeclaration;
use Contenir\Extension\Tracking\Check\ConsentCheck;
use Contenir\Extension\Tracking\Check\EnvironmentCheck;
use Contenir\Extension\Tracking\Field\GoogleTagField;
use Contenir\Extension\Tracking\Field\LinkedInInsightField;
use Contenir\Extension\Tracking\Field\MetaPixelField;
use Contenir\Extension\Tracking\Field\RegionsField;
use Contenir\Extension\Tracking\Field\TagManagerField;
use Contenir\Extension\Tracking\Script\TrackingScripts;
use Contenir\Extension\Tracking\TrackingExtension;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function array_map;
use function array_values;

#[Group('unit')]
final class TrackingExtensionTest extends TestCase
{
    #[Test]
    public function declaresAFieldTypeForEachKindOfValue(): void
    {
        static::assertSame(
            [
                'tracking:google-tag'       => GoogleTagField::class,
                'tracking:tag-manager'      => TagManagerField::class,
                'tracking:meta-pixel'       => MetaPixelField::class,
                'tracking:linkedin-insight' => LinkedInInsightField::class,
                'tracking:regions'          => RegionsField::class,
            ],
            array_map(
                static fn(FieldTypeDeclaration $type): string => $type->class,
                $this->registration()->fieldTypes,
            ),
        );
    }

    #[Test]
    public function declaresItsChecks(): void
    {
        static::assertSame(
            ['environment' => EnvironmentCheck::class, 'consent' => ConsentCheck::class],
            array_map(
                static fn(DiagnosticDeclaration $check): string => $check->class,
                $this->registration()->diagnostics,
            ),
        );
    }

    #[Test]
    public function declaresOneSettingsSection(): void
    {
        static::assertSame(['tracking'], array_keys($this->registration()->settingsSections));
    }

    #[Test]
    public function declaresTheTagsAsScripts(): void
    {
        static::assertSame(
            [['tags', TrackingScripts::class]],
            array_values(array_map(
                static fn(ScriptDeclaration $script): array => [$script->name, $script->class],
                $this->registration()->scripts,
            )),
        );
    }

    #[Test]
    public function needsNoListenersHandlersTablesOrOtherAddOns(): void
    {
        $registration = $this->registration();

        static::assertSame(
            [[], [], [], [], [], [], [], null],
            [
                $registration->listeners,
                $registration->handlers,
                $registration->webhooks,
                $registration->migrations,
                $registration->schedules,
                $registration->panels,
                $registration->dependencies,
                $registration->icons,
            ],
        );
    }

    private function registration(): Registration
    {
        $registry = new Registry('tracking');
        (new TrackingExtension())->register($registry);

        return $registry->registration();
    }
}

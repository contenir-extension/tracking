<?php

declare(strict_types=1);

namespace Contenir\Extension\Tracking\Tests\Unit\Field;

use Contenir\Extension\Api\Form\FieldType;
use Contenir\Extension\Tracking\Field\RegionsField;
use Contenir\Extension\Tracking\Tests\TestAsset\Settings\ArraySettings;
use Contenir\Extension\Tracking\Tests\TestAsset\Settings\SettingsContext;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class RegionsFieldTest extends TestCase
{
    /**
     * @return array<string, array{mixed, string}>
     */
    public static function normalised(): array
    {
        return [
            'as entered'      => ["es fr\nus-ca, es", 'ES, FR, US-CA'],
            'blank'           => ['  ', ''],
            'not text'        => [['ES'], ''],
            'with a bad code' => ['es, Spain', 'es, Spain'],
        ];
    }

    #[Test]
    public function checksEachCodeAsItIsSaved(): void
    {
        static::assertSame(
            ['"SPAIN" is not a region code. Use a country code such as ES or a subdivision such as US-CA.'],
            (new RegionsField())->validate('ES, Spain'),
        );
    }

    #[Test]
    public function drawsAPlainTextControlWithNoChoices(): void
    {
        $field = new RegionsField();

        static::assertSame(
            [null, FieldType::Text, []],
            [$field->component(), $field->control(), $field->options(new SettingsContext(new ArraySettings()))],
        );
    }

    #[Test]
    #[DataProvider('normalised')]
    public function storesTheCodesInUpperCaseSeparatedByCommas(mixed $value, string $expected): void
    {
        static::assertSame($expected, (new RegionsField())->normalise($value));
    }

    #[Test]
    public function takesNothingAsEverywhere(): void
    {
        static::assertSame([], (new RegionsField())->validate(null));
    }
}

<?php

declare(strict_types=1);

namespace Contenir\Extension\Tracking\Tests\Unit\Field;

use Contenir\Extension\Api\Field\FieldTypeInterface;
use Contenir\Extension\Api\Form\FieldType;
use Contenir\Extension\Tracking\Field\GoogleTagField;
use Contenir\Extension\Tracking\Field\LinkedInInsightField;
use Contenir\Extension\Tracking\Field\MetaPixelField;
use Contenir\Extension\Tracking\Field\TagManagerField;
use Contenir\Extension\Tracking\Tests\TestAsset\Settings\ArraySettings;
use Contenir\Extension\Tracking\Tests\TestAsset\Settings\SettingsContext;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class TagIdFieldTest extends TestCase
{
    /**
     * @return array<string, array{FieldTypeInterface}>
     */
    public static function fields(): array
    {
        return [
            'Google tag'  => [new GoogleTagField()],
            'Tag Manager' => [new TagManagerField()],
            'Meta pixel'  => [new MetaPixelField()],
            'LinkedIn'    => [new LinkedInInsightField()],
        ];
    }

    /**
     * @return array<string, array{FieldTypeInterface, mixed, string}>
     */
    public static function normalised(): array
    {
        return [
            'Google tag, lower case'  => [new GoogleTagField(), ' g-abc12345 ', 'G-ABC12345'],
            'Tag Manager, lower case' => [new TagManagerField(), 'gtm-abcd123', 'GTM-ABCD123'],
            'Meta pixel, padded'      => [new MetaPixelField(), ' 123456789012345 ', '123456789012345'],
            'LinkedIn, as a number'   => [new LinkedInInsightField(), 1_234_567, '1234567'],
            'not text'                => [new GoogleTagField(), ['G-ABC12345'], ''],
            'nothing'                 => [new MetaPixelField(), null, ''],
        ];
    }

    /**
     * @return array<string, array{FieldTypeInterface, mixed, list<string>}>
     */
    public static function validated(): array
    {
        return [
            'a Google tag'                => [new GoogleTagField(), 'g-abc12345', []],
            'a blank Google tag'          => [new GoogleTagField(), '', []],
            'a Universal Analytics ID'    => [
                new GoogleTagField(),
                'UA-142376215-1',
                [
                    'UA- IDs belong to Universal Analytics, which stopped collecting data in July 2023. '
                        . 'Enter the G- measurement ID from Google Analytics 4.',
                ],
            ],
            'a container'                 => [new TagManagerField(), 'GTM-ABCD123', []],
            'a Google tag as a container' => [
                new TagManagerField(),
                'G-ABC12345',
                ['Enter a Tag Manager container ID such as GTM-XXXXXXX.'],
            ],
            'a pixel'                     => [new MetaPixelField(), '123456789012345', []],
            'letters as a pixel'          => [
                new MetaPixelField(),
                'pixel',
                ['Enter the Meta pixel ID: the number from Events Manager, such as 123456789012345.'],
            ],
            'a partner ID as a number'    => [new LinkedInInsightField(), 1_234_567, []],
            'letters as a partner ID'     => [
                new LinkedInInsightField(),
                'abc',
                ['Enter the LinkedIn Insight partner ID: the number from Campaign Manager, such as 1234567.'],
            ],
        ];
    }

    /**
     * @param list<string> $problems
     */
    #[Test]
    #[DataProvider('validated')]
    public function checksTheIdAsItIsSaved(FieldTypeInterface $field, mixed $value, array $problems): void
    {
        static::assertSame($problems, $field->validate($value));
    }

    #[Test]
    #[DataProvider('fields')]
    public function drawsAPlainTextControlWithNoChoices(FieldTypeInterface $field): void
    {
        static::assertSame(
            [null, FieldType::Text, []],
            [$field->component(), $field->control(), $field->options(new SettingsContext(new ArraySettings()))],
        );
    }

    #[Test]
    #[DataProvider('normalised')]
    public function storesTheIdTheWayTheProviderWritesIt(
        FieldTypeInterface $field,
        mixed $value,
        string $expected,
    ): void {
        static::assertSame($expected, $field->normalise($value));
    }
}

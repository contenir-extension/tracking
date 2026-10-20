<?php

declare(strict_types=1);

namespace Contenir\Extension\Tracking\Tests\Unit;

use Contenir\Extension\Api\Form\Field;
use Contenir\Extension\Api\Handle;
use Contenir\Extension\Api\Settings\SettingsCategory;
use Contenir\Extension\Tracking\Tests\TestAsset\Settings\ArraySettings;
use Contenir\Extension\Tracking\TrackingSection;
use Contenir\Tracking\Provider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_filter;
use function array_keys;
use function array_map;

#[Group('unit')]
final class TrackingSectionTest extends TestCase
{
    #[Test]
    public function checksEachIdWithTheAddOnsFieldTypes(): void
    {
        static::assertSame(
            [
                'google_analytics'   => 'tracking:google-tag',
                'google_tag_manager' => 'tracking:tag-manager',
                'facebook_pixel'     => 'tracking:meta-pixel',
                'linkedin_insight'   => 'tracking:linkedin-insight',
                'wait_for_consent'   => 'checkbox',
                'consent_regions'    => 'tracking:regions',
                'ad_signals'         => 'checkbox',
                'url_passthrough'    => 'checkbox',
                'track_environment'  => 'checkbox',
            ],
            $this->byName(static fn(Field $field): string => $field->type),
        );
    }

    #[Test]
    public function describesEachFieldForEditors(): void
    {
        static::assertSame(
            [
                'google_analytics'   => [
                    'Google tag ID',
                    'From Google Analytics or Google Ads: G-XXXXXXXXXX, GT-XXXXXXX or AW-XXXXXXXXX. Leave blank for no Google tag.',
                ],
                'google_tag_manager' => [
                    'Tag Manager container',
                    'GTM-XXXXXXX, from Google Tag Manager. Leave blank for no container.',
                ],
                'facebook_pixel'     => [
                    'Meta pixel ID',
                    'The number from Meta Events Manager. Leave blank for no pixel.',
                ],
                'linkedin_insight'   => [
                    'LinkedIn Insight partner ID',
                    'The number from LinkedIn Campaign Manager. Leave blank for no Insight tag.',
                ],
                'wait_for_consent'   => [
                    'Wait for consent',
                    "Hold the tags back until a visitor accepts them in the site's cookie banner. Without a banner, "
                        . 'nothing is counted while this is on.',
                ],
                'consent_regions'    => [
                    'Regions that wait',
                    "Where Google's tags wait for consent, as country codes (ES, GB) or subdivisions (US-CA), "
                        . 'separated by commas. Leave blank to wait everywhere. The Meta pixel and LinkedIn wait everywhere.',
                ],
                'ad_signals'         => [
                    'Send ad signals',
                    'Let Google use visits for ad measurement and personalisation when a visitor allows ads.',
                ],
                'url_passthrough'    => [
                    'Pass ad click IDs through links',
                    "Keep Google's ad click IDs in links while consent is refused, so conversions are still counted "
                        . 'without cookies.',
                ],
                'track_environment'  => [
                    'Track on this environment',
                    'Write the tags on this copy of the site. Saved for this environment only, so a staging or local '
                        . 'copy never reports to the live accounts.',
                ],
            ],
            $this->byName(static fn(Field $field): array => [$field->label, $field->description]),
        );
    }

    #[Test]
    public function groupsTheTagsConsentAndEnvironment(): void
    {
        $groups = TrackingSection::define(new Handle('tracking'))->form->groups;

        static::assertSame(
            [
                [
                    'tags',
                    'Tags',
                    [
                        ['google_analytics'],
                        ['google_tag_manager'],
                        ['facebook_pixel'],
                        ['linkedin_insight'],
                    ],
                ],
                ['consent', 'Consent', [['wait_for_consent'], ['consent_regions'], ['ad_signals', 'url_passthrough']]],
                ['environment', 'This environment', [['track_environment']]],
            ],
            array_map(static fn($group): array => [$group->id, $group->legend, $group->rows], $groups),
        );
    }

    #[Test]
    public function keepsTheEnvironmentSwitchInLocalSettingsOnly(): void
    {
        static::assertSame(
            ['track_environment'],
            array_keys(array_filter($this->byName(static fn(Field $field): bool => $field->local))),
        );
    }

    #[Test]
    public function readsTheSitesTrackingFromTheSavedValues(): void
    {
        $config = TrackingSection::config(new ArraySettings([
            'google_analytics'  => 'G-ABC12345',
            'facebook_pixel'    => '123456789012345',
            'consent_regions'   => 'ES, FR',
            'url_passthrough'   => '1',
            'track_environment' => '1',
        ]));

        static::assertSame(
            ['G-ABC12345', null, '123456789012345', true, ['ES', 'FR'], true, true],
            [
                $config->tag(Provider::GoogleTag)?->value,
                $config->tag(Provider::TagManager),
                $config->tag(Provider::MetaPixel)?->value,
                $config->consent->waitForConsent,
                $config->consent->regions->codes,
                $config->consent->urlPassthrough,
                $config->track,
            ],
        );
    }

    #[Test]
    public function sitsWhereTheCmssTrackingPluginWas(): void
    {
        $section = TrackingSection::define(new Handle('tracking'));

        static::assertSame(
            ['tracking', 'Tracking Codes', SettingsCategory::Seo],
            [$section->name, $section->title, $section->category],
        );
    }

    #[Test]
    public function storesEachValueUnderTheKeySitesRead(): void
    {
        static::assertSame(
            [
                'google_analytics'   => 'google.analytics.key',
                'google_tag_manager' => 'google.tag_manager.id',
                'facebook_pixel'     => 'facebook.analytics.key',
                'linkedin_insight'   => 'linkedin.insight.partner_id',
                'wait_for_consent'   => 'extensions.tracking.wait_for_consent',
                'consent_regions'    => 'extensions.tracking.consent_regions',
                'ad_signals'         => 'extensions.tracking.ad_signals',
                'url_passthrough'    => 'extensions.tracking.url_passthrough',
                'track_environment'  => 'extensions.tracking.track_environment',
            ],
            $this->byName(static fn(Field $field): ?string => $field->setting),
        );
    }

    #[Test]
    public function waitsForConsentWithAdSignalsAndDoesNotTrackByDefault(): void
    {
        static::assertSame(
            [
                'wait_for_consent'  => true,
                'ad_signals'        => true,
                'url_passthrough'   => false,
                'track_environment' => false,
            ],
            array_filter(
                $this->byName(static fn(Field $field): mixed => $field->default),
                static fn(mixed $value): bool => null !== $value,
            ),
        );
    }

    /**
     * @template T
     *
     * @param callable(Field): T $read
     *
     * @return array<string, T>
     */
    private function byName(callable $read): array
    {
        $values = [];
        foreach (TrackingSection::define(new Handle('tracking'))->form->fields as $field) {
            $values[$field->name] = $read($field);
        }

        return $values;
    }
}

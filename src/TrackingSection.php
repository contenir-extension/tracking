<?php

declare(strict_types=1);

namespace Contenir\Extension\Tracking;

use Contenir\Extension\Api\Exception\UnknownSettingException;
use Contenir\Extension\Api\Form\Field;
use Contenir\Extension\Api\Form\FieldType;
use Contenir\Extension\Api\Form\Form;
use Contenir\Extension\Api\Form\Group;
use Contenir\Extension\Api\Handle;
use Contenir\Extension\Api\Settings\SettingsCategory;
use Contenir\Extension\Api\Settings\SettingsInterface;
use Contenir\Extension\Api\Settings\SettingsSection;
use Contenir\Extension\Tracking\Field\GoogleTagField;
use Contenir\Extension\Tracking\Field\LinkedInInsightField;
use Contenir\Extension\Tracking\Field\MetaPixelField;
use Contenir\Extension\Tracking\Field\RegionsField;
use Contenir\Extension\Tracking\Field\TagManagerField;
use Contenir\Tracking\SettingKeys;
use Contenir\Tracking\TrackingConfig;

use function array_key_exists;
use function array_replace_recursive;
use function explode;

/**
 * The add-on's settings section, Tracking Codes under SEO, where the CMS's
 * Tracking plugin was. Each field stores its value under the key
 * contenir/contenir-tracking reads, so sites read the same settings the CMS
 * saves.
 *
 * @api
 */
final class TrackingSection
{
    public const string NAME = 'tracking';

    public const string GOOGLE_TAG = 'google_analytics';

    public const string TAG_MANAGER = 'google_tag_manager';

    public const string META_PIXEL = 'facebook_pixel';

    public const string LINKEDIN_INSIGHT = 'linkedin_insight';

    public const string WAIT_FOR_CONSENT = 'wait_for_consent';

    public const string CONSENT_REGIONS = 'consent_regions';

    public const string AD_SIGNALS = 'ad_signals';

    public const string URL_PASSTHROUGH = 'url_passthrough';

    public const string TRACK_ENVIRONMENT = 'track_environment';

    /**
     * The settings key each field stores its value under.
     */
    public const array KEYS = [
        self::GOOGLE_TAG        => SettingKeys::GOOGLE_TAG,
        self::TAG_MANAGER       => SettingKeys::TAG_MANAGER,
        self::META_PIXEL        => SettingKeys::META_PIXEL,
        self::LINKEDIN_INSIGHT  => SettingKeys::LINKEDIN_INSIGHT,
        self::WAIT_FOR_CONSENT  => SettingKeys::WAIT_FOR_CONSENT,
        self::CONSENT_REGIONS   => SettingKeys::CONSENT_REGIONS,
        self::AD_SIGNALS        => SettingKeys::AD_SIGNALS,
        self::URL_PASSTHROUGH   => SettingKeys::URL_PASSTHROUGH,
        self::TRACK_ENVIRONMENT => SettingKeys::TRACK_ENVIRONMENT,
    ];

    /**
     * The site's tracking, read from the section's saved values.
     *
     * @throws UnknownSettingException Never: every key belongs to a declared field.
     */
    public static function config(SettingsInterface $settings): TrackingConfig
    {
        $nested = [];
        foreach (self::KEYS as $field => $key) {
            $nested = array_replace_recursive($nested, self::nest($key, $settings->get((string) $field)));
        }

        return TrackingConfig::fromSettings($nested);
    }

    /**
     * The section, with field types named for the add-on's handle.
     */
    public static function define(Handle $handle): SettingsSection
    {
        return new SettingsSection(
            name: self::NAME,
            title: 'Tracking Codes',
            category: SettingsCategory::Seo,
            form: new Form(
                [
                    new Field(
                        name: self::GOOGLE_TAG,
                        label: 'Google tag ID',
                        type: $handle->fieldType(GoogleTagField::NAME),
                        description: 'From Google Analytics or Google Ads: G-XXXXXXXXXX, GT-XXXXXXX or AW-XXXXXXXXX.'
                            . ' Leave blank for no Google tag.',
                        setting: SettingKeys::GOOGLE_TAG,
                    ),
                    new Field(
                        name: self::TAG_MANAGER,
                        label: 'Tag Manager container',
                        type: $handle->fieldType(TagManagerField::NAME),
                        description: 'GTM-XXXXXXX, from Google Tag Manager. Leave blank for no container.',
                        setting: SettingKeys::TAG_MANAGER,
                    ),
                    new Field(
                        name: self::META_PIXEL,
                        label: 'Meta pixel ID',
                        type: $handle->fieldType(MetaPixelField::NAME),
                        description: 'The number from Meta Events Manager. Leave blank for no pixel.',
                        setting: SettingKeys::META_PIXEL,
                    ),
                    new Field(
                        name: self::LINKEDIN_INSIGHT,
                        label: 'LinkedIn Insight partner ID',
                        type: $handle->fieldType(LinkedInInsightField::NAME),
                        description: 'The number from LinkedIn Campaign Manager. Leave blank for no Insight tag.',
                        setting: SettingKeys::LINKEDIN_INSIGHT,
                    ),
                    new Field(
                        name: self::WAIT_FOR_CONSENT,
                        label: 'Wait for consent',
                        type: FieldType::Checkbox,
                        description: 'Hold the tags back until a visitor accepts them in the site\'s cookie banner.'
                            . ' Without a banner, nothing is counted while this is on.',
                        setting: SettingKeys::WAIT_FOR_CONSENT,
                        default: true,
                    ),
                    new Field(
                        name: self::CONSENT_REGIONS,
                        label: 'Regions that wait',
                        type: $handle->fieldType(RegionsField::NAME),
                        description: 'Where Google\'s tags wait for consent, as country codes (ES, GB) or'
                            . ' subdivisions (US-CA), separated by commas. Leave blank to wait everywhere.'
                            . ' The Meta pixel and LinkedIn wait everywhere.',
                        setting: SettingKeys::CONSENT_REGIONS,
                    ),
                    new Field(
                        name: self::AD_SIGNALS,
                        label: 'Send ad signals',
                        type: FieldType::Checkbox,
                        description: 'Let Google use visits for ad measurement and personalisation when a visitor'
                            . ' allows ads.',
                        setting: SettingKeys::AD_SIGNALS,
                        default: true,
                    ),
                    new Field(
                        name: self::URL_PASSTHROUGH,
                        label: 'Pass ad click IDs through links',
                        type: FieldType::Checkbox,
                        description: 'Keep Google\'s ad click IDs in links while consent is refused, so'
                            . ' conversions are still counted without cookies.',
                        setting: SettingKeys::URL_PASSTHROUGH,
                        default: false,
                    ),
                    new Field(
                        name: self::TRACK_ENVIRONMENT,
                        label: 'Track on this environment',
                        type: FieldType::Checkbox,
                        description: 'Write the tags on this copy of the site. Saved for this environment only,'
                            . ' so a staging or local copy never reports to the live accounts.',
                        setting: SettingKeys::TRACK_ENVIRONMENT,
                        default: false,
                        local: true,
                    ),
                ],
                [
                    new Group('tags', 'Tags', [
                        self::GOOGLE_TAG,
                        self::TAG_MANAGER,
                        self::META_PIXEL,
                        self::LINKEDIN_INSIGHT,
                    ]),
                    new Group('consent', 'Consent', [
                        self::WAIT_FOR_CONSENT,
                        self::CONSENT_REGIONS,
                        [self::AD_SIGNALS, self::URL_PASSTHROUGH],
                    ]),
                    new Group('environment', 'This environment', [self::TRACK_ENVIRONMENT]),
                ],
            ),
        );
    }

    /**
     * $value under a dotted key, as nested arrays.
     *
     * @return array<string, mixed>
     */
    private static function nest(string $key, mixed $value): array
    {
        $segments = explode('.', $key, limit: 2);

        return [$segments[0] => array_key_exists(1, $segments) ? self::nest($segments[1], $value) : $value];
    }
}

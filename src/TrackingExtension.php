<?php

declare(strict_types=1);

namespace Contenir\Extension\Tracking;

use Contenir\Extension\Api\ExtensionInterface;
use Contenir\Extension\Api\Registry;
use Contenir\Extension\Tracking\Check\ConsentCheck;
use Contenir\Extension\Tracking\Check\EnvironmentCheck;
use Contenir\Extension\Tracking\Field\GoogleTagField;
use Contenir\Extension\Tracking\Field\LinkedInInsightField;
use Contenir\Extension\Tracking\Field\MetaPixelField;
use Contenir\Extension\Tracking\Field\RegionsField;
use Contenir\Extension\Tracking\Field\TagManagerField;
use Contenir\Extension\Tracking\Script\TrackingScripts;
use Override;

/**
 * The Tracking add-on: a settings section for the tag IDs and how they treat
 * consent, field types that check each ID as it is saved, the tags as
 * scripts for public pages, and two checks.
 *
 * @api
 */
final class TrackingExtension implements ExtensionInterface
{
    #[Override]
    public function register(Registry $registry): void
    {
        $registry->fieldType(GoogleTagField::NAME, GoogleTagField::class);
        $registry->fieldType(TagManagerField::NAME, TagManagerField::class);
        $registry->fieldType(MetaPixelField::NAME, MetaPixelField::class);
        $registry->fieldType(LinkedInInsightField::NAME, LinkedInInsightField::class);
        $registry->fieldType(RegionsField::NAME, RegionsField::class);

        $registry->settingsSection(TrackingSection::define($registry->handle()));

        $registry->script('tags', TrackingScripts::class);
        $registry->diagnostic('environment', EnvironmentCheck::class);
        $registry->diagnostic('consent', ConsentCheck::class);
    }
}

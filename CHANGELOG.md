# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project
adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased] - 2026-10-21

### Added

- The "Tracking and consent" check passes once the site enables the Consent add-on, whose banner holds the tags back
  (TRK-1). The enabled add-ons are read from the site's `config/autoload/extensions.*.php` (`EnabledAddOns`).

### Changed

- Requires contenir/tracking `^0.1` from Packagist, where it required `0.1.x-dev` through a `vcs` repository entry.
  A project at stable stability can now install the add-on.
- Allows contenir/extension-api 0.3, 0.4 and 0.5, whose changes (module templates, menus, handler privileges,
  tabs on described screens and template fragments) are additions the add-on doesn't use, so the CMS can install it
  beside a module.
- Allows contenir/extension-api 0.6, whose changes (services add-ons provide, and their context in settings
  operations) are additions the add-on doesn't use, so the CMS can install it beside the Cloudflare add-on.

## [0.1.0] - 2026-10-20

### Added

- The Tracking settings section (Site Settings > SEO > Tracking Codes): the Google tag ID, Tag Manager container,
  Meta pixel ID and LinkedIn Insight partner ID under the keys the CMS's Tracking plugin and today's sites use;
  wait for consent, the regions that wait, ad signals and URL passthrough; and "Track on this environment", kept in
  local settings and off by default.
- Field types that check each ID and the region list as they are saved (`tracking:google-tag`,
  `tracking:tag-manager`, `tracking:meta-pixel`, `tracking:linkedin-insight`, `tracking:regions`).
- The tags as scripts for public pages (`Script\TrackingScripts`), each with its consent category.
- Two checks: "Tracking outside production", with fixes to stop tracking a copy or to track production again, and
  "Tracking and consent", which warns while the tags run before visitors consent.
- The reference documentation for building, testing and releasing an add-on, in the README.

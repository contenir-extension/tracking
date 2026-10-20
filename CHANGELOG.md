# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project
adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased] - 2026-10-20

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

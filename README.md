# contenir-extension/tracking

[![Continuous Integration](https://github.com/contenir-extension/tracking/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/contenir-extension/tracking/actions/workflows/continuous-integration.yml)

The Tracking add-on for Contenir CMS: the Google tag, Google Tag Manager, the Meta pixel and LinkedIn Insight, with
Google Consent Mode v2 and a switch for each environment. It replaces the CMS's built-in Tracking plugin, with the
same settings keys, so moving a site to it changes nothing for the site.

It is also **the reference add-on**: the first in the contenir-extension organisation, and the one every later add-on
copies. The second half of this README, [Building an add-on](#building-an-add-on), describes how an add-on is laid
out, built, tested and released, using this one as the example.

## What it adds

**A settings section**, Site Settings > SEO > Tracking Codes, where the CMS's Tracking plugin was:

| Field | Stored under | Default |
|---|---|---|
| Google tag ID (G-, GT- or AW-) | `google.analytics.key` | blank |
| Tag Manager container (GTM-) | `google.tag_manager.id` | blank |
| Meta pixel ID | `facebook.analytics.key` | blank |
| LinkedIn Insight partner ID | `linkedin.insight.partner_id` | blank |
| Wait for consent | `extensions.tracking.wait_for_consent` | on |
| Regions that wait (ES, US-CA…) | `extensions.tracking.consent_regions` | blank: everywhere |
| Send ad signals | `extensions.tracking.ad_signals` | on |
| Pass ad click IDs through links | `extensions.tracking.url_passthrough` | off |
| Track on this environment | `extensions.tracking.track_environment`, in local settings | off |

The tag IDs keep the keys the CMS's plugin and today's sites already use. Each ID, and the region list, is checked as
it is saved, by the add-on's own field types (`tracking:google-tag`, `tracking:tag-manager`, `tracking:meta-pixel`,
`tracking:linkedin-insight`, `tracking:regions`). A UA- ID is refused with a message explaining that Universal
Analytics has stopped.

"Track on this environment" is kept in the environment's local settings. A copy of the site made from production's
files or database therefore never reports to the live accounts.

**Scripts for public pages** (P14). The tags go through the API's script contract, each with its placement and
consent category, so a site renders and gates them along with every other add-on's scripts:

| Script | Consent category |
|---|---|
| Consent Mode defaults and the `contenir:consent` bridge | necessary |
| Google tag (gtag.js and its config) | analytics (marketing for an AW- ID) |
| Tag Manager container | analytics |
| Meta pixel | marketing |
| LinkedIn Insight | marketing |

Sites print them with a site adapter. On Laminas MVC that is
[contenir/tracking-laminas-mvc](https://github.com/contenir/contenir-tracking-laminas-mvc)'s `tracking()->head()`
and `tracking()->bodyStart()`. [contenir/tracking](https://github.com/contenir/contenir-tracking#consent) describes
the tags, Consent Mode and the `contenir:consent` event that a consent banner fires.

**Two checks** in the update check:

| Check | Warns when | Fix |
|---|---|---|
| Tracking outside production | tracking is on in an environment other than production | guided: stop tracking here |
| | production has tags but doesn't track | one click: track on production |
| Tracking and consent | the tags run before visitors consent (TRK-1) | guided: wait for consent, once a banner is on the site |

The environment is the CMS's `APPLICATION_ENV`. A CMS that names none is taken to be production.

It needs no listeners, handlers, tables or other add-ons. It stores no personal data, only IDs. The tags themselves
send visitor data to Google, Meta and LinkedIn, which is why they wait for consent by default.

## Requirements

- PHP 8.3, 8.4 or 8.5
- A CMS that hosts contenir/extension-api 0.2

## Installing

```bash
composer require contenir-extension/tracking
```

Then enable it in the site's `config/autoload/extensions.global.php`:

```php
return ['extensions' => ['enabled' => ['tracking']]];
```

---

## Building an add-on

Every add-on has the same shape, which the plan's council review settled:

```
contenir/<capability>                   a framework-free library: the actual work
contenir-extension/<handle>             the add-on: a thin CMS adapter over the library
contenir/<capability>-laminas-mvc       site adapters, only when a public site needs them
contenir/<capability>-mezzio
```

For Tracking, that is:

- [contenir/tracking](https://github.com/contenir/contenir-tracking): tags, consent, validation and rendering;
- this add-on: settings, field types, scripts and checks;
- [contenir/tracking-laminas-mvc](https://github.com/contenir/contenir-tracking-laminas-mvc): the view helper.

Keep anything a public site also needs in the library, so the add-on and the site adapters share it rather than
repeat it. One add-on per vendor: a service with a connection gets one add-on, whatever it touches.

### Layout

```
composer.json                  type contenir-extension, extra.contenir.handle and .extension
mago.toml                      extends the shared QA config and mago-boundary.toml
mago-boundary.toml             written by vendor/bin/extension-boundary; never edited by hand
phpunit.xml.dist               unit and integration suites
infection.json5.dist
.github/workflows/continuous-integration.yml
CHANGELOG.md  LICENSE.md  README.md
src/
  TrackingExtension.php        the entry class: register() declares everything
  TrackingSection.php          the settings section, its field names and keys
  Environment.php
  Field/                       field types (validation on save)
  Script/TrackingScripts.php   the script provider
  Check/                       update-check diagnostics
  Fix/                         their fixes
tests/
  Unit/                        mirrors src/; no I/O, fakes for the host
  Integration/                 the API's add-on conformance case and the in-memory host
  TestAsset/                   fakes shared by tests
```

### composer.json

```json
{
    "name": "contenir-extension/tracking",
    "type": "contenir-extension",
    "require": {
        "contenir/extension-api": "^0.2",
        "contenir/tracking": "^0.1"
    },
    "autoload": { "psr-4": { "Contenir\\Extension\\Tracking\\": "src/" } },
    "extra": {
        "contenir": {
            "handle": "tracking",
            "extension": "Contenir\\Extension\\Tracking\\TrackingExtension"
        }
    }
}
```

- The package is named `contenir-extension/<handle>`, its namespace is `Contenir\Extension\<Package>\`, and its
  entry class is `<Package>Extension` (decision D4).
- The handle names the add-on's settings section, its settings keys (`extensions.<handle>.*`), field types
  (`<handle>:<name>`), permissions (`extension.<handle>.*`) and tables (`ext_<handle>_*`). It never changes.
- Require the API with a caret constraint, plus the add-on's own library. Never require a CMS, ZF1, Laminas MVC or
  Mezzio: the boundary refuses them. An add-on may require another add-on, but only to use its `Event\` and `Api\`
  namespaces.

### The entry class

The host constructs the entry class and calls `register()` once. Each `Registry` method declares one thing, and
nothing runs at registration:

```php
public function register(Registry $registry): void
{
    $registry->fieldType(GoogleTagField::NAME, GoogleTagField::class);
    $registry->settingsSection(TrackingSection::define($registry->handle()));
    $registry->script('tags', TrackingScripts::class);
    $registry->diagnostic('environment', EnvironmentCheck::class);
}
```

The host makes each class it is given: field types, script providers, checks, fixes, listeners and handlers. It
takes the class from its services when it has one by that name, or constructs it with no arguments. So give
collaborators a default in the constructor (`EnvironmentCheck` takes an optional `Environment`), and tests can hand
the host a configured instance.

### Settings

- Declare the section as data (`Settings\SettingsSection`, `Form\Form`, `Form\Field`, `Form\Group`). The CMS draws,
  saves and loads it; an add-on has no controller or template.
- **A built-in feature that moves out keeps its keys.** Give each field `setting:` with the key the CMS used before,
  so the move is invisible. A new value can leave `setting` out, and is then stored under
  `extensions.<handle>.<field>`. This add-on names its new keys explicitly, because its library and the site adapters
  read the same keys.
- Validate with a field type: `validate()` returns the messages the form shows, and `normalise()` the value to store.
  A field type works wherever the field is drawn, which a save listener wouldn't.
- `local: true` keeps a value in the site's `.local.php`: per environment, and never committed. `Field::password()`
  is local and masked.
- Read settings through `$context->settings()`, which applies the declared defaults. Write them with `set()`, which
  saves as the settings screen does and fires the settings events.

### Public pages

An add-on with scripts for public pages declares a `Script\ScriptProviderInterface`, which returns `Script` values,
each with a `Placement` and a `ConsentCategory`. The add-on doesn't print anything itself: site adapters render every
add-on's scripts in one place, with the CSP nonce, and gate them behind consent. Add-ons talk to each other in the
browser through events on `window`, such as `contenir:consent`, so no add-on needs another's code.

### Checks and fixes

A `Diagnostic\CheckInterface` returns `CheckResult::success()`, `warning()`, `failure()` or `skipped()`. A warning or
failure can offer a `OneClickFix` or a `GuidedFix` (an explanation, text to copy, fields to fill). Fixes run only for
someone holding `extension.<handle>.fix`, and the check runs again afterwards. Return `skipped()` when the check
doesn't apply (no tags set, for example), so the update check stays quiet.

### The boundary

```bash
vendor/bin/extension-boundary
```

This writes `mago-boundary.toml` from composer.json. The add-on may use PHP itself, the API, the PSR interfaces, its
own code and the libraries it requires. `mago guard` enforces this in CI, and the integration test fails if the file
is out of date. Run it again whenever `require` changes.

### Testing

- **Unit tests** go in `tests/Unit/`, with `#[Group('unit')]`. They test one class at a time, with no I/O. The
  add-on's code sees the host only through `ExtensionContextInterface` and `SettingsInterface`, so small fakes stand
  in for it. For example, `tests/TestAsset/Settings/ArraySettings` holds the section's values in an array, with its
  declared defaults.
- **Integration tests** go in `tests/Integration/`, with `#[Group('integration')]`:
  - extend `Conformance\AbstractAddOnConformanceTestCase` once. It checks that the add-on registers cleanly, that
    the boundary file is current, and that every webhook refuses a request it can't verify;
  - drive the add-on on `Testing\InMemoryHost`, the API's in-memory host. Save settings as the settings screen
    would, run checks and fixes with the grants a person would hold, and bind listeners to sections.
- Aim for 100% line coverage and mutation score. Settings text counts: assert the labels and descriptions editors
  see, or Infection will rightly point out that no test does.

```bash
composer cs-check          # mago format --check, lint, guard
composer static-analysis   # mago analyze
composer test              # unit suite
composer test-integration
composer mutation-test     # infection
```

CI pins Mago 1.52.0, and newer releases format some code differently, so format with 1.52.0 before pushing.

### CI

`.github/workflows/continuous-integration.yml` calls the shared workflow, `contenir/contenir-qa-tools` at `0.1.x`.
It runs:

- Mago: format, lint, analysis and guard;
- PHPUnit on PHP 8.3, 8.4 and 8.5, with the lowest, locked and latest dependencies;
- `composer audit`;
- Infection on the lines a pull request changes, at 80%.

Its `qa / No AI attributions` job is required on `main` by the organisation's rules.

The contenir-extension organisation only allows these actions: those owned by GitHub, `contenir/*`,
`codecov/codecov-action`, `shivammathur/setup-php`, `webfactory/ssh-agent` and `anthropics/claude-code-action`. A
workflow that needs any other action needs the organisation's settings changed first.

### Releasing

1. Every pull request adds to `CHANGELOG.md` under a dated heading (Keep a Changelog), and keeps the README current.
2. `main` is protected: changes arrive by pull request, reviewed by the team, with CI green.
3. A maintainer tags each release: `0.1.0` first, then semantic versions. Only a reviewed tag is installable, and
   sites pin it in `composer.lock`.
4. While its library is unreleased, an add-on may require it from its repository, with a `vcs` entry and a branch
   constraint. Switch to the tagged caret constraint before the add-on's own first tag.

### A new add-on, step by step

1. Create `contenir-extension/<handle>` and, unless the work is CMS-specific, `contenir/<capability>`.
2. Copy this repository's tooling: `composer.json` (renamed), `mago.toml`, `phpunit.xml.dist`,
   `infection.json5.dist`, `.github/workflows/`, `.gitignore`, `.gitattributes` and `LICENSE.md`.
3. Write the entry class and its declarations, and keep the work itself in the library.
4. Run `vendor/bin/extension-boundary`, then the checks above.
5. Write both halves of the README. The first says what the add-on adds: its settings and their keys, scripts, checks
   and any personal data. The second records anything a later add-on should do differently because of this one.

## Licence

BSD-3-Clause. See [LICENSE.md](LICENSE.md).

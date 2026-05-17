# Changelog

All notable changes to this project will be documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.8.0] - 2026-05-17

### Added
- **Geolocation** setting on the global config form — dropdown (Widget Default / On / Off) to enable or disable location-based search (the Near Me button and typed-location search). Complements the existing per-block and per-shortcode `geolocation` overrides, which still take precedence. `widget_config` JSON `geolocation` key still wins over both.

## [0.7.0] - 2026-05-12

### Added
- **Raw BMLT query** as a per-block override and matching `query` shortcode attribute (no global setting). Passes through to the Crumb widget's `data-query`, which routes through `bmlt-query-client`'s `rawQuery()` for filters the structured options can't express (e.g. multi-value `meeting_key_value[]`). When set, it replaces the default load entirely — Service Body and Format IDs are ignored — and forces geolocation off so geo params can't be layered on top. In shortcodes, encode brackets as `%5B` / `%5D` because the filter parser stops at a literal `]`. Requires Crumb Widget 1.5.0+.

## [0.6.0] - 2026-05-11

### Added
- **Language** setting on the global config form and as a per-block override, plus a matching `language` shortcode attribute. Forces the widget UI language; leave empty to auto-detect from the visitor's browser. Supported codes: `en`, `es`, `fr`, `de`, `pt`, `it`, `sv`, `da`, `el`, `fa`, `pl`, `ru`, `ja`. Per-block / per-shortcode value overrides the saved setting; `widget_config` JSON `language` key still wins over both. Unsupported codes are silently dropped.

## [0.5.0] - 2026-05-11

### Added
- **Columns** setting on the global config form and as a per-block override, plus a matching `columns` shortcode attribute. Comma-separated list of columns to show in list view (e.g. `time,name,location,address,service_body`). Omit a name to hide that column. Leave empty to use the widget default.

## [0.4.0] - 2026-05-10

### Added
- **Update Meeting URL** setting on the global config form and as a per-block override, plus a matching `update_url` shortcode attribute. Powers the "Update Meeting Info" link on the meeting detail panel. Supports tokens `{meeting_id}`, `{meeting_name}`, `{server_url}`, and `{return_url}` (URL-encoded on substitution). Works with [bmlt-workflow](https://github.com/bmlt-enabled/bmlt-workflow), arbitrary hosted forms, or `mailto:` URLs. Leave empty to hide the link.

## [0.3.0] - 2026-05-06

### Added
- **Geolocation Radius** setting — dedicated field for geolocation search radius, separate from the JSON config textarea. Positive integer = fixed radius in miles (or km per server settings). Negative integer = BMLT auto-radius (e.g. `-50` finds ~50 nearby meetings).
- `geolocation_radius` block and shortcode attribute to override the radius per-block or per-page (`[crumb geolocation_radius="-50"]`).
- `geolocation_radius` override respected in `hook_crumb_config_alter()` callers via the standard override array.

## [0.2.0] - 2026-05-05

### Added
- **Format IDs** setting and `format_ids` shortcode/block attribute to lock the widget to specific BMLT format IDs (single ID or comma-separated list). Supported globally via the settings form, per-block via the block config form, and per-shortcode via `[crumb format_ids="17,54"]`.

## [0.1.0] - 2026-04-26

Initial release. Wraps the [Crumb meeting finder widget](https://github.com/bmlt-enabled/crumb-widget) for Drupal 10.3+ / 11.

### Added
- **Settings form** at `/admin/config/services/crumb` (BMLT server URL, service body IDs, default view, CSS template, base path, and JSON widget config).
- **Block plugin** "Crumb meeting finder" (category: BMLT) for region placement, with per-block overrides for server, service body, view, and geolocation.
- **Text-format filter** "Crumb meeting finder shortcode" — replaces `[crumb]` and `[crumb attr="value"]` with the widget. Supported attributes: `server`, `service_body`, `view`, `geolocation`.
- **`crumb.renderer` service** for programmatic embedding from custom code.
- **`hook_crumb_config_alter()`** for runtime customization of `CrumbWidgetConfig`.
- **CSS templates** — Full Width and Full Width (Force Viewport).
- **Pretty URL support** via the Base Path setting (e.g. `/meetings/monday-night-meeting-42` instead of hash routing).
- **Docker compose dev environment** — Drupal 11 + MariaDB with auto-install on first boot, Xdebug pre-configured.
- **Make targets**: `dev`, `down`, `nuke`, `shell`, `drush`, `logs`, `lint`, `fmt`, `test`, `build`.
- **GitHub workflows**: PR lint + tests, main-branch lint + tests, tag-driven release with zip artifact and auto-generated release notes.
- **PHPUnit unit tests** for the renderer (server overrides, view validation, base path, full-width template, widget-config emission, geolocation merge).
- `README.md`, `CONTRIBUTING.md`, and this `CHANGELOG.md`.
- Crumb logo (`crumb-logo.svg`) and project icon (`icon-256x256.png`).
- `LICENSE.txt` (GPL-2.0-or-later) — required for drupal.org publication.

[Unreleased]: https://github.com/bmlt-enabled/crumb-drupal/compare/v0.4.0...HEAD
[0.4.0]: https://github.com/bmlt-enabled/crumb-drupal/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/bmlt-enabled/crumb-drupal/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/bmlt-enabled/crumb-drupal/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/bmlt-enabled/crumb-drupal/releases/tag/v0.1.0

# Changelog

All notable changes to this project will be documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/bmlt-enabled/crumb-drupal/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/bmlt-enabled/crumb-drupal/releases/tag/v0.1.0

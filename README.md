<p align="center">
  <img src="crumb-logo.svg" alt="Crumb Widget logo" width="128" height="128">
</p>

# Crumb for Drupal

Drupal module that embeds the [Crumb Widget](https://github.com/bmlt-enabled/crumb-widget) meeting finder via a block, a `[crumb]` shortcode in filtered text, or programmatically via a render service.

## Requirements

- Drupal 10.3 or 11
- PHP 8.3+

## Installation

1. Place this directory at `web/modules/custom/crumb` (or install via Composer once published).
2. Enable the module: `drush en crumb` (or via **Extend**).
3. Visit **Configuration → Web services → Crumb** and enter your BMLT Server URL.

## Usage

### Block

Place the **Crumb meeting finder** block (category: BMLT) in any region. Per-block settings allow overriding server, service body, view, and geolocation.

### Shortcode in body fields

Add the **Crumb meeting finder shortcode** filter to a text format, then use:

```
[crumb]
[crumb server="https://your-server/main_server" service_body="42" view="map" geolocation="true"]
[crumb update_url="https://example.org/meeting-update-form/?meeting_id={meeting_id}"]
```

### Programmatic

```php
$build = \Drupal::service('crumb.renderer')->build([
  'server'       => 'https://your-server/main_server',
  'service_body' => '42',
  'view'         => 'map',
]);
```

### Altering widget config

Implement `hook_crumb_config_alter()` in any custom module:

```php
function MYMODULE_crumb_config_alter(array &$config): void {
  $config['language']          = 'es';
  $config['geolocation']       = TRUE;
  $config['geolocationRadius'] = 20;
  $config['height']            = 800;
}
```

## Settings

| Setting              | Description                                            |
|----------------------|--------------------------------------------------------|
| Server URL           | Required. Full URL to your BMLT Server.                |
| Service Body IDs     | Optional. Single ID or comma-separated.                |
| Default View         | Optional. `list` or `map`.                             |
| CSS Template         | Optional. Full Width or Full Width (Force Viewport).   |
| Base Path            | Optional. Page path for pretty URLs.                   |
| Update Meeting URL   | Optional. URL template for the "Update Meeting Info" link. Tokens: `{meeting_id}`, `{meeting_name}`, `{server_url}`, `{return_url}`. Works with bmlt-workflow, hosted forms, or `mailto:` URLs. |
| Widget Configuration | Optional. JSON for `CrumbWidgetConfig`.                |

## Local development

```bash
make dev      # Bring up Drupal 11 + MariaDB on http://localhost:8080
make drush ARGS="status"
make shell
make nuke     # Wipe DB volume for a clean reinstall
```

The compose file mounts this repo at `/opt/drupal/web/modules/custom/crumb` and auto-installs Drupal on first boot. Default admin: `admin / admin`.

## Commands

```bash
make lint     # Run PHP_CodeSniffer (Drupal standards)
make fmt      # Auto-fix style with phpcbf
make test     # Run PHPUnit unit tests
make build    # Create a distributable zip
```

Full widget docs at **[crumb.bmlt.app](https://crumb.bmlt.app/)**.

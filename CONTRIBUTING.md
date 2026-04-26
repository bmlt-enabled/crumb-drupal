# Contributing to Crumb for Drupal

## How to Contribute

1. Fork the repository
2. Create a branch off `main`
3. Make your changes — make sure `make lint` and `make test` pass
4. Send a pull request to the `main` branch

Take a look at the [issues](https://github.com/bmlt-enabled/crumb-drupal/issues) for bugs you might be able to help fix.

Once your pull request is merged, it will be released in the next version.

## Local Development Setup

The dev stack runs in Docker — you don't need PHP, Drupal, or MariaDB on your host.

```bash
make dev          # Bring up Drupal 11 + MariaDB on http://localhost:8080
```

On first boot the container auto-installs Drupal (Standard profile) and enables the `crumb` module. Default admin credentials are `admin` / `admin`.

```bash
make drush ARGS="status"   # Run drush in the running container
make shell                 # Open a bash shell in the drupal container
make logs                  # Tail container logs
make down                  # Stop the stack (preserves DB volume)
make nuke                  # Stop and wipe DB volume for a clean reinstall
```

The repo is mounted at `/opt/drupal/web/modules/custom/crumb`, so PHP edits take effect immediately. After editing routing, services, libraries, plugin annotations, or config schema, clear the cache:

```bash
make drush ARGS="cr"
```

### Configuring the widget

After login, visit **Configuration → Web services → Crumb** to set the BMLT Server URL and other defaults.

### Testing the embed

Two ways to embed the widget on a page:

- **Block** — *Structure → Block layout → Place block → Crumb meeting finder*. Place it in the **Content** region of the active theme.
- **Shortcode** — *Configuration → Content authoring → Text formats and editors*, edit a format (e.g. *Basic HTML*), enable the **Crumb meeting finder shortcode** filter, save. Then create a Basic page and put `[crumb]` (or `[crumb view="map"]`) in the body.

## Code Standards

Please follow the `.editorconfig` settings (2-space indent for PHP and YAML, LF line endings). Most editors honor it automatically; PHPStorm requires the EditorConfig plugin.

### PHP Code Style

The project uses PHP_CodeSniffer with the **Drupal** and **DrupalPractice** rulesets, configured in `phpcs.xml`.

```bash
make lint    # Check for violations
make fmt     # Auto-fix what phpcbf can fix
```

Run both before submitting a PR. CI will reject style violations.

## Testing

Unit tests use [PHPUnit](https://phpunit.de/) and run on the host (no Drupal install required) — they mock the `ConfigFactoryInterface` and `ModuleHandlerInterface` via stubs declared in `tests/bootstrap.php`.

```bash
make test
```

### Writing Tests

Test files live under `tests/src/Unit/`. They extend `PHPUnit\Framework\TestCase` and use plain mocks rather than the Drupal test base classes — this keeps the test job fast and free of Drupal core dependencies.

```php
namespace Drupal\Tests\crumb\Unit;

use Drupal\crumb\CrumbRenderer;
use PHPUnit\Framework\TestCase;

class MyTest extends TestCase {
    public function testSomething(): void {
        // …
    }
}
```

Kernel and Functional tests (which require a full Drupal install) are not currently in this repo's test job; if you add them, run them inside the dev container against a real Drupal install.

## Continuous Integration

Three workflows run in `.github/workflows/`:

- **pull-requests.yml** — runs lint and tests on every PR against `main`
- **latest.yml** — runs lint and tests on every push to `main`
- **release.yml** — on tag push, runs lint and tests, builds a zip, and creates a GitHub Release

Both lint and tests must pass before a release is created.

## Release Tagging

Releases are cut by pushing a git tag. Semantic versioning is encouraged:

```bash
git tag v1.0.0
git push origin v1.0.0
```

- A tag containing `beta`, `alpha`, or `rc` is published as a **prerelease**.
- Any other tag is published as a regular release.
- Release notes are auto-generated from commits and PRs since the previous tag (`generateReleaseNotes: true`).

The release artifact is `crumb.zip` (a `git archive` of the tagged commit, excluding development files via `.gitattributes` `export-ignore` rules).

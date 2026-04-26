<?php

/**
 * @file
 * PHPUnit bootstrap — autoloads composer deps and the module's src/ classes.
 *
 * Drupal core is intentionally NOT a composer dependency of this module repo
 * (it's only required by sites that consume the module). To let unit tests
 * type-hint against `ConfigFactoryInterface` / `ModuleHandlerInterface`
 * without dragging in all of Drupal core, we declare minimal stub interfaces
 * here. Production code uses the real Drupal interfaces; both share the same
 * fully-qualified names so the type system is happy in both contexts.
 *
 * For Kernel/Functional tests, run inside a full Drupal install via core's
 * test runner (not this bootstrap).
 */

declare(strict_types=1);

namespace {
  require_once __DIR__ . '/../vendor/autoload.php';

  spl_autoload_register(function (string $class): void {
    $prefix = 'Drupal\\crumb\\';
    if (!str_starts_with($class, $prefix)) {
      return;
    }
    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/../src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
      require_once $path;
    }
  });
}

namespace Drupal\Core\Config {
  if (!\interface_exists(ConfigFactoryInterface::class, FALSE)) {
    interface ConfigFactoryInterface {
      public function get(string $name);
    }
  }
}

namespace Drupal\Core\Extension {
  if (!\interface_exists(ModuleHandlerInterface::class, FALSE)) {
    interface ModuleHandlerInterface {
      public function alter($hook, &$data, &$context1 = NULL, &$context2 = NULL): void;
    }
  }
}

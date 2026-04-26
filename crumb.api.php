<?php

/**
 * @file
 * Hooks provided by the Crumb module.
 */

declare(strict_types=1);

/**
 * Alter the CrumbWidgetConfig array before it is emitted to the page.
 *
 * @param array $config
 *   The widget configuration array. See https://crumb.bmlt.app/ for available
 *   keys (language, geolocation, geolocationRadius, height, darkMode,
 *   nowOffset, hideHeader, columns, map, …).
 */
function hook_crumb_config_alter(array &$config): void {
  $config['language'] = 'es';
  $config['geolocation'] = TRUE;
  $config['geolocationRadius'] = 20;
}

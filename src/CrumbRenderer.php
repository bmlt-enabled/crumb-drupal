<?php

declare(strict_types=1);

namespace Drupal\crumb;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Builds the render array for the Crumb widget container.
 */
class CrumbRenderer {

  public const ALLOWED_VIEWS = ['list', 'map'];

  /** Languages the widget supports (mirrors src/stores/localization.ts). */
  public const SUPPORTED_LANGUAGES = ['en', 'es', 'fr', 'de', 'pt', 'it', 'sv', 'da', 'el', 'fa', 'pl', 'ru', 'ja'];

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * Build a render array for the widget.
   *
   * @param array $overrides
   *   Per-instance overrides. Keys: server, service_body, view, geolocation.
   *   Use NULL to fall back to saved config; '' (empty string) explicitly omits
   *   service_body so all meetings show.
   */
  public function build(array $overrides = []): array {
    $config = $this->configFactory->get('crumb.settings');

    $server = trim((string) ($overrides['server'] ?? $config->get('server') ?? ''));
    if ($server === '') {
      return [
        '#markup' => '<p style="color:red"><strong>Crumb:</strong> a <code>server</code> URL is required.</p>',
      ];
    }

    $service_body = $overrides['service_body'] ?? $config->get('service_body');
    $format_ids   = $overrides['format_ids'] ?? $config->get('format_ids');
    $view_raw     = $overrides['view'] ?? $config->get('view') ?? '';
    $view         = in_array($view_raw, self::ALLOWED_VIEWS, TRUE) ? $view_raw : '';
    $base_path    = trim((string) ($config->get('base_path') ?? ''), '/');
    $template     = (string) ($config->get('css_template') ?? '');
    $update_url   = trim((string) ($overrides['update_url'] ?? $config->get('update_url') ?? ''));
    $columns      = trim((string) ($overrides['columns'] ?? $config->get('columns') ?? ''));

    $attributes = [
      'id' => 'crumb-widget',
      'data-server' => $server,
    ];
    if ($service_body !== NULL && $service_body !== '') {
      $attributes['data-service-body'] = trim((string) $service_body);
    }
    if ($format_ids !== NULL && $format_ids !== '') {
      $attributes['data-format-ids'] = trim((string) $format_ids);
    }
    if ($view !== '') {
      $attributes['data-view'] = $view;
    }
    if ($base_path !== '') {
      $attributes['data-path'] = '/' . $base_path;
    }
    if ($update_url !== '') {
      $attributes['data-update-url'] = $update_url;
    }
    if ($columns !== '') {
      $attributes['data-columns'] = $columns;
    }

    $widget = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => $attributes,
    ];

    // #attached lives at the TOP level so it bubbles correctly through both
    // BlockViewBuilder (which only steals #attributes) and FilterProcessResult
    // (which reads #attached / #cache via BubbleableMetadata::createFromRenderArray).
    $attached = [
      'library' => ['crumb/widget'],
    ];

    $config_array = $this->buildWidgetConfig();

    if (isset($overrides['geolocation']) && $overrides['geolocation'] !== NULL) {
      $config_array['geolocation'] = filter_var(
        $overrides['geolocation'],
        FILTER_VALIDATE_BOOLEAN
      );
    }

    // Merge geolocation_radius admin setting if not already set in widget_config JSON.
    $radius_setting = (string) ($config->get('geolocation_radius') ?? '');
    if ($radius_setting !== '' && !isset($config_array['geolocationRadius'])) {
      $radius_int = (int) $radius_setting;
      if ($radius_int !== 0) {
        $config_array['geolocationRadius'] = $radius_int;
      }
    }

    // Apply per-block / per-shortcode geolocation_radius override.
    if (isset($overrides['geolocation_radius']) && $overrides['geolocation_radius'] !== NULL && $overrides['geolocation_radius'] !== '') {
      $radius = (int) $overrides['geolocation_radius'];
      if ($radius !== 0) {
        $config_array['geolocationRadius'] = $radius;
      }
    }

    // Language: per-instance override wins; otherwise the saved setting fills in
    // when the JSON widget_config did not already supply one. Anything that is
    // not a supported code is silently dropped (widget falls back to navigator.language).
    $override_lang = isset($overrides['language']) ? strtolower(trim((string) $overrides['language'])) : '';
    if ($override_lang !== '' && in_array($override_lang, self::SUPPORTED_LANGUAGES, TRUE)) {
      $config_array['language'] = $override_lang;
    }
    elseif (!isset($config_array['language'])) {
      $saved_lang = strtolower(trim((string) ($config->get('language') ?? '')));
      if ($saved_lang !== '' && in_array($saved_lang, self::SUPPORTED_LANGUAGES, TRUE)) {
        $config_array['language'] = $saved_lang;
      }
    }

    $this->moduleHandler->alter('crumb_config', $config_array);

    if (!empty($config_array)) {
      $attached['html_head'][] = [
        [
          '#tag' => 'script',
          '#value' => 'window.CrumbWidgetConfig = ' . json_encode(
            $config_array,
            JSON_UNESCAPED_SLASHES
          ) . ';',
        ],
        'crumb_widget_config',
      ];
    }

    if ($template === 'full_width') {
      $attached['library'][] = 'crumb/full_width';
      return [
        'wrapper' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => ['class' => ['crumb-full-width']],
          'widget' => $widget,
        ],
        '#attached' => $attached,
      ];
    }
    if ($template === 'full_width_force') {
      $attached['library'][] = 'crumb/full_width_force';
      return [
        'wrapper' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => ['class' => ['crumb-full-width-force']],
          'widget' => $widget,
        ],
        '#attached' => $attached,
      ];
    }

    // Wrap in a child key so block plugins (or any container that hoists a
    // top-level #attributes onto its own wrapper) cannot strip the widget
    // element's id and data-* attributes.
    return [
      'widget' => $widget,
      '#attached' => $attached,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function buildWidgetConfig(): array {
    $json = (string) ($this->configFactory->get('crumb.settings')->get('widget_config') ?? '');
    if ($json === '') {
      return [];
    }
    $decoded = json_decode($json, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

}

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
    $view_raw     = $overrides['view'] ?? $config->get('view') ?? '';
    $view         = in_array($view_raw, self::ALLOWED_VIEWS, TRUE) ? $view_raw : '';
    $base_path    = trim((string) ($config->get('base_path') ?? ''), '/');
    $template     = (string) ($config->get('css_template') ?? '');

    $attributes = [
      'id' => 'crumb-widget',
      'data-server' => $server,
    ];
    if ($service_body !== NULL && $service_body !== '') {
      $attributes['data-service-body'] = trim((string) $service_body);
    }
    if ($view !== '') {
      $attributes['data-view'] = $view;
    }
    if ($base_path !== '') {
      $attributes['data-path'] = '/' . $base_path;
    }

    $widget = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => $attributes,
      '#attached' => [
        'library' => ['crumb/widget'],
        'drupalSettings' => [],
      ],
    ];

    $config_array = $this->buildWidgetConfig();

    if (isset($overrides['geolocation']) && $overrides['geolocation'] !== NULL) {
      $config_array['geolocation'] = filter_var(
        $overrides['geolocation'],
        FILTER_VALIDATE_BOOLEAN
      );
    }

    $this->moduleHandler->alter('crumb_config', $config_array);

    if (!empty($config_array)) {
      $widget['#attached']['html_head'][] = [
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
      $widget['#attached']['library'][] = 'crumb/full_width';
      return [
        'wrapper' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => ['class' => ['crumb-full-width']],
          'widget' => $widget,
        ],
      ];
    }
    if ($template === 'full_width_force') {
      $widget['#attached']['library'][] = 'crumb/full_width_force';
      return [
        'wrapper' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => ['class' => ['crumb-full-width-force']],
          'widget' => $widget,
        ],
      ];
    }

    // Wrap in a child key so block plugins (or any container that hoists a
    // top-level #attributes onto its own wrapper) cannot strip the widget
    // element's id and data-* attributes.
    return [
      'widget' => $widget,
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

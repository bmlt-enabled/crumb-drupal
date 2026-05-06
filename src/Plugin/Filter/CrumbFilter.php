<?php

declare(strict_types=1);

namespace Drupal\crumb\Plugin\Filter;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\crumb\CrumbRenderer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Replaces a [crumb …] token in text with the widget markup.
 *
 * @Filter(
 *   id = "crumb",
 *   title = @Translation("Crumb meeting finder shortcode"),
 *   description = @Translation("Replaces [crumb] / [crumb attr=&quot;value&quot;] with the meeting finder widget."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE
 * )
 */
class CrumbFilter extends FilterBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected CrumbRenderer $renderer,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('crumb.renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode): FilterProcessResult {
    $result = new FilterProcessResult($text);

    if (stripos($text, '[crumb') === FALSE) {
      return $result;
    }

    $pattern = '/\[crumb(\s+[^\]]*)?\]/i';
    $text = preg_replace_callback($pattern, function (array $match) use ($result): string {
      $overrides = $this->parseAttributes($match[1] ?? '');
      $build = $this->renderer->build($overrides);
      $renderer = \Drupal::service('renderer');
      $html = (string) $renderer->renderInIsolation($build);
      $bubbleable = BubbleableMetadata::createFromRenderArray($build);
      $result->addAttachments($build['#attached'] ?? []);
      $result->addCacheableDependency($bubbleable);
      return $html;
    }, $text);

    $result->setProcessedText($text);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('Use <code>[crumb]</code> to embed the Crumb meeting finder. Optional attributes: <code>server</code>, <code>service_body</code>, <code>format_ids</code>, <code>view</code>, <code>geolocation</code>, <code>geolocation_radius</code>. Example: <code>[crumb server="https://your-server/main_server" service_body="42" format_ids="17,54" view="map" geolocation="true" geolocation_radius="-50"]</code>');
    }
    return $this->t('Use [crumb] to embed the meeting finder.');
  }

  /**
   * Parse `key="value" key=value` attribute strings into an array.
   */
  protected function parseAttributes(string $attrs): array {
    $out = [];
    if (trim($attrs) === '') {
      return $out;
    }
    preg_match_all('/(\w+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|(\S+))/', $attrs, $matches, PREG_SET_ORDER);
    foreach ($matches as $m) {
      $key = strtolower($m[1]);
      $value = $m[2] !== '' ? $m[2] : ($m[3] !== '' ? $m[3] : ($m[4] ?? ''));
      $out[$key] = $value;
    }
    return $out;
  }

}

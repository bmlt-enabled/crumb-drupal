<?php

declare(strict_types=1);

namespace Drupal\Tests\crumb\Unit;

use Drupal\crumb\CrumbRenderer;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\crumb\CrumbRenderer
 */
class CrumbRendererTest extends TestCase {

  /**
   * Build a renderer wired to a fake config and module-handler stub.
   */
  private function makeRenderer(array $settings = []): CrumbRenderer {
    $defaults = [
      'server' => 'https://example.com/main_server/',
      'service_body' => '42',
      'view' => '',
      'css_template' => '',
      'base_path' => '',
      'widget_config' => '',
    ];
    $merged = $settings + $defaults;

    $config = new class($merged) {
      public function __construct(private array $values) {}
      public function get(string $key) {
        return $this->values[$key] ?? NULL;
      }
    };
    $configFactory = new class($config) implements ConfigFactoryInterface {
      public function __construct(private object $config) {}
      public function get(string $name) {
        return $this->config;
      }
    };
    $moduleHandler = new class implements ModuleHandlerInterface {
      public function alter($hook, &$data, &$context1 = NULL, &$context2 = NULL): void {}
    };

    return new CrumbRenderer($configFactory, $moduleHandler);
  }

  public function testBuildReturnsWidgetWrappedInChildKey(): void {
    $build = $this->makeRenderer()->build();

    $this->assertArrayHasKey('widget', $build, 'Top level must wrap the widget in a child key so block plugins cannot hoist its #attributes.');
    $this->assertArrayNotHasKey('#attributes', $build, 'Top level must not have #attributes.');

    $widget = $build['widget'];
    $this->assertSame('html_tag', $widget['#type']);
    $this->assertSame('div', $widget['#tag']);
    $this->assertSame('crumb-widget', $widget['#attributes']['id']);
    $this->assertSame('https://example.com/main_server/', $widget['#attributes']['data-server']);
    $this->assertSame('42', $widget['#attributes']['data-service-body']);
    $this->assertContains('crumb/widget', $widget['#attached']['library']);
  }

  public function testServerOverrideTakesPrecedence(): void {
    $build = $this->makeRenderer()->build([
      'server' => 'https://override.test/main_server/',
    ]);
    $this->assertSame(
      'https://override.test/main_server/',
      $build['widget']['#attributes']['data-server'],
    );
  }

  public function testEmptyServiceBodyOverrideOmitsAttribute(): void {
    $build = $this->makeRenderer()->build([
      'service_body' => '',
    ]);
    $this->assertArrayNotHasKey(
      'data-service-body',
      $build['widget']['#attributes'],
      'Empty string sentinel must omit the data-service-body attribute.',
    );
  }

  public function testInvalidViewIsIgnored(): void {
    $build = $this->makeRenderer()->build(['view' => 'gallery']);
    $this->assertArrayNotHasKey('data-view', $build['widget']['#attributes']);
  }

  public function testValidViewIsApplied(): void {
    $build = $this->makeRenderer()->build(['view' => 'map']);
    $this->assertSame('map', $build['widget']['#attributes']['data-view']);
  }

  public function testMissingServerReturnsErrorMarkup(): void {
    $build = $this->makeRenderer(['server' => ''])->build();
    $this->assertArrayHasKey('#markup', $build);
    $this->assertStringContainsString('server', $build['#markup']);
  }

  public function testFullWidthTemplateWraps(): void {
    $build = $this->makeRenderer(['css_template' => 'full_width'])->build();
    $this->assertArrayHasKey('wrapper', $build);
    $this->assertContains('crumb-full-width', $build['wrapper']['#attributes']['class']);
    $this->assertSame('crumb-widget', $build['wrapper']['widget']['#attributes']['id']);
  }

  public function testBasePathAddsDataPath(): void {
    $build = $this->makeRenderer(['base_path' => 'meetings'])->build();
    $this->assertSame('/meetings', $build['widget']['#attributes']['data-path']);
  }

  public function testWidgetConfigIsEmittedAsHtmlHead(): void {
    $build = $this->makeRenderer([
      'widget_config' => json_encode(['language' => 'es', 'height' => 800]),
    ])->build();
    $head = $build['widget']['#attached']['html_head'] ?? [];
    $this->assertNotEmpty($head, 'Widget config must produce an html_head entry.');
    $this->assertStringContainsString('window.CrumbWidgetConfig', $head[0][0]['#value']);
    $this->assertStringContainsString('"language":"es"', $head[0][0]['#value']);
  }

  public function testGeolocationOverrideMergesIntoConfig(): void {
    $build = $this->makeRenderer([
      'widget_config' => json_encode(['height' => 600]),
    ])->build(['geolocation' => 'true']);
    $head = $build['widget']['#attached']['html_head'] ?? [];
    $this->assertNotEmpty($head);
    $this->assertStringContainsString('"geolocation":true', $head[0][0]['#value']);
  }

}

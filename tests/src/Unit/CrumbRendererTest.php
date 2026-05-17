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
      'format_ids' => '',
      'view' => '',
      'css_template' => '',
      'base_path' => '',
      'geolocation' => '',
      'geolocation_radius' => '',
      'update_url' => '',
      'columns' => '',
      'language' => '',
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
    // #attached lives at the top of the build (so it bubbles through both
    // BlockViewBuilder and FilterProcessResult), not inside the widget.
    $this->assertContains('crumb/widget', $build['#attached']['library']);
    $this->assertArrayNotHasKey('#attached', $widget);
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

  public function testFormatIdsOverrideAddsAttribute(): void {
    $build = $this->makeRenderer()->build(['format_ids' => '17,54']);
    $this->assertSame('17,54', $build['widget']['#attributes']['data-format-ids']);
  }

  public function testFormatIdsFromConfigAddsAttribute(): void {
    $build = $this->makeRenderer(['format_ids' => '42'])->build();
    $this->assertSame('42', $build['widget']['#attributes']['data-format-ids']);
  }

  public function testEmptyFormatIdsOmitsAttribute(): void {
    $build = $this->makeRenderer(['format_ids' => ''])->build();
    $this->assertArrayNotHasKey('data-format-ids', $build['widget']['#attributes']);
  }

  public function testEmptyFormatIdsOverrideOmitsAttribute(): void {
    $build = $this->makeRenderer(['format_ids' => '99'])->build(['format_ids' => '']);
    $this->assertArrayNotHasKey('data-format-ids', $build['widget']['#attributes']);
  }

  public function testColumnsOverrideAddsAttribute(): void {
    $build = $this->makeRenderer()->build(['columns' => 'time,name,location,address,service_body']);
    $this->assertSame('time,name,location,address,service_body', $build['widget']['#attributes']['data-columns']);
  }

  public function testColumnsFromConfigAddsAttribute(): void {
    $build = $this->makeRenderer(['columns' => 'time,name'])->build();
    $this->assertSame('time,name', $build['widget']['#attributes']['data-columns']);
  }

  public function testEmptyColumnsOmitsAttribute(): void {
    $build = $this->makeRenderer()->build();
    $this->assertArrayNotHasKey('data-columns', $build['widget']['#attributes']);
  }

  public function testColumnsOverrideBeatsConfig(): void {
    $build = $this->makeRenderer(['columns' => 'time,name'])->build(['columns' => 'name,location']);
    $this->assertSame('name,location', $build['widget']['#attributes']['data-columns']);
  }

  public function testEmptyColumnsOverrideOmitsAttribute(): void {
    $build = $this->makeRenderer(['columns' => 'time,name'])->build(['columns' => '']);
    $this->assertArrayNotHasKey('data-columns', $build['widget']['#attributes']);
  }

  public function testColumnsTrimmed(): void {
    $build = $this->makeRenderer()->build(['columns' => '  time,name  ']);
    $this->assertSame('time,name', $build['widget']['#attributes']['data-columns']);
  }

  public function testQueryOverrideAddsAttribute(): void {
    $build = $this->makeRenderer()->build(['query' => 'meeting_key=location_nation&meeting_key_value[]=USA']);
    $this->assertSame('meeting_key=location_nation&meeting_key_value[]=USA', $build['widget']['#attributes']['data-query']);
  }

  public function testQueryTrimmed(): void {
    $build = $this->makeRenderer()->build(['query' => '  weekdays=2  ']);
    $this->assertSame('weekdays=2', $build['widget']['#attributes']['data-query']);
  }

  public function testEmptyQueryOverrideOmitsAttribute(): void {
    $build = $this->makeRenderer()->build(['query' => '']);
    $this->assertArrayNotHasKey('data-query', $build['widget']['#attributes']);
  }

  public function testNoQueryOverrideOmitsAttribute(): void {
    // query is per-instance only — there is no crumb.settings.query, so a default build has no data-query.
    $build = $this->makeRenderer()->build();
    $this->assertArrayNotHasKey('data-query', $build['widget']['#attributes']);
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

  public function testLanguageSettingMergesIntoConfig(): void {
    $build = $this->makeRenderer(['language' => 'es'])->build();
    $head = $build['#attached']['html_head'] ?? [];
    $this->assertNotEmpty($head);
    $this->assertStringContainsString('"language":"es"', $head[0][0]['#value']);
  }

  public function testLanguageOverrideTakesPrecedenceOverSetting(): void {
    $build = $this->makeRenderer(['language' => 'es'])->build(['language' => 'de']);
    $head = $build['#attached']['html_head'] ?? [];
    $this->assertStringContainsString('"language":"de"', $head[0][0]['#value']);
    $this->assertStringNotContainsString('"language":"es"', $head[0][0]['#value']);
  }

  public function testLanguageOverrideDroppedForUnsupportedCode(): void {
    $build = $this->makeRenderer(['language' => 'es'])->build(['language' => 'banana']);
    $head = $build['#attached']['html_head'] ?? [];
    // Saved 'es' fills in because the unsupported override never wrote a value.
    $this->assertStringContainsString('"language":"es"', $head[0][0]['#value']);
  }

  public function testWidgetConfigLanguageTakesPrecedenceOverSetting(): void {
    $build = $this->makeRenderer([
      'language' => 'es',
      'widget_config' => json_encode(['language' => 'fr']),
    ])->build();
    $head = $build['#attached']['html_head'] ?? [];
    $this->assertStringContainsString('"language":"fr"', $head[0][0]['#value']);
  }

  public function testEmptyLanguageProducesNoConfigScript(): void {
    $build = $this->makeRenderer(['language' => ''])->build();
    $head = $build['#attached']['html_head'] ?? [];
    $this->assertEmpty($head, 'Empty language with no other config must produce no CrumbWidgetConfig script.');
  }

  public function testWidgetConfigIsEmittedAsHtmlHead(): void {
    $build = $this->makeRenderer([
      'widget_config' => json_encode(['language' => 'es', 'height' => 800]),
    ])->build();
    $head = $build['#attached']['html_head'] ?? [];
    $this->assertNotEmpty($head, 'Widget config must produce an html_head entry.');
    $this->assertStringContainsString('window.CrumbWidgetConfig', $head[0][0]['#value']);
    $this->assertStringContainsString('"language":"es"', $head[0][0]['#value']);
  }

  public function testGeolocationOverrideMergesIntoConfig(): void {
    $build = $this->makeRenderer([
      'widget_config' => json_encode(['height' => 600]),
    ])->build(['geolocation' => 'true']);
    $head = $build['#attached']['html_head'] ?? [];
    $this->assertNotEmpty($head);
    $this->assertStringContainsString('"geolocation":true', $head[0][0]['#value']);
  }

  public function testGeolocationSettingOnMergesIntoConfig(): void {
    $build = $this->makeRenderer(['geolocation' => '1'])->build();
    $head = $build['#attached']['html_head'] ?? [];
    $this->assertNotEmpty($head);
    $this->assertStringContainsString('"geolocation":true', $head[0][0]['#value']);
  }

  public function testGeolocationSettingOffMergesIntoConfig(): void {
    $build = $this->makeRenderer(['geolocation' => '0'])->build();
    $head = $build['#attached']['html_head'] ?? [];
    $this->assertStringContainsString('"geolocation":false', $head[0][0]['#value']);
  }

  public function testEmptyGeolocationSettingProducesNoConfigScript(): void {
    $build = $this->makeRenderer(['geolocation' => ''])->build();
    $head = $build['#attached']['html_head'] ?? [];
    $this->assertEmpty($head, 'Empty geolocation setting with no other config must produce no CrumbWidgetConfig script.');
  }

  public function testGeolocationOverrideTakesPrecedenceOverSetting(): void {
    $build = $this->makeRenderer(['geolocation' => '0'])->build(['geolocation' => 'true']);
    $head = $build['#attached']['html_head'] ?? [];
    $this->assertStringContainsString('"geolocation":true', $head[0][0]['#value']);
    $this->assertStringNotContainsString('"geolocation":false', $head[0][0]['#value']);
  }

  public function testWidgetConfigGeolocationTakesPrecedenceOverSetting(): void {
    $build = $this->makeRenderer([
      'geolocation' => '0',
      'widget_config' => json_encode(['geolocation' => TRUE]),
    ])->build();
    $head = $build['#attached']['html_head'] ?? [];
    $this->assertStringContainsString('"geolocation":true', $head[0][0]['#value']);
    $this->assertStringNotContainsString('"geolocation":false', $head[0][0]['#value']);
  }

  public function testGeolocationRadiusSettingMergesAsInteger(): void {
    $build = $this->makeRenderer(['geolocation_radius' => '-50'])->build();
    $head = $build['#attached']['html_head'] ?? [];
    $this->assertNotEmpty($head);
    $this->assertStringContainsString('"geolocationRadius":-50', $head[0][0]['#value']);
  }

  public function testGeolocationRadiusSettingPreservesIntegerType(): void {
    $build = $this->makeRenderer(['geolocation_radius' => '25'])->build();
    $head = $build['#attached']['html_head'] ?? [];
    // Extract the JSON value from the inline script.
    preg_match('/window\.CrumbWidgetConfig\s*=\s*(\{.*\});/', $head[0][0]['#value'], $m);
    $config = json_decode($m[1], TRUE);
    $this->assertSame(25, $config['geolocationRadius'], 'geolocationRadius must be an integer, not a string.');
  }

  public function testGeolocationRadiusOverrideTakesPrecedenceOverSetting(): void {
    $build = $this->makeRenderer(['geolocation_radius' => '-50'])->build(['geolocation_radius' => '30']);
    $head = $build['#attached']['html_head'] ?? [];
    $this->assertStringContainsString('"geolocationRadius":30', $head[0][0]['#value']);
    $this->assertStringNotContainsString('"geolocationRadius":-50', $head[0][0]['#value']);
  }

  public function testWidgetConfigGeolocationRadiusTakesPrecedenceOverSetting(): void {
    $build = $this->makeRenderer([
      'geolocation_radius' => '-50',
      'widget_config' => json_encode(['geolocationRadius' => 10]),
    ])->build();
    $head = $build['#attached']['html_head'] ?? [];
    $this->assertStringContainsString('"geolocationRadius":10', $head[0][0]['#value']);
    $this->assertStringNotContainsString('"geolocationRadius":-50', $head[0][0]['#value']);
  }

  public function testZeroGeolocationRadiusIsIgnored(): void {
    $build = $this->makeRenderer(['geolocation_radius' => '0'])->build();
    $head = $build['#attached']['html_head'] ?? [];
    $this->assertEmpty($head, 'Zero radius must be ignored and produce no CrumbWidgetConfig script.');
  }

  public function testEmptyGeolocationRadiusProducesNoConfigScript(): void {
    $build = $this->makeRenderer(['geolocation_radius' => ''])->build();
    $head = $build['#attached']['html_head'] ?? [];
    $this->assertEmpty($head, 'Empty radius with no other config must produce no CrumbWidgetConfig script.');
  }

  public function testUpdateUrlSettingEmitsDataAttribute(): void {
    $build = $this->makeRenderer([
      'update_url' => 'https://example.org/form/?meeting_id={meeting_id}',
    ])->build();
    $this->assertSame(
      'https://example.org/form/?meeting_id={meeting_id}',
      $build['widget']['#attributes']['data-update-url']
    );
  }

  public function testUpdateUrlMailtoIsEmitted(): void {
    $build = $this->makeRenderer([
      'update_url' => 'mailto:web@example.org?subject=Update%20{meeting_name}',
    ])->build();
    $this->assertSame(
      'mailto:web@example.org?subject=Update%20{meeting_name}',
      $build['widget']['#attributes']['data-update-url']
    );
  }

  public function testEmptyUpdateUrlOmitsDataAttribute(): void {
    $build = $this->makeRenderer()->build();
    $this->assertArrayNotHasKey('data-update-url', $build['widget']['#attributes']);
  }

  public function testUpdateUrlOverrideBeatsSavedSetting(): void {
    $build = $this->makeRenderer([
      'update_url' => 'https://saved/?meeting_id={meeting_id}',
    ])->build(['update_url' => 'https://override/?meeting_id={meeting_id}']);
    $this->assertSame(
      'https://override/?meeting_id={meeting_id}',
      $build['widget']['#attributes']['data-update-url']
    );
  }

  public function testEmptyUpdateUrlOverrideOmitsAttribute(): void {
    $build = $this->makeRenderer([
      'update_url' => 'https://saved/?meeting_id={meeting_id}',
    ])->build(['update_url' => '']);
    $this->assertArrayNotHasKey('data-update-url', $build['widget']['#attributes']);
  }

}

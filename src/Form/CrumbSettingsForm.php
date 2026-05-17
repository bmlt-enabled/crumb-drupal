<?php

declare(strict_types=1);

namespace Drupal\crumb\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\crumb\CrumbRenderer;

/**
 * Settings form for the Crumb widget.
 */
class CrumbSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'crumb_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['crumb.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('crumb.settings');

    $form['server'] = [
      '#type' => 'url',
      '#title' => $this->t('BMLT Server URL'),
      '#description' => $this->t('Required. The full URL to your BMLT Server.'),
      '#default_value' => $config->get('server') ?? '',
      '#placeholder' => 'https://your-server/main_server',
      '#required' => TRUE,
    ];

    $form['service_body'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Service Body IDs'),
      '#description' => $this->t('Optional. Single ID or comma-separated list. Leave empty to show all meetings. Child service bodies are always included.'),
      '#default_value' => $config->get('service_body') ?? '',
      '#placeholder' => '42 or 42,57,103',
    ];

    $form['format_ids'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Format IDs'),
      '#description' => $this->t('Optional. Single ID or comma-separated list of BMLT format IDs to lock the widget to. Leave empty to show all formats. Can be overridden per-block or per-shortcode.'),
      '#default_value' => $config->get('format_ids') ?? '',
      '#placeholder' => '17 or 17,54,78',
    ];

    $form['css_template'] = [
      '#type' => 'select',
      '#title' => $this->t('CSS Template'),
      '#options' => [
        '' => $this->t('— None —'),
        'full_width' => $this->t('Full Width'),
        'full_width_force' => $this->t('Full Width (Force Viewport)'),
      ],
      '#default_value' => $config->get('css_template') ?? '',
      '#description' => $this->t('Full Width fits the content area. Full Width (Force Viewport) breaks out to span the full browser width.'),
    ];

    $form['base_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base Path for Pretty URLs'),
      '#description' => $this->t('Optional. The path where the widget lives (e.g. <code>meetings</code>). Enables clean URLs like <code>/meetings/monday-night-meeting-42</code> instead of hash-based routing. Leave empty to use default hash-based routing.'),
      '#default_value' => $config->get('base_path') ?? '',
      '#placeholder' => 'meetings',
    ];

    $form['view'] = [
      '#type' => 'select',
      '#title' => $this->t('Default View'),
      '#options' => [
        '' => $this->t('— Widget Default (list) —'),
        'list' => $this->t('List'),
        'map' => $this->t('Map'),
      ],
      '#default_value' => $config->get('view') ?? '',
      '#description' => $this->t('Optional. Sets the default view when the widget loads. Can be overridden via the <code>?view=</code> query parameter, or per-block / per-shortcode.'),
    ];

    $form['language'] = [
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#options' => [
        '' => $this->t('— Auto-detect from browser —'),
        'en' => 'English (en)',
        'es' => 'Español (es)',
        'fr' => 'Français (fr)',
        'de' => 'Deutsch (de)',
        'pt' => 'Português (pt)',
        'it' => 'Italiano (it)',
        'sv' => 'Svenska (sv)',
        'da' => 'Dansk (da)',
        'el' => 'Ελληνικά (el)',
        'fa' => 'فارسی (fa)',
        'pl' => 'Polski (pl)',
        'ru' => 'Русский (ru)',
        'ja' => '日本語 (ja)',
      ],
      '#default_value' => $config->get('language') ?? '',
      '#description' => $this->t('Optional. Forces the widget UI language. Default behavior is to detect from the visitor\'s browser (<code>navigator.language</code>). Can be overridden per-block or per-shortcode.'),
    ];

    $form['geolocation'] = [
      '#type' => 'select',
      '#title' => $this->t('Geolocation'),
      '#options' => [
        '' => $this->t('— Widget Default —'),
        '1' => $this->t('On'),
        '0' => $this->t('Off'),
      ],
      '#default_value' => $config->get('geolocation') ?? '',
      '#description' => $this->t('Optional. Enable or disable location-based search (the Near Me button and typed-location search). Widget defaults to off for most servers, and on when the BMLT Server URL points at the unconstrained aggregator with no service body set. Can be overridden per-block or per-shortcode. Ignored if <code>geolocation</code> is already set in Widget Configuration JSON.'),
    ];

    $form['geolocation_radius'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Geolocation Radius'),
      '#description' => $this->t('Optional. Geolocation search radius. Positive integer = fixed radius in miles (or km per server settings). Negative integer = BMLT auto-radius (e.g. <code>-50</code> finds ~50 nearby meetings). Leave empty to use the widget default (<code>-50</code>). Ignored if <code>geolocationRadius</code> is already set in Widget Configuration JSON.'),
      '#default_value' => $config->get('geolocation_radius') ?? '',
      '#placeholder' => '-50',
      '#size' => 10,
    ];

    $form['update_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Update Meeting URL'),
      '#description' => $this->t('Optional. URL template for the "Update Meeting Info" link shown at the bottom of the meeting detail panel. Supported tokens (URL-encoded on substitution): <code>{meeting_id}</code>, <code>{meeting_name}</code>, <code>{server_url}</code>, <code>{return_url}</code>. Works with bmlt-workflow, hosted forms, or <code>mailto:</code> URLs. Leave empty to hide the link.'),
      '#default_value' => $config->get('update_url') ?? '',
      '#placeholder' => 'https://example.org/meeting-update-form/?meeting_id={meeting_id}',
      '#maxlength' => 1024,
    ];

    $form['columns'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Columns'),
      '#description' => $this->t('Optional. Comma-separated list of columns to show in list view (e.g. <code>time,name,location,address,service_body</code>). Omit a name to hide that column. Leave empty to use the widget default. Can be overridden per-block or per-shortcode.'),
      '#default_value' => $config->get('columns') ?? '',
      '#placeholder' => 'time,name,location,address,service_body',
    ];

    $example_config = json_encode([
      'language' => 'en',
      'geolocation' => TRUE,
      'geolocationRadius' => -50,
      'height' => 800,
      'darkMode' => 'auto',
      'nowOffset' => 10,
      'hideHeader' => FALSE,
      'columns' => ['time', 'name', 'location', 'address'],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced Configuration'),
      '#open' => FALSE,
    ];

    $form['advanced']['widget_config'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Widget Configuration (JSON)'),
      '#description' => $this->t('Optional. CrumbWidgetConfig in JSON format. Leave empty to use defaults. See <a href=":url" target="_blank">documentation</a>. Example:<br><pre>@example</pre>', [
        ':url' => 'https://crumb.bmlt.app/',
        '@example' => $example_config,
      ]),
      '#default_value' => $config->get('widget_config') ?? '',
      '#rows' => 12,
      '#attributes' => ['style' => 'font-family: monospace;'],
    ];

    $form['usage'] = [
      '#type' => 'details',
      '#title' => $this->t('Usage'),
      '#open' => FALSE,
    ];
    $form['usage']['notes'] = [
      '#markup' => $this->t('<p>Embed the widget in any of these ways:</p>
        <ul>
          <li><strong>Block:</strong> place the "Crumb meeting finder" block in any region.</li>
          <li><strong>Shortcode in body fields:</strong> add the "Crumb meeting finder shortcode" filter to a text format, then use <code>[crumb]</code> or <code>[crumb server="…" service_body="42" view="map" geolocation="true" geolocation_radius="-50"]</code>.</li>
        </ul>'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $geo_radius = trim((string) $form_state->getValue('geolocation_radius'));
    if ($geo_radius !== '' && (int) $geo_radius === 0) {
      $form_state->setErrorByName('geolocation_radius', $this->t('Geolocation Radius must be a non-zero integer (e.g. 25 or -50).'));
    }

    $widget_config = trim((string) $form_state->getValue('widget_config'));
    if ($widget_config !== '') {
      $decoded = json_decode($widget_config, TRUE);
      if (json_last_error() !== JSON_ERROR_NONE) {
        $form_state->setErrorByName('widget_config', $this->t('Widget Configuration must be valid JSON. JSON error: @err', [
          '@err' => json_last_error_msg(),
        ]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $widget_config = trim((string) $form_state->getValue('widget_config'));
    if ($widget_config !== '') {
      $decoded = json_decode($widget_config, TRUE);
      $widget_config = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    $geo_radius_raw = trim((string) $form_state->getValue('geolocation_radius'));
    $geo_radius = ($geo_radius_raw !== '' && (int) $geo_radius_raw !== 0) ? (string) (int) $geo_radius_raw : '';

    $this->config('crumb.settings')
      ->set('server', trim((string) $form_state->getValue('server')))
      ->set('service_body', trim((string) $form_state->getValue('service_body')))
      ->set('format_ids', trim((string) $form_state->getValue('format_ids')))
      ->set('css_template', (string) $form_state->getValue('css_template'))
      ->set('base_path', trim((string) $form_state->getValue('base_path'), "/ \t\n\r\0\x0B"))
      ->set('view', (string) $form_state->getValue('view'))
      ->set('language', (string) $form_state->getValue('language'))
      ->set('geolocation', (string) $form_state->getValue('geolocation'))
      ->set('geolocation_radius', $geo_radius)
      ->set('update_url', trim((string) $form_state->getValue('update_url')))
      ->set('columns', trim((string) $form_state->getValue('columns')))
      ->set('widget_config', $widget_config)
      ->save();

    parent::submitForm($form, $form_state);
  }

}

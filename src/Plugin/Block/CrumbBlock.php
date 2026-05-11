<?php

declare(strict_types=1);

namespace Drupal\crumb\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\crumb\CrumbRenderer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a "Crumb meeting finder" block.
 *
 * @Block(
 *   id = "crumb_widget",
 *   admin_label = @Translation("Crumb meeting finder"),
 *   category = @Translation("BMLT")
 * )
 */
class CrumbBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
  public function defaultConfiguration(): array {
    return [
      'server' => '',
      'service_body' => NULL,
      'format_ids' => '',
      'view' => '',
      'geolocation' => '',
      'geolocation_radius' => '',
      'update_url' => '',
      'columns' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $config = $this->getConfiguration();

    $form['server'] = [
      '#type' => 'url',
      '#title' => $this->t('BMLT Server URL'),
      '#description' => $this->t('Optional. Overrides the global setting for this block.'),
      '#default_value' => $config['server'] ?? '',
    ];
    $form['service_body'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Service Body IDs'),
      '#description' => $this->t('Optional. Single ID or comma-separated. Leave blank to inherit; enter a single space to explicitly omit and show all meetings.'),
      '#default_value' => $config['service_body'] ?? '',
    ];
    $form['format_ids'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Format IDs'),
      '#description' => $this->t('Optional. Single ID or comma-separated. Leave blank to inherit from global settings.'),
      '#default_value' => $config['format_ids'] ?? '',
      '#placeholder' => '17 or 17,54,78',
    ];
    $form['view'] = [
      '#type' => 'select',
      '#title' => $this->t('Default view'),
      '#options' => [
        '' => $this->t('— Inherit —'),
        'list' => $this->t('List'),
        'map' => $this->t('Map'),
      ],
      '#default_value' => $config['view'] ?? '',
    ];
    $form['geolocation'] = [
      '#type' => 'select',
      '#title' => $this->t('Geolocation'),
      '#options' => [
        '' => $this->t('— Inherit —'),
        'true' => $this->t('Enabled'),
        'false' => $this->t('Disabled'),
      ],
      '#default_value' => $config['geolocation'] ?? '',
    ];
    $form['geolocation_radius'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Geolocation Radius'),
      '#description' => $this->t('Optional. Non-zero integer. Positive = fixed radius in miles; negative integer = BMLT auto-radius (e.g. <code>-50</code> finds ~50 nearby meetings). Leave blank to inherit.'),
      '#default_value' => $config['geolocation_radius'] ?? '',
      '#size' => 10,
    ];
    $form['update_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Update Meeting URL'),
      '#description' => $this->t('Optional. Overrides the global Update Meeting URL template for this block. Leave empty to inherit.'),
      '#default_value' => $config['update_url'] ?? '',
      '#placeholder' => 'https://example.org/meeting-update-form/?meeting_id={meeting_id}',
      '#maxlength' => 1024,
    ];
    $form['columns'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Columns'),
      '#description' => $this->t('Optional. Comma-separated list of columns to show in list view. Leave empty to inherit.'),
      '#default_value' => $config['columns'] ?? '',
      '#placeholder' => 'time,name,location,address,service_body',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $keys = [
      'server',
      'service_body',
      'format_ids',
      'view',
      'geolocation',
      'geolocation_radius',
      'update_url',
      'columns',
    ];
    foreach ($keys as $key) {
      $this->configuration[$key] = $form_state->getValue($key);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $config = $this->getConfiguration();
    $overrides = [];

    if (!empty($config['server'])) {
      $overrides['server'] = $config['server'];
    }
    // Treat a single space as the explicit "show all meetings" sentinel.
    if (isset($config['service_body']) && $config['service_body'] !== '') {
      $overrides['service_body'] = trim($config['service_body']);
    }
    if (isset($config['format_ids']) && $config['format_ids'] !== '') {
      $overrides['format_ids'] = trim($config['format_ids']);
    }
    if (!empty($config['view'])) {
      $overrides['view'] = $config['view'];
    }
    if (isset($config['geolocation']) && $config['geolocation'] !== '') {
      $overrides['geolocation'] = $config['geolocation'];
    }
    if (isset($config['geolocation_radius']) && $config['geolocation_radius'] !== '') {
      $overrides['geolocation_radius'] = $config['geolocation_radius'];
    }
    if (!empty($config['update_url'])) {
      $overrides['update_url'] = $config['update_url'];
    }
    if (isset($config['columns']) && $config['columns'] !== '') {
      $overrides['columns'] = trim($config['columns']);
    }

    return $this->renderer->build($overrides);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}

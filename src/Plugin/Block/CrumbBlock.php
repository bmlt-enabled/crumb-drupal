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
      'view' => '',
      'geolocation' => '',
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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    foreach (['server', 'service_body', 'view', 'geolocation'] as $key) {
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
    if (!empty($config['view'])) {
      $overrides['view'] = $config['view'];
    }
    if (isset($config['geolocation']) && $config['geolocation'] !== '') {
      $overrides['geolocation'] = $config['geolocation'];
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

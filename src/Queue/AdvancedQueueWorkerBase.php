<?php

namespace Drupal\advancedqueue\Queue;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base implementation for a AdvancedQueueWorker plugin.
 *
 * @see \Drupal\Core\Queue\QueueWorkerBase
 * @see \Drupal\Core\Queue\QueueWorkerInterface
 * @see \Drupal\Core\Queue\QueueWorkerManager
 * @see \Drupal\Core\Annotation\QueueWorker
 * @see plugin_api
 */
abstract class AdvancedQueueWorkerBase extends QueueWorkerBase implements AdvancedQueueWorkerInterface, ContainerFactoryPluginInterface {

  /**
   * The queue object.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * Constructs a new LocaleTranslation object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Queue\QueueInterface $queue
   *   The queue object.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, QueueInterface $queue) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->queue = $queue;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('queue')->get($plugin_id, TRUE)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQueue() {
    return $this->queue;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->pluginDefinition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    return $this->pluginDefinition['group'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLeaseTime() {
    return $this->pluginDefinition['lease']['time'];
  }

  /**
   * {@inheritdoc}
   */
  public function getRetryDelay($attempt = NULL) {
    $retry_after_attempts = 0;

    // Retry delays should be separated from each other by a comma.
    foreach (explode(',', $this->pluginDefinition['retry']['delay']) as $retry_after) {
      // Handle retry delays [D] defined using multiplier [M] (ie. "D*M").
      if (strpos($retry_after, '*') !== FALSE) {
        list($retry_after, $multiplier) = explode('*', $retry_after);
        $retry_after_attempts += $multiplier;
      }
      // Handle retry delays defined without multiplier (ie. "D")
      // (M is always 1).
      else {
        $retry_after_attempts++;
      }

      // If the next attempt number falls within current retry delay range.
      if ($attempt <= $retry_after_attempts) {
        return $retry_after;
      }
    }

    // If we were not able to find a specific retry delay for the next attempt
    // number, just use the last retry delay value.
    return $retry_after;
  }

  /**
   * {@inheritdoc}
   */
  public function getRetryAttempts() {
    return $this->pluginDefinition['retry']['attempts'];
  }

  /**
   * {@inheritdoc}
   */
  public function executeHooks($type) {
    return $this->pluginDefinition['execute_hooks'][$type];
  }

  /**
   * {@inheritdoc}
   */
  public function getCronAllow() {
    return $this->pluginDefinition['cron']['allow'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCronTime() {
    return $this->pluginDefinition['cron']['time'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDeleteWhenCompleted() {
    return $this->pluginDefinition['delete']['when_completed'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDeleteHard() {
    return $this->pluginDefinition['delete']['hard'];
  }

}

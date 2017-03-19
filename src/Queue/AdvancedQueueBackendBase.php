<?php

namespace Drupal\advancedqueue\Queue;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Database\Connection;

/**
 * Provides a base implementation for an AdvancedQueueBackend plugin.
 *
 * @see \Drupal\advancedqueue\Queue\AdvancedQueueBackendInterface
 * @see \Drupal\advancedqueue\Queue\AdvancedQueueBackendManager
 * @see \Drupal\advancedqueue\Annotation\AdvancedQueueBackend
 * @see plugin_api
 */
abstract class AdvancedQueueBackendBase extends PluginBase implements AdvancedQueueBackendInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection $connection
   */
  protected $connection;

  /**
   * Constructs a \Drupal\advancedqueue\Queue\AdvancedQueue object.
   *
   * @param string $name
   *   The name of the backend.
   * @param \Drupal\Core\Database\Connection $connection
   *   The Connection object containing the key-value tables.
   */
  public function __construct($name, Connection $connection) {
    $this->connection = $connection;
  }

}

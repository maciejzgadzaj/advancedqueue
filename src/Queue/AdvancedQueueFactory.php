<?php

namespace Drupal\advancedqueue\Queue;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueDatabaseFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines the key/value store factory for the database backend.
 */
class AdvancedQueueFactory extends QueueDatabaseFactory {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs this factory object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The Connection object containing the key-value tables.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(Connection $connection, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($connection);
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Constructs a new queue object for a given name.
   *
   * @param string $queue_name
   *   The name of the collection holding key and value pairs.
   *
   * @return \Drupal\advancedqueue\Queue\AdvancedQueue
   *   A key/value store implementation for the given $collection.
   */
  public function get($queue_name) {
    return new AdvancedQueue($queue_name, $this->connection, $this->eventDispatcher);
  }

}

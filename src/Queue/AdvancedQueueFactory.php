<?php

namespace Drupal\advancedqueue\Queue;

use Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager;
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
   * The queue worker plugin manager.
   *
   * @var \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager
   */
  protected $queueManager;

  /**
   * Constructs this factory object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The Connection object containing the key-value tables.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\advancedqueue\Queue\AdvancedQueueWorkerManager $queue_manager
   *   The queue worker plugin manager.
   */
  public function __construct(Connection $connection, EventDispatcherInterface $event_dispatcher, AdvancedQueueWorkerManager $queue_manager) {
    parent::__construct($connection);
    $this->eventDispatcher = $event_dispatcher;
    $this->queueManager = $queue_manager;
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
    $queues = &drupal_static(__FUNCTION__);

    if (!isset($queues[$queue_name])) {
      $queues[$queue_name] = new AdvancedQueue($queue_name, $this->connection, $this->eventDispatcher, $this->queueManager);
    }

    return $queues[$queue_name];
  }

}

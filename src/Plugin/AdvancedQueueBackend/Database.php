<?php

namespace Drupal\advancedqueue\Plugin\AdvancedQueueBackend;

use Drupal\advancedqueue\Queue\AdvancedQueueBackendBase;

/**
 * An example of full AdvancedQueue queue worker definition.
 *
 * @AdvancedQueueBackend(
 *   id = "database",
 *   title = @Translation("Database"),
 *   description = @Translation("A queue that has a good chance of successfully processing items."),
 * )
 */
class Database extends AdvancedQueueBackendBase {

  /**
   * The database table name.
   */
  const TABLE_NAME = 'advancedqueue';

  /**
   * {@inheritdoc}
   */
  public function queueCreate($queue_name) {

  }

  /**
   * {@inheritdoc}
   */
  public function queueDelete($queue_name) {
    return $this->connection->delete(self::TABLE_NAME)
      ->condition('name', $queue_name)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function itemCreate($item) {
    $merge_keys = !empty($item->item_key) ? ['item_key' => $item->item_key] : [];
    return $this->connection->merge(self::TABLE_NAME)
      ->keys($merge_keys)
      ->fields((array) $item);

  }

  /**
   * {@inheritdoc}
   */
  public function itemUpdate($item) {
    return $this->connection->update(self::TABLE_NAME)
      ->fields((array) $item)
      ->condition('item_id', $item->item_id)
      ->execute();

  }

  /**
   * {@inheritdoc}
   */
  public function itemDelete($item) {
    return $this->connection->delete(self::TABLE_NAME)
      ->condition('item_id', $item->item_id)
      ->execute();
  }

}

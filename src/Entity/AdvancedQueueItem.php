<?php

namespace Drupal\advancedqueue\Entity;

use Drupal\advancedqueue\Entity\AdvancedQueueItemInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the advancedqueue item entity class.
 *
 * @ContentEntityType(
 *   id = "advancedqueue_item",
 *   label = @Translation("Queue item"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "advancedqueue",
 *   admin_permission = "administer advancedqueue_item entity",
 *   entity_keys = {
 *     "id" = "item_id",
 *     "label" = "title",
 *     "status" = "status",
 *     "uid" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/queues/{queue_name}/{advancedqueue_item}",
 *   }
 * )
 */
class AdvancedQueueItem extends ContentEntityBase implements AdvancedQueueItemInterface {

  /**
   * Status indicating item was added to the queue.
   */
  const STATUS_QUEUED = -1;

  /**
   * Status indicating item is currently being processed.
   */
  const STATUS_PROCESSING = 0;

  /**
   * Status indicating item was processed successfully.
   */
  const STATUS_SUCCESS = 1;

  /**
   * Status indicating item processing failed.
   */
  const STATUS_FAILURE = 2;

  /**
   * Status indicating item processing failed, and should be retried.
   */
  const STATUS_FAILURE_RETRY = 3;

  /**
   * Status indicating item processing failed, and should be retried.
   */
  const STATUS_MODIFIER_DELETED = 10;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['item_key'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Item key'))
      ->setDescription(t('The unique key of the queue item, if any.'))
      ->setSetting('max_length', 255);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Queue name'))
      ->setDescription(t('The queue name the item belongs to.'))
      ->setRequired(TRUE)
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ]);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setDescription(t('The user to which the item belongs.'))
      ->setDefaultValueCallback('Drupal\advancedqueue\Entity\AdvancedQueueItem::getCurrentUserId')
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ]);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 400)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('Timestamp when the item was created.'))
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ]);

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Status'))
      ->setDescription(t('Indicates whether the item has been processed.'))
      ->setRequired(TRUE)
      ->setSetting('size', 'tiny')
      ->setDefaultValue(-1)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ]);

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('The arbitrary data for the item.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'map',
        'weight' => 0,
      ]);

    $fields['expire'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Expire'))
      ->setDescription(t('Timestamp when the claim lease expires on the item.'))
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ]);

    $fields['processed'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Processed'))
      ->setDescription(t('Timestamp when the item was last processed.'))
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ]);

    $fields['result'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Result'))
      ->setDescription(t('A serialized array of result data.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'map',
        'weight' => 0,
      ]);

    return $fields;
  }

  public static function loadItems($conditions) {
    $query = \Drupal::database()->select('advancedqueue', 'aq')
      ->fields('aq');
    foreach ($conditions as $key => $value) {
      if (!is_array($value)) {
        $value = [$value];
      }
      $query->condition($key, $value, 'IN');
    }
    $items = $query->orderBy('item_id', 'ASC')
      ->execute()
      ->fetchAllAssoc('item_id');

    foreach ($items as $item) {
      $item->data = unserialize($item->data);
      $item->result = unserialize($item->result);
    }

    return $items;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * Returns an array of possible queue item status as options list.
   *
   * @return array
   *   An array of possible queue item status as options list.
   */
  public static function getStatusOptions() {
    return [
      self::STATUS_QUEUED => t('Queued'),
      self::STATUS_PROCESSING => t('Processing'),
      self::STATUS_SUCCESS => t('Processed'),
      self::STATUS_FAILURE => t('Failed'),
      self::STATUS_FAILURE_RETRY => t('Retry'),
    ];
  }

  public static function getStatusLabel($status_code) {
    $status_options = self::getStatusOptions();
    return isset($status_options[$status_code]) ? $status_options[$status_code] : $status_code;
  }

}

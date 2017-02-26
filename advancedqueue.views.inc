<?php

/**
 * Implements hook_views_data_alter().
 */
function advancedqueue_views_data_alter(array &$data) {
  // Custom handler to display the queue name either as human-readable
  // queue title or queue machine name.
  $data['advancedqueue']['name']['field']['id'] = 'advancedqueue_queue_name';
  $data['advancedqueue']['name']['filter']['id'] = 'advancedqueue_queue_name';

  // Custom handlers for status field to show its label instead of number.
  $data['advancedqueue']['status']['field']['id'] = 'advancedqueue_status';
  $data['advancedqueue']['status']['filter']['id'] = 'advancedqueue_status';

  // Custom timestamp handler to fix hiding the field if original value is 0.
  $data['advancedqueue']['created']['field']['id'] = 'advancedqueue_timestamp';
  $data['advancedqueue']['expire']['field']['id'] = 'advancedqueue_timestamp';
  $data['advancedqueue']['processed']['field']['id'] = 'advancedqueue_timestamp';

  // Custom handler for serialized fields to avoid PHP "Undefined index" notices
  // in case the requested element does not exist in the serialized array.
  $data['advancedqueue']['data']['field']['id'] = 'advancedqueue_serialized';
  $data['advancedqueue']['data']['filter']['id'] = 'string';
  $data['advancedqueue']['result']['field']['id'] = 'advancedqueue_serialized';
  $data['advancedqueue']['result']['filter']['id'] = 'string';
}

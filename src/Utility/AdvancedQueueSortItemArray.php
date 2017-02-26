<?php

namespace Drupal\advancedqueue\Utility;

use Drupal\Component\Utility\SortArray;

/**
 * Provides queue items-specific sorting helper method.
 *
 * @ingroup utility
 */
class AdvancedQueueSortItemArray extends SortArray {

  /**
   * Sorts queue items array by the 'created' and 'item_id' element values.
   *
   * Callback for uasort().
   *
   * @param array $a
   *   First item for comparison. The compared items should be associative
   *   arrays that optionally include a 'created' element. For items without a
   *   'created' element, a default value of 0 will be used.
   * @param array $b
   *   Second item for comparison.
   *
   * @return int
   *   The comparison result for uasort().
   *
   * @see BulkConfirmFormBase::submitForm()
   */
  public static function sortByCreatedAndItemID($a, $b) {
    $c = ['created_item_id' => $a->created . $a->item_id];
    $d = ['created_item_id' => $b->created . $b->item_id];
    return static::sortByKeyInt($c, $d, 'created_item_id');
  }

}

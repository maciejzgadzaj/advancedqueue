# Advanced Queue

An extended queuing module fully backward compatible with and a drop-in replacement for DatabaseQueue.

**Work in progress on 8.x-1.x.**


## Features

  * _Queue Item_ (`advancedqueue_item`) entity type
  
  * `AdvancedQueue` queue implementation
    - proper FIFO implementation (items processed in order based on their `created` date, regardless of the queue they belong to)
    - additional queue item details:
      - human readable, translatable names for queued items
      - status of queued items (queued, processing, processed, failed, retry),
      - result payload
    - option to start processing queue items at set time in the future
    - option to repeat processing in case of failure with pre-set time delay (based on queue worker definition)
    - suspension of queue processing for a specific time
  
  * queue- and item-related events:
    - `queue.suspend`
    - `queue.unsuspend`
    - `item.preprocess`
    - `item.postprocess`
    - `item.release`
    - `item.requeue`
    - `item.reset`
    - `item.delete`

  * `AdvancedQueueWorkerManager`
    - support for additional queue worker definition elements (through annotations):
      - `description`
      - `group`
      - `lease.time`
      - `retry.attempts`
      - `retry.delay`
      - `suspend.time`
      - `execute_hooks.queue.suspend`
      - `execute_hooks.queue.unsuspend`
      - `execute_hooks.item.preprocess`
      - `execute_hooks.item.postprocess`
      - `delete.when_completed`
      - `delete.hard`
      - `cron.time`
  
  * cron support
    - process advanced queues with cron
    - exclude specific queues from cron processing
    - processing timeout
    - garbage collection
  
  * Drush commands:
    - `advancedqueue-queue-list` (`aqql`)                                                                            
    - `advancedqueue-queue-process` (`aqqp`)
    - `advancedqueue-queue-delete` (`aqqd`)                                                                          
    - `advancedqueue-queue-suspend` (`aqqs`)
    - `advancedqueue-queue-unsuspend` (`aqqu`)
    - `advancedqueue-item-list` (`aqil`)
    - `advancedqueue-item-process` (`aqip`)                                                                          
    - `advancedqueue-item-release` (`aqirel`)                                                                        
    - `advancedqueue-item-requeue` (`aqireq`)                                                                        
    - `advancedqueue-item-reset` (`aqires`)                                                                          
    - `advancedqueue-item-delete` (`aqid`)                                                                           

  * Drupal Console commands:
    - `advancedqueue:queue:list` (`aqql`)
    - `advancedqueue:queue:process` (`aqqp`)
    - `advancedqueue:queue:delete` (`aqqd`)
    - `advancedqueue:queue:suspend` (`aqqs`)
    - `advancedqueue:queue:unsuspend` (`aqqu`)
    - `advancedqueue:item:list` (`aqil`)
    - `advancedqueue:item:process` (`aqip`)
    - `advancedqueue:item:release` (`aqirel`)
    - `advancedqueue:item:requeue` (`aqireq`)
    - `advancedqueue:item:reset` (`aqires`)
    - `advancedqueue:item:delete` (`aqid`)

  * submodules:
    - `advancedqueue_ui`
      - extensive admin user interface to view and manage queues, queue groups and queue items
      - bulk actions available for queue item operations
      - _View queues_ and _Manage queues_ user permissions
    - `advancedqueue_example`
      - 3 example queue definitions illustrating queue worker annotation elements and processing results
      - user interface to generate queue items

## Installation

To use enable at least main `advancedqueue` module and add following line to your `settings.php`:

`$settings['queue_default'] = 'queue.advancedqueue';`

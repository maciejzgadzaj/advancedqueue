# Advanced Queue

An extended queuing module fully backward compatible with and a drop-in replacement for DatabaseQueue.

**Work in progress on 8.x-1.x.**


## Features

  * _Queue Item_ (`advancedqueue_item`) entity type
  
  * `AdvancedQueue` queue implementation
    - additional queue item details:
      - human readable, translatable names for queued items
      - status of queued items (queued, processing, processed, failed, retry),
      - result payload
    - option to start processing queue items at set time in the future
    - option to repeat processing in case of failure with pre-set time delay (based on queue worker definition)
  
  * queue item processig events: `advancedqueue_pre_execute` and `advancedqueue_post_execute`
  
  * `AdvancedQueueWorkerManager`
    - support for additional queue worker definition elements (annotations):
      - `description`
      - `group`
      - `lease.time`
      - `retry.attempts`
      - `retry.delay`
      - `execute_hooks.preprocess`
      - `execute_hooks.postprocess`
      - `delete.when_completed`
      - `delete.hard`
      - `cron.allow`
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
      - 2 example queue definitions illustrating queue worker annotation elements
      - user interface to generate queue items

## Installation

To use add the following line to your `settings.php`:

`$settings['queue_default'] = 'queue.advancedqueue';`

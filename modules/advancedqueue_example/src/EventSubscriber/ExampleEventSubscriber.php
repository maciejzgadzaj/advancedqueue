<?php

namespace Drupal\advancedqueue_example\EventSubscriber;

use Drupal\advancedqueue\Entity\AdvancedQueueItem;
use Drupal\advancedqueue\Event\AdvancedQueueEvents;
use Drupal\advancedqueue\Event\ItemClaimEvent;
use Drupal\advancedqueue\Event\ItemCreateEvent;
use Drupal\advancedqueue\Event\ItemDeleteEvent;
use Drupal\advancedqueue\Event\ItemPostProcessEvent;
use Drupal\advancedqueue\Event\ItemPreProcessEvent;
use Drupal\advancedqueue\Event\ItemReleaseEvent;
use Drupal\advancedqueue\Event\ItemRequeueEvent;
use Drupal\advancedqueue\Event\ItemResetEvent;
use Drupal\advancedqueue\Event\QueueDeleteEvent;
use Drupal\advancedqueue\Event\QueueSuspendEvent;
use Drupal\advancedqueue\Event\QueueUnsuspendEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ExampleEventSubscriber.
 *
 * @package Drupal\advancedqueue_example
 */
class ExampleEventSubscriber implements EventSubscriberInterface {

  /**
   * Constructor.
   */
  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AdvancedQueueEvents::QUEUE_DELETE][] = ['onQueueDelete'];
    $events[AdvancedQueueEvents::QUEUE_SUSPEND][] = ['onQueueSuspend'];
    $events[AdvancedQueueEvents::QUEUE_UNSUSPEND][] = ['onQueueUnsuspend'];
    $events[AdvancedQueueEvents::ITEM_CREATE][] = ['onItemCreate'];
    $events[AdvancedQueueEvents::ITEM_CLAIM][] = ['onItemClaim'];
    $events[AdvancedQueueEvents::ITEM_PREPROCESS][] = ['onItemPreprocess'];
    $events[AdvancedQueueEvents::ITEM_POSTPROCESS][] = ['onItemPostprocess'];
    $events[AdvancedQueueEvents::ITEM_RELEASE][] = ['onItemRelease'];
    $events[AdvancedQueueEvents::ITEM_REQUEUE][] = ['onItemRequeue'];
    $events[AdvancedQueueEvents::ITEM_RESET][] = ['onItemReset'];
    $events[AdvancedQueueEvents::ITEM_DELETE][] = ['onItemDelete'];

    return $events;
  }

  /**
   * This method is called whenever the "queue.suspend" event is dispatched.
   *
   * @param QueueDeleteEvent $event
   *   The queue event.
   * @param string $event_name
   *   The name of the event.
   */
  public function onQueueDelete(QueueDeleteEvent $event, $event_name) {
    \Drupal::logger('advancedqueue_example')->debug(t('[@event_name] All queue items have been @type-deleted from queue "@queue_name".', [
      '@event_name' => $event_name,
      '@type' => $event->getHard() ? 'hard' : 'soft',
      '@queue_name' => $event->getQueueName(),
    ]));
  }

  /**
   * This method is called whenever the "queue.suspend" event is dispatched.
   *
   * @param QueueSuspendEvent $event
   *   The queue event.
   * @param string $event_name
   *   The name of the event.
   */
  public function onQueueSuspend(QueueSuspendEvent $event, $event_name) {
    \Drupal::logger('advancedqueue_example')->debug(t('[@event_name] Queue "@queue_name" has been suspended for @time seconds.', [
      '@event_name' => $event_name,
      '@queue_name' => $event->getQueueName(),
      '@time' => $event->getSuspendTime(),
    ]));
  }

  /**
   * This method is called whenever the "queue.unsuspend" event is dispatched.
   *
   * @param QueueUnsuspendEvent $event
   *   The queue event.
   * @param string $event_name
   *   The name of the event.
   */
  public function onQueueUnsuspend(QueueUnsuspendEvent $event, $event_name) {
    \Drupal::logger('advancedqueue_example')->debug(t('[@event_name] Queue "@queue_name" has been unsuspended.', [
      '@event_name' => $event_name,
      '@queue_name' => $event->getQueueName(),
    ]));
  }

  /**
   * This method is called whenever the "item.create" event is dispatched.
   *
   * @param ItemCreateEvent $event
   *   The item event.
   * @param string $event_name
   *   The name of the event.
   */
  public function onItemCreate(ItemCreateEvent $event, $event_name) {
    $item = $event->getItem();

    \Drupal::logger('advancedqueue_example')->debug(t('[@event_name] Item @item_id "@item_title" has been created in queue "@queue_name".', [
      '@event_name' => $event_name,
      '@item_id' => $item->item_id,
      '@item_title' => $item->title,
      '@queue_name' => $event->getQueueName(),
    ]));
  }

  /**
   * This method is called whenever the "item.claim" event is dispatched.
   *
   * @param ItemClaimEvent $event
   *   The item event.
   * @param string $event_name
   *   The name of the event.
   */
  public function onItemClaim(ItemClaimEvent $event, $event_name) {
    $item = $event->getItem();

    \Drupal::logger('advancedqueue_example')->debug(t('[@event_name] Item @item_id "@item_title" has been claimed in queue "@queue_name" for @time seconds.', [
      '@event_name' => $event_name,
      '@item_id' => $item->item_id,
      '@item_title' => $item->title,
      '@queue_name' => $event->getQueueName(),
      '@time' => $event->getLeaseTime(),
    ]));
  }

  /**
   * This method is called whenever the "item.preprocess" event is dispatched.
   *
   * @param ItemPreProcessEvent $event
   *   The item event.
   * @param string $event_name
   *   The name of the event.
   */
  public function onItemPreprocess(ItemPreProcessEvent $event, $event_name) {
    $item = $event->getItem();

    \Drupal::logger('advancedqueue_example')->debug(t('[@event_name] Item @item_id "@item_title" in queue "@queue_name" is about to be processed.', [
      '@event_name' => $event_name,
      '@item_id' => $item->item_id,
      '@item_title' => $item->title,
      '@queue_name' => $event->getQueueName(),
    ]));
  }

  /**
   * This method is called whenever the "item.postprocess" event is dispatched.
   *
   * @param ItemPostProcessEvent $event
   *   The item event.
   * @param string $event_name
   *   The name of the event.
   */
  public function onItemPostprocess(ItemPostProcessEvent $event, $event_name) {
    $item = $event->getItem();

    \Drupal::logger('advancedqueue_example')->debug(t('[@event_name] Item @item_id "@item_title" in queue "@queue_name" has just been processed with result @status (@status_label) and message %message.', [
      '@event_name' => $event_name,
      '@item_id' => $item->item_id,
      '@item_title' => $item->title,
      '@queue_name' => $event->getQueueName(),
      '@status' => $item->status,
      '@status_label' => AdvancedQueueItem::getStatusLabel($item->status),
      '%message' => $item->result['return'],
    ]));
  }

  /**
   * This method is called whenever the "item.release" event is dispatched.
   *
   * @param ItemReleaseEvent $event
   *   The item event.
   * @param string $event_name
   *   The name of the event.
   */
  public function onItemRelease(ItemReleaseEvent $event, $event_name) {
    $item = $event->getItem();

    \Drupal::logger('advancedqueue_example')->debug(t('[@event_name] Item @item_id "@item_title" has just been released in queue "@queue_name".', [
      '@event_name' => $event_name,
      '@item_id' => $item->item_id,
      '@item_title' => $item->title,
      '@queue_name' => $event->getQueueName(),
    ]));
  }

  /**
   * This method is called whenever the "item.requeue" event is dispatched.
   *
   * @param ItemRequeueEvent $event
   *   The item event.
   * @param string $event_name
   *   The name of the event.
   */
  public function onItemRequeue(ItemRequeueEvent $event, $event_name) {
    $item = $event->getItem();

    \Drupal::logger('advancedqueue_example')->debug(t('[@event_name] Item @item_id "@item_title" has just been requeued in queue "@queue_name".', [
      '@event_name' => $event_name,
      '@item_id' => $item->item_id,
      '@item_title' => $item->title,
      '@queue_name' => $event->getQueueName(),
    ]));
  }

  /**
   * This method is called whenever the "item.reset" event is dispatched.
   *
   * @param ItemResetEvent $event
   *   The item event.
   * @param string $event_name
   *   The name of the event.
   */
  public function onItemReset(ItemResetEvent $event, $event_name) {
    $item = $event->getItem();

    \Drupal::logger('advancedqueue_example')->debug(t('[@event_name] Attempt counter for item @item_id "@item_title" has just been reset in queue "@queue_name".', [
      '@event_name' => $event_name,
      '@item_id' => $item->item_id,
      '@item_title' => $item->title,
      '@queue_name' => $event->getQueueName(),
    ]));
  }

  /**
   * This method is called whenever the "item.delete" event is dispatched.
   *
   * @param ItemDeleteEvent $event
   *   The item event.
   * @param string $event_name
   *   The name of the event.
   */
  public function onItemDelete(ItemDeleteEvent $event, $event_name) {
    $item = $event->getItem();

    \Drupal::logger('advancedqueue_example')->debug(t('[@event_name] Item @item_id "@item_title" has just been @type-deleted from queue "@queue_name".', [
      '@event_name' => $event_name,
      '@item_id' => $item->item_id,
      '@item_title' => $item->title,
      '@type' => $event->getHard() ? 'hard' : 'soft',
      '@queue_name' => $event->getQueueName(),
    ]));
  }

}

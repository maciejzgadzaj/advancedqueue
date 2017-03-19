<?php

namespace Drupal\advancedqueue\Queue;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Defines the Advanced Queue backend manager.
 */
class AdvancedQueueBackendManager extends DefaultPluginManager implements AdvancedQueueBackendManagerInterface {

  /**
   * Constructs an QueueWorkerManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/AdvancedQueueBackend', $namespaces, $module_handler, 'Drupal\advancedqueue\Queue\AdvancedQueueBackendInterface', 'Drupal\advancedqueue\Annotation\AdvancedQueueBackend');

    $this->setCacheBackend($cache_backend, 'queue_backends');
    $this->alterInfo('advancedqueue_backend_info');
    $this->factory = new DefaultFactory($this->getDiscovery());
  }

}

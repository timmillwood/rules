<?php

/**
 * @file
 * Contains \Drupal\rules\Plugin\Condition\ConditionManager.
 */

namespace Drupal\rules\Plugin\Condition;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Condition\ConditionManager as CoreConditinManager;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Extends the core condition manager to add in Rules' context improvements.
 */
class ConditionManager extends CoreConditinManager {

  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    get_parent_class(get_parent_class($thing));

    parent::__construct($namespaces, $cache_backend, $module_handler);
}
<?php

/**
 * @file
 * Contains \Drupal\rules\Engine\ExecutionStateDefinition.
 */

namespace Drupal\rules\Engine;

use Drupal\Core\Plugin\Context\ContextDefinitionInterface;

/**
 * Defines the execution state of an expression.
 *
 * This class defines the context available to the execution state when executed
 * as well as any context provided back to the caller.
 *
 * @todo: Complete with settings for skip-save and provided variables.
 *
 * @see ExecutionState
 */
class ExecutionStateDefinition {

  /**
   * The definition array.
   *
   * @var array
   */
  protected $definition = [
    'context' => [],
  ];

  /**
   * Creates the object.
   *
   * @param array $values
   *   (optional) Some initial values to set. In the same format as returned
   *   from static::toArray().
   *
   * @return static
   */
  public static function create(array $values = []) {
    return new static($values);
  }

  /**
   * Constructs the object.
   *
   * @param array $values
   *   Some initial values to set. In the same format as returned from
   *   static::toArray().
   */
  protected function __construct(array $values) {
    $this->definition = $values + $this->definition;
  }

  /**
   * Defines a context.
   *
   * @param string $name
   *   The name of the context to define.
   * @param \Drupal\Core\Plugin\Context\ContextDefinitionInterface $definition
   *   The definition of the context.
   *
   * @return $this
   */
  public function setContextDefinition($name, ContextDefinitionInterface $definition) {
    $this->definition['context'][$name] = $definition;
    return $this;
  }

  /**
   * Exports the definition to an array.
   *
   * @return array
   *   The definition array, with the following keys set:
   *   - context: An array of context definitions, keyed by context name.
   */
  public function toArray() {
    $definition = $this->definition;
    foreach ($definition['context'] as $name => $context_definition) {
      // @todo: toArray() required.
      $definition['context'][$name] = get_object_vars($context_definition);
    }
    return $this->definition;
  }

}
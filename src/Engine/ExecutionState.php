<?php

/**
 * @file
 * Contains \Drupal\rules\Engine\ExecutionState.
 */

namespace Drupal\rules\Engine;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\DataReferenceInterface;
use Drupal\Core\TypedData\ListInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedDataTrait;
use Drupal\rules\Context\ContextDefinitionInterface;
use Drupal\rules\Exception\RulesEvaluationException;

/**
 * The rules execution state.
 *
 * A rule element may clone the state, so any added variables are only visible
 * for elements in the current PHP-variable-scope.
 */
class ExecutionState implements ExecutionStateInterface {

  use TypedDataTrait;

  /**
   * Globally keeps the ids of rules blocked due to recursion prevention.
   *
   * @todo Implement recursion prevention from D7.
   */
  static protected $blocked = [];

  /**
   * The known variables.
   *
   * @var \Drupal\Core\TypedData\TypedDataInterface[]
   */
  protected $variables = [];

  /**
   * Holds variables for auto-saving later.
   *
   * @var array
   */
  protected $saveLater = [];

  /**
   * Variable for saving currently blocked configs for serialization.
   */
  protected $currentlyBlocked;

  /**
   * Creates the object.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface[] $variables
   *   (optional) Variables to initialize this state with.
   *
   * @return static
   */
  public static function create($variables = []) {
    return new static($variables);
    // @todo Initialize the global "site" variable.
  }

  /**
   * Constructs the object.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface[] $variables
   *   (optional) Variables to initialize this state with.
   */
  protected function __construct($variables) {
    $this->variables = $variables;
  }

  /**
   * {@inheritdoc}
   */
  public function addVariable($name, ContextDefinitionInterface $definition, $value) {
    $data = $this->getTypedDataManager()->create(
      $definition->getDataDefinition(),
      $value
    );
    $this->addVariableData($name, $data);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addVariableData($name, TypedDataInterface $data) {
    $this->variables[$name] = $data;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getVariable($name) {
    if (!array_key_exists($name, $this->variables)) {
      throw new RulesEvaluationException("Unable to get variable $name, it is not defined.");
    }
    return $this->variables[$name];
  }

  /**
   * {@inheritdoc}
   */
  public function getVariableValue($name) {
    return $this->getVariable($name)->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function hasVariable($name) {
    return array_key_exists($name, $this->variables);
  }

  /**
   * {@inheritdoc}
   */
  public function applyDataSelector($selector, $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED) {
    $parts = explode(':', $selector, 2);
    $typed_data = $this->getVariable($parts[0]);

    if (count($parts) == 1) {
      return $typed_data;
    }
    $current_selector = $parts[0];
    foreach (explode(':', $parts[1]) as $name) {
      // If the current data is just a reference then directly dereference the
      // target.
      if ($typed_data instanceof DataReferenceInterface) {
        $typed_data = $typed_data->getTarget();
        if ($typed_data === NULL) {
          throw new RulesEvaluationException("Unable to apply data selector $current_selector. The specified reference is NULL.");
        }
      }

      // Make sure we are using the right language.
      if ($typed_data instanceof TranslatableInterface) {
        if ($typed_data->hasTranslation($langcode)) {
          $typed_data = $typed_data->getTranslation($langcode);
        }
        // @todo What if the requested translation does not exist? Currently
        // we just ignore that and continue with the current object.
      }

      // If this is a list but the selector is not an integer, we forward the
      // selection to the first element in the list.
      if ($typed_data instanceof ListInterface && !ctype_digit($name)) {
        $typed_data = $typed_data->offsetGet(0);
      }

      $current_selector .= ":$name";

      // Drill down to the next step in the data selector.
      if ($typed_data instanceof ListInterface || $typed_data instanceof ComplexDataInterface) {
        try {
          $typed_data = $typed_data->get($name);
        }
        catch (\InvalidArgumentException $e) {
          // In case of an exception, re-throw it.
          throw new RulesEvaluationException("Unable to apply data selector $current_selector: " . $e->getMessage());
        }
      }
      else {
        throw new RulesEvaluationException("Unable to apply data selector $current_selector. The specified variable is not a list or a complex structure: $name.");
      }
    }

    return $typed_data;
  }

  /**
   * {@inheritdoc}
   */
  public function saveChangesLater($selector) {
    $this->saveLater[$selector] = TRUE;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function autoSave() {
    // Make changes permanent.
    foreach ($this->saveLater as $selector => $flag) {
      $typed_data = $this->applyDataSelector($selector);
      // The returned data can be NULL, only save it if we actually have
      // something here.
      if ($typed_data) {
        // Things that can be saved must have a save() method, right?
        // Saving is always done at the root of the typed data tree, for example
        // on the entity level.
        $typed_data->getRoot()->getValue()->save();
      }
    }
    return $this;
  }

}

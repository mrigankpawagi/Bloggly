<?php

namespace Drupal\background_image;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormStateValuesTrait;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Render\Element;

/**
 * Trait BackgroundImageFormTrait.
 */
trait BackgroundImageFormTrait {

  use FormStateValuesTrait;

  /**
   * @var \Drupal\background_image\BackgroundImageManagerInterface
   */
  static $backgroundImageManager;

  /**
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  static $elementInfoManager;

  /**
   * @var array
   */
  protected $form;

  /**
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $formState;

  /**
   * @var array
   */
  protected $subform;

  /**
   * @var \Drupal\Core\Form\SubformStateInterface
   */
  protected $subformState;

  /**
   * @var array
   */
  protected $subformValues;

  /**
   * Provides a way to add #states to an element, but in a deferred way.
   *
   * This ensures that if the form is altered somewhere in the #process or
   * #after_build phase, it generates a selector for the States API using the
   * proper #parents array values.
   *
   * @param array $element
   *   The render array element in which to attach #states, passed by reference.
   * @param string|string[] $state
   *   The state(s) to set for passed $element.
   * @param array $input
   *   The render array input element that is used when determining the
   *   conditions.
   * @param array $conditions
   *   A standard States API conditions array. In place of a condition selector,
   *   you can use "%selector" anywhere and it will be replaced with the real
   *   selector value. An example of what this real selector value would be is
   *   if the $element #parents value contained an array with the values
   *   ['parent', 'child', 'input_element'], then the corresponding selector
   *   would be "edit-parent-child-input-selector". In most cases, you can pass
   *   "*" as the whole selector which will turn into the following selector:
   *   "[data-drupal-selector="%selector"]".
   *
   * @see \Drupal\background_image\BackgroundImageFormTrait::mapStatesConditions
   * @see \Drupal\background_image\BackgroundImageFormTrait::preRenderStates
   */
  public static function addState(array &$element, $state, array &$input, array $conditions = []) {
    $element['#pre_render_states'][] = [$state, &$input, $conditions];
    self::prependCallback($element, '#pre_render', [self::class, 'preRenderStates']);
  }

  /**
   * Adds a "Browse available tokens" link to the specified element.
   *
   * Note: this requires the "token" contrib module.
   *
   * @param string[] $token_types
   *   The token types to display.
   *
   * @param array $element
   *   The render array element to attach the token link to.
   */
  public static function addTokenBrowser(array &$element, $token_types = ['background_image']) {
    static $token;
    if (!isset($token)) {
      $token = [
        '#access' => \Drupal::moduleHandler()->moduleExists('token'),
        '#theme' => 'token_tree_link',
        '#token_types' => $token_types,
        '#global_types' => TRUE,
        '#dialog' => TRUE,
      ];
    }
    $element['token'] = $token;
  }

  /**
   * Appends a callback to an element.
   *
   * @param $element
   *   A render array element, passed by reference.
   * @param $property
   *   The property name of $element that should be used.
   * @param callable $handler
   *   A callback handler that should be executed for $property.
   * @param mixed $default
   *   The default value to use if no existing $property exists. This defaults
   *   to retrieving the element info default value first and falling back to
   *   this default value as the absolute last resort.
   */
  public static function appendCallback(&$element, $property, callable $handler, $default = []) {
    $existing = isset($element[$property]) ? $element[$property] : self::getElementInfo($element, $property, $default);
    $element[$property] = array_merge($existing, [$handler]);
  }

  /**
   * Filters a nested array recursively, from the bottom up.
   *
   * @param array $array
   *   The filtered nested array.
   * @param callable|null $callable
   *   The callable to apply for filtering.
   *
   * @return array
   *   The filtered array.
   */
  public static function arrayFilter(array $array, callable $callable = NULL) {
    foreach ($array as &$element) {
      if (is_array($element)) {
        $element = self::arrayFilter($element, $callable);
      }
    }
    return is_callable($callable) ? array_filter($array, $callable) : array_filter($array);
  }

  /**
   * Attaches a submit handler to the given form.
   *
   * @param callable $handler
   *   The handler to attach.
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function attachSubmitHandler(callable $handler, &$form, FormStateInterface $form_state) {
    if (!empty($form['#background_image_submit_handler'])) {
      return;
    }

    $form['#background_image_submit_handler'] = TRUE;

    // Entity form actions.
    foreach (['submit', 'publish', 'unpublish'] as $action) {
      if (!empty($form['actions'][$action])) {
        if (!isset($form['actions'][$action]['#submit'])) {
          $form['actions'][$action]['#submit'] = [];
        }
        if (!in_array($handler, $form['actions'][$action]['#submit'])) {
          array_unshift($form['actions'][$action]['#submit'], $handler);
        }
      }
    }

    // Generic submit button.
    if (!empty($form['submit'])) {
      if (!isset($form['submit']['#submit'])) {
        $form['submit']['#submit'] = [];
      }
      if (!in_array($handler, $form['submit']['#submit'])) {
        array_unshift($form['submit']['#submit'], $handler);
      }
    }
    // Generic form #submit.
    else {
      if (!isset($form['#submit'])) {
        $form['#submit'] = [];
      }
      if (!in_array($handler, $form['#submit'])) {
        array_unshift($form['#submit'], $handler);
      }
    }
  }

  /**
   * Attaches a submit handler to the given form.
   *
   * @param callable $handler
   *   The handler to attach.
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function attachValidationHandler(callable $handler, &$form, FormStateInterface $form_state) {
    if (!empty($form['#background_image_validate_handler'])) {
      return;
    }

    $form['#background_image_validate_handler'] = TRUE;

    if (!isset($form['#validate'])) {
      $form['#validate'] = [];
    }
    if (!in_array($handler, $form['#validate'])) {
      array_unshift($form['#validate'], $handler);
    }
  }

  /**
   * Creates a new group in the form, assigning child elements as needed.
   *
   * @param string $key
   *   The name to use when creating the group element in the form render array.
   * @param array $element
   *   The render array element that the group will be added to and children
   *   are direct descendants of, passed by reference.
   * @param array $group_array
   *   Default render array properties to use when creating the group.
   * @param array $children
   *   Optional. An indexed array of child element keys/names that should be
   *   added to the group.
   *
   * @return array
   *   The created group render array element, passed by reference.
   */
  public static function &createGroup($key, array &$element, array $group_array = [], array $children = []) {
    if (!isset($element[$key])) {
      $element[$key] = $group_array + [
        '#type' => 'details',
        '#title' => t(Unicode::ucfirst(trim(preg_replace('/_|-/', ' ', preg_replace('/^group/', '', $key))))),
        '#open' => TRUE,
        '#tree' => TRUE,
        '#group' => 'vertical_tabs',
        '#parents' => array_merge($element['#parents'], [$key]),
      ];
    }
    // Iterate over the child elements and assign them to the group.
    foreach ($children as $child) {
      if (isset($element[$child])) {
        $element[$child]['#group'] = $key;
      }
    }

    return $element[$key];
  }

  /**
   * Retrieves first available #input element, going through children if needed.
   *
   * @param array $element
   *   The render array element to use, passed by reference
   * @param bool $file
   *   Flag that indicates whether to use the hidden "fids" field as the input
   *   value for managed files.
   * @param bool $found
   *   A variable that is passed by reference to determine if the element has
   *   been found.
   *
   * @return array
   *   The first available #input element, passed by reference.
   */
  public static function &findFirstInput(array &$element, $file = TRUE, &$found = FALSE) {
    // Handle managed files differently (use their hidden fids element).
    if (!empty($element['#input']) && isset($element['#type']) && $element['#type'] === 'managed_file' && isset($element['fids']) && $file) {
      $fids = &self::findFirstInput($element['fids'], $file, $found);
      if ($found && $fids) {
        return $fids;
      }
      return $element;
    }
    else if (!empty($element['#input']) || (isset($element['#type']) && $element['#type'] === 'managed_file')) {
      $found = TRUE;
      return $element;
    }
    foreach (Element::children($element) as $child) {
      $child_element = &self::findFirstInput($element[$child], $file, $found);
      if ($found && $child_element) {
        return $child_element;
      }
    }
    return $element;
  }

  /**
   * Retrieves the Background Image Manager service.
   *
   * @return \Drupal\background_image\BackgroundImageManagerInterface
   */
  public static function getBackgroundImageManager() {
    if (!isset(self::$backgroundImageManager)) {
      self::$backgroundImageManager = \Drupal::service('background_image.manager');
    }
    return self::$backgroundImageManager;
  }

  /**
   * Retrieves default element info or a property from it.
   *
   * Note: this is necessary because of the way Drupal's form and rendering
   * process works. If you provide an element with a callback property, it does
   * not merge with the default values. This allows us to merge custom property
   * callbacks with the defaults.
   *
   * @param array $element
   *   The render array element to retrieve info for. It must have a valid
   *   element with a #type property.
   * @param string $property
   *   A specific property to retrieve from the element info.
   * @param mixed $default
   *   The default value to use if the provided $property does not exist.
   *
   * @return mixed
   *   If $property was provided, it will return the value of said property or
   *   the $default value if it does not exist. Otherwise, the entire element
   *   type's info array will be returned. If the type is not valid, an empty
   *   array will be returned.
   */
  public static function getElementInfo(array $element, $property = NULL, $default = []) {
    if (isset($element['#type'])) {
      return $property ? self::getElementInfoManager()->getInfoProperty($element['#type'], $property, $default) : self::getElementInfoManager()->getInfo($element['#type']);
    }
    return [];
  }

  /**
   * Retrieves the Element Info Manager service.
   *
   * @return \Drupal\Core\Render\ElementInfoManagerInterface
   */
  public static function getElementInfoManager() {
    if (!isset(self::$elementInfoManager)) {
      self::$elementInfoManager = \Drupal::service('plugin.manager.element_info');
    }
    return self::$elementInfoManager;
  }

  /**
   * Retrieves a subform value.
   *
   * @param string|string[] $key
   *   The key to retrieve.
   * @param mixed $default
   *   The default value to use if $key doesn't exist.
   *
   * @return mixed|null
   *   The value of $key or $default.
   */
  public function &getSubformValue($key, $default = NULL) {
    $exists = NULL;
    $value = &NestedArray::getValue($this->getSubformValues(), (array) $key, $exists);
    if (!$exists) {
      $value = $default;
    }
    return $value;
  }

  /**
   * @return array
   */
  public function &getSubformValues() {
    return $this->subformValues;
  }

  /**
   * {@inheritdoc}
   */
  public function &getValues() {
    return $this->values;
  }

  /**
   * Retrieves existing instance or creates new one from form state.
   *
   * @param array $form
   *   The complete form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state object.
   *
   * @return self
   */
  public static function initForm(array &$form, FormStateInterface $form_state) {
    /** @var self $self */
    $self = $form_state->get(self::class);
    if (!$self) {
      $self = new self();
      $form_state->set(self::class, $self);
    }
    return $self->setForm($form)->setFormState($form_state);
  }

  /**
   * Retrieves existing instance or creates new one from form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state object.
   *
   * @return self
   */
  public static function initWithFormState(FormStateInterface $form_state) {
    /** @var self $self */
    $self = $form_state->get(self::class);
    if (!$self) {
      $self = new self();
      $form_state->set(self::class, $self);
    }
    return $self->setForm($form_state->getCompleteForm())->setFormState($form_state);
  }

  /**
   * Retrieves existing instance or creates new one from form state.
   *
   * @param array $complete_form
   *   The complete form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state object.
   *
   * @return self
   */
  public static function initSubform(array &$complete_form, FormStateInterface $form_state) {
    /** @var self $self */
    $self = $form_state->get(self::class);
    if (!$self) {
      $self = new self();
      $form_state->set(self::class, $self);
    }
    if ($self->subform) {
      $subform = NestedArray::getValue($complete_form, $self->subform['#parents'], $exists);
      if ($exists && $subform) {
        $self->setSubform($subform);
      }
    }
    return $self->setForm($complete_form)->setFormState($form_state);
  }



  /**
   * Recursively maps a selector for a set of #states conditions.
   *
   * @param string $selector
   *   The real DOM selector that should be used in the #states conditions.
   * @param array $conditions
   *   A standard States API conditions array. In place of a condition selector,
   *   you can use "%selector" anywhere and it will be replaced with the real
   *   $selector value above. An example of what this real selector value would
   *   be is if an element's #parents value contained an array with the values
   *   ['parent', 'child', 'input_element'], then the corresponding selector
   *   would be "edit-parent-child-input-selector". In most cases, you can pass
   *   "*" as the whole selector which will turn into the following selector:
   *   "[data-drupal-selector="%selector"]".
   *
   * @return array
   *   The newly mapped #states conditions.
   *
   * @see \Drupal\background_image\BackgroundImageFormTrait::addState
   * @see \Drupal\background_image\BackgroundImageFormTrait::preRenderStates
   */
  public static function mapStatesConditions($selector, array $conditions = []) {
    $mapped = [];
    foreach ($conditions as $key => $value) {
      if (is_numeric($key) && is_array($value)) {
        $mapped[] = self::mapStatesConditions($selector, $value);
      }
      else {
        if ($key === '*') {
          $key = '[data-drupal-selector="%selector"]';
        }
        $real_selector = preg_replace_callback('/%selector/', function () use ($selector) {
          return $selector;
        }, $key);
        $mapped[$real_selector] = $value;
      }
    }
    return $mapped;
  }

  /**
   * Prepends a callback to an element.
   *
   * @param $element
   *   A render array element, passed by reference.
   * @param $property
   *   The property name of $element that should be used.
   * @param callable $handler
   *   A callback handler that should be executed for $property.
   * @param mixed $default
   *   The default value to use if no existing $property exists. This defaults
   *   to retrieving the element info default value first and falling back to
   *   this default value as the absolute last resort.
   */
  public static function prependCallback(&$element, $property, callable $handler, $default = []) {
    $existing = isset($element[$property]) ? $element[$property] : self::getElementInfo($element, $property, $default);
    $element[$property] = array_merge([$handler], $existing);
  }

  /**
   * The #pre_render callback for ::addState.
   *
   * @param array $element
   *   The render array element that is being pre-rendered.
   *
   * @return array
   *   The modified render array.
   *
   * @see \Drupal\background_image\BackgroundImageFormTrait::addState
   * @see \Drupal\background_image\BackgroundImageFormTrait::mapStatesConditions
   */
  public static function preRenderStates($element) {
    $pre_render_states = isset($element['#pre_render_states']) ? $element['#pre_render_states'] : [];
    foreach ($pre_render_states as $data) {
      // Retrieve the stored values.
      list($states, $passed_input, $conditions) = $data;

      // Find the real input element from the passed value.
      $input = &self::findFirstInput($passed_input);

      // Generate a proper selector based on the input element's #parents.
      $selector = 'edit-' . Html::cleanCssIdentifier(implode('-', $input['#parents']));

      // Typecast to an array.
      $states = (array) $states;
      foreach ($states as $state) {
        // Ensure the state exists.
        if (!isset($element['#states'][$state])) {
          $element['#states'][$state] = [];
        }

        // Merge in the mapped conditions for the state.
        $element['#states'][$state] = array_merge($element['#states'][$state], self::mapStatesConditions($selector, $conditions));
      }
    }

    // Remove the stored values.
    unset($element['#pre_render_states']);

    return $element;
  }

  /**
   * Sets the form.
   *
   * @param array $form
   *   The complete form render array, passed by reference.
   *
   * @return self
   */
  public function setForm(array &$form) {
    $this->form = &$form;
    return $this;
  }

  /**
   * Sets the current FormState object.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return self
   */
  public function setFormState(FormStateInterface $form_state) {
    $this->formState = $form_state;
    $this->values = $form_state->getValues();
    return $this->setSubformState();
  }

  /**
   * Sets a subform element.
   *
   * Note: this may not be the complete form.
   *
   * @param array $subform
   *   A render array element for the subform, passed by reference.
   *
   * @return self
   */
  public function setSubform(array &$subform) {
    $this->subform = &$subform;
    return $this;
  }

  /**
   * Sets the subform state, if one exists.
   *
   * @return self
   */
  public function setSubformState() {
    $this->subformState = NULL;
    $this->subformValues = [];
    if (isset($this->subform) && isset($this->form) && isset($this->formState)) {
      $this->subformState = SubformState::createForSubform($this->subform, $this->form, $this->formState);
      $this->subformValues = $this->subformState->getValues();
    }
    return $this;
  }


  /**
   * Implements \Drupal\Core\Form\FormStateInterface::setValues()
   */
  public function setSubformValues(array $values) {
    $existing_values = &$this->getSubformValues();
    $existing_values = $values;
    return $this;
  }

  /**
   * Implements \Drupal\Core\Form\FormStateInterface::setValue()
   */
  public function setSubformValue($key, $value) {
    NestedArray::setValue($this->getSubformValues(), (array) $key, $value, TRUE);
    return $this;
  }

  /**
   * Implements \Drupal\Core\Form\FormStateInterface::unsetValue()
   */
  public function unsetSubformValue($key) {
    NestedArray::unsetValue($this->getSubformValues(), (array) $key);
    return $this;
  }

}

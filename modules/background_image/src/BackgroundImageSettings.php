<?php

namespace Drupal\background_image;

use Drupal\Component\Utility\DiffArray;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ImmutableConfigException;
use Drupal\Core\Config\StorableConfigBase;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

class BackgroundImageSettings extends StorableConfigBase {

  /**
   * Overridden data.
   *
   * @var array
   */
  protected $overriddenData;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->name = 'background_image.settings.fake';
    $this->typedConfigManager = \Drupal::service('config.typed');
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    throw new ImmutableConfigException("Can not delete immutable configuration {$this->getName()}. This config is automatically stored in the \\Drupal\\background_image\\Entity\\BackgroundImage entity.");
  }

  /**
   * {@inheritdoc}
   */
  public function save($has_trusted_data = FALSE) {
    throw new ImmutableConfigException("Can not save immutable configuration {$this->getName()}. This config is automatically handled by the \\Drupal\\background_image\\Entity\\BackgroundImage entity.");
  }

  /**
   * Retrieves the data currently set as a drupalSettings array.
   *
   * @param string $name
   *   A specific setting to retrieve. If not set, the entire settings array
   *   will be returned.
   *
   * @return array
   *   The data.
   */
  public function drupalSettings($name = '') {
    $data = self::snakeCaseToCamelCase(empty($name) ? $this->get() : [$name => $this->get($name)]);
    if (empty($name)) {
      return $data;
    }
    return reset($data);
  }

  public function getOriginal($key = '') {
    if (empty($key)) {
      return $this->originalData;
    }
    else {
      $parts = explode('.', $key);
      if (count($parts) == 1) {
        return isset($this->originalData[$key]) ? $this->originalData[$key] : NULL;
      }
      else {
        $value = NestedArray::getValue($this->originalData, $parts, $key_exists);
        return $key_exists ? $value : NULL;
      }
    }
  }

  public function getOverridden($key = '') {
    // Whole overridden array.
    if (empty($key)) {
      return $this->overriddenData;
    }

    // Top level key.
    $parts = explode('.', $key);
    if (count($parts) == 1) {
      return isset($this->overriddenData[$key]) ? $this->overriddenData[$key] : NULL;
    }

    // Nested key.
    $value = NestedArray::getValue($this->overriddenData, $parts, $key_exists);
    return $key_exists ? $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function initWithData(array $data) {
    $this->isNew = FALSE;

    // Set initial data to default settings for casting to work (needs keys).
    $this->data = $data;

    // Now cast initial data to ensure proper values.
    $this->merge($data);

    // Indicate this is the original data.
    $this->originalData = $this->data;

    // Set initial overridden data to an empty array.
    $this->overriddenData = [];

    return $this;
  }

  /**
   * @param string $key
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function isOverridden($key = NULL) {
    return !empty($this->getOverridden($key));
  }

  /**
   * {@inheritdoc}
   */
  public function merge(array $data_to_merge) {
    foreach ($data_to_merge as $key => $value) {
      $this->set($key, $value);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    // The schema wrapper depends on the most recent data structure to cast
    // values. It must be reset every time a value has changed to properly cast.
    $this->schemaWrapper = NULL;

    // Ensure all MarkupInterface objects are cast to strings.
    $value = $this->castSafeStrings($value);

    // The dot/period is a reserved character; it may appear between keys, but
    // not within keys.
    if (is_array($value)) {
      $this->validateKeys($value);
    }

    $parts = explode('.', $key);
    $nested = count($parts) > 1;
    if ($nested) {
      // For casting to work properly, the raw value must first be set.
      NestedArray::setValue($this->data, $parts, $value);

      // Now cast the value and set it again.
      $value = $this->castValue($key, $value);
      NestedArray::setValue($this->data, $parts, $value);
    }
    else {
      // For casting to work properly, the raw value must first be set.
      $this->data[$key] = $value;

      // Now cast the value and set it again.
      $value = $this->castValue($key, $value);
      $this->data[$key] = $value;
    }

    // Determine if value overrides original data.
    $overridden_value = NULL;
    if ($this->originalData) {
      $overridden_value = $value;
      $original_value = $this->getOriginal($key);
      if (is_array($value) && is_array($original_value)) {
        if ($diff_value = DiffArray::diffAssocRecursive($value, $original_value)) {
          $overridden_value = $value;
        }
      }

      if (isset($overridden_value) && $overridden_value !== $original_value) {
        if ($nested) {
          NestedArray::setValue($this->overriddenData, $parts, $overridden_value);
        }
        else {
          $this->overriddenData[$key] = $overridden_value;
        }
      }
      elseif ($nested) {
        NestedArray::unsetValue($this->overriddenData, $parts);
      }
      else {
        unset($this->overriddenData[$key]);
      }
    }

    return $this;
  }

  /**
   * Converts snake_case_keys into camelCaseKeys.
   *
   * @param array $array
   *   An array to iterate over.
   *
   * @return array
   *   The converted array.
   */
  protected static function snakeCaseToCamelCase(array $array = []) {
    $converter = new CamelCaseToSnakeCaseNameConverter();
    $data = [];
    foreach ($array as $key => $value) {
      $data[$converter->denormalize($key)] = is_array($value) ? self::snakeCaseToCamelCase($value) : $value;
    }
    ksort($data);
    return $data;
  }

}

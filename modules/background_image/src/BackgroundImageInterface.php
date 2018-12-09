<?php

namespace Drupal\background_image;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a Background Image entity.
 *
 * @ingroup background_image
 */
interface BackgroundImageInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * General value to indicate "inherit".
   *
   * @type int
   */
  const INHERIT = -1;

  /**
   * General value to indicate "normal".
   *
   * @type int
   */
  const NORMAL = 0;

  /**
   * Never blur the background image.
   *
   * @type int
   */
  const BLUR_NONE = 0;

  /**
   * Only blur the background image after the user has scrolled.
   *
   * @type int
   */
  const BLUR_SCROLL = 1;

  /**
   * Same as BLUR_SCROLL, but also only if using the full_viewport setting.
   *
   * @type int
   */
  const BLUR_SCROLL_FULL_VIEWPORT = 2;

  /**
   * Always blur the background image.
   *
   * @type int
   */
  const BLUR_PERSISTENT = 3;

  /**
   * Attached to whole site.
   *
   * @type int
   */
  const TYPE_GLOBAL = -1;

  /**
   * Attached to an entity.
   *
   * @type int
   */
  const TYPE_ENTITY = 0;

  /**
   * Attached to an entity bundle.
   *
   * @type int
   */
  const TYPE_ENTITY_BUNDLE = 1;

  /**
   * Attached to a path or multiple paths.
   *
   * @type int
   */
  const TYPE_PATH = 2;

  /**
   * Attached to a route or multiple routes.
   *
   * @type int
   */
  const TYPE_ROUTE = 3;

  /**
   * Attached to a view page.
   *
   * @type int
   */
  const TYPE_VIEW = 4;

  /**
   * Associates a specific entity with the background image.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity object.
   * @param bool $save
   *   Flag indicating whether or not to save the background image entity
   *   after it has been associated with the entity.
   */
  public function associateEntity(EntityInterface $entity = NULL, $save = TRUE);

  /**
   * @return int
   */
  public function getBlur();

  /**
   * @return int
   */
  public function getBlurRadius();

  /**
   * @return int
   */
  public function getBlurSpeed();

  /**
   * @return string
   */
  public function getCssClass();

  /**
   * Retrieves the CSS file this background image.
   *
   * @return string
   *   An internal scheme path to the CSS file.
   */
  public function getCssUri();

  /**
   * Retrieves the image based hash.
   *
   * @return string
   */
  public function getImageHash();

  /**
   * Retrieves the image file.
   *
   * @param bool $parents
   *   Flag indicating whether to use parent image if this image is not set.
   *
   * @return \Drupal\file\FileInterface|null
   *   The image File object or NULL if it doesn't exist.
   */
  public function getImageFile($parents = TRUE);

  /**
   * Retrieves the parent background image, if one exists.
   *
   * @return \Drupal\background_image\BackgroundImageInterface|null
   */
  public function getParent();

  /**
   * @param string $name
   *   The name of the setting to retrieve.
   * @param mixed $default
   *   The default value to use if the setting doesn't exist.
   *
   * @return mixed
   *   The value for the the setting or $default if the setting doesn't exist.
   */
  public function getSetting($name, $default = NULL);

  /**
   * Retrieves the settings for this background image.
   *
   * @return \Drupal\background_image\BackgroundImageSettings
   *   A custom fake immutable config object containing the current settings.
   */
  public function getSettings();

  /**
   * Retrieves the settings hash.
   *
   * @param string $name
   *   A setting name to retrieve.
   *
   * @return string
   */
  public function getSettingsHash($name = NULL);

  /**
   * Retrieves the target identifier that is specific to the type.
   *
   * @param bool $explode
   *   Flag indicating whether to split the target into an array based on the
   *   colon (:) delimiter. This is useful for entity based targets.
   *
   * @return string|string[]
   */
  public function getTarget($explode = FALSE);

  /**
   * Retrieves the target entity, if the type is supported and exists.
   *
   * @param int $type
   *   The type. Defaults to the currently set type.
   * @param string $target
   *   A target identifier split by a colon (:) where the entity type is on
   *   the left and the UUID of the entity to load is on the right. Defaults
   *   to the currently set target.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The target Entity object or NULL if not a valid target.
   */
  public function getTargetEntity($type = NULL, $target = NULL);

  /**
   * Retrieves the target entity bundle, if the type is supported and exists.
   *
   * @param int $type
   *   The type. Defaults to the currently set type.
   * @param string $target
   *   A target identifier split by a colon (:) where the entity type is on
   *   the left and the entity bundle identifier to load is on the right.
   *   Defaults to the currently set target.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface|\Drupal\Core\Entity\EntityInterface|null
   *   The target EntityType object if it has bundle support or an Entity object
   *   if it does not. NULL if not a valid target.
   */
  public function getTargetEntityBundle($type = NULL, $target = NULL);

  /**
   * Retrieves the target entity view, if the type is supported and exists.
   *
   * @param int $type
   *   The type. Defaults to the currently set type.
   * @param string $target
   *   A target identifier split by a colon (:) where the view identifier is
   *   on the left and the page display identifier to load is on the right.
   *   Defaults to the currently set target.
   *
   * @return \Drupal\views\ViewEntityInterface|null
   *   The target View object or NULL if not a valid target.
   */
  public function getTargetView($type = NULL, $target = NULL);

  /**
   * @return string
   */
  public function getText();

  /**
   * The type.
   *
   * @return int
   */
  public function getType();

  /**
   * The type label.
   *
   * @param bool $link
   *   Whether or not to link to the target entity, if one exists.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   */
  public function getTypeLabel($link = FALSE);

  /**
   * Retrieves all the types.
   *
   * @return array
   *   An indexed array where the type is the key and the label is the value.
   */
  public static function getTypes();

  /**
   * Indicates whether this background image contains entity based tokens.
   *
   * @param string|string[] $entity_type
   *   Optional. Specific entity types to look for.
   * @param string|string[] $property
   *   Optional. Specific entity properties to look for.
   *
   * @return bool
   */
  public function hasEntityToken($entity_type = NULL, $property = NULL);

  /**
   * Gets the label of the entity.
   *
   * @param bool $link
   *   Whether or not to link to the target entity, if one exists.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string|null
   *   The label of the entity, or NULL if there is no label defined.
   */
  public function label($link = FALSE);

}

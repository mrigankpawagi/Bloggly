<?php

namespace Drupal\background_image;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageInterface;
use Drupal\file\FileInterface;
use Drupal\views\ViewEntityInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

interface BackgroundImageManagerInterface extends ContainerAwareInterface, ContainerInjectionInterface {

  /**
   * Perform alterations before a form is rendered.
   *
   * @param $form
   *   Nested array of form elements that comprise the form.
   * @param $form_state
   *   The current state of the form. The arguments that
   *   \Drupal::formBuilder()->getForm() was originally called with are available
   *   in the array $form_state->getBuildInfo()['args'].
   */
  public function alterEntityForm(array &$form, FormStateInterface $form_state);

  /**
   * Flushes the cache.
   */
  public function cacheFlush();

  /**
   * Determines if a color is "dark".
   *
   * @param string $hex
   *   A HEX color representation.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function colorIsDark($hex = NULL);

  /**
   * Samples the average color of an image file.
   *
   * @param \Drupal\file\FileInterface $file
   *   A File entity object.
   * @param string $default
   *   The default lowercase simple color (HEX) representation to use if
   *   unable to sample the image.
   *
   * @return string
   *   The lowercase simple color (HEX) representation of the sampled image.
   */
  public function colorSampleFile(FileInterface $file = NULL, $default = NULL);

  /**
   * Samples the average color of an image file.
   *
   * @param \Drupal\Core\Image\ImageInterface $image
   *   An Image object.
   * @param string $default
   *   The default lowercase simple color (HEX) representation to use if
   *   unable to sample the image.
   *
   * @return string
   *   The lowercase simple color (HEX) representation of the sampled image.
   */
  public function colorSampleImage(ImageInterface $image, $default = NULL);

  /**
   * Retrieves a background image based on the current route.
   *
   * @return \Drupal\background_image\BackgroundImageInterface|null
   *   A background image entity, if one exists.
   */
  public function getBackgroundImage();

  /**
   * Retrieves the base class.
   *
   * @return string
   */
  public function getBaseClass();

  /**
   * Retrieves the AdvAgg CSS Minifier service, if it exists.
   *
   * @return \Drupal\advagg_css_minify\Asset\CssMinifier|null
   */
  public function getCssMinifier();

  /**
   * Retrieves the default background image settings.
   *
   * @return array
   */
  public function getDefaultSettings();

  /**
   * Retrieves the enabled entity types.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   An associative array of EntityTypeInterface objects, keyed by their
   *   machine name.
   */
  public function getEnabledEntityTypes();

  /**
   * Retrieves the enabled bundles for an entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   An EntityTypeInterface object.
   *
   * @return array
   *   An array of enabled bundle information where the outer array is keyed by
   *   the bundle name, or the entity type name if the entity does not have
   *   bundles. The inner arrays are associative arrays of bundle information,
   *   such as the label for the bundle.
   */
  public function getEnabledEntityTypeBundles(EntityTypeInterface $entity_type);

  /**
   * Retrieves a background image that matches a specific entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to use as the target.
   *
   * @return \Drupal\background_image\BackgroundImageInterface|null
   *   A background image entity, if one exists.
   */
  public function getEntityBackgroundImage(EntityInterface $entity);

  /**
   * Retrieves a background image that matches a specific entity type.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to use as the target.
   *
   * @return \Drupal\background_image\BackgroundImageInterface|null
   *   A background image entity, if one exists.
   */
  public function getEntityBundleBackgroundImage(EntityInterface $entity);

  /**
   * Retrieves the bundle label for a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to use as the target.
   *
   * @return string|null
   *   The bundle label or NULL if it doesn't exist.
   */
  public function getEntityBundleLabel(EntityInterface $entity);

  /**
   * Retrieves an entity property value from the config.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity.
   * @param string $property
   *   A nested property to pluck.
   *
   * @return mixed
   */
  public function getEntityConfig(EntityInterface $entity, $property);

  /**
   * Retrieves the entity configuration values from storage.
   *
   * @param string $entity_type
   *   The entity type identifier.
   * @param string $bundle
   *   The entity bundle identifier.
   * @param string $property
   *   A nested property to pluck.
   * @param bool $filter
   *   Flag indicating whether to filter empty results.
   *
   * @return mixed
   */
  public function getEntityConfigArray($entity_type = NULL, $bundle = NULL, $property = NULL, $filter = TRUE);

  /**
   * Retrieves the current route entity object.
   *
   * @param string $entity_type
   *   The type of entity to retrieve from the current route.
   * @param string $bundle
   *   The entity bundle type to retrieve from the current route.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The current route entity object, if one exists.
   */
  public function getEntityFromCurrentRoute($entity_type = NULL, $bundle = NULL);

  /**
   * Retrieves the bundle info for a given entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   An EntityTypeInterface object.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface|null
   *   The bundle entity type associated with provided entity type.
   */
  public function getEntityTypeBundleEntityType(EntityTypeInterface $entity_type);

  /**
   * Retrieves the bundle info for a given entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   An EntityTypeInterface object.
   *
   * @return array
   *   An array of bundle information where the outer array is keyed by the
   *   bundle name, or the entity type name if the entity does not have bundles.
   *   The inner arrays are associative arrays of bundle information, such as
   *   the label for the bundle.
   */
  public function getEntityTypeBundles(EntityTypeInterface $entity_type);

  /**
   * Retrieves the fallback image style.
   *
   * @return string
   */
  public function getFallbackImageStyle();

  /**
   * Retrieves a fallback or "global" background image for the entire site.
   *
   * @return \Drupal\background_image\BackgroundImageInterface|null
   *   A background image entity, if one exists.
   */
  public function getGlobalBackgroundImage();

  /**
   * Retrieves the preload image style.
   *
   * @return string
   */
  public function getPreloadImageStyle();

  /**
   * Retrieves the responsive image style, if one exists.
   *
   * @return \Drupal\responsive_image\ResponsiveImageStyleInterface|null
   */
  public function getResponsiveImageStyle();

  /**
   * Retrieves the retina rules to use, if any.
   *
   * @return array
   *   An array of individual media query rules.
   */
  public function getRetinaRules();

  /**
   * Retrieves the supported entity types.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   An associative array of EntityTypeInterface objects, keyed by their
   *   machine name.
   */
  public function getSupportedEntityTypes();

  /**
   * Retrieves the bundle info for a given entity type.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   A ViewEntityInterface object.
   *
   * @return \Drupal\background_image\BackgroundImageInterface|null
   *   A background image entity, if one exists.
   */
  public function getViewBackgroundImage(ViewEntityInterface $view);

  /**
   * @return array
   */
  public function getViewsPages();

  /**
   * Acts on an entity object about to be shown on an entity form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that is about to be shown on the form.
   * @param $operation
   *   The current operation.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function prepareEntityForm(EntityInterface $entity, $operation, FormStateInterface $form_state);

  /**
   * Retrieves the service instance for this object.
   *
   * @return self
   */
  public static function service();

  /**
   * Determines whether minified CSS URIs should be used.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function useMinifiedCssUri();

  /**
   * Checks whether an entity can use background images.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An EntityInterface object.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function validEntity(EntityInterface $entity);

  /**
   * Checks whether a view can use background images.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   An ViewEntityInterface object.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function validView(ViewEntityInterface $view);

  /**
   * Builds the render array for the provided entity.
   *
   * @param \Drupal\background_image\BackgroundImageInterface $background_image
   *   The background image entity to render.
   * @param string $view_mode
   *   (optional) The view mode that should be used to render the entity.
   * @param string $langcode
   *   (optional) For which language the entity should be rendered, defaults to
   *   the current content language.
   *
   * @return array
   *   A render array for the entity.
   *
   * @throws \InvalidArgumentException
   *   Can be thrown when the set of parameters is inconsistent, like when
   *   trying to view a Comment and passing a Node which is not the one the
   *   comment belongs to, or not passing one, and having the comment node not
   *   be available for loading.
   */
  public function view($background_image, $view_mode = 'full', $langcode = NULL);

}

<?php

namespace Drupal\background_image\Entity;

use Drupal\background_image\BackgroundImageInterface;
use Drupal\background_image\BackgroundImageSettings;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Defines the Background Image entity.
 *
 * @ingroup background_image
 *
 * @ContentEntityType(
 *   id = "background_image",
 *   label = @Translation("Background Image"),
 *   label_collection = @Translation("Background Image"),
 *   label_singular = @Translation("background image"),
 *   label_plural = @Translation("background images"),
 *   label_count = @PluralTranslation(
 *     singular = "@count background image",
 *     plural = "@count background images"
 *   ),
 *   handlers = {
 *     "access" = "Drupal\background_image\BackgroundImageAccessControlHandler",
 *     "list_builder" = "Drupal\background_image\BackgroundImageListBuilder",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "view_builder" = "Drupal\background_image\BackgroundImageViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\background_image\Form\BackgroundImageHandlerForm",
 *       "edit" = "Drupal\background_image\Form\BackgroundImageHandlerForm",
 *       "delete" = "Drupal\background_image\Form\BackgroundImageDeleteForm",
 *     },
 *   },
 *   admin_permission = "administer background image",
 *   base_table = "background_image",
 *   data_table = "background_image_field_data",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "bid",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/media/background_image/{background_image}/edit",
 *     "edit-form" = "/admin/config/media/background_image/{background_image}/edit",
 *     "delete-form" = "/admin/config/media/background_image/{background_image}/delete",
 *     "collection" = "/admin/config/media/background_image"
 *   },
 * )
 */
class BackgroundImage extends ContentEntityBase implements BackgroundImageInterface {

  use ContainerAwareTrait;
  use EntityChangedTrait;
  use StringTranslationTrait;

  /**
   * @var \Drupal\background_image\BackgroundImageManagerInterface
   */
  protected $backgroundImageManager;

  /**
   * @var string
   */
  protected $cssSelector;

  /**
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * @var string
   */
  protected $settingsHash;

  /**
   * @var string
   */
  protected $imageHash;

  /**
   * @var \Drupal\background_image\BackgroundImageSettings
   */
  protected $settings;

  /**
   * @var \Drupal\background_image\BackgroundImageInterface|null
   */
  protected $parent;

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    // Don't use unset() here because the magic method
    // \Drupal\Core\Entity\ContentEntityBase::__get can cause the value to be
    // set as a FieldItemList object. Instead, always explicitly set to NULL.
    $this->cssSelector = NULL;
    $this->settingsHash = NULL;
    $this->imageHash = NULL;
    $this->parent = NULL;
    $this->settings = NULL;
    return parent::__sleep();
  }

  /**
   * {@inheritdoc}
   */
  public function associateEntity(EntityInterface $entity = NULL, $save = TRUE) {
    // Immediately return if not a valid entity.
    if (!$this->getBackgroundImageManager()->validEntity($entity)) {
      return;
    }

    $this
      ->set('type', self::TYPE_ENTITY)
      ->set('target', $entity->getEntityTypeId() . ':' . $entity->uuid())
      ->set('label', NULL)
    ;

    if ($save) {
      $this->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the background image was created.'))
      ->setDisplayOptions('view', [
        'region' => 'hidden',
      ])
    ;

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the background image was last edited.'))
      ->setDisplayOptions('view', [
        'region' => 'hidden',
      ])
    ;

    $fields['image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Image'))
      ->setDescription(t('The background image to display.'))
      ->setSettings([
        'file_directory' => 'background_image',
        'alt_field' => 0,
        'alt_field_required' => 0,
        'title_field' => 0,
        'title_field_required' => 0,
        'max_resolution' => '',
        'min_resolution' => '',
        'default_image' => [
          'uuid' => NULL,
          'alt' => '',
          'title' => '',
          'width' => NULL,
          'height' => NULL,
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'image_image',
        'weight' => 1,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'weight' => 0,
      ])
    ;

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setDescription(t('An administrative description to help identify this specific background image.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue('')
    ;

    $fields['settings'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Settings'))
      ->setDescription(t('Specific settings for this background image.'))
    ;

    $fields['type'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Type'))
      ->setDescription(t('Choose when this background image should be displayed.'))
      ->setRequired(TRUE)
      ->setDefaultValue(self::TYPE_GLOBAL)
      ->setSettings([
        'allowed_values' => self::getTypes(),
      ])
    ;

    $fields['target'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Target'))
      ->setDescription(t('A target, if any.'))
      ->setDefaultValue('')
    ;

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('An entity reference to the user who created this background image.'))
      ->addConstraint('ReferenceAccess')
      ->addConstraint('ValidReference')
      ->setRequired(TRUE)
      ->setSettings([
        'target_type' => 'user',
      ])
    ;

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBackgroundImageManager() {
    if (!isset($this->backgroundImageManager)) {
      $this->backgroundImageManager = $this->getContainer()->get('background_image.manager');
    }
    return $this->backgroundImageManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getBlur() {
    return $this->getSetting('blur');
  }

  /**
   * {@inheritdoc}
   */
  public function getBlurRadius() {
    return $this->getSetting('blur_radius');
  }

  /**
   * {@inheritdoc}
   */
  public function getBlurSpeed() {
    return $this->getSetting('blur_speed');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = parent::getCacheTags();
    // Make sure to get
    if ($this->hasEntityToken() && ($entity = $this->getBackgroundImageManager()->getEntityFromCurrentRoute())) {
      $tags[] = "{$entity->getEntityTypeId()}:{$entity->id()}";
    }
    return $tags;
  }

  /**
   * Retrieves the Container.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected function getContainer() {
    if (!isset($this->container)) {
      $this->setContainer(\Drupal::getContainer());
    }
    return $this->container;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCssClass() {
    if (!isset($this->cssSelector)) {
      $this->cssSelector = $this->getBackgroundImageManager()->getBaseClass() . '--' . Html::cleanCssIdentifier($this->getImageHash());
    }
    return $this->cssSelector;
  }

  /**
   * {@inheritdoc}
   */
  public function getCssUri() {
    $default_scheme = file_default_scheme();
    $min = $this->getBackgroundImageManager()->useMinifiedCssUri() ? '.min' : '';
    return "$default_scheme://background_image/css/{$this->id()}/$default_scheme/{$this->getImageHash()}$min.css";
  }

  /**
   * Retrieves the Entity Repository service.
   *
   * @return \Drupal\Core\Entity\EntityRepositoryInterface|mixed
   */
  protected function getEntityRepository() {
    if (!isset($this->entityRepository)) {
      $this->entityRepository = $this->getContainer()->get('entity.repository');
    }
    return $this->entityRepository;
  }

  /**
   * {@inheritdoc}
   */
  public function getImageHash() {
    if (!isset($this->imageHash)) {
      $image = $this->getImageFile();
      $data = [
        'preload.background_color' => $this->getSetting('preload.background_color'),
        'file' => $image ? $image->getFileUri() : '',
      ];
      $this->imageHash = Crypt::hashBase64(serialize($data));
    }
    return $this->imageHash;
  }

  /**
   * {@inheritdoc}
   */
  public function getImageFile($parents = TRUE) {
    $file = $this->get('image')->entity;
    if (!$file && $parents && ($parent = $this->getParent())) {
      $file = $parent->getImageFile($parents);
    }
    return $file;
  }

  /**
   * {@inheritdoc}
   */
  public function getParent() {
    if (!isset($this->parent)) {
      $this->parent = FALSE;
      if (($target_entity = $this->getTargetEntity()) && ($this->parent = $this->getBackgroundImageManager()->getEntityBundleBackgroundImage($target_entity))) {
        return $this->parent;
      }
      else if ($this->getType() !== self::TYPE_GLOBAL) {
        $this->parent = $this->getBackgroundImageManager()->getGlobalBackgroundImage() ?: FALSE;
      }
    }
    return $this->parent;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($name, $default = NULL) {
    $value = $this->getSettings()->get($name);
    return isset($value) ? $value : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    if (!isset($this->settings)) {
      $settings = $this->get('settings')->first();
      $parent = $this->getParent();
      $this->settings = new BackgroundImageSettings();
      $this->settings->initWithData($parent ? $parent->getSettings()->get() : $this->getBackgroundImageManager()->getDefaultSettings());
      $this->settings->merge($settings ? $settings->getValue() : []);
    }
    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsHash($name = NULL) {
    if (!isset($this->settingsHash)) {
      $data = [];
      if (isset($name)) {
        $data[$name] = $this->getSetting($name);
      }
      else {
        $data['settings'] = $this->getSettings()->get();
      }

      // Add entity specific target.
      if ((!isset($name) || $name === 'text' || $name === 'text.value') && $this->hasEntityToken() && ($entity = $this->getBackgroundImageManager()->getEntityFromCurrentRoute())) {
        $data['entity'] = $entity;
      }

      $serialized = serialize($data);
      $this->settingsHash = Crypt::hashBase64($serialized);
    }
    return $this->settingsHash;
  }

  /**
   * {@inheritdoc}
   */
  public function getTarget($explode = FALSE) {
    $target = $this->get('target')->value;
    return $target && $explode ? explode(':', $target) : $target;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntity($type = NULL, $target = NULL) {
    if (!isset($type)) {
      $type = $this->getType();
    }
    if (!isset($target)) {
      $target = $this->getTarget();
    }
    $entity = NULL;
    if ($type === self::TYPE_ENTITY && isset($target)) {
      list($entity_type, $entity_id) = explode(':', $target);
      if (isset($entity_type) && isset($entity_id)) {
        // If the entity identifier is all numbers, it's likely a database id.
        if (preg_match('/^\d+$/', $entity_id)) {
          $entity = $this->entityTypeManager()->getStorage($entity_type)->load($entity_id);
        }
        // Otherwise, attempt to load by UUID.
        else {
          $entity = $this->getEntityRepository()->loadEntityByUuid($entity_type, $entity_id);
        }
      }
    }
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityBundle($type = NULL, $target = NULL) {
    if (!isset($type)) {
      $type = $this->getType();
    }
    if (!isset($target)) {
      $target = $this->getTarget();
    }
    $entity = NULL;
    if ($type === self::TYPE_ENTITY_BUNDLE && $target) {
      list($entity_type_id, $entity_bundle) = explode(':', $target);
      if (isset($entity_type_id) && isset($entity_bundle) && ($entity_type = $this->entityTypeManager()->getDefinition($entity_type_id))) {
        if ($bundle_entity_type = $entity_type->getBundleEntityType()) {
          $entity = $this->entityTypeManager()->getStorage($bundle_entity_type)->load($entity_bundle);
        }
        else {
          $entity = $entity_type;
        }
      }
    }
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetView($type = NULL, $target = NULL) {
    if (!isset($type)) {
      $type = $this->getType();
    }
    if (!isset($target)) {
      $target = $this->getTarget();
    }
    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = NULL;
    if ($type === self::TYPE_VIEW) {
      list($view_id, $display_id) = explode(':', $target);

      if (isset($view_id) && isset($display_id) && ($view = $this->entityTypeManager()->getStorage('view')->load($view_id))) {
        $view_executable = $view->getExecutable();
        $view_executable->setDisplay($display_id);
        if (!$this->getBackgroundImageManager()->validView($view)) {
          $view = NULL;
        }
      }
    }
    return $view;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return (int) $this->get('type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeLabel($link = FALSE) {
    if (!isset($type)) {
      $type = $this->getType();
    }

    $types = self::getTypes();
    if (!isset($types[$type])) {
      return $this->t('Unknown');
    }

    if ($label = $this->label($link)) {
      if ($type === self::TYPE_ENTITY || $type === self::TYPE_ENTITY_BUNDLE || $type === self::TYPE_VIEW) {
        return $label;
      }
      return new FormattableMarkup('@type: @label', ['@type' => $types[$type], '@label' => $label]);
    }

    return $types[$type];
  }

  /**
   * {@inheritdoc}
   */
  public static function getTypes() {
    return [
      self::TYPE_GLOBAL => t('Global'),
      self::TYPE_ENTITY => t('Entity'),
      self::TYPE_ENTITY_BUNDLE => t('Entity Bundle'),
      // @todo Re-enable once support has been properly done.
      // self::TYPE_PATH => t('Path'),
      // self::TYPE_ROUTE => t('Route'),
      self::TYPE_VIEW => t('View'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getText() {
    return $this->getSetting('text.value');
  }

  /**
   * {@inheritdoc}
   */
  public function hasEntityToken($entity_type = NULL, $property = NULL) {
    $entity_type = isset($entity_type) ? (array) $entity_type : '[^:]+';
    if (is_array($entity_type)) {
      $types = [];
      foreach ($entity_type as $type) {
        $types[] = preg_quote($type);
      }
      $entity_type = '(' . implode('|', $types) . ')';
    }

    $property = isset($property) ? (array) $property : '[^\]]+';
    if (is_array($property)) {
      $properties = [];
      foreach ($property as $value) {
        $properties[] = preg_quote($value);
      }
      $property = '(' . implode('|', $properties) . ')';
    }

    $text = $this->getSetting('text.value', '');
    $matched = !!preg_match_all("/\[$entity_type:$property\]/", $text);
    return $matched;
  }


  /**
   * {@inheritdoc}
   */
  public function label($link = FALSE) {
    if ($entity = $this->getTargetEntity()) {
      return $this->t('%entity_type (@entity_id): @entity_label', [
        '%entity_type' => $this->getBackgroundImageManager()->getEntityBundleLabel($entity) ?: $entity->getEntityType()->getLabel(),
        '@entity_label' => $link ? $entity->toLink()->toString() : $entity->label(),
        '@entity_id' => $entity->id(),
      ]);
    }
    else if ($entity_bundle = $this->getTargetEntityBundle()) {
      if ($entity_bundle instanceof EntityInterface) {
        return $this->t('%entity_type (@entity_id): @entity_label', [
          '%entity_type' => $entity_bundle->getEntityType()->getLabel(),
          '@entity_label' => $link && $entity_bundle->hasLinkTemplate('edit-form') ? $entity_bundle->toLink(NULL, 'edit-form')->toString() : $entity_bundle->label(),
          '@entity_id' => $entity_bundle->id(),
        ]);
      }
      else if ($entity_bundle instanceof EntityTypeInterface) {
        return $this->t('%entity_type (@entity_id)', [
          '%entity_type' => $entity_bundle->getLabel(),
          '@entity_id' => $entity_bundle->id(),
        ]);
      }
    }
    else if ($view = $this->getTargetView()) {
      $executable = $view->getExecutable();
      $display = $executable->getDisplay();
      $path = FALSE;
      if ($display->hasPath()) {
        $path = '/' . $display->getPath();
        if ($view->status() && strpos($path, '%') === FALSE) {
          $path = \Drupal::l($path, Url::fromUserInput($path));
        }
      }
      return $this->t('View (@entity_id): @entity_label', [
        '@entity_label' => $link && $path ? $path : $view->label(),
        '@entity_id' => "{$view->id()}:{$executable->current_display}",
      ]);
    }
    $label = parent::label();
    return isset($label) ? trim($label) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += ['uid' => \Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    // Only save overridden settings.
    $this->set('settings', $this->getSettings()->getOverridden());
    return parent::save();
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

}

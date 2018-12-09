<?php

namespace Drupal\background_image;

use Drupal\Component\Utility\Color;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Image\ImageInterface;
use Drupal\Core\Routing\ResettableStackedRouteMatchInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\FileInterface;
use Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit;
use Drupal\system\Plugin\ImageToolkit\GDToolkit;
use Drupal\views\ViewEntityInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BackgroundImageManager implements BackgroundImageManagerInterface {

  use ContainerAwareTrait;
  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * @var \Drupal\background_image\BackgroundImageInterface
   */
  protected $backgroundImage;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\responsive_image\ResponsiveImageStyleInterface
   */
  protected $responsiveImageStyle;

  /**
   * @var \Drupal\Core\Routing\ResettableStackedRouteMatchInterface
   */
  protected $routeMatch;

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $viewBuilder;

  /**
   * BackgroundImageManager constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface
   *   The Config Factory service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The Entity Type Bundle Info service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type manager service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The File System service.
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   The Image Factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The Module Handler service.
   * @param \Drupal\Core\Routing\ResettableStackedRouteMatchInterface $route_match
   *   The Route Match service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The State service.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The UrlGenerator service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityTypeManagerInterface $entity_type_manager, FileSystemInterface $file_system, ImageFactory $image_factory, ModuleHandlerInterface $module_handler, ResettableStackedRouteMatchInterface $route_match, StateInterface $state, UrlGeneratorInterface $url_generator) {
    $this->config = $config_factory->get('background_image.settings');
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
    $this->imageFactory = $image_factory;
    $this->moduleHandler = $module_handler;
    $this->routeMatch = $route_match;
    $this->state = $state;
    $this->urlGenerator = $url_generator;
    $this->storage = $this->entityTypeManager->getStorage('background_image');
    $this->viewBuilder = $this->entityTypeManager->getViewBuilder('background_image');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('config.factory'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('image.factory'),
      $container->get('module_handler'),
      $container->get('current_route_match'),
      $container->get('state'),
      $container->get('url_generator.non_bubbling')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function alterEntityForm(array &$form, FormStateInterface $form_state) {
    // Check if inline_entity_form exists.
    $inline_entity_form = $this->moduleHandler->moduleExists('inline_entity_form');

    // Only alter forms that have a "inline_entity_form_entity" set.
    // @see \Drupal\background_image\BackgroundImageManager::prepareForm
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $form_state->get('inline_entity_form_entity');
    if (!$inline_entity_form || !$entity) {
      return;
    }


    $group = $this->getEntityConfig($entity, 'group');
    $require = $this->getEntityConfig($entity, 'require');
    $background_image = $this->getEntityBackgroundImage($entity);

    $form['background_image'] = [
      '#type' => 'details',
      '#theme_wrappers' => ['details__background_image'],
      '#title' => $this->t('Background Image'),
      '#open' => !$require && $group ? FALSE : TRUE,
      '#group' => $group,
      '#weight' => $group ? NULL : 100,
      '#tree' => TRUE,
    ];

    $form['background_image']['inline_entity_form'] = [
      '#theme_wrappers' => NULL,
      '#type' => 'inline_entity_form',
      '#entity_type' => 'background_image',
      '#langcode' => $entity->language()->getId(),
      '#default_value' => $background_image,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function cacheFlush() {
    file_scan_directory('public://background_image/css', '/.*/', ['callback' => function ($uri) {
      @file_unmanaged_delete_recursive($uri);
    }]);
  }

  /**
   * {@inheritdoc}
   */
  public function colorIsDark($hex = NULL) {
    if (!isset($hex)) {
      return FALSE;
    }
    $rgb = array_values(Color::hexToRgb($hex));
    return (0.213 * $rgb[0] + 0.715 * $rgb[1] + 0.072 * $rgb[2] < 255 / 2);
  }

  /**
   * {@inheritdoc}
   */
  public function colorSampleFile(FileInterface $file = NULL, $default = NULL) {
    return isset($file) ? $this->colorSampleImage($this->imageFactory->get($file->getFileUri()), $default) : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function colorSampleImage(ImageInterface $image, $default = NULL) {
    // Immediately return if the image is not valid.
    if (!$image->isValid()) {
      return $default;
    }

    // Retrieve the toolkit and use it, if valid.
    $toolkit = $image->getToolkit();
    if ($toolkit instanceof GDToolkit) {
      return $this->colorSampleGdImage($toolkit, $default);
    }
    else if ($toolkit instanceof ImagemagickToolkit) {
      return $this->colorSampleImagemagickImage($toolkit, $default);
    }
    return $default;
  }

  /**
   * Determines the average color of an image using the GD toolkit.
   *
   * @param \Drupal\system\Plugin\ImageToolkit\GDToolkit $image
   *   A GD image toolkit object.
   * @param string $default
   *   A default lowercase simple color (HEX) representation to use if
   *   unable to sample the image.
   *
   * @return string
   *   An associative array with red, green, blue and alpha keys that contains
   *   the appropriate values for the specified color index.
   */
  protected function colorSampleGdImage(GDToolkit $image, $default = NULL) {
    if ($image->apply('resize', ['width' => 1, 'height' => 1]) && ($resource = $image->getResource())) {
      return @Color::rgbToHex(array_slice(@imagecolorsforindex($resource, @imagecolorat($resource, 0, 0)), 0, 3)) ?: $default;
    }
    return $default;
  }

  /**
   * Determines the average color of an image using the Imagemagick toolkit.
   *
   * Due to how Imagemagick's toolkit works in Drupal, this doesn't actually
   * use any of the methods provided by the toolkit. This is because it
   * operates under the assumption that the output will be saved as an image.
   *
   * Since this is using an external binary and requires reading the text
   * output, the arguments must be constructed manually and the
   * ImagemagickExecManager service must be used directly.
   *
   * @see https://stackoverflow.com/a/25488429
   *
   * @param \Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit $image
   *   An Imagemagick toolkit object.
   * @param string $default
   *   A default lowercase simple color (HEX) representation to use if
   *   unable to sample the image.
   *
   * @return string
   *   An associative array with red, green, blue and alpha keys that contains
   *   the appropriate values for the specified color index.
   */
  protected function colorSampleImagemagickImage(ImagemagickToolkit $image, $default = NULL) {
    // Note: this service cannot be injected because not everyone will have
    // this module installed. It can only be accessed here via runtime.
    /** @var \Drupal\imagemagick\ImagemagickExecManagerInterface $exec_manager */
    $exec_manager = \Drupal::service('imagemagick.exec_manager');
    $arguments = (new \ReflectionClass('\Drupal\imagemagick\ImagemagickExecArguments'))->newInstance($exec_manager)
      ->setSourceLocalPath($this->fileSystem->realpath($image->getSource()))
      ->addArgument('-resize 1x1\!')
      ->addArgument('-format "%[fx:int(255*r+.5)],%[fx:int(255*g+.5)],%[fx:int(255*b+.5)]"')
      ->addArgument('info:-');
    if ($exec_manager->execute('convert', $arguments, $output, $error)) {
      return @Color::rgbToHex(explode(',', $output)) ?: $default;
    }
    return $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getBackgroundImage() {
    if (!isset($this->backgroundImage)) {
      if ($entity = $this->getEntityFromCurrentRoute()) {
        if ($entity instanceof EntityInterface && ($this->backgroundImage = $this->getEntityBackgroundImage($entity))) {
          return $this->backgroundImage;
        }
        else if ($entity instanceof EntityInterface && ($this->backgroundImage = $this->getEntityBundleBackgroundImage($entity))) {
          return $this->backgroundImage;
        }
        else if ($entity instanceof ViewEntityInterface && ($this->backgroundImage = $this->getViewBackgroundImage($entity))) {
          return $this->backgroundImage;
        }
      }
      $this->backgroundImage = $this->getGlobalBackgroundImage() ?: FALSE;
    }
    return $this->backgroundImage;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseClass() {
    return Html::cleanCssIdentifier(preg_replace('/^(\.|#)/', '', $this->config->get('css.base_class') ?: 'background-image'));
  }

  /**
   * {@inheritdoc}
   */
  public function getCssMinifier() {
    // @todo this should be using $this->container, but it's not always set?
    return \Drupal::getContainer()->get('advagg.css_minifier', ContainerInterface::NULL_ON_INVALID_REFERENCE);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSettings() {
    // Even though this is in the install config, it has the potential of
    // becoming removed. Due to the way BackgroundImageSettings works, the keys
    // must exist for values to be typecast properly.
    return NestedArray::mergeDeep([
      'blur' => [
        'type' => 2,
        'radius' => 50,
        'speed' => 1,
      ],
      'dark' => FALSE,
      'full_viewport' => FALSE,
      'preload' => [
        'background_color' => '#ffffff',
      ],
      'text' => [
        'format' => 'full_html',
        'value' => '',
      ],
    ], $this->config->get('defaults') ?: []);
  }

  /**
   * {@inheritdoc}
   */
  public function getEnabledEntityTypes() {
    $supported = $this->getSupportedEntityTypes();
    $enabled = [];
    foreach ($supported as $entity_type) {
      if (array_filter($this->getEnabledEntityTypeBundles($entity_type))) {
        $enabled[$entity_type->id()] = TRUE;
      }
    }
    return array_intersect_key($supported, $enabled);
  }

  /**
   * {@inheritdoc}
   */
  public function getEnabledEntityTypeBundles(EntityTypeInterface $entity_type) {
    $enabled = $this->getEntityConfigArray($entity_type, NULL, 'enable');
    $bundles = $this->getEntityTypeBundles($entity_type);
    if (empty($enabled[$entity_type->id()]) || !$bundles) {
      return [];
    }
    return array_intersect_key($bundles, $enabled[$entity_type->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityBackgroundImage(EntityInterface $entity) {
    if ($this->validEntity($entity)) {
      $entity_type = $entity->getEntityType();
      $result = $this->storage->loadByProperties([
        'type' => BackgroundImageInterface::TYPE_ENTITY,
        'target' => $entity_type->id() . ':' . $entity->uuid(),
      ]);
      if ($result) {
        return reset($result);
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityBundleBackgroundImage(EntityInterface $entity) {
    if ($bundle_entity_type = $entity->getEntityType()->getBundleEntityType()) {
      $result = $this->storage->loadByProperties([
        'type' => BackgroundImageInterface::TYPE_ENTITY_BUNDLE,
        'target' => $entity->getEntityTypeId() . ':' . $entity->bundle(),
      ]);
      if ($result) {
        return reset($result);
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityBundleLabel(EntityInterface $entity) {
    $bundles = $info = $this->getEnabledEntityTypeBundles($entity->getEntityType());
    if (isset($bundles[$entity->bundle()]['label'])) {
      return $bundles[$entity->bundle()]['label'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityConfig(EntityInterface $entity, $property) {
    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    $config = $this->getEntityConfigArray($entity_type, $bundle, $property);
    return isset($config[$entity_type][$bundle]) ? $config[$entity_type][$bundle] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityConfigArray($entity_type = NULL, $bundle = NULL, $property = NULL, $filter = TRUE) {
    if ($entity_type instanceof EntityInterface) {
      $entity_type = $entity_type->getEntityTypeId();
    }
    else if ($entity_type instanceof EntityTypeInterface) {
      $entity_type = $entity_type->id();
    }
    $entity_type = (string) $entity_type;

    // Return with the config if no property was specified.
    if (!$property) {
      $keys = ['entities'];
      if ($entity_type) {
        $keys[] = $entity_type;
        if ($bundle) {
          $keys[] = (string) $bundle;
        }
      }
      $config = $this->config->get(implode('.', $keys)) ?: [];
      return $filter ? array_filter($config) : $config;
    }

    // Return a nested property if a property was specified.
    $properties = [];
    foreach ($this->config->get('entities') as $t => $bundles) {
      if ($entity_type && $entity_type !== $t) {
        continue;
      }
      foreach ($bundles as $b => $data) {
        if ($bundle && $bundle !== $b) {
          continue;
        }
        foreach ($data as $key => $value) {
          if ($key === $property) {
            $properties[$t][$b] = $value;
          }
        }
        if ($filter) {
          $properties[$t] = array_filter($properties[$t]);
        }
      }
      if ($filter) {
        $properties = array_filter($properties);
      }
    }

    return $properties;
  }
  /**
   * {@inheritdoc}
   */
  public function getEntityFromCurrentRoute($entity_type = NULL, $bundle = NULL) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = NULL;
    $parameters = $this->routeMatch->getParameters();

    // If an entity type was passed, attempt to retrieve that object by name.
    if ($entity_type && $entity_type !== 'view' && ($parameter = $parameters->get($entity_type)) && $this->validEntity($parameter)) {
      $entity = $parameter;
    }
    // Retrieve the current view.
    else if (($view_id = $parameters->get('view_id')) && ($display_id = $parameters->get('display_id')) && ($view = $this->entityTypeManager->getStorage('view')->load($view_id))) {
      /** @var \Drupal\views\ViewEntityInterface $view */
      $executable = $view->getExecutable();
      $executable->setDisplay($display_id);
      $entity = $view;
    }
    // Otherwise, simply try to retrieve the first found entity object.
    else {
      foreach ($parameters->all() as $parameter) {
        if ($parameter instanceof EntityInterface && $this->validEntity($parameter)) {
          $entity = $parameter;
          break;
        }
      }
    }

    // If no bundle was provided, just return the entity.
    if (!isset($bundle)) {
      return $entity;
    }

    // Check if bundle matches.
    return $entity->bundle() === $bundle ? $entity : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeBundleEntityType(EntityTypeInterface $entity_type) {
    if ($bundle_entity_type = $entity_type->getBundleEntityType()) {
      return $this->entityTypeManager->getDefinition($bundle_entity_type);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeBundles(EntityTypeInterface $entity_type) {
    return $this->entityTypeBundleInfo->getBundleInfo($entity_type->id());
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackImageStyle() {
    $image_style = $this->config->get('image_style.fallback') ?: 'background_image_lg';
    if ($responsive_image_style = $this->getResponsiveImageStyle()) {
      return $responsive_image_style->getFallbackImageStyle() ?: $image_style;
    }
    return $image_style;
  }

  /**
   * {@inheritdoc}
   */
  public function getGlobalBackgroundImage() {
    $result = $this->storage->loadByProperties(['type' => BackgroundImageInterface::TYPE_GLOBAL]);
    if ($result) {
      return reset($result);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreloadImageStyle() {
    return $this->config->get('image_style.preload') ?: 'background_image_preload';
  }

  /**
   * {@inheritdoc}
   */
  public function getResponsiveImageStyle() {
    if (!isset($this->responsiveImageStyle)) {
      $this->responsiveImageStyle = $this->moduleHandler->moduleExists('responsive_image') ? $this->entityTypeManager->getStorage('responsive_image_style')->load($this->config->get('image_style.responsive') ?: 'background_image') : NULL;
    }
    return $this->responsiveImageStyle;
  }

  /**
   * {@inheritdoc}
   */
  public function getRetinaRules() {
    return $this->config->get('css.retina_rules') ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedEntityTypes() {
    static $entity_types;
    if (!isset($entity_types)) {
      $allowed_entity_types = ['view'];
      foreach ($this->entityTypeManager->getDefinitions() as $entity_type) {
        // Attempt to determine if the entity type can actually display a full page.
        $full_page = $entity_type->hasViewBuilderClass() && $entity_type->hasLinkTemplate('canonical') && $entity_type->getLinkTemplate('canonical') !== $entity_type->getLinkTemplate('edit-form');
        if (in_array($entity_type->id(), $allowed_entity_types) || $full_page) {
          $entity_types[$entity_type->id()] = $entity_type;
        }
      }
    }
    return $entity_types;
  }

  /**
   * {@inheritdoc}
   */
  public function getViewBackgroundImage(ViewEntityInterface $view) {
    if ($this->validView($view)) {
      $result = $this->storage->loadByProperties([
        'type' => BackgroundImageInterface::TYPE_VIEW,
        'target' => "{$view->id()}:{$view->getExecutable()->current_display}",
      ]);
      if ($result) {
        return reset($result);
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getViewsPages() {
    static $views;
    if (!isset($views)) {
      $views = [];
      if (!$this->moduleHandler->moduleExists('views')) {
        return $views;
      }
      foreach (Views::getEnabledViews() as $view_id => $view) {
        $displays = $view->get('display');
        foreach ($displays as $display_id => $display) {
          if ($display['display_plugin'] === 'page') {
            $id = "$view_id:$display_id";
            if (isset($display['display_options']['menu']['title'])) {
              $views[$id] = $display['display_options']['menu']['title'] . " ($id)";
            }
            else {
              $label = $view->label();
              if (isset($display['display_title']) && $display['display_title'] !== 'Page') {
                $label .= ' - ' . $display['display_title'];
              }
              $views[$id] = $label . " ($id)";
            }
          }
        }
      }
      ksort($views, SORT_NATURAL);
    }
    return $views;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareEntityForm(EntityInterface $entity, $operation, FormStateInterface $form_state) {
    // Only continue if the necessary conditions are met. Views, while valid
    // entities, cannot be embedded. They must be handled in a special way.
    if ($entity->bundle() === 'view' || !$this->validEntity($entity) || !($operation === 'default' || $operation === 'add' || $operation === 'edit')) {
      return;
    }

    // Add the entity as a special key in the form state to indicate that
    // the form should be altered by Background Image.
    $form_state->set('inline_entity_form_entity', $entity);
  }

  /**
   * {@inheritdoc}
   */
  public static function service() {
    return \Drupal::service('background_image.manager');
  }

  /**
   * {@inheritdoc}
   */
  public function useMinifiedCssUri() {
    return \Drupal::config('system.performance')->get('css.preprocess') && $this->getCssMinifier();
  }

  /**
   * {@inheritdoc}
   */
  public function validEntity(EntityInterface $entity) {
    // Check enabled entity bundles.
    if (!in_array($entity->bundle(), array_keys($this->getEnabledEntityTypeBundles($entity->getEntityType())))) {
      return FALSE;
    }
    // Check enabled entity types.
    if (!in_array($entity->getEntityTypeId(), array_keys($this->getEnabledEntityTypes()))) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function validView(ViewEntityInterface $view) {
    // Check enabled entity bundles.
    if (!in_array("{$view->id()}:{$view->getExecutable()->current_display}", array_keys($this->getViewsPages()))) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function view($background_image, $view_mode = 'full', $langcode = NULL) {
    return $this->viewBuilder->view($background_image, $view_mode, $langcode);
  }

}

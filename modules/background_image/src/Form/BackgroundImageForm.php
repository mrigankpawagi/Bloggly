<?php

namespace Drupal\background_image\Form;

use Drupal\background_image\BackgroundImageFormTrait;
use Drupal\background_image\BackgroundImageInterface;
use Drupal\background_image\Entity\BackgroundImage;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\Entity\File;

class BackgroundImageForm {

  use BackgroundImageFormTrait {
    setFormState as traitSetFormState;
    setSubformState as traitSetSubformState;
  }
  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * @var \Drupal\background_image\BackgroundImageInterface
   */
  protected $backgroundImage;

  /**
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $inlineEntity;

  /**
   * @var \Drupal\file\FileInterface
   */
  protected $imageFile;

  /**
   * @var string
   */
  protected $imageType;

  /**
   * @var array
   */
  protected $overriddenSettings;

  /**
   * @var \Drupal\background_image\BackgroundImageInterface
   */
  protected $parent;

  /**
   * @var bool
   */
  protected $required;

  /**
   * @var bool
   */
  protected $step = 1;

  /**
   * @var string
   */
  protected $target;

  /**
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $targetEntity;

  /**
   * @var int
   */
  protected $type;

  /**
   * @param \Drupal\background_image\BackgroundImageInterface $background_image
   * @param array $complete_form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param array $subform
   *
   * @return \Drupal\background_image\Form\BackgroundImageForm
   */
  public static function create(BackgroundImageInterface $background_image, array &$complete_form, FormStateInterface $form_state, array &$subform) {
    $self = $form_state->get(self::class);
    if (!$self) {
      $self = new self();
      $form_state->set(self::class, $self);
    }
    return $self
      ->setInlineEntity($form_state->get('inline_entity_form_entity'))
      ->setBackgroundImage($background_image)
      ->setSubform($subform)
      ->setForm($complete_form)
      ->setFormState($form_state);
  }

  /**
   * Builds the Background Image form.
   */
  public function build() {
    // Add necessary handlers.
    self::attachValidationHandler([self::class, 'staticValidate'], $this->form, $this->formState);
    self::attachSubmitHandler([self::class, 'staticSubmit'], $this->form, $this->formState);

    switch ($this->step) {
      // Type selection.
      case 1:
        $this->buildTypeSelection();
        break;

      // Complete form.
      case 2:
        // Begin constructing the form. Use vertical tabs if this is not embedded
        // on another entity form.
        $this->subform['vertical_tabs'] = [
          '#type' => 'vertical_tabs',
          '#access' => !$this->inlineEntity,
        ];

        // Image.
        $this->buildImage($this->createGroup('image_group', $this->subform, [
          '#title' => $this->t('Image'),
          '#group' => $this->inlineEntity ? NULL : 'vertical_tabs',
          '#type' => $this->inlineEntity ? 'container' : 'details',
          '#weight' => 10,
        ]));

        // Settings.
        $this->buildSettings();
        break;
    }
  }

  /**
   * Builds the new type selection step.
   *
   * @return bool
   *   TRUE if a valid type has been selected, FALSE otherwise.
   */
  public function buildTypeSelection() {
    // Hide the image field.
    $this->subform['image']['#access'] = FALSE;

    // Change submit text to indicate that this is the first step.
    $this->subform['actions']['submit']['#value'] = $this->t('Continue');

    // Type.
    $this->subform['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => BackgroundImage::getTypes(),
      '#default_value' => $this->getSubformValue('type', $this->backgroundImage->getType()),
      '#required' => TRUE,
    ];

    // Label.
    $this->subform['label'] = [
      '#access' => !$this->inlineEntity,
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->getSubformValue('label', $this->backgroundImage->label()),
    ];
    self::addState($this->subform['label'], ['required', 'visible'], $this->subform['type'], [
      '*' => [
        ['value' => BackgroundImageInterface::TYPE_GLOBAL],
        ['value' => BackgroundImageInterface::TYPE_PATH],
        ['value' => BackgroundImageInterface::TYPE_ROUTE],
      ],
    ]);


    // Entity types.
    $entity_types = [];
    foreach (self::getBackgroundImageManager()->getEnabledEntityTypes() as $id => $entity_type) {
      // Views are handled differently (below), skip.
      if ($id === 'view') {
        continue;
      }
      $entity_types[$id] = $entity_type->getLabel();
    }

    $this->subform['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity'),
      '#options' => $entity_types,
      '#default_value' => $this->getSubformValue('entity_type'),
    ];
    self::addState($this->subform['entity_type'], ['required', 'visible'], $this->subform['type'], [
      '*' => ['value' => BackgroundImageInterface::TYPE_ENTITY],
    ]);
    foreach (self::getBackgroundImageManager()->getEnabledEntityTypes() as $id => $entity_type) {
      // Views are handled differently (below), skip.
      if ($id === 'view') {
        continue;
      }
      $this->subform["entity_type_$id"] = [
        '#type' => 'entity_autocomplete',
        '#title' => $entity_type->getLabel(),
        '#target_type' => $id,
        '#default_value' => $this->getSubformValue("entity_type_$id"),
      ];
      // Limit to only enabled bundles, if necessary.
      if ($entity_type->getBundleEntityType()) {
        $this->subform["entity_type_$id"]['#selection_settings'] = ['target_bundles' => array_keys(self::getBackgroundImageManager()->getEnabledEntityTypeBundles($entity_type))];
      }
      self::addState($this->subform["entity_type_$id"], ['required', 'visible'], $this->subform['type'], [
        '*' => ['value' => BackgroundImageInterface::TYPE_ENTITY],
      ]);
      self::addState($this->subform["entity_type_$id"], ['required', 'visible'], $this->subform['entity_type'], [
        '*' => ['value' => $id],
      ]);
    }

    // Entity Bundle types.
    $entity_bundles = [];
    foreach (self::getBackgroundImageManager()->getEnabledEntityTypes() as $id => $entity_type) {
      // Views are handled differently (below), skip.
      if ($id === 'view') {
        continue;
      }
      $entity_type_id = $id;
      $entity_type_label = $entity_type->getLabel();
      $bundles = self::getBackgroundImageManager()->getEnabledEntityTypeBundles($entity_type);
      foreach ($bundles as $bundle => $info) {
        $entity_bundles["$entity_type_id:$bundle"] = $entity_type_id === $bundle ? $info['label'] : "${info['label']} ($entity_type_label)";
      }
    }

    $this->subform['entity_bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Bundle'),
      '#options' => $entity_bundles,
      '#default_value' => $this->getSubformValue('entity_bundle', $this->type === BackgroundImageInterface::TYPE_ENTITY_BUNDLE ? $this->target : NULL),
    ];
    self::addState($this->subform['entity_bundle'], ['required', 'visible'], $this->subform['type'], [
      '*' => ['value' => BackgroundImageInterface::TYPE_ENTITY_BUNDLE],
    ]);

    // Path.
    $this->subform['path'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Path'),
      '#description' => $this->t('Valid internal path(s), separated by new lines. Use % as a wild card if needed.'),
      '#default_value' => $this->getSubformValue('path', $this->type === BackgroundImageInterface::TYPE_PATH ? $this->target : NULL),
    ];
    self::addState($this->subform['path'], ['required', 'visible'], $this->subform['type'], [
      '*' => ['value' => BackgroundImageInterface::TYPE_PATH],
    ]);

    // Route.
    $this->subform['route'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Route'),
      '#description' => $this->t('Valid route name(s), separated by new lines.'),
      '#default_value' => $this->getSubformValue('route', $this->type === BackgroundImageInterface::TYPE_ROUTE ? $this->target : NULL),
    ];
    self::addState($this->subform['route'], ['required', 'visible'], $this->subform['type'], [
      '*' => ['value' => BackgroundImageInterface::TYPE_ROUTE],
    ]);

    // View.
    $this->subform['view'] = [
      '#type' => 'select',
      '#title' => $this->t('View'),
      '#description' => $this->t('A specific View page display.'),
      '#options' => self::getBackgroundImageManager()->getViewsPages(),
      '#default_value' => $this->getSubformValue('view', $this->type === BackgroundImageInterface::TYPE_VIEW ? $this->target : NULL),
    ];
    self::addState($this->subform['view'], ['required', 'visible'], $this->subform['type'], [
      '*' => ['value' => BackgroundImageInterface::TYPE_VIEW],
    ]);

    return FALSE;
  }

  /**
   * Builds the "Image" group.
   *
   * @param array $group
   *   The group render array, passed by reference.
   */
  public function buildImage(array &$group) {
    $group['#require'] = $this->required;

    // Label.
    $group['label'] = [
      '#access' => !$this->inlineEntity,
      '#type' => 'item',
      '#title' => $this->backgroundImage->getTypeLabel(TRUE),
    ];

    // Image type.
    $group['image_type'] = [
      '#access' => !$this->required && $this->parent,
      '#type' => 'radios',
      '#title' => $this->t('Image'),
      '#options' => $this->inheritOverrideOptions(),
      '#default_value' => $this->getSubformValue(['image_group', 'image_type'], $this->imageType),
    ];
    if ($this->parent) {
      self::addState($this->subform['image'], 'visible', $group['image_type'], [
        '[data-drupal-selector="%selector"] :input' => ['value' => BackgroundImageInterface::NORMAL],
      ]);
    }

    // Add the image field widget to the group and increase its weight.
    $this->subform['image']['#group'] = 'image_group';
    $this->subform['image']['#weight'] = 11;
    $this->subform['image']['#attached']['library'][] = 'background_image/jscolor.picker';
    $image = &static::findFirstInput($this->subform['image'], FALSE);
    $image['#description'] = $this->required ? $this->t('A background image is required.') : NULL;
    $image['#required'] = $this->required;
    $image['#title'] = $this->t('Background Image');

    // Hide the title if there is a parent.
    if ($this->parent) {
      $image['#title_display'] = 'invisible';
    }

    // Return if there is no image.
    if (!$this->imageFile) {
      return;
    }

    // Background Image specific settings for the image.
    $image['background_image'] = ['#type' => 'container', '#weight' => 1000];

    // Calculate preload background color.
    $calculated_preload_background_color = static::getBackgroundImageManager()->colorSampleFile($this->imageFile, '');

    // For some reason, the default value of this is "" not NULL.
    // @todo Fix this somehow?
    $preload_background_color = $this->getSubformValue(['image', '0', 'background_image', 'preload_background_color', 'value']);
    if (empty($preload_background_color)) {
      $preload_background_color = $this->backgroundImage->getSettings()->get('preload.background_color') ?: $calculated_preload_background_color;
    }

    // Dark.
    $dark = $this->getSubformValue(['image', '0', 'background_image', 'dark']);
    if (!isset($dark) || empty($preload_background_color)) {
      $dark = $this->backgroundImage->getSettings()->get('dark');
      if (!isset($dark)) {
        $dark = self::getBackgroundImageManager()->colorIsDark($preload_background_color);
      }
    }
    $dark_description = $this->t('If enabled, the "@base_class-dark" class will be added to the BODY element so your theme can adjust styles accordingly.', [
      '@base_class' => static::getBackgroundImageManager()->getBaseClass(),
    ]);

    $image['background_image']['dark'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Dark Image'),
      '#attributes' => ['title' => $dark_description],
      '#label_attributes' => ['title' => $dark_description],
      '#default_value' => $dark,
    ];

    // Preload background color.
    $image['background_image']['preload_background_color'] = [
      '#type' => 'item',
      '#title' => $this->t('Preload background color'),
      '#description' => $this->t('This color will be used while the background images are still loading.'),
    ];

    $image['background_image']['preload_background_color']['value'] = [
      '#type' => 'textfield',
      '#default_value' => $preload_background_color,
      '#size' => 8,
      '#max_length' => 7,
      '#attributes' => [
        'data-jscolor' => '{hash:true,required:false,uppercase:false}',
        'data-calculated-value' => $calculated_preload_background_color,
        'data-default-value' => $preload_background_color,
        'autocomplete' => 'off',
        'autocorrect' => 'off',
        'autocapitalize' => 'off',
        'spellcheck' => 'false',
        'style' => 'width:100px',
      ],
      '#wrapper_attributes' => ['style' => 'display: inline-block'],
    ];

    $image['background_image']['preload_background_color']['calculate'] = [
      '#access' => $calculated_preload_background_color !== $preload_background_color,
      '#type' => 'button',
      '#value' => $this->t('Calculate'),
      '#attributes' => [
        'data-jscolor-value' => 'calculatedValue',
        'title' => $this->t('Calculates the average color of the image.'),
      ],
    ];
    self::addState($image['background_image']['preload_background_color']['calculate'], 'invisible', $image['background_image']['preload_background_color']['value'], ['*' => ['value' => $calculated_preload_background_color]]);

    $image['background_image']['preload_background_color']['reset'] = [
      '#type' => 'button',
      '#value' => $this->t('Reset'),
      '#attributes' => [
        'data-jscolor-value' => 'defaultValue',
        'title' => $this->t('Resets to the default value.'),
      ],
    ];
    self::addState($image['background_image']['preload_background_color']['reset'], 'invisible', $image['background_image']['preload_background_color']['value'], ['*' => ['value' => $preload_background_color]]);
  }

  /**
   * Builds a setting element.
   *
   * @param string $name
   *   The base name of the setting.
   * @param array $element
   *   A render array element used to construct the setting element.
   *
   * @return array
   *   The group container for the newly created setting, passed by reference.
   */
  public function &buildSetting($name, array $element = []) {
    // Create the setting group.
    self::createGroup($name, $this->subform['settings'], [
      '#open' => !$this->inlineEntity,
      '#weight' => 11,
    ]);
    if (!$this->parent) {
      self::addState($this->subform['settings'][$name], 'visible', $this->subform['image'], ['*' => ['empty' => FALSE]]);
    }

    // Move title and description into the fieldset.
    if (isset($element['#title'])) {
      $this->subform['settings'][$name]['#title'] = $element['#title'];
      if (isset($element['#type']) && ($element['#type'] === 'checkbox' || $element['#type'] === 'radio')) {
        $element['#title'] = $this->t('Enabled');
      }
      else {
        unset($element['#title']);
      }
    }
    if (isset($element['#description'])) {
      $this->subform['settings'][$name]['#description'] = $element['#description'];
      unset($element['#description']);
    }

    // Provide an override toggle.
    $this->subform['settings'][$name]['toggle'] = [
      // Radios need to be rendered with a fieldset, setting a blank title will
      // force it and create the necessary data-drupal-selector attribute.
      '#title' => '',
      '#access' => !!$this->parent,
      '#type' => 'radios',
      '#options' => $this->inheritOverrideOptions(),
      '#default_value' => $this->getSubformValue(['settings', $name, 'toggle'], !$this->backgroundImage->getSettings()->isOverridden($name) && $this->parent ? BackgroundImageInterface::INHERIT : BackgroundImageInterface::NORMAL),
    ];

    // Provide a container to put settings in.
    $this->subform['settings'][$name]['container'] = [
      '#type' => 'container',
      '#parents' => array_merge($this->subform['settings'][$name]['#parents'], ['container']),
    ];
    if ($this->parent) {
      self::addState($this->subform['settings'][$name]['container'], 'visible', $this->subform['settings'][$name]['toggle'], [
        '[data-drupal-selector="%selector"] :input' => ['value' => BackgroundImageInterface::NORMAL],
      ]);
    }

    // Create the actual setting element.
    if (!isset($element['#default_value'])) {
      $element['#default_value'] = $this->getSettingValue($name);
    }
    $this->subform['settings'][$name]['container']['value'] = $element;

    return $this->subform['settings'][$name];
  }

  /**
   * Builds the "Settings" group.
   */
  public function buildSettings() {
    $this->createGroup('settings', $this->subform, [
      '#type' => 'container',
      '#weight' => 11,
    ]);

    $this->buildSetting('full_viewport', [
      '#type' => 'checkbox',
      '#title' => $this->t('Full Viewport'),
      '#description' => $this->t('If enabled, the class "@base_class-full-viewport" will be added to the BODY element so the background image will take up the full height of the viewport. This should push any content after it out of the way so it appears below the background image. Enabling this will require the user to scroll to view the main content of the page.', [
        '@base_class' => static::getBackgroundImageManager()->getBaseClass(),
      ]),
    ]);

    $blur_type = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#description' => $this->t('Determines when the background should be blurred.'),
      '#default_value' => $this->getSettingValue('blur.type'),
      '#options' => [
        BackgroundImageInterface::BLUR_NONE => 'None',
        BackgroundImageInterface::BLUR_SCROLL => 'Scrolling',
        BackgroundImageInterface::BLUR_SCROLL_FULL_VIEWPORT => 'Scrolling (Full Viewport)',
        BackgroundImageInterface::BLUR_PERSISTENT => 'Persistent',
      ],
    ];
    $blur_radius = [
      '#type' => 'number',
      '#title' => $this->t('Radius'),
      '#description' => $this->t('Maximum blur radius, in pixels, that will be applied to the background image.'),
      '#default_value' => $this->getSettingValue('blur.radius'),
      '#min' => 0,
      '#max' => 100,
    ];
    self::addState($blur_radius, 'invisible', $blur_type, [
      '*' => ['value' => BackgroundImageInterface::BLUR_NONE],
    ]);

    $blur_speed = [
      '#type' => 'number',
      '#title' => $this->t('Speed'),
      '#description' => $this->t('The speed in which the background image is blurred when scrolled.'),
      '#default_value' => $this->getSettingValue('blur.speed'),
      '#min' => 1,
      '#max' => 10,
    ];
    self::addState($blur_speed, 'invisible', $blur_type, [
      ['*' => ['value' => BackgroundImageInterface::BLUR_NONE]],
      [
        '*' => [
          ['value' => BackgroundImageInterface::BLUR_SCROLL],
          ['!value' => BackgroundImageInterface::BLUR_SCROLL_FULL_VIEWPORT],
        ],
      ],
    ]);

    $this->buildSetting('blur', [
      '#title' => $this->t('Blur'),
      'type' => &$blur_type,
      'radius' => &$blur_radius,
      'speed' => &$blur_speed,
    ]);

    $text = &$this->buildSetting('text', [
      '#type' => 'text_format',
      '#title' => $this->t('Overlay Text'),
      '#description' => $this->t(''),
      '#format' => $this->getSettingValue('text.format'),
      '#default_value' => $this->getSettingValue('text.value'),
    ]);
    self::addTokenBrowser($text['container'], array_merge(['background_image'], array_keys(self::getBackgroundImageManager()->getEnabledEntityTypes())));
  }

  /**
   * Retrieves a setting value from the form state values or default values.
   *
   * @param string|string[] $name
   *   The name of the setting value to retrieve.
   * @param mixed $default
   *   The default value.
   *
   * @return mixed|null
   *   The setting value or NULL if not set.
   */
  public function getSettingValue($name, $default = NULL) {
    if (!isset($default)) {
      $default = $this->backgroundImage->getSetting($name, $this->parent ? $this->parent->getSetting($name) : NULL);
    }
    $parts = explode('.', $name);
    $first = array_shift($parts);
    $toggle = (int) $this->getSubformValue(array_merge(['settings'], (array) $first, ['toggle']), BackgroundImageInterface::NORMAL);
    if ($toggle === BackgroundImageInterface::INHERIT) {
      return $this->parent ? $this->parent->getSetting($name) : NULL;
    }
    return $this->getSubformValue(array_merge(['settings'], (array) $first, ['container', 'value'], $parts), $default);
  }

  /**
   * Retrieves all the setting values.
   *
   * @return array
   */
  public function getSettingValues() {
    $values = [];
    foreach (array_keys($this->backgroundImage->getSettings()->get()) as $name) {
      $value = $this->getSettingValue($name);
      if (isset($value)) {
        $values[$name] = $value;
      }
    }
    return $values;
  }

  /**
   * @return array
   */
  public function inheritOverrideOptions() {
    return [
      BackgroundImageInterface::INHERIT => $this->parent ? $this->t('Inherit from %label', ['%label' => $this->parent->getTypeLabel()]) : $this->t('Inherit'),
      BackgroundImageInterface::NORMAL => $this->t('Override'),
    ];
  }

  /**
   * Sets the Background Image entity.
   *
   * @param \Drupal\background_image\BackgroundImageInterface $background_image
   *   A Background Image entity.
   *
   * @return self
   */
  public function setBackgroundImage(BackgroundImageInterface $background_image) {
    $this->backgroundImage = $background_image;
    if ($this->inlineEntity) {
      $this->backgroundImage->associateEntity($this->inlineEntity, FALSE);
    }
    return $this;
  }

  /**
   * Sets the inline entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $inline_entity
   *   The inline entity.
   *
   * @return self
   */
  public function setInlineEntity($inline_entity = NULL) {
    $this->inlineEntity = $inline_entity instanceof EntityInterface ? $inline_entity : NULL;
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
    // Unfortunately, do to how forms are processed, the triggering element's
    // #submit handlers are only copied and not referenced. This means that
    // if Inline Entity Form embeds this on other forms, the real triggering
    // element's submit handlers are ignored. This simply resets the real
    // triggering element and its #submit handlers.
    $element = &$form_state->getTriggeringElement();
    if ($element && isset($element['#array_parents']) && ($real_element = &NestedArray::getValue($this->form, $element['#array_parents']))) {
      $form_state->setTriggeringElement($real_element);
      $form_state->setSubmitHandlers(isset($real_element['#submit']) ? $real_element['#submit'] : []);
    }

    return $this->traitSetFormState($form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function setSubformState() {
    $this->traitSetSubformState();

    // Make sure we have a type and target set.
    if (!isset($this->type) || !isset($this->target)) {
      // If there is an inline entity, then the type is already determined.
      if ($this->inlineEntity) {
        $this->backgroundImage->associateEntity($this->inlineEntity, FALSE);
        $this->type = $this->backgroundImage->getType();
        $this->target = $this->backgroundImage->getTarget();
        $this->step = 2;
      }
      // Otherwise, if this is not a new entity, it must have a type and target
      // already set. Use it.
      else if (!$this->backgroundImage->isNew()) {
        $this->type = $this->backgroundImage->getType();
        $this->target = $this->backgroundImage->getTarget();
        $this->step = 2;
      }
      // Otherwise this is a new entity. Retrieve the values for the form, if any.
      else {
        $type = $this->getSubformValue('type');
        if (isset($type)) {
          $this->type = (int) $type;
          if ($this->type === BackgroundImageInterface::TYPE_GLOBAL) {
            $this->target = '';
            $this->backgroundImage->set('label', $this->getSubformValue('label', ''));
          }
          else if ($this->type === BackgroundImageInterface::TYPE_ENTITY && ($entity_type = $this->getSubformValue('entity_type'))) {
            $entity_type_id = $this->getSubformValue("entity_type_$entity_type");
            if (isset($entity_type_id)) {
              $this->target = "$entity_type:$entity_type_id";
            }
          }
          else if ($this->type === BackgroundImageInterface::TYPE_ENTITY_BUNDLE && ($target = $this->getSubformValue('entity_bundle'))) {
            $this->target = $target;
          }
          else if ($this->type === BackgroundImageInterface::TYPE_PATH && ($target = $this->getSubformValue('path'))) {
            $this->target = $target;
          }
          else if ($this->type === BackgroundImageInterface::TYPE_ROUTE && ($target = $this->getSubformValue('route'))) {
            $this->target = $target;
          }
          else if ($this->type === BackgroundImageInterface::TYPE_VIEW && ($target = $this->getSubformValue('view'))) {
            $this->target = $target;
          }

          $this->backgroundImage->set('type', $this->type)->set('target', $this->target);
        }
      }

      // If there is still no type or target, then do not continue. The type
      // selection form must be shown.
      if ($this->type === BackgroundImageInterface::TYPE_GLOBAL) {
        // Ensure there is a label.
        $label = $this->getSubformValue('label', $this->backgroundImage->label());
        if (!isset($label)) {
          return $this;
        }
      }
      else if (!isset($this->type) || !isset($this->target)) {
        return $this;
      }
    }

    $this->parent = $this->backgroundImage->getParent();
    $this->overriddenSettings = $this->backgroundImage->getSettings()->merge($this->getSettingValues())->getOverridden();
    $this->targetEntity = $this->backgroundImage->getTargetEntity();

    // Determine if an image is required.
    $this->required = $this->type === BackgroundImageInterface::TYPE_GLOBAL || ($this->targetEntity ? self::getBackgroundImageManager()->getEntityConfig($this->targetEntity, 'require') : FALSE);

    // Determine the image file.
    if (($fids = $this->getSubformValue(['image', '0', 'fids'])) && ($fid = reset($fids))) {
      $this->imageFile = File::load($fid);
    }
    else {
      $this->imageFile = $this->backgroundImage->getImageFile(FALSE);
    }

    // Determine whether to override the image file.
    if ($this->required || $this->imageFile) {
      $this->imageType = BackgroundImageInterface::NORMAL;
    }
    else {
      $this->imageType = $this->parent ? BackgroundImageInterface::INHERIT : BackgroundImageInterface::NORMAL;
    }

    return $this;
  }

  /**
   * Static submit handler.
   *
   * Note: this is just a stub so it can retrieve the existing instantiated
   * class and pass the responsibility along to it.
   *
   * @param array $form
   *   The complete form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current FormState object.
   */
  public static function staticSubmit(array &$form, FormStateInterface $form_state) {
    self::initSubform($form, $form_state)->submit();
  }

  /**
   * Static validation handler.
   *
   * Note: this is just a stub so it can retrieve the existing instantiated
   * class and pass the responsibility along to it.
   *
   * @param array $form
   *   The complete form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current FormState object.
   */
  public static function staticValidate(array &$form, FormStateInterface $form_state) {
    self::initSubform($form, $form_state)->validate();
  }

  /**
   * Submit handler.
   */
  public function submit() {
    // If user was selecting a type, then rebuild the form.
    if ($this->step < 2) {
      $this->step++;
      $this->subformState->setRebuild();
      return;
    }

    // If there is no image and no overridden settings, then this shouldn't
    // create a new background image entity and should immediately return.
    if (!$this->imageFile && !$this->overriddenSettings) {
      // Immediately return if this is an embedded form.
      if ($this->inlineEntity) {
        return;
      }

      // Otherwise, show an error message explaining why this cannot be saved.
      if ($this->parent) {
        drupal_set_message($this->t('To create a new background image, you must override the inherited background image. Either by setting a new image or changing some of its settings.'), 'error');
      }
      else {
        drupal_set_message($this->t('To create a new background image, you must first set an image.'), 'error');
      }
      $this->subformState->setRebuild();
      return;
    }

    // Set the image.
    if ($this->imageFile) {
      $this->backgroundImage->set('image', $this->imageFile);
    }

    // If this background image should inherit its parent image, go ahead and
    // delete any images currently associated.
    $image_type = (int) $this->getSubformValue(['image_group', 'image_type'], BackgroundImageInterface::NORMAL);
    if ($image_type === BackgroundImageInterface::INHERIT) {
      if ($this->parent && $this->imageFile) {
        try {
          $this->imageFile->delete();
        }
        catch (\Exception $e) {
          // Intentionally left empty.
        }
        $this->setSubformValue(['image', '0'], []);
      }
      $this->imageFile = NULL;
    }

    // Merge in any overridden settings.
    if ($this->overriddenSettings) {
      $this->backgroundImage->getSettings()->merge($this->overriddenSettings);
    }

    // Set whether image is dark.
    $dark = $this->getSubformValue(['image', '0', 'background_image', 'dark'], $this->backgroundImage->getSettings()->getOverridden('dark'));
    if ($this->imageFile && isset($dark)) {
      $this->backgroundImage->getSettings()->set('dark', $dark);
    }
    // Otherwise, reset preload background color to the original value.
    else {
      $this->backgroundImage->getSettings()->set('dark', $this->backgroundImage->getSetting('dark'));
    }

    // Set the preload background color.
    if ($this->imageFile && ($preload_background_color = $this->getSubformValue(['image', '0', 'background_image', 'preload_background_color', 'value'], $this->backgroundImage->getSettings()->getOverridden('preload.background_color')))) {
      $this->backgroundImage->getSettings()->set('preload.background_color', $preload_background_color);
    }
    // Otherwise, reset preload background color to the original value.
    else {
      $this->backgroundImage->getSettings()->set('preload.background_color', $this->backgroundImage->getSetting('preload.background_color'));
    }

    try {
      $status = $this->backgroundImage->save();

      // Only show a message and redirect if this is not embedded on another form.
      if (!$this->inlineEntity) {
        if ($status == SAVED_UPDATED) {
          drupal_set_message($this->t('The background image @link has been updated.', [
            '@link' => $this->backgroundImage->toLink()->toString(),
          ]));
        }
        else {
          drupal_set_message($this->t('The background image @link has been added.', [
            '@link' => $this->backgroundImage->toLink()->toString(),
          ]));
        }
        $this->subformState->setRedirectUrl($this->backgroundImage->toUrl('collection'));
      }
    }
    catch (\Exception $e) {
      // Intentionally left empty.
    }
  }

  /**
   * Validation handler.
   */
  public function validate() {
    // Handle type selection.
    if ($this->step == 1) {
      if (!isset($this->type) || !in_array($this->type, array_keys(BackgroundImage::getTypes()))) {
        return $this->subformState->setError($this->subform['type'], $this->t('You must select a valid type.'));
      }
      else if ($this->type === BackgroundImageInterface::TYPE_GLOBAL) {
        if (empty($this->backgroundImage->label())) {
          return $this->subformState->setError($this->subform['label'], $this->t('You must enter a valid label.'));
        }
      }
      else if (empty($this->target)) {
        if ($this->type === BackgroundImageInterface::TYPE_ENTITY) {
          $entity_type = $this->getSubformValue('entity_type');
          ;
          if (!isset($entity_type)) {
            return $this->subformState->setError($this->subform['entity_type'], $this->t('You select a valid entity type.'));
          }
          else {
            $entity_target = $this->getSubformValue("entity_type_$entity_type");
            if (!isset($entity_target)) {
              return $this->subformState->setError($this->subform["entity_type_$entity_type"], $this->t('You enter a specific entity to target.'));
            }
            else if (!$this->backgroundImage->getTargetEntity($this->type, "$entity_type:$entity_target")) {
              return $this->subformState->setError($this->subform["entity_type_$entity_type"], $this->t('You enter a valid entity to target.'));
            }
          }
        }
        else if ($this->type === BackgroundImageInterface::TYPE_ENTITY_BUNDLE && ($target = $this->getSubformValue('entity_bundle'))) {
          return $this->subformState->setError($this->subform['view'], $this->t('You select a valid entity bundle.'));
        }
        else if ($this->type === BackgroundImageInterface::TYPE_PATH && ($target = $this->getSubformValue('path'))) {
          return $this->subformState->setError($this->subform['path'], $this->t('You enter a valid path.'));
        }
        else if ($this->type === BackgroundImageInterface::TYPE_ROUTE && ($target = $this->getSubformValue('route'))) {
          return $this->subformState->setError($this->subform['route'], $this->t('You enter a valid route.'));
        }
        else if ($this->type === BackgroundImageInterface::TYPE_VIEW && ($target = $this->getSubformValue('view'))) {
          return $this->subformState->setError($this->subform['view'], $this->t('You select a valid view.'));
        }
      }
    }
  }

}

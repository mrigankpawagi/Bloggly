<?php

namespace Drupal\background_image;

use Drupal\background_image\Controller\BackgroundImageCssController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\views\ViewEntityInterface;

class BackgroundImageViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function build(array $build) {
    /** @type \Drupal\background_image\BackgroundImageInterface $background_image */
    $background_image = $build['#background_image'];
    $manager = BackgroundImageManager::service();
    switch ($build['#view_mode']) {
      case 'image':
        $build['image'] = $this->buildImage($background_image, $manager);
        break;

      case 'text':
        $build['image'] = $this->buildText($background_image, $manager);
        break;

      default:
      case 'full':
        $build['image'] = $this->buildImage($background_image, $manager);
        $build['text'] = $this->buildText($background_image, $manager);
        break;
    }
    return $build;
  }

  /**
   * Builds the image render array.
   *
   * @param \Drupal\background_image\BackgroundImageInterface $background_image
   *   The background image being processed.
   * @param \Drupal\background_image\BackgroundImageManagerInterface $manager
   *   The Background Image Manager service.
   *
   * @return array
   *   The built render array element.
   */
  public function buildImage(BackgroundImageInterface $background_image, BackgroundImageManagerInterface $manager) {
    $image_file = $background_image->getImageFile();

    // Immediately return if there is no image.
    if (!$image_file) {
      $build['#access'] = FALSE;
      $build['#cache']['contexts'][] = 'background_image';
      return $build;
    }

    $base_class = $manager->getBaseClass();

    $build = [
      '#type' => 'container',
      '#theme_wrappers' => ['container__background_image__inner'],
      '#bootstrap_ignore_pre_render' => TRUE,
      '#bootstrap_ignore_process' => TRUE,
      '#attributes' => ['class' => [
        "$base_class-inner",
      ]],
    ];

    $build['image'] = [
      '#type' => 'container',
      '#theme_wrappers' => ['container__background_image__inner__image'],
      '#attributes' => ['class' => [
        $base_class,
        $background_image->getCssClass(),
      ]],
      '#bootstrap_ignore_pre_render' => TRUE,
      '#bootstrap_ignore_process' => TRUE,
    ];

    $build['overlay'] = [
      '#type' => 'container',
      '#theme_wrappers' => ['container__background_image__inner__overlay'],
      '#attributes' => ['class' => ["$base_class-overlay"]],
      '#bootstrap_ignore_pre_render' => TRUE,
      '#bootstrap_ignore_process' => TRUE,
    ];

    // Attach the scrolling blur effect JavaScript, if necessary.
    $full_viewport = $background_image->getSetting('full_viewport');
    $blur_type = $background_image->getSetting('blur.type');
    if ($blur_type == BackgroundImageInterface::BLUR_SCROLL || ($full_viewport && BackgroundImageInterface::BLUR_SCROLL_FULL_VIEWPORT)) {
      $build['#attached']['library'][] = 'background_image/scrolling.blur';
      $build['#attached']['drupalSettings']['backgroundImage']['blur'] = $background_image->getSettings()->drupalSettings('blur');
      $build['#attached']['drupalSettings']['backgroundImage']['fullViewport'] = $background_image->getSettings()->drupalSettings('full_viewport');
    }

    // Preload the necessary background background image.
    // @see https://www.smashingmagazine.com/2016/02/preload-what-is-it-good-for/
    $build['#attached']['html_head_link'][][] = [
      'rel' => 'preload',
      'href' => BackgroundImageCssController::imageStyleUrl($manager->getPreloadImageStyle(), $image_file->getFileUri()),
      'as' => 'image',
    ];

    // Attach the necessary background image CSS.
    // Due to the dynamic nature of how these are generated, this must be
    // attached via html_head_link instead of a library.
    // @see \Drupal\background_image\Controller\BackgroundImageCssController::deliver
    $build['#attached']['html_head_link'][][] = [
      'rel' => 'stylesheet',
      'href' => file_url_transform_relative(file_create_url($background_image->getCssUri())) . '?' . \Drupal::state()->get('system.css_js_query_string') ?: '0',
      'media' => 'all',
    ];

    $build['#cache']['contexts'][] = 'background_image';
    $build['#cache']['contexts'][] = 'background_image.settings.blur';
    $build['#cache']['contexts'][] = 'background_image.settings.full_viewport';

    $context = [
      'background_image' => $background_image,
      'entity' => $this->getEntity($background_image, $manager),
    ];
    $this->moduleHandler()->alter('background_image_build', $build, $context);
    \Drupal::service('theme.manager')->alter('background_image_build', $build, $context);

    return $build;
  }

  /**
   * Builds the text render array.
   *
   * @param \Drupal\background_image\BackgroundImageInterface $background_image
   *   The background image being processed.
   * @param \Drupal\background_image\BackgroundImageManagerInterface $manager
   *   The Background Image Manager service.
   *
   * @return array
   *   The built render array element.
   */
  public function buildText(BackgroundImageInterface $background_image, BackgroundImageManagerInterface $manager) {
    $text = trim($background_image->getSetting('text.value', ''));

    // Immediately return if there is no text.
    if (!$text) {
      $build['#access'] = FALSE;
      $build['#cache']['contexts'][] = 'background_image.settings.text';
      return $build;
    }

    $base_class = $manager->getBaseClass();
    $build = [
      '#type' => 'processed_text',
      '#theme_wrappers' => ['container__background_image__text'],
      '#attributes' => ['class' => ["$base_class-text"]],
      '#format' => $background_image->getSetting('text.format', 'full_html'),
      '#langcode' => $background_image->language()->getId(),
      '#text' => $text,
    ];
    $build['#cache']['contexts'][] = 'background_image.settings.text';

    // Add entity to token data.
    $token_data = ['background_image' => $background_image];
    $entity = $this->getEntity($background_image, $manager);
    if ($entity) {
      $token_data[$entity->getEntityTypeId()] = $entity instanceof ViewEntityInterface ? $entity->getExecutable() : $entity;
    }

    // Allow extensions a chance to alter the text before it's tokenized.
    $context = [
      'background_image' => $background_image,
      'entity' => $entity,
      'token_data' => $token_data,
      'token_options' => [
        'clear' => TRUE,
        'langcode' => &$build['#langcode'],
      ],
    ];
    $this->moduleHandler()->alter('background_image_text_build', $build, $context);
    \Drupal::service('theme.manager')->alter('background_image_text_build', $build, $context);

    // Perform token replacements.
    $build['#text'] = \Drupal::token()->replace($build['#text'], $context['token_data'], $context['token_options']);

    // Allow extensions a chance to alter the text after it's tokenized.
    $this->moduleHandler()->alter('background_image_text_after_build', $build, $context);
    \Drupal::service('theme.manager')->alter('background_image_text_after_build', $build, $context);

    return $build;
  }

  /**
   * Determines the property entity to associate with this background image.
   *
   * @param \Drupal\background_image\BackgroundImageInterface $background_image
   *   The background image being processed.
   * @param \Drupal\background_image\BackgroundImageManagerInterface $manager
   *   The Background Image Manager service.
   *
   * @todo This should really be moved to the BackgroundImage entity class.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   */
  protected function getEntity(BackgroundImageInterface $background_image, BackgroundImageManagerInterface $manager) {
    $type = $background_image->getType();

    // Determine the proper "entity" to use.
    if ($entity = $background_image->getTargetEntity()) {
      // Intentionally left empty, this is a specific associated entity.
    }
    // Handle a specific associated view.
    else if (($view = $background_image->getTargetView()) && $view->status()) {
      $entity = $view;
    }
    // Attempt to retrieve an entity based on a specific associated bundle.
    else if ($type === BackgroundImageInterface::TYPE_ENTITY_BUNDLE && (list($entity_type, $bundle) = $background_image->getTarget(TRUE)) && ($entity = $manager->getEntityFromCurrentRoute($entity_type, $bundle))) {
      // Intentionally left empty. Entity is assigned in the if block to ensure
      // that if it doesn't find one, to move on to the next if statement.
    }
    // Attempt to retrieve an entity based on current route object.
    else if (($type === BackgroundImageInterface::TYPE_GLOBAL || $type === BackgroundImageInterface::TYPE_PATH || $type === BackgroundImageInterface::TYPE_ROUTE)) {
      $entity = $manager->getEntityFromCurrentRoute();
    }
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $background_image, $view_mode = 'full', $langcode = NULL) {
    /** @type \Drupal\background_image\BackgroundImageInterface $background_image */
    $build = parent::view($background_image, $view_mode, $langcode);
    $build['#langcode'] = $langcode;
    $build['#access'] = $background_image->access('view', NULL, TRUE);

    // Attach, at the minimum, the baseClass drupal setting.
    $build['#attached']['drupalSettings']['backgroundImage']['baseClass'] = BackgroundImageManager::service()->getBaseClass();

    // Add user permissions context.
    $build['#cache']['contexts'][] = 'user.permissions';

    return $build;
  }

}

<?php

namespace Drupal\background_image\Plugin\Block;

use Drupal\background_image\BackgroundImageManager;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @Block(
 *   id = "background_image",
 *   admin_label = @Translation("Background Image"),
 *   category = @Translation("Background Image")
 * )
 */
class BackgroundImageBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  protected function baseConfigurationDefaults() {
    $defaults = parent::baseConfigurationDefaults();
    $defaults['label_display'] = 'hidden';
    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $background_image_manager = BackgroundImageManager::service();
    if ($background_image = $background_image_manager->getBackgroundImage()) {
      $build = $background_image_manager->view($background_image, 'image');
    }
    $build['#cache']['contexts'][] = 'user.permissions';
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['label_display']['#access'] = FALSE;

    return $form;
  }

}

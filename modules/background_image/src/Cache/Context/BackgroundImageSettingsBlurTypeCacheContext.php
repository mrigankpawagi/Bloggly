<?php

namespace Drupal\background_image\Cache\Context;

/**
 * Cache context ID: 'background_image.settings.blur.type'.
 */
class BackgroundImageSettingsBlurTypeCacheContext extends BackgroundImageBaseCacheContext {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Background Image: Blur Type Setting');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->backgroundImage ? $this->backgroundImage->getSetting('blur.type') : 0;
  }

}

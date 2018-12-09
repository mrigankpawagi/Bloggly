<?php

namespace Drupal\background_image\Cache\Context;

/**
 * Cache context ID: 'background_image.settings.blur'.
 */
class BackgroundImageSettingsBlurCacheContext extends BackgroundImageBaseCacheContext {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Background Image: Blur Settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->backgroundImage ? $this->backgroundImage->getSettingsHash('blur') : 0;
  }

}

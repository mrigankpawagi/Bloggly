<?php

namespace Drupal\background_image\Cache\Context;

/**
 * Cache context ID: 'background_image.settings.dark'.
 */
class BackgroundImageSettingsDarkCacheContext extends BackgroundImageBaseCacheContext {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Background Image: Dark Setting');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->backgroundImage ? $this->backgroundImage->getSetting('dark') : 0;
  }

}

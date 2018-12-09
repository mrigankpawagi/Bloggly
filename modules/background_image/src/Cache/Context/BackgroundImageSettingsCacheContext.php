<?php

namespace Drupal\background_image\Cache\Context;

/**
 * Cache context ID: 'background_image.settings'.
 */
class BackgroundImageSettingsCacheContext extends BackgroundImageBaseCacheContext {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Background Image: Settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->backgroundImage ? $this->backgroundImage->getSettingsHash() : 0;
  }

}

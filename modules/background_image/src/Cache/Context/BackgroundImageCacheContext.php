<?php

namespace Drupal\background_image\Cache\Context;

/**
 * Cache context ID: 'background_image'.
 */
class BackgroundImageCacheContext extends BackgroundImageBaseCacheContext {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Background Image');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->backgroundImage ? $this->backgroundImage->getImageHash() : 0;
  }

}

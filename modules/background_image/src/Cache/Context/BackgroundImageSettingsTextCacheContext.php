<?php

namespace Drupal\background_image\Cache\Context;

/**
 * Cache context ID: 'background_image.settings.text'.
 */
class BackgroundImageSettingsTextCacheContext extends BackgroundImageBaseCacheContext {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Background Image: Text Setting');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    $context = $this->backgroundImage ? $this->backgroundImage->getSettingsHash('text') : 0;
    // Add entity specific target if text has tokens.
    if ((!isset($name) || $name === 'text' || $name === 'text.value') && $this->backgroundImage->hasEntityToken() && ($entity = $this->manager->getEntityFromCurrentRoute())) {
      $context .= ":{$entity->getEntityTypeId()}:{$entity->id()}";
    }
    return $context;
  }

}

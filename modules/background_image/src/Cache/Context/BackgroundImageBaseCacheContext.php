<?php

namespace Drupal\background_image\Cache\Context;

use Drupal\background_image\BackgroundImageManagerInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;

/**
 * Provides a base for Background Image based contexts.
 */
abstract class BackgroundImageBaseCacheContext implements CacheContextInterface {

  /**
   * @var \Drupal\background_image\BackgroundImageManagerInterface
   */
  protected $manager;

  /**
   * @var \Drupal\background_image\BackgroundImageInterface|null
   */
  protected $backgroundImage;

  /**
   * Constructs a BackgroundImageCacheContext object.
   *
   * @param \Drupal\background_image\BackgroundImageManagerInterface $background_image_manager
   */
  public function __construct(BackgroundImageManagerInterface $background_image_manager) {
    $this->manager = $background_image_manager;
    $this->backgroundImage = $background_image_manager->getBackgroundImage();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    $metadata = new CacheableMetadata();
    if ($this->backgroundImage) {
      $metadata->addCacheTags($this->backgroundImage->getCacheTags());
    }
    return $metadata;
  }

}

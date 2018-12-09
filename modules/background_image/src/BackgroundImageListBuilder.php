<?php

namespace Drupal\background_image;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for background_image entity.
 *
 * @ingroup background_image
 */
class BackgroundImageListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['#attached']['library'][] = 'background_image/admin';
    $build['table']['#empty'] = $this->t('There are no @label to display.', ['@label' => $this->entityType->getPluralLabel()]);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['image'] = ['data' => $this->t('Background Image'), 'class' => ['small']];
    $header['type'] = $this->t('Type');
    $header['settings'] = $this->t('Settings');
    $header['operations'] = ['data' => $this->t('Operations'), 'class' => ['op', 'small']];
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\background_image\BackgroundImageInterface $entity */
    $background_image = $entity instanceof BackgroundImageInterface ? $entity : NULL;
    if (!$background_image) {
      return parent::buildRow($entity);
    }

    $manager = BackgroundImageManager::service();

    $build = [];
    if ($file = $background_image->getImageFile()) {
      $build = [
        '#theme' => 'image_style',
        '#style_name' => $manager->getPreloadImageStyle(),
        '#uri' => $file->getFileUri(),
      ];

      // Add the file entity to the cache dependencies.
      // This will clear our cache when this entity updates.
      /** @var \Drupal\Core\Render\RendererInterface $renderer */
      $renderer = \Drupal::service('renderer');
      $renderer->addCacheableDependency($build, $file);
    }

    if ($build) {
      $row['image'] = [
        'data' => [
          '#type' => 'link',
          '#title' => $build,
          '#url' => $background_image->toUrl(),
        ],
      ];
    }
    else {
      $row['image'] = $this->t('N/A');
    }
    $row['type'] = $background_image->getTypeLabel(TRUE);
    $settings = [];
    foreach ($background_image->getSettings()->getOverridden() as $key => $value) {
      if ($key === 'preload') {
        continue;
      }
      if (is_bool($value)) {
        $value = $value ? $this->t('Yes') : $this->t('No');
      }
      $label = Unicode::ucfirst(str_replace(['_', '-', '.'], ' ', $key));
      $settings[] = is_array($value) ? $label : $label . ': ' . $value;
    }
    $row['settings'] = $settings ? implode(', ', $settings) : $this->t('None');
    return $row + parent::buildRow($background_image);
  }

}

<?php

namespace Drupal\Tests\background_image\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Defines a base-class for background-image tests.
 */
abstract class BackgroundImageTestBase extends BrowserTestBase {

  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
    compareFiles as drupalCompareFiles;
  }

  /**
   * Adds a image.
   *
   * @param int $type
   *   The type (-1, 0, 1, 4).
   * @param string $label
   *   The form label.
   */
  public function addImageForm($type, $label) {
    $this->drupalGet('admin/config/media/background_image/add');
    $edit = [
      'type' => $type,
      'label' => $label,
    ];
    $this->drupalPostForm(NULL, $edit, t('Continue'));
  }

}

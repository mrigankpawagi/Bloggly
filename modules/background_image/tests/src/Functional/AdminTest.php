<?php

namespace Drupal\Tests\background_image\Functional;

/**
 * Tests adding image and viewing them through UI.
 *
 * @group background_image
 */
class AdminTest extends BackgroundImageTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'user',
    'node',
    'field',
    'image',
    'options',
    'background_image',
  ];

  /**
   * An administrative user with permission administer contact forms.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'administer background image',
      'add background image',
      'edit background image',
    ]);
  }

  /**
   * Tests view builder functionality.
   */
  public function testListAddEdit() {
    $collection = 'admin/config/media/background_image';
    // Login as admin user.
    $this->drupalLogin($this->adminUser);

    $this->drupalGet($collection);
    $this->assertSession()->pageTextContains('There are no background images to display.');

    // Create first valid global image.
    $label = 'Global label';
    $this->addImageForm(-1, $label);

    $files = $this->drupalGetTestFiles('image');
    $file = \Drupal::service('file_system')->realpath($files[0]->uri);
    $edit = [
      'files[image_0]' => $file,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $session = $this->assertSession();
    $session->pageTextContains("The background image $label has been added.");
    $session->pageTextContains("Global: $label");

    $this->drupalGet('admin/config/media/background_image/1/edit');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $session->pageTextContains("Global: $label");
  }

}

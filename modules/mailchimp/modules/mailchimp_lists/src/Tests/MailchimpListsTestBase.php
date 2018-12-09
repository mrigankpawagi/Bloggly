<?php

namespace Drupal\mailchimp_lists\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\mailchimp_lists_test\MailchimpListsConfigOverrider;

include_once __DIR__ . "/../../../../lib/mailchimp-api-php/tests/src/Client.php";
include_once __DIR__ . "/../../../../lib/mailchimp-api-php/tests/src/Mailchimp.php";
include_once __DIR__ . "/../../../../lib/mailchimp-api-php/tests/src/MailchimpTestHttpResponse.php";
include_once __DIR__ . "/../../../../lib/mailchimp-api-php/tests/src/MailchimpTestHttpClient.php";
include_once __DIR__ . "/../../../../lib/mailchimp-api-php/tests/src/MailchimpLists.php";

/**
 * Sets up Mailchimp Lists module tests.
 */
abstract class MailchimpListsTestBase extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    \Drupal::configFactory()->addOverride(new MailchimpListsConfigOverrider());
  }

}

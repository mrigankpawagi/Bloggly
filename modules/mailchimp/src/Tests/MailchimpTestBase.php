<?php

namespace Drupal\mailchimp\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\mailchimp_test\MailchimpConfigOverrider;

include_once __DIR__ . "/../../lib/mailchimp-api-php/tests/src/Client.php";
include_once __DIR__ . "/../../lib/mailchimp-api-php/tests/src/Mailchimp.php";
include_once __DIR__ . "/../../lib/mailchimp-api-php/tests/src/MailchimpTestHttpClient.php";
include_once __DIR__ . "/../../lib/mailchimp-api-php/tests/src/MailchimpTestHttpResponse.php";

/**
 * Sets up Mailchimp module tests.
 */
abstract class MailchimpTestBase extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    \Drupal::configFactory()->addOverride(new MailchimpConfigOverrider());
  }

}

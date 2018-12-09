<?php

namespace Drupal\mailchimp_campaign\Tests;

use Drupal\mailchimp_campaign_test\MailchimpCampaignConfigOverrider;
use Drupal\simpletest\WebTestBase;

include_once __DIR__ . "/../../../../lib/mailchimp-api-php/tests/src/Client.php";
include_once __DIR__ . "/../../../../lib/mailchimp-api-php/tests/src/Mailchimp.php";
include_once __DIR__ . "/../../../../lib/mailchimp-api-php/tests/src/MailchimpTestHttpClient.php";
include_once __DIR__ . "/../../../../lib/mailchimp-api-php/tests/src/MailchimpTestHttpResponse.php";
include_once __DIR__ . "/../../../../lib/mailchimp-api-php/tests/src/MailchimpCampaigns.php";

/**
 * Sets up Mailchimp Campaign module tests.
 */
abstract class MailchimpCampaignTestBase extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    \Drupal::configFactory()->addOverride(new MailchimpCampaignConfigOverrider());
  }

}

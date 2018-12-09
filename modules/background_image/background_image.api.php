<?php

/**
 * @file
 * API hooks and alters for the background_image module.
 */

/**
 * Provides extensions a way to alter the CSS template and variables used.
 *
 * @param array $variables
 *   An associative array of key/value pairs containing:
 *   - base_class: (string) The base class used in all CSS selectors.
 *   - background_image_class: (string) The specific background image selector.
 *   - fallback_url: (string) The fallback background image URL.
 *   - media_queries: (array) An associative array of media queries:
 *     - image_style: (string) The image style used.
 *     - multiplier: (string) The breakpoint multiplier, if it exists.
 *     - query: (string) The media query rule.
 *     - url: (string) The background image URL.
 * @param string $template_filename
 *   The path to the *.css.twig template file that will be processed with the
 *   above $variables.
 * @param \Drupal\background_image\BackgroundImageInterface $background_image
 *   The current Background Image entity being processed.
 *
 * @see \Drupal\background_image\Controller\BackgroundImageCssController::buildCss
 */
function hook_background_image_css_template_alter(array &$variables, &$template_filename, \Drupal\background_image\BackgroundImageInterface $background_image) {
  // Let a theme handle this.
  $template_filename = drupal_get_path('theme', 'my_subtheme') . '/templates/background_image.css.twig';
}

/**
 * Provides extensions a way to alter the image after its been built.
 *
 * @param array $element
 *   The background image render array element.
 * @param array $context
 *   An associative array containing:
 *   - background_image: \Drupal\background_image\BackgroundImageInterface
 *     The current Background Image entity being processed.
 *   - entity: \Drupal\Core\Entity\EntityInterface
 *     The entity determined to be associated with the background image.
 *     Note: this may not be set.
 */
function hook_background_image_build_alter(array &$element, array &$context) {
  /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
  $entity = $context['entity'];

  // Add some custom field to float over the image.
  if ($entity && $entity->hasField('field_custom_field') && ($custom_field = $entity->get('field_custom_field')->view())) {
    $element['custom_field'] = $custom_field;
  }
}

/**
 * Provides extensions a way to alter the text before it's been tokenized.
 *
 * @param array $element
 *   The text render array element.
 * @param array $context
 *   An associative array containing:
 *   - background_image: \Drupal\background_image\BackgroundImageInterface
 *     The current Background Image entity being processed.
 *   - entity: \Drupal\Core\Entity\EntityInterface
 *     The entity determined to be associated with the background image.
 *     Note: this may not be set.
 *   - token_data: The token data to use for any token replacements.
 *   - token_options: The token options to use for any token replacements.
 */
function hook_background_image_text_build_alter(array &$element, array &$context) {
  /** @var \Drupal\background_image\BackgroundImageInterface $background_image */
  $background_image = $context['background_image'];
  /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
  $entity = $context['entity'];

  // Prepend a header to all full viewport background images.
  if ($background_image->getSetting('full_viewport') && $entity && $entity->hasField('field_header') && ($header = $entity->get('field_header')->value)) {
    $element['#text'] = '<h2>' . $header . '</h2>' . $element['#text'];
  }
}

/**
 * Provides extensions a way to alter the text after it's been tokenized.
 *
 * @param array $element
 *   The text render array element.
 * @param array $context
 *   An associative array containing:
 *   - background_image: \Drupal\background_image\BackgroundImageInterface
 *     The current Background Image entity being processed.
 *   - entity: \Drupal\Core\Entity\EntityInterface
 *     The entity determined to be associated with the background image.
 *     Note: this may not be set.
 *   - token_data: The token data to use for any token replacements.
 *   - token_options: The token options to use for any token replacements.
 */
function hook_background_image_text_after_build_alter(array &$element, array &$context) {
  // Remove any empty tags that may exist due to tokens not matching.
  $element['#text'] = preg_replace('/(?:\n|\s*)?<([^>\s]+)[^>]*>(?:(?:<br\s*\/?>|&nbsp;|&thinsp;|&ensp;|&emsp;|&#8201;|&#8194;|&#8195;|\n|\r|\s)*)*<\/\1>(?:\n|\s*)?/mi', '', $element['#text']);
}

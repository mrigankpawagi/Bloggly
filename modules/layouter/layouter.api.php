<?php
/**
 * @file
 * Contains declaration of hook_layouter_templates_info.
 *
 * @see hook_layouter_templates_info().
 */

/**
 * Write your custom layout templates by implementing this hook.
 * Template must have structure:
 *
 * LAYOUT_NAME = [
 *   'title' => t('LAYOUT_TITLE'),
 *   'fields' => [
 *     'FIELD_NAME' => [
 *       'type' => 'FIELD_TYPE', (Required, allowed: text, image)
 *       'title' => t('FIELD_TITLE'), (Optional)
 *       'description' => t('ADDITIONAL_DESCRIPTION'), (Optional)
 *     ],
 *   ],
 *   'theme' => 'LAYOUT_THEME_NAME',
 * ];
 */
function hook_layouter_templates_info() {}

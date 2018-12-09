<?php

namespace Drupal\layouter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\image\ImageStyleInterface;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeTypeInterface;

/**
 * Provides admin page settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('layouter.settings');

    $text_formats = filter_formats();
    $text_formats_options = [];
    $default_text_formats = $config->get('text_formats');
    foreach ($text_formats as $format) {
      // Pick formats with disabled html filtering.
      if ($format->filters()->get('filter_html')->status == FALSE) {
        $format_id = $format->get('format');
        $text_formats_options[$format_id] = $format->get('name');
        // Adds not specified default values.
        if (!isset($default_text_formats[$format_id])) {
          $default_text_formats[$format_id] = 0;
        }
      }
    }

    $content_types = NodeType::loadMultiple();
    $content_types_options = [];
    $default_content_types = $config->get('content_types');
    foreach ($content_types as $type) {
      /** @var NodeTypeInterface $type */
      $name = $type->get('type');
      $content_types_options[$name] = $name;
      // Adds not specified default values.
      if (!isset($default_content_types[$name])) {
        $default_content_types[$name] = 0;
      }
    }

    $image_styles = ImageStyle::loadMultiple();
    $image_styles_options = [];
    $default_image_styles = $config->get('image_styles');
    foreach ($image_styles as $style) {
      /** @var ImageStyleInterface $style */
      $style_name = $style->get('name');
      $image_styles_options[$style_name] = $style->get('label');
      // Adds not specified default values.
      if (!isset($default_image_styles[$style_name])) {
        $default_image_styles[$style_name] = 0;
      }
    }

    $stream_wrappers = \Drupal::service('stream_wrapper_manager');
    $scheme_options = $stream_wrappers
      ->getDescriptions(StreamWrapperInterface::WRITE_VISIBLE);
    $default_uri_scheme = $config->get('uri_scheme') ?: 0;

    $form['text_formats'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Text formats'),
      '#description' => $this->t('Choose input formats for which you want to enable Layouter<br />Attention : Only formats with disabled html filter are allowed'),
      '#options' => $text_formats_options,
      '#default_value' => $default_text_formats,
    ];
    $form['content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content types'),
      '#description' => $this->t('Choose content types for which you want to enable Layouter'),
      '#options' => $content_types_options,
      '#default_value' => $default_content_types,
    ];
    $form['image_styles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Image styles'),
      '#description' => $this->t('Choose image styles for which you want to enable alter the original image in Layouter'),
      '#options' => $image_styles_options,
      '#default_value' => $default_image_styles,
    ];
    $form['uri_scheme'] = [
      '#type' => 'radios',
      '#title' => $this->t('Upload destination'),
      '#description' => $this->t('Select where the final files should be stored. Private file storage has significantly more overhead than public files, but allows restricted access to files within this field.'),
      '#required' => TRUE,
      '#options' => $scheme_options,
      '#default_value' => $default_uri_scheme,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('layouter.settings');
    $data = [
      'text_formats' => $form_state->getValue('text_formats'),
      'content_types' => $form_state->getValue('content_types'),
      'image_styles' => $form_state->getValue('image_styles'),
      'uri_scheme' => $form_state->getValue('uri_scheme'),
    ];
    $config->setData($data)->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['layouter.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layouter_settings_form';
  }

}

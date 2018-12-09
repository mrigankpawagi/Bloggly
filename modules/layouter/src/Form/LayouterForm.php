<?php

namespace Drupal\layouter\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;

/**
 * Provides multistep ajax form for an layout choice.
 */
class LayouterForm extends FormBase {

  /**
   * The steps count of multiform.
   *
   * @var integer
   */
  protected $steps = 2;

  /**
   * All layouter templates invoked by hook_layouter_templates_info.
   *
   * @var array
   */
  protected $templates;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->templates = \Drupal::moduleHandler()
      ->invokeAll('layouter_templates_info');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layouter_multistep_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $textarea_id = NULL) {
    $step = $form_state->get('step');
    if (!$step) {
      $step = 1;
      $form_state->set('step', $step);
    }

    $form['#prefix'] = '<div id="layouter-form-wrapper" class="layouter-form">';
    $form['#sufix'] = '</div>';
    $form['errors'] = [];

    $button_label = '';
    if ($step == 1) {
      $options = [];
      foreach ($this->templates as $id => $params) {
        $options[$id] = $params['title']->render();
      }

      $form['data']['type'] = [
        '#title' => $this->t('Choose the layout'),
        '#type' => 'radios',
        '#options' => $options,
        '#required' => TRUE,
        '#after_build' => ['::processLayoutTypeRadios'],
      ];

      $button_label = $this->t('Next');
    }
    if ($step == 2) {
      $this->buildLayouterFields($form, $form_state);
      $button_label = $this->t('Submit');
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $button_label,
      '#name' => 'submit_button',
      '#attributes' => [
        'class' => ['use-ajax-submit'],
      ],
      '#ajax' => [
        'callback' => '::ajaxResponse',
      ],
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#name' => 'cancel_button',
      '#submit' => ['::cancelForm'],
      '#limit_validation_errors' => [],
      '#attributes' => [
        'class' => ['use-ajax-submit'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $step = $form_state->get('step');
    if ($step < $this->steps) {
      $form_state->setRebuild();
      if ($step == 1) {
        $form_state->set('type', $form_state->getValue('type'));
      }
    }
    $step++;
    $form_state->set('step', $step);
  }

  /**
   * Ajax callback prints rebuilded form.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function ajaxResponse(array &$form, FormStateInterface $form_state) {
    if ($form_state->hasAnyErrors()) {
      $form['errors']['#prefix'] = '<div class="messages messages--error">';
      $form['errors']['#suffix'] = '</div>';
      $form['errors']['#markup'] = '';
      $mess = drupal_get_messages('error');
      foreach ($mess as $errors) {
        foreach ($errors as $error) {
          $form['errors']['#markup'] .= $error . '<br />';
        }
      }
      $form_state->clearErrors();
    }

    $step = $form_state->get('step');
    $response = new AjaxResponse();
    if ($step == $this->steps + 1 && $form_state->isExecuted()) {
      $textarea_id = $form_state->getBuildInfo()['args'][0];
      $content = $this->buildResponseHtml($form_state);

      $command = new CloseModalDialogCommand();
      $response->addCommand($command);
      $command = new InvokeCommand(
        NULL,
        'layouterAddContent',
        [$textarea_id, $content]
      );
      $response->addCommand($command);
    }
    else {
      $command = new ReplaceCommand('#layouter-form-wrapper', $form);
      $response->addCommand($command);
    }
    return $response;
  }

  /**
   * Form submit handler triggered by 'cancel' button. Closes popup form.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function cancelForm(array &$form, FormStateInterface $form_state) {
    // Delete loaded files.
    if ($form_state->hasFileElement()) {
      foreach ($form_state->get('fields') as $name => $params) {
        if ($params['type'] == 'image') {
          $input = $form_state->getUserInput();
          if (!empty($input[$name]['fids'])) {
            File::load($input[$name]['fids'])->delete();
          }
        }
      }
    }
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    $form_state->setResponse($response);
  }

  /**
   * Wraps each radio button item in the 'radios' set into additional container.
   * After-build callback.
   *
   * @param array
   *   $element - form element.
   *
   * @return array
   */
  public function processLayoutTypeRadios($element) {
    foreach ($this->templates as $id => $params) {
      if (!empty($element[$id])) {
        $element[$id]['#prefix'] = '<div class="layouter-radio-wrapper '
          . $id . '" title="' . $params['title'] . '">';
        $element[$id]['#suffix'] = '</div>';
      }
    }
    return $element;
  }

  /**
   * Returns the 'textarea' form item.
   *
   * @param string $name
   *   Field name.
   * @param array $params
   *   Additional parameters for field from layouter template.
   *
   * @return array
   *   Renderable array for textfield.
   */
  private function textContentHandler($name, array $params) {
    $result[$name] = [
      '#type' => 'textarea',
      '#title' => $params['title'],
      '#description' => $params['description'],
      '#rows' => 10,
      '#required' => 1,
    ];
    return $result;
  }

  /**
   * Returns the form item with actual settings, to upload image.
   *
   * @param string $name
   *   Field name.
   * @param array $params
   *   Additional parameters for field from layouter template.
   *
   * @return array
   *   Renderable array for file field.
   */
  private function imageContentHandler($name, array $params) {
    $fieldset_name = 'image_' . $name;
    // Fieldset for image fields.
    $result['#type'] = 'fieldset';
    $result['#title'] = $params['title'];

    // Prepare managed_file field.
    $allowed_extensions = ['png gif jpeg jpg'];
    $max_upload_size_mb = (int) ini_get('upload_max_filesize');
    $max_upload_size = [$max_upload_size_mb * 1024 * 1024];
    $image_field_description = $this->t(
      'Files must be less than @size.',
      ['@size' => format_size($max_upload_size[0])]
    );
    $image_field_description .= '<br />' .
      $this->t(
        'Allowed file types: @extensions.',
        ['@extensions' => $allowed_extensions[0]]
      );
    if (!empty($params['description'])) {
      $image_field_description .= '<br />' . $params['description'];
    }
    $location_scheme = \Drupal::config('layouter.settings')->get('uri_scheme');

    // Add managed_file field and textfield for image alternative text.
    $result[$name] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Image'),
      '#field_name' => 'layouter_image',
      '#description' => $image_field_description,
      '#required' => 1,
      '#upload_location' => $location_scheme . '://layouter_images',
      '#upload_validators' => [
        'file_validate_extensions' => $allowed_extensions,
        'file_validate_size' => [$max_upload_size],
      ],
    ];
    $result[$name . '_alt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Alternative text'),
    ];

    // Prepare list field with allowed image styles.
    if (\Drupal::currentUser()->hasPermission('administer image styles')) {
      $url = Url::fromRoute('entity.image_style.collection')->getInternalPath();
      $description = $this->t('You can also')
        . ' <a href="/' . $url . '" target="_blank">'
        . $this->t('add your own image style') . '</a> '
        . $this->t('if you need to.');
      $admin_image_style_description = $description;
    }
    else {
      $admin_image_style_description = '';
    }
    $image_styles = \Drupal::config('layouter.settings')->get('image_styles');
    $image_styles_options['none'] = 'none';
    foreach ($image_styles as $k => $v) {
      if ($v != '0') {
        $image_styles_options[$k] = $v;
      }
    }

    // Add image style field to result.
    $result[$name . '_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Image style'),
      '#required' => TRUE,
      '#options' => $image_styles_options,
      '#description' => $admin_image_style_description,
    ];

    $fieldset[$fieldset_name] = $result;
    return $fieldset;
  }

  /**
   * Builds HTML that will be added to textarea.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return null
   *   Content for output.
   */
  private function buildResponseHtml(FormStateInterface $form_state) {
    $content = [
      '#theme' => $this->templates[$form_state->get('type')]['theme'],
    ];
    $fields = $form_state->get('fields');
    foreach ($fields as $field_name => $fiels_params) {
      switch ($fiels_params['type']) {
        case 'image':
          $image_fid = $form_state->getValue($field_name)[0];
          $image = File::load($image_fid);
          /** @var FileInterface $image */
          $image->setPermanent();
          $image_style = $form_state->getValue($field_name . '_style');
          if ($image_style == 'none') {
            $image_content = [
              '#theme' => 'image',
            ];
          }
          else {
            $image_content = [
              '#theme' => 'image_style',
              '#style_name' => $image_style,
            ];
          }
          $image_content['#uri'] = $image->getFileUri();
          $image_content['#alt'] = $form_state->getValue($field_name . '_alt');
          $content['#' . $field_name] = render($image_content);
          break;

        case 'text':
          $content['#' . $field_name] = $form_state->getValue($field_name);
          break;

      }
    }
    return render($content);
  }

  /**
   * Sets up and builds fields from selected layouter template.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  private function buildLayouterFields(array &$form, FormStateInterface $form_state) {
    $type = $form_state->get('type');
    $fields = $this->templates[$type]['fields'];
    if (!is_null($fields)) {
      $form_state->set('fields', $fields);
      $form['data'] = [];
      foreach ($fields as $name => $params) {
        $params['description'] = ($params['description']) ?: '';
        if ($params['type'] == 'image') {
          $params['title'] = ($params['title']) ?: $this->t('Image settings');
          $form['data'] += $this->imageContentHandler($name, $params);
        }
        if ($params['type'] == 'text') {
          $params['title'] = ($params['title']) ?: $this->t('Text');
          $form['data'] += $this->textContentHandler($name, $params);
        }
      }
    }
  }

}

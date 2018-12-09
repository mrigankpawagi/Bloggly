<?php

namespace Drupal\background_image\Form;

use Drupal\background_image\BackgroundImageFormTrait;
use Drupal\background_image\BackgroundImageManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class BackgroundImageSettingsForm.
 *
 * @ingroup background_image
 */
class BackgroundImageSettingsForm extends ConfigFormBase {

  use BackgroundImageFormTrait;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['background_image.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'background_image_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['#attached']['library'][] = 'background_image/admin';

    $inline_entity_form = \Drupal::moduleHandler()->moduleExists('inline_entity_form');
    $inline_entity_form_tip = $this->t('The Inline Entity Form module is required to embed forms.');

    $background_image_manager = BackgroundImageManager::service();
    $supported_entity_types = $background_image_manager->getSupportedEntityTypes();
    $config = $this->config('background_image.settings');

    $form['vertical_tabs'] = ['#type' => 'vertical_tabs'];

    // Enabled Entities.
    $form['entities'] = [
      '#group' => 'vertical_tabs',
      '#type' => 'details',
      '#title' => $this->t('Entities'),
      '#description' => $this->t('Configure how entities and bundles are associated with background images. Only the "full" view mode of an entity will trigger the condition in which the associated background image will be used.'),
      '#open' => TRUE,
    ];

    if (!$inline_entity_form) {
      $form['entities']['inline_entity_form']['#markup'] = t('If you wish to embed the background image form while editing an entity, you will need install the @inline_entity_form module.', [
        '@inline_entity_form' => Link::fromTextAndUrl('Inline Entity Form', Url::fromUri('https://www.drupal.org/project/inline_entity_form')) ->toString(),
      ]);
    }

    // Iterate over each supported entity type.
    $form['entities']['table'] = [
      '#type' => 'table',
      '#sticky' => TRUE,
      '#attributes' => ['class' => ['entities']],
      '#header' => [
        ['data' => $this->t('Entity/Bundle')],
        ['data' => $this->t('Enable'), 'class' => ['small']],
        ['data' => $this->t('Embed'), 'class' => ['small']],
        ['data' => $this->t('Require'), 'class' => ['small']],
      ],
      '#tree' => FALSE,
    ];
    $rows = [];
    foreach ($supported_entity_types as $type => $entity_type) {
      $row = [];
      $row[] = [
        'data' => $entity_type->getLabel(),
        'colspan' => 5,
        'class' => ['entity-type'],
      ];
      $rows[] = $row;
      if ($bundles = $background_image_manager->getEntityTypeBundles($entity_type)) {
        foreach ($bundles as $bundle => $info) {
          $row = [];
          $row[] = ['data' => $info['label'], 'class' => ['entity-bundle']];

          // Enable.
          $form["entities-$type-$bundle-enable"] = [
            '#type' => 'checkbox',
            '#parents' => ['entities', $type, $bundle, 'enable'],
            '#default_value' => !!$config->get(implode('.', ['entities', $type, $bundle, 'enable'])),
          ];

          // Embed/group.
          $form["entities-$type-$bundle-embed"] = [
            '#type' => 'container',
          ];
          $form["entities-$type-$bundle-embed"]['embed'] = [
            '#type' => 'checkbox',
            '#parents' => ['entities', $type, $bundle, 'embed'],
            '#default_value' => !!$config->get(implode('.', ['entities', $type, $bundle, 'embed'])),
          ];
          $form["entities-$type-$bundle-embed"]['group'] = [
            '#type' => 'select',
            '#parents' => ['entities', $type, $bundle, 'group'],
            '#default_value' => $config->get(implode('.', ['entities', $type, $bundle, 'group'])),
            '#attributes' => ['title' => $this->t('The group in which the background image form will be embedded into (if it exists).')],
            '#options' => [
              '' => $this->t('Default'),
              'advanced' => $this->t('Advanced'),
            ],
          ];
          if (!$inline_entity_form) {
            $form["entities-$type-$bundle-embed"]['embed']['#disabled'] = TRUE;
            $form["entities-$type-$bundle-embed"]['embed']['#default_value'] = FALSE;
            $form["entities-$type-$bundle-embed"]['embed']['#attributes']['title'] = $inline_entity_form_tip;
            $form["entities-$type-$bundle-embed"]['group']['#disabled'] = TRUE;
            $form["entities-$type-$bundle-embed"]['group']['#default_value'] = '';
          }
          else {
            self::addState($form["entities-$type-$bundle-embed"]['embed'], ['enabled'], $form["entities-$type-$bundle-enable"], [
              '*' => ['checked' => TRUE],
            ]);
            self::addState($form["entities-$type-$bundle-embed"]['group'], ['enabled'], $form["entities-$type-$bundle-enable"], [
              '*' => ['checked' => TRUE],
            ]);
            self::addState($form["entities-$type-$bundle-embed"]['group'], ['enabled'], $form["entities-$type-$bundle-embed"], [
              '*' => ['checked' => TRUE],
            ]);
          }

          // Require
          $form["entities-$type-$bundle-require"] = [
            '#type' => 'checkbox',
            '#parents' => ['entities', $type, $bundle, 'require'],
            '#default_value' => !!$config->get(implode('.', ['entities', $type, $bundle, 'require'])),
          ];
          self::addState($form["entities-$type-$bundle-require"], ['enabled'], $form["entities-$type-$bundle-enable"], [
            '*' => ['checked' => TRUE],
          ]);

          // Add the form elements to the row.
          foreach (['enable', 'embed', 'require'] as $property) {
            $row[] = ['data' => &$form["entities-$type-$bundle-$property"], 'class' => ['small']];
            $form["entities-$type-$bundle-$property"]['#printed'] = TRUE;
          }

          // Add the row to the rows.
          $rows[] = $row;
        }
      }
    }
    $form['entities']['table']['#rows'] = $rows;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('background_image.settings')
      ->merge(array_filter($form_state->cleanValues()->getValues()))
      ->save();
  }

}

<?php

namespace Drupal\sitemap;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\Component\Utility\Xss;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

/**
 * Defines a helper class for stuff related to views data.
 */
class SitemapHelper {

  use StringTranslationTrait;
  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * SitemapHelper constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository, ModuleHandlerInterface $module_handler) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Sets options based on admin input parameters for rendering.
   *
   * @param array $options
   *   The array of options to the sitemap theme.
   * @param string $option_string
   *   The string index given from the admin form to match.
   * @param int $equal_param
   *   Result of param test, 0 or 1.
   * @param string $set_string
   *   Index of option to set, or the option name.
   * @param bool $set_value
   *   The option, on or off, or strings or ints for other options.
   */
  public function setOption(array &$options, $option_string, $equal_param, $set_string, $set_value) {
    $config = $this->configFactory->get('sitemap.settings');
    if ($config->get($option_string) == $equal_param) {
      $options[$set_string] = $set_value;
    }
  }

  /**
   * Render the latest maps for the taxonomy tree.
   *
   * @param object $voc
   *   Vocabulary entity.
   *
   * @return array
   *   Returns a renderable array for sitemap taxonomies.
   */
  public function getTerms($voc) {
    $output = '';
    $options = array();

    if ($this->moduleHandler->moduleExists('taxonomy') && !empty($voc)) {
      $output = $this->getTaxonomyTree($voc->get('vid'), $voc->get('name'), $voc->get('description'));
      $this->setOption($options, 'show_titles', 1, 'show_titles', TRUE);
    }

    return $output;
  }

  /**
   * Render the taxonomy tree.
   *
   * @param string $vid
   *   Vocabulary id.
   * @param string $name
   *   An optional name for the tree. (Default: NULL).
   * @param string $description
   *   $description An optional description of the tree. (Default: NULL).
   *
   * @return string
   *   A string representing a rendered tree.
   */
  public function getTaxonomyTree($vid, $name = NULL, $description = NULL) {
    $output = '';
    $options = array();
    $attributes = new Attribute();
    $config = $this->configFactory->get('sitemap.settings');

    if ($this->moduleHandler->moduleExists('forum') && $vid == $this->configFactory->get('forum.settings')->get('vocabulary')) {
      $title = Link::fromTextAndUrl($name, Url::fromRoute('forum.index'))->toString();
      $threshold = $config->get('forum_threshold');
      $forum_link = TRUE;
    }
    else {
      $title = $name;
      $threshold = $config->get('term_threshold');
      $forum_link = FALSE;
    }

    $last_depth = -1;

    $description = !empty($description) && $config->get('show_description') ? '<div class="description">' . Xss::filterAdmin($description) . "</div>\n" : '';

    $depth = $config->get('vocabulary_depth');
    if ($depth <= -1) {
      $depth = NULL;
    }

    $tree = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vid, 0, $depth, TRUE);
    /** @var \Drupal\taxonomy\TermInterface $term */
    foreach ($tree as $term) {
      // Get the term translated if exists.
      $term = $this->entityRepository->getTranslationFromContext($term);
      // don't render the term is no access allowed.
      if (!$term->access('view')) {
        continue;
      }
      $term->count = sitemap_taxonomy_term_count_nodes($term->id());
      if ($term->count <= $threshold) {
        continue;
      }

      // Adjust the depth of the <ul> based on the change
      // in $term->depth since the $last_depth.
      if ($term->depth > $last_depth) {
        for ($i = 0; $i < ($term->depth - $last_depth); $i++) {
          $output .= "\n<ul>";
        }
      }
      elseif ($term->depth == $last_depth) {
        $output .= '</li>';
      }
      elseif ($term->depth < $last_depth) {
        for ($i = 0; $i < ($last_depth - $term->depth); $i++) {
          $output .= "</li>\n</ul>\n</li>";
        }
      }
      // Display the $term.
      $output .= "\n<li>";
      $term_item = '';
      if ($forum_link) {
        $link_options = [
          array('attributes' => array('title' => $term->description->value)),
        ];
        $term_item .= Link::fromTextAndUrl($term->label(), Url::fromRoute('forum.page', array('taxonomy_term' => $term->id()), $link_options))->toString();
      }
      elseif ($term->count || $config->get('vocabulary_show_links')) {
        $link_options = [
          array('attributes' => array('title' => $term->description->value)),
        ];
        $term_item .= Link::fromTextAndUrl($term->label(), Url::fromRoute('entity.taxonomy_term.canonical', array('taxonomy_term' => $term->id()), $link_options))->toString();
      }
      else {
        $term_item .= $term->label();
      }
      if ($config->get('show_count')) {
        $span_title = $this->formatPlural($term->count, '1 item has this term', '@count items have this term');
        $term_item .= " <span title=\"" . $span_title . "\">(" . $term->count . ")</span>";
      }

      // RSS depth.
      $rss_depth = $config->get('rss_taxonomy');
      if ($config->get('show_rss_links') != 0 && ($rss_depth == -1 || $term->depth < $rss_depth)) {
        $feed_icon = array(
          '#theme' => 'sitemap_feed_icon',
          '#url' => 'taxonomy/term/' . $term->id() . '/feed',
          '#name' => $term->label(),
        );
        $rss_link = \Drupal::service('renderer')->render($feed_icon);

        if ($config->get('show_rss_links') == 1) {
          $term_item .= ' ' . $rss_link;
        }
        else {
          $attributes->addClass('sitemap-rss-left');
          $term_item = $rss_link . ' ' . $term_item;
        }
      }

      // Add an alter hook for modules to manipulate the taxonomy term output.
      $this->moduleHandler->alter(array('sitemap_taxonomy_term', 'sitemap_taxonomy_term_' . $term->id()), $term_item, $term);

      $output .= $term_item;

      // Reset $last_depth in preparation for the next $term.
      $last_depth = $term->depth;
    }

    // Bring the depth back to where it began, -1.
    if ($last_depth > -1) {
      for ($i = 0; $i < ($last_depth + 1); $i++) {
        $output .= "</li>\n</ul>\n";
      }
    }
    $this->setOption($options, 'show_titles', 1, 'show_titles', TRUE);

    $attributes->addClass('sitemap-box-terms', 'sitemap-box-terms-' . $vid);

    // Only provide content where terms can be listed
    if (!empty($output)) {

      $sitemap_box = [
        'title' => $title,
        'content' => ['#markup' => $description . $output],
        'attributes' => $attributes,
        'options' => $options,
      ];

      return $sitemap_box;
    }
  }

}

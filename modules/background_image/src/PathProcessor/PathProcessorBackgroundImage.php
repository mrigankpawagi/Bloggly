<?php

namespace Drupal\background_image\PathProcessor;

use Drupal\background_image\BackgroundImageManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a path processor to rewrite background image CSS URLs.
 *
 * @see \Drupal\image\PathProcessor\PathProcessorImageStyles
 */
class PathProcessorBackgroundImage implements InboundPathProcessorInterface {

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The stream wrapper manager service.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Constructs a new PathProcessorImageStyles object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The Module Handler service.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The Stream Wrapper Manager service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, StreamWrapperManagerInterface $stream_wrapper_manager) {
    $this->moduleHandler = $module_handler;
    $this->streamWrapperManager = $stream_wrapper_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $directory_path = $this->streamWrapperManager->getViaScheme('public')->getDirectoryPath();
    // Immediately return if not a background image CSS path.
    if (strpos($path, '/' . $directory_path . '/background_image/css/') !== 0 && strpos($path, '/system/files/background_image/css/') !== 0) {
      return $path;
    }

    // Redirect minified CSS to non-minified CSS if site does is not able
    // to minify CSS. Note: the Background Image Manager service cannot be
    // injected because it will cause a circular reference. Instead, it must
    // be accessed during runtime.
    if (preg_match('/\.min\.css$/', $path) && !BackgroundImageManager::service()->getCssMinifier()) {
      $path = preg_replace('/\.min\.css$/', '.css', $path);
    }

    return $path;
  }

}

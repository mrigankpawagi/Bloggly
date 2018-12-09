<?php

namespace Drupal\background_image\EventSubscriber;

use Drupal\stage_file_proxy\EventDispatcher\AlterExcludedPathsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StageFileProxySubscriber implements EventSubscriberInterface {

  /**
   * @param \Drupal\stage_file_proxy\EventDispatcher\AlterExcludedPathsEvent $event
   */
  public function getExcludedPaths(AlterExcludedPathsEvent $event) {
    $event->addExcludedPath('/background_image/css/');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['stage_file_proxy.alter_excluded_paths'][] = ['getExcludedPaths'];
    return $events;
  }

}

<?php

namespace Drupal\background_image;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Background Image entity.
 *
 * @see \Drupal\background_image\Entity\BackgroundImage.
 */
class BackgroundImageAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * Link the activities to the permissions. checkAccess is called with the
   * $operation as defined in the routing.yml file.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @type \Drupal\background_image\Entity\BackgroundImage $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view background image');

      case 'edit':
      case 'update':
        if ($entity->getOwnerId() === $account->id()) {
          return AccessResult::allowedIfHasPermission($account, 'edit own background image')->orIf(AccessResult::allowedIfHasPermission($account, 'edit background image'));
        }
        return AccessResult::allowedIfHasPermission($account, 'edit background image');

      case 'delete':
        if ($entity->getOwnerId() === $account->id()) {
          return AccessResult::allowedIfHasPermission($account, 'delete own background image')->orIf(AccessResult::allowedIfHasPermission($account, 'delete background image'));
        }
        return AccessResult::allowedIfHasPermission($account, 'delete background image');
    }
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   *
   * Separate from the checkAccess because the entity does not yet exist, it
   * will be created during the 'add' process.
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add background image');
  }

}

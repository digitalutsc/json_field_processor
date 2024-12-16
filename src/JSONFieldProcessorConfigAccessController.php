<?php

namespace Drupal\json_field_processor;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the json_field_processor_config entity.
 *
 * This controller checks access permissions for the json_field_processor_config entity.
 *
 * @see \Drupal\json_field_processor\Entity\JSONFieldProcessorConfig
 *
 * @ingroup json_field_processor
 */
class JSONFieldProcessorConfigAccessController extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        // If the user has the overarching permission, allow all operations.
        if ($account->hasPermission('administer json field processor')) {
            return AccessResult::allowed();
        }

        // Otherwise, check operation-specific permissions.
        switch ($operation) {
        case 'view':
            // Allow access for view operation (or customize this further if needed).
            return AccessResult::allowedIfHasPermission($account, 'view json field processor configurations');

        case 'edit':
            return AccessResult::allowedIfHasPermission($account, 'edit json field processor configurations');

        case 'delete':
            return AccessResult::allowedIfHasPermission($account, 'delete json field processor configurations');
        }

        // Default to the parent's access logic for unknown operations.
        return parent::checkAccess($entity, $operation, $account);
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = null)
    {
        // If the user has the overarching permission, allow create access.
        if ($account->hasPermission('administer json field processor')) {
            return AccessResult::allowed();
        }

        // Otherwise, forbid creation unless additional permissions are defined.
        return AccessResult::allowedIfHasPermission($account, 'create json_field_processor_config');
    }

}

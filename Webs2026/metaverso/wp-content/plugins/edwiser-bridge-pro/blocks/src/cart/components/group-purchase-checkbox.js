import { Checkbox } from '@mantine/core';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { useGroupPurchase } from '../hooks/use-group-purchase';

function GroupPurchaseCheckbox({ cartItems }) {
  const {
    isGroupPurchase,
    groupPurchaseError,
    groupPurchaseWarning,
    isLoading,
    showCheckbox,
    updateGroupPurchaseStatus,
  } = useGroupPurchase(cartItems);

  if (!showCheckbox) {
    return null;
  }

  return (
    <div className="eb-cart__checkbox-group">
      <Checkbox
        label={__('Add all product in same group', 'edwiser-bridge-pro')}
        checked={isGroupPurchase}
        onChange={(e) => updateGroupPurchaseStatus(e.currentTarget.checked)}
        disabled={isLoading}
      />
      {groupPurchaseError && (
        <span className="eb-cart__checkbox-group-error">
          {groupPurchaseError.includes('<ul>') ? (
            <div dangerouslySetInnerHTML={{ __html: groupPurchaseError }} />
          ) : (
            __(groupPurchaseError, 'edwiser-bridge-pro')
          )}
        </span>
      )}
      {groupPurchaseWarning && (
        <span className="eb-cart__checkbox-group-warning">
          {groupPurchaseWarning.includes('<ul>') ? (
            <div dangerouslySetInnerHTML={{ __html: groupPurchaseWarning }} />
          ) : (
            __(groupPurchaseWarning, 'edwiser-bridge-pro')
          )}
        </span>
      )}
    </div>
  );
}

export default GroupPurchaseCheckbox;

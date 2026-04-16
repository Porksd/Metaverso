import { Checkbox, Textarea } from '@mantine/core';
import React from 'react';

function OrderNote({
  customerNote,
  updateCustomerNote,
  showCustomerNote,
  toggleCustomerNote,
  validationError,
}) {
  return (
    <div className="eb-checkout__order-note">
      <Checkbox
        label="Add a note to your order"
        checked={showCustomerNote}
        onChange={(e) => toggleCustomerNote(e.currentTarget.checked)}
      />
      {showCustomerNote && (
        <Textarea
          placeholder="Notes about your order, e.g. special notes for delivery."
          value={customerNote}
          onChange={(e) => updateCustomerNote(e.target.value)}
          error={validationError}
        />
      )}
    </div>
  );
}

export default OrderNote;

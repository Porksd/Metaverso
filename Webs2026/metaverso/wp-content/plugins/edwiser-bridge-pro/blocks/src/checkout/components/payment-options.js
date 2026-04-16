import { Radio, RadioGroup, Skeleton } from '@mantine/core';
import React from 'react';

function PaymentOptions({
  paymentOptions,
  selectedPaymentMethod,
  updatePaymentMethod,
}) {
  return (
    <div className="order-summary__payment-options">
      <div className="payment-options__header">
        <h2 className="payment-options__title">Payment options</h2>
      </div>
      <div className="payment-options__content">
        <RadioGroup
          value={selectedPaymentMethod}
          onChange={updatePaymentMethod}
        >
          {paymentOptions.map((option) => (
            <div key={option.value} className="payment-option">
              <Radio value={option.id} label={option.title} />
              {selectedPaymentMethod === option.id && (
                <div
                  className="payment-option__content"
                  dangerouslySetInnerHTML={{ __html: option.fields }}
                />
              )}
            </div>
          ))}
        </RadioGroup>
      </div>
    </div>
  );
}

export default PaymentOptions;

export function PaymentOptionsSkeleton() {
  return (
    <div className="order-summary__payment-options">
      <div className="payment-options__header">
        <Skeleton w={150} h={28} />
      </div>
      <div className="payment-options__content">
        <Skeleton w={120} h={20} radius="lg" />
        <div>
          <Skeleton w={'100%'} h={12} />
          <Skeleton w={'100%'} h={12} mt={4} />
          <Skeleton w={'50%'} h={12} mt={4} />
        </div>
        <Skeleton w={100} h={20} radius="lg" />
        <Skeleton w={120} h={20} radius="lg" />
      </div>
    </div>
  );
}

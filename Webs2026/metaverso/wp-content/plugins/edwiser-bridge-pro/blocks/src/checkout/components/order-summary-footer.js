import React from 'react';
import { __ } from '@wordpress/i18n';
import { Skeleton } from '@mantine/core';
import { Icons } from './icons';

function OrderSummaryFooter({
  privacyPolicy,
  placeOrderBtnText,
  subscriptions,
  handleCheckout,
  isPlacingOrder,
  paymentError,
}) {
  const btnText = __(
    subscriptions.length > 0 ? placeOrderBtnText : 'Place Order'
  );

  return (
    <div className="order-summary__footer">
      <p
        className="footer__desc"
        dangerouslySetInnerHTML={{ __html: privacyPolicy }}
      />
      <button
        className="footer__btn-place-order"
        onClick={handleCheckout}
        disabled={isPlacingOrder}
      >
        {isPlacingOrder ? <Icons.loader /> : btnText}
      </button>
      {paymentError && (
        <span className="footer__place-order-error">{paymentError}</span>
      )}
    </div>
  );
}

export default OrderSummaryFooter;

export function OrderSummaryFooterSkeleton() {
  return (
    <div className="order-summary__footer">
      <div>
        <Skeleton w="100%" h={16} />
        <Skeleton w="100%" h={16} mt={2} />
        <Skeleton w="60%" h={16} mt={2} />
      </div>
      <Skeleton w="100%" h={40} />
    </div>
  );
}

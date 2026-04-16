import React from 'react';
import AddCoupon, { AddCouponSkeleton } from './add-coupon';
import OrderSummaryFooter, {
  OrderSummaryFooterSkeleton,
} from './order-summary-footer';
import OrderSummaryTotal, {
  OrderSummaryTotalSkeleton,
} from './order-summary-total';
import OrderSummaryRecurringTotal from './order-summary-recurring-total';
import OrderSummaryItems, {
  OrderSummaryItemsSkeleton,
} from './order-summary-items';
import PaymentOptions, { PaymentOptionsSkeleton } from './payment-options';
import { Skeleton } from '@mantine/core';

function OrderSummary({
  cartItems,
  cartTotals,
  cartCoupons,
  couponsEnabled,
  handleApplyCoupon,
  handleRemoveCoupon,
  privacyPolicy,
  placeOrderBtnText,
  needsShipping,
  includesTax,
  singleTaxTotal,
  shippingRates,
  subscriptions,
  paymentOptions,
  handleCheckout,
  selectedPaymentMethod,
  updatePaymentMethod,
  isPlacingOrder,
  paymentError,
  applyCouponError,
}) {
  return (
    <aside className="eb-checkout__order-summary">
      <div className="order-summary__header">
        <h2 className="order-summary__title">Order summary</h2>
      </div>
      <OrderSummaryItems cartItems={cartItems} />
      {couponsEnabled && (
        <AddCoupon
          handleApplyCoupon={handleApplyCoupon}
          applyCouponError={applyCouponError}
        />
      )}
      <OrderSummaryTotal
        cartCoupons={cartCoupons}
        cartTotals={cartTotals}
        handleRemoveCoupon={handleRemoveCoupon}
        includesTax={includesTax}
        singleTaxTotal={singleTaxTotal}
        shippingRates={shippingRates}
        subscriptions={subscriptions}
      />
      {subscriptions.length > 0 && (
        <OrderSummaryRecurringTotal
          subscriptions={subscriptions}
          includesTax={includesTax}
          singleTaxTotal={singleTaxTotal}
          needsShipping={needsShipping}
        />
      )}
      <PaymentOptions
        paymentOptions={paymentOptions}
        selectedPaymentMethod={selectedPaymentMethod}
        updatePaymentMethod={updatePaymentMethod}
      />
      <OrderSummaryFooter
        privacyPolicy={privacyPolicy}
        placeOrderBtnText={placeOrderBtnText}
        subscriptions={subscriptions}
        handleCheckout={handleCheckout}
        isPlacingOrder={isPlacingOrder}
        paymentError={paymentError}
      />
    </aside>
  );
}

export default OrderSummary;

export function OrderSummarySkeleton() {
  return (
    <aside className="eb-checkout__order-summary">
      <div className="order-summary__header">
        <Skeleton h={28} w={180} />
      </div>
      <OrderSummaryItemsSkeleton />
      <AddCouponSkeleton />
      <OrderSummaryTotalSkeleton />
      <PaymentOptionsSkeleton />
      <OrderSummaryFooterSkeleton />
    </aside>
  );
}

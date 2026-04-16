import { MantineProvider, Skeleton } from '@mantine/core';
import { __ } from '@wordpress/i18n';
import React from 'react';
import OrderSummary, { OrderSummarySkeleton } from './components/order-summary';
import CheckoutMain, { CheckoutMainSkeleton } from './components/checkout-main';
import { useCheckout } from './hooks/use-checkout';
import { useSummary } from './hooks/use-summary';

function Checkout() {
  const {
    isLoadingSummary,
    cartItems,
    cartTotals,
    cartCoupons,
    needsShipping,
    subscriptions,
    shippingRates,
    applyCouponError,
    handleApplyCoupon,
    handleRemoveCoupon,
    getCart,
  } = useSummary();
  const {
    isLoadingCheckout,
    initialShippingAddress,
    initialBillingAddress,
    customerId,
    loginUrl,
    enableGuestCheckout,
    enableLogin,
    enableSignup,
    enableSubscriptionsSignup,
    customFields,
    groups,
    privacyPolicy,
    placeOrderBtnText,
    couponsEnabled,
    includesTax,
    singleTaxTotal,
    countries,
    states,
    postcodeValid,
    localeFields,
    updateStates,
    updateLocaleFields,
    validatePostcode,
    updateShippingRate,
    updateAddress,
    billingStates,
    billingLocaleFields,
    updateBillingStates,
    updateBillingLocaleFields,
    customerNote,
    updateCustomerNote,
    giftPurchaseData,
    updateGiftPurchaseData,
    groupsData,
    updateGroupsData,
    customFieldsData,
    updateCustomFieldsData,
    customerPassword,
    updateCustomerPassword,
    showCustomerNote,
    toggleCustomerNote,
    giftPurchase,
    toggleGiftPurchase,
    validationErrors,
    handleCheckout,
    shippingAddressRef,
    billingAddressRef,
    contactInformationRef,
    paymentOptions,
    selectedPaymentMethod,
    updatePaymentMethod,
    isPlacingOrder,
    paymentError,
    enableGiftPurchase,
  } = useCheckout(getCart);

  return (
    <MantineProvider>
      <div className="eb-checkout__wrapper">
        {isLoadingCheckout && isLoadingSummary ? (
          <Skeleton w={140} h={40} />
        ) : (
          <h1 className="eb-checkout__page-title">Checkout</h1>
        )}
        <main className="eb-checkout">
          {isLoadingCheckout ? (
            <CheckoutMainSkeleton />
          ) : (
            <CheckoutMain
              initialShippingAddress={initialShippingAddress}
              initialBillingAddress={initialBillingAddress}
              customerNote={customerNote}
              customFields={customFields}
              needsShipping={needsShipping}
              shippingRates={shippingRates}
              subscriptions={subscriptions}
              includesTax={includesTax}
              updateShippingRate={updateShippingRate}
              customerId={customerId}
              enableGuestCheckout={enableGuestCheckout}
              enableLogin={enableLogin}
              enableSignup={enableSignup}
              enableSubscriptionsSignup={enableSubscriptionsSignup}
              loginUrl={loginUrl}
              countries={countries}
              states={states}
              localeFields={localeFields}
              updateStates={updateStates}
              updateLocaleFields={updateLocaleFields}
              billingStates={billingStates}
              billingLocaleFields={billingLocaleFields}
              updateBillingStates={updateBillingStates}
              updateBillingLocaleFields={updateBillingLocaleFields}
              validatePostcode={validatePostcode}
              postcodeValid={postcodeValid}
              updateAddress={updateAddress}
              groups={groups}
              updateCustomerNote={updateCustomerNote}
              giftPurchaseData={giftPurchaseData}
              updateGiftPurchaseData={updateGiftPurchaseData}
              groupsData={groupsData}
              updateGroupsData={updateGroupsData}
              customFieldsData={customFieldsData}
              updateCustomFieldsData={updateCustomFieldsData}
              customerPassword={customerPassword}
              updateCustomerPassword={updateCustomerPassword}
              showCustomerNote={showCustomerNote}
              toggleCustomerNote={toggleCustomerNote}
              enableGiftPurchase={enableGiftPurchase}
              giftPurchase={giftPurchase}
              toggleGiftPurchase={toggleGiftPurchase}
              validationErrors={validationErrors}
              shippingAddressRef={shippingAddressRef}
              billingAddressRef={billingAddressRef}
              contactInformationRef={contactInformationRef}
            />
          )}
          {isLoadingSummary ? (
            <OrderSummarySkeleton />
          ) : (
            <OrderSummary
              cartItems={cartItems}
              cartTotals={cartTotals}
              cartCoupons={cartCoupons}
              needsShipping={needsShipping}
              subscriptions={subscriptions}
              applyCouponError={applyCouponError}
              handleApplyCoupon={handleApplyCoupon}
              handleRemoveCoupon={handleRemoveCoupon}
              couponsEnabled={couponsEnabled}
              includesTax={includesTax}
              singleTaxTotal={singleTaxTotal}
              privacyPolicy={privacyPolicy}
              placeOrderBtnText={placeOrderBtnText}
              shippingRates={shippingRates}
              paymentOptions={paymentOptions}
              handleCheckout={handleCheckout}
              selectedPaymentMethod={selectedPaymentMethod}
              updatePaymentMethod={updatePaymentMethod}
              isPlacingOrder={isPlacingOrder}
              paymentError={paymentError}
            />
          )}
        </main>
      </div>
    </MantineProvider>
  );
}

export default Checkout;

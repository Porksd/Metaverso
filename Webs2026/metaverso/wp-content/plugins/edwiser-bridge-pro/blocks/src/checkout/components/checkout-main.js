import React, { useEffect, useState } from 'react';
import BillingAddress from './billing-address';
import ContactInformation, {
  ContactInformationSkeleton,
} from './contact-information';
import CustomFields from './custom-fields';
import GiftPurchase from './gift-purchase';
import GroupsName from './groups-name';
import OrderNote from './order-note';
import ShippingAddress, { ShippingAddressSkeleton } from './shipping-address';
import ShippingOptions from './shipping-options';
import { Skeleton } from '@mantine/core';

function CheckoutMain({
  customFields,
  needsShipping,
  shippingRates,
  subscriptions,
  includesTax,
  updateShippingRate,
  initialShippingAddress,
  initialBillingAddress,
  customerId,
  enableGuestCheckout,
  enableLogin,
  enableSignup,
  enableSubscriptionsSignup,
  loginUrl,
  countries,
  states,
  localeFields,
  updateStates,
  updateLocaleFields,
  postcodeValid,
  validatePostcode,
  updateAddress,
  groups,
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
  enableGiftPurchase,
  giftPurchase,
  toggleGiftPurchase,
  validationErrors,
  billingStates,
  billingLocaleFields,
  updateBillingStates,
  updateBillingLocaleFields,
  shippingAddressRef,
  billingAddressRef,
  contactInformationRef,
}) {
  const [sameBillingAddress, setSameBillingAddress] = useState(false);

  useEffect(() => {
    if (initialShippingAddress && initialBillingAddress) {
      const addressFields = [
        'first_name',
        'last_name',
        'country',
        'state',
        'address_1',
        'address_2',
        'city',
        'postcode',
        'phone',
      ];

      const areAddressesSame = addressFields.every(
        (field) =>
          initialShippingAddress[field] === initialBillingAddress[field]
      );

      setSameBillingAddress(areAddressesSame);
    }
  }, [initialShippingAddress, initialBillingAddress]);

  return (
    <div className="eb-checkout__main">
      <ContactInformation
        ref={contactInformationRef}
        customerEmail={initialBillingAddress.email}
        customerId={customerId}
        enableGuestCheckout={enableGuestCheckout}
        enableLogin={enableLogin}
        enableSignup={enableSignup}
        enableSubscriptionsSignup={enableSubscriptionsSignup}
        loginUrl={loginUrl}
        updateAddress={updateAddress}
        shippingAddress={initialShippingAddress}
        billingAddress={initialBillingAddress}
        customerPassword={customerPassword}
        updateCustomerPassword={updateCustomerPassword}
        isSubscriptionProduct={subscriptions.length > 0}
      />
      {needsShipping && (
        <ShippingAddress
          ref={shippingAddressRef}
          sameBillingAddress={sameBillingAddress}
          setSameBillingAddress={setSameBillingAddress}
          shippingAddress={initialShippingAddress}
          billingAddress={initialBillingAddress}
          countries={countries}
          states={states}
          localeFields={localeFields}
          updateStates={updateStates}
          updateLocaleFields={updateLocaleFields}
          validatePostcode={validatePostcode}
          postcodeValid={postcodeValid}
          updateAddress={updateAddress}
        />
      )}
      {(!sameBillingAddress || !needsShipping) && (
        <BillingAddress
          ref={billingAddressRef}
          billingAddress={initialBillingAddress}
          shippingAddress={initialShippingAddress}
          countries={countries}
          billingStates={billingStates}
          billingLocaleFields={billingLocaleFields}
          updateBillingStates={updateBillingStates}
          updateBillingLocaleFields={updateBillingLocaleFields}
          validatePostcode={validatePostcode}
          postcodeValid={postcodeValid}
          updateAddress={updateAddress}
        />
      )}
      {needsShipping && (
        <ShippingOptions
          subscriptions={subscriptions}
          shippingRates={shippingRates}
          includesTax={includesTax}
          updateShippingRate={updateShippingRate}
        />
      )}
      <OrderNote
        customerNote={customerNote}
        updateCustomerNote={updateCustomerNote}
        showCustomerNote={showCustomerNote}
        toggleCustomerNote={toggleCustomerNote}
        validationError={validationErrors.customerNote}
      />
      {enableGiftPurchase && (
        <GiftPurchase
          giftPurchaseData={giftPurchaseData}
          updateGiftPurchaseData={updateGiftPurchaseData}
          giftPurchase={giftPurchase}
          toggleGiftPurchase={toggleGiftPurchase}
          validationErrors={validationErrors.giftPurchase}
        />
      )}
      {groups?.length > 0 && (
        <GroupsName
          groups={groups}
          groupsData={groupsData}
          updateGroupsData={updateGroupsData}
          validationErrors={validationErrors.groupsData}
        />
      )}
      {customFields?.length > 0 && (
        <CustomFields
          customFields={customFields}
          customFieldsData={customFieldsData}
          updateCustomFieldsData={updateCustomFieldsData}
          validationErrors={validationErrors.customFields}
        />
      )}
    </div>
  );
}

export default CheckoutMain;

export function CheckoutMainSkeleton() {
  return (
    <div className="eb-checkout__main">
      <ContactInformationSkeleton />
      <ShippingAddressSkeleton />
      <Skeleton w={190} h={20} />
      <Skeleton w={190} h={20} />
    </div>
  );
}

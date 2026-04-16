import { MantineProvider, Skeleton } from '@mantine/core';
import { __ } from '@wordpress/i18n';
import React from 'react';
import CartItem, { CartItemSkeleton } from './components/cart-item';
import CartTotal, { CartTotalSkeleton } from './components/cart-total';
import EmptyCart from './components/empty-cart';
import GroupPurchaseCheckbox from './components/group-purchase-checkbox';
import { useCart } from './hooks/use-cart';

function Cart() {
  const {
    cartItems,
    cartTotal,
    itemCount,
    couponsEnabled,
    cartCoupon,
    loadingCartItems,
    loadingCartTotal,
    shopPageUrl,
    checkoutUrl,
    includesTax,
    singleTaxTotal,
    handleQuantityUpdate,
    handleRemoveItem,
    handleApplyCoupon,
    handleRemoveCoupon,
    shippingAddress,
    shippingRates,
    needsShipping,
    fetchCartTotal,
    subscriptions,
    enableShippingCalculator,
    applyCouponError,
  } = useCart();

  return (
    <MantineProvider>
      <div className="eb-cart__wrapper">
        <div className="eb-cart__left">
          <div className="eb-cart__left-header">
            <h2 className="eb-title">{__('Cart', 'edwiser-bridge-pro')}</h2>
            {loadingCartItems ? (
              <>
                <Skeleton width={100} height={24} radius="sm" />
                <Skeleton width={250} height={20} radius="sm" />
              </>
            ) : (
              <>
                <p className="eb-cart__total-items">
                  {__('Total items : ', 'edwiser-bridge-pro')}
                  <span>{__(itemCount, 'edwiser-bridge-pro')}</span>
                </p>
                <GroupPurchaseCheckbox cartItems={cartItems} />
              </>
            )}
          </div>
          {loadingCartItems ? (
            Array.from({ length: 3 }).map((_, index) => (
              <CartItemSkeleton key={index} />
            ))
          ) : cartItems.length > 0 ? (
            <div className="eb-cart__item-wrapper">
              {cartItems.map((item, index) => (
                <CartItem
                  key={item.key}
                  item={item}
                  itemIndex={index}
                  onQuantityUpdate={handleQuantityUpdate}
                  onRemoveItem={handleRemoveItem}
                  includesTax={includesTax}
                />
              ))}
            </div>
          ) : (
            <EmptyCart shopPageUrl={shopPageUrl} />
          )}
        </div>
        <div className="eb-cart__right">
          {loadingCartItems ? (
            <CartTotalSkeleton />
          ) : (
            <CartTotal
              cartTotal={cartTotal}
              couponsEnabled={couponsEnabled}
              cartCoupon={cartCoupon}
              onApplyCoupon={handleApplyCoupon}
              onRemoveCoupon={handleRemoveCoupon}
              loadingCartTotal={loadingCartTotal}
              checkoutUrl={checkoutUrl}
              includesTax={includesTax}
              singleTaxTotal={singleTaxTotal}
              shippingAddress={shippingAddress}
              shippingRates={shippingRates}
              needsShipping={needsShipping}
              fetchCartTotal={fetchCartTotal}
              subscriptions={subscriptions}
              enableShippingCalculator={enableShippingCalculator}
              applyCouponError={applyCouponError}
            />
          )}
        </div>
      </div>
    </MantineProvider>
  );
}

export default Cart;

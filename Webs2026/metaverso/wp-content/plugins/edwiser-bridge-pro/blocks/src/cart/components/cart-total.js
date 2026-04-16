import { Skeleton } from '@mantine/core';
import { __, sprintf } from '@wordpress/i18n';
import React from 'react';
import {
  formatDiscountPrice,
  formatSubTotalPrice,
  formatTaxPrices,
  formatTotalPrice,
} from '../utils';
import ApplyCoupon from './apply-coupon';
import ShippingDetails from './shipping-details';
import { Icons } from './icons';
import SubscriptionTotal from './subscription-total';

function CartTotal({
  cartTotal,
  couponsEnabled,
  cartCoupon,
  onApplyCoupon,
  onRemoveCoupon,
  loadingCartTotal,
  checkoutUrl,
  includesTax,
  singleTaxTotal,
  shippingAddress,
  shippingRates,
  needsShipping,
  fetchCartTotal,
  subscriptions,
  enableShippingCalculator,
  applyCouponError,
}) {
  const subTotal = formatSubTotalPrice(cartTotal, includesTax);
  const couponDiscount = formatDiscountPrice(cartTotal, includesTax);
  const total = formatTotalPrice(cartTotal);
  const hasTaxLines = cartTotal.tax_lines?.length > 0;

  return (
    <div className="eb-cart__total">
      <div className="eb-cart__total-top">
        <h4>{__('Cart total', 'edwiser-bridge-pro')}</h4>
        {couponsEnabled && (
          <ApplyCoupon
            onApplyCoupon={onApplyCoupon}
            applyCouponError={applyCouponError}
          />
        )}

        {/* Subtotal */}
        {loadingCartTotal ? (
          <div className="sub-total">
            <Skeleton width={52} height={24} radius="sm" />
            <Skeleton width={56} height={24} radius="sm" />
          </div>
        ) : (
          <div className="sub-total">
            <p className="label">{__('Subtotal', 'edwiser-bridge-pro')}</p>
            <p className="price">{__(subTotal, 'edwiser-bridge-pro')}</p>
          </div>
        )}

        {/* Coupon Discount */}
        {cartCoupon.length > 0 && (
          <div>
            <div className="coupon-discount">
              {loadingCartTotal ? (
                <>
                  <Skeleton width={52} height={24} radius="sm" />
                  <Skeleton width={56} height={24} radius="sm" />
                </>
              ) : (
                <>
                  <p className="label">
                    {__('Discount', 'edwiser-bridge-pro')}
                  </p>
                  <p className="price">
                    -{__(couponDiscount, 'edwiser-bridge-pro')}
                  </p>
                </>
              )}
            </div>
            <div className="applied-coupons">
              {cartCoupon.map((coupon) => (
                <div key={coupon.code} className="eb-cart__coupon-applied">
                  <p>{__(coupon.code, 'edwiser-bridge-pro')}</p>
                  <button
                    className="eb-btn__remove-coupon"
                    onClick={() => onRemoveCoupon(coupon.code)}
                  >
                    <Icons.cross />
                  </button>
                </div>
              ))}
            </div>
          </div>
        )}

        {needsShipping && (
          <ShippingDetails
            shippingAddress={shippingAddress}
            shippingRates={shippingRates}
            includesTax={includesTax}
            fetchCartTotal={fetchCartTotal}
            subscriptions={subscriptions}
            enableShippingCalculator={enableShippingCalculator}
          />
        )}

        {/* Taxes (if not included in price) */}
        {!includesTax && (
          <div className="eb-cart__taxes">
            {loadingCartTotal ? (
              <div className="tax">
                <Skeleton width={52} height={24} radius="sm" />
                <Skeleton width={56} height={24} radius="sm" />
              </div>
            ) : hasTaxLines ? (
              cartTotal.tax_lines.map((tax, index) => (
                <div className="tax" key={index}>
                  <p className="label">{__(tax.name, 'edwiser-bridge-pro')}</p>
                  <p className="price">
                    {__(
                      formatTaxPrices(cartTotal, 'tax', index),
                      'edwiser-bridge-pro'
                    )}
                  </p>
                </div>
              ))
            ) : (
              singleTaxTotal && (
                <div className="tax">
                  <p className="label">{__('Taxes', 'edwiser-bridge-pro')}</p>
                  <p className="price">
                    {__(
                      formatTaxPrices(cartTotal, 'total-tax'),
                      'edwiser-bridge-pro'
                    )}
                  </p>
                </div>
              )
            )}
          </div>
        )}
      </div>

      {subscriptions.length > 0 && (
        <SubscriptionTotal
          subscriptions={subscriptions}
          includesTax={includesTax}
          singleTaxTotal={singleTaxTotal}
          needsShipping={needsShipping}
        />
      )}

      <div className="eb-cart__total-bottom">
        {loadingCartTotal ? (
          <div className="total">
            <Skeleton width={42} height={24} radius="sm" />
            <Skeleton width={85} height={24} radius="sm" />
          </div>
        ) : (
          <div>
            <div className="total">
              <p className="label">
                {subscriptions.length > 0
                  ? __('Total due today', 'edwiser-bridge-pro')
                  : __('Total', 'edwiser-bridge-pro')}
              </p>
              <p className="price">{__(total, 'edwiser-bridge-pro')}</p>
            </div>
            {includesTax && (
              <div className="including-tax">
                {hasTaxLines ? (
                  <>
                    {__('Including', 'edwiser-bridge-pro')}{' '}
                    {cartTotal.tax_lines
                      .map((tax, index) => {
                        const formattedTax = formatTaxPrices(
                          cartTotal,
                          'tax',
                          index
                        );
                        return `${formattedTax} ${tax.name}`;
                      })
                      .join(', ')}
                  </>
                ) : (
                  singleTaxTotal && (
                    <>
                      {/* translators: %s = formatted tax amount (e.g. "$10.00") */}
                      {sprintf(
                        __('Including %s in taxes', 'edwiser-bridge-pro'),
                        formatTaxPrices(cartTotal, 'total-tax')
                      )}
                    </>
                  )
                )}
              </div>
            )}
          </div>
        )}
        <a href={checkoutUrl} className="eb-btn eb-btn__checkout">
          {__('Proceed to checkout', 'edwiser-bridge-pro')}
        </a>
      </div>
    </div>
  );
}

export default CartTotal;

export function CartTotalSkeleton() {
  return (
    <div className="eb-cart__total">
      <div className="eb-cart__total-top">
        <h4>{__('Cart total', 'edwiser-bridge-pro')}</h4>
        <Skeleton width={265} height={40} radius="sm" />
        <div className="sub-total">
          <Skeleton width={52} height={24} radius="sm" />
          <Skeleton width={56} height={24} radius="sm" />
        </div>
        <div className="coupon-discount">
          <Skeleton width={52} height={24} radius="sm" />
          <Skeleton width={56} height={24} radius="sm" />
        </div>
      </div>
      <div className="eb-cart__total-bottom">
        <div className="total">
          <Skeleton width={42} height={24} radius="sm" />
          <Skeleton width={85} height={24} radius="sm" />
        </div>
        <Skeleton width={265} height={40} radius="sm" />
      </div>
    </div>
  );
}

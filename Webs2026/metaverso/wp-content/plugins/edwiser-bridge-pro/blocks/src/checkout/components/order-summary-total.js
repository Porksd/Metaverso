import { __ } from '@wordpress/i18n';
import React from 'react';
import {
  formatDiscountPrice,
  formatShippingPrices,
  formatSubTotalPrice,
  formatTaxPrices,
  formatTotalPrice,
} from '../utils';
import { Icons } from './icons';
import { Skeleton } from '@mantine/core';

function OrderSummaryTotal({
  cartTotals,
  cartCoupons,
  handleRemoveCoupon,
  includesTax,
  singleTaxTotal,
  shippingRates,
  subscriptions,
}) {
  const subTotal = formatSubTotalPrice(cartTotals, includesTax);
  const couponDiscount = formatDiscountPrice(cartTotals, includesTax);
  const total = formatTotalPrice(cartTotals);
  const hasTaxLines = cartTotals.tax_lines?.length > 0;
  const primaryShippingOption = shippingRates[0]?.shipping_rates.find(
    (option) => option.selected === true
  );

  return (
    <div className="order-summary__total">
      <div className="total__sub-total">
        <span className="label">{__('Subtotal')}</span>
        <span className="value">{__(subTotal)}</span>
      </div>
      {cartCoupons.length > 0 && (
        <div>
          <div className="total__coupon-discount">
            <span className="label">{__('Discount')}</span>
            <span className="value">-{__(couponDiscount)}</span>
          </div>
          <div className="total__applied-coupons">
            {cartCoupons.map((coupon) => (
              <div key={coupon.code} className="applied-coupon">
                <span>{coupon.code}</span>
                <button
                  className="remove-coupon"
                  onClick={() => handleRemoveCoupon(coupon.code)}
                >
                  <Icons.cross />
                </button>
              </div>
            ))}
          </div>
        </div>
      )}
      {primaryShippingOption && (
        <div className="total__delivery-price">
          <div>
            <span className="label">{__('Delivery')}</span>
            <span className="label-sm">{__(primaryShippingOption?.name)}</span>
          </div>
          <span className="value">
            {__(formatShippingPrices(primaryShippingOption, includesTax))}
          </span>
        </div>
      )}
      {!includesTax && (
        <div className="total__taxes">
          {hasTaxLines
            ? cartTotals.tax_lines.map((tax, index) => (
                <div className="tax" key={index}>
                  <span className="label">{__(tax.name)}</span>
                  <span className="price">
                    {__(formatTaxPrices(cartTotals, 'tax', index))}
                  </span>
                </div>
              ))
            : singleTaxTotal && (
                <div className="tax">
                  <span className="label">{__('Taxes')}</span>
                  <span className="price">
                    {__(formatTaxPrices(cartTotals, 'total-tax'))}
                  </span>
                </div>
              )}
        </div>
      )}
      <div>
        <div className="total__total">
          <span className="label">
            {__(subscriptions.length > 0 ? 'Total due today' : 'Total')}
          </span>
          <span className="value">{__(total)}</span>
        </div>
        {includesTax && (
          <div className="total__including-tax">
            {hasTaxLines ? (
              <>
                {__('Including')}{' '}
                {cartTotals.tax_lines
                  .map((tax, index) => {
                    const formattedTax = formatTaxPrices(
                      cartTotals,
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
                  {__('Including ')}
                  {formatTaxPrices(cartTotals, 'total-tax')}
                  {__(' in taxes')}
                </>
              )
            )}
          </div>
        )}
      </div>
    </div>
  );
}

export default OrderSummaryTotal;

export function OrderSummaryTotalSkeleton() {
  return (
    <div className="order-summary__total">
      <div className="total__sub-total">
        <Skeleton w={56} h={18} />
        <Skeleton w={60} h={20} />
      </div>
      <div className="total__delivery-price">
        <Skeleton w={56} h={18} />
        <Skeleton w={50} h={20} />
      </div>
      <div className="total__taxes">
        <div className="tax">
          <Skeleton w={30} h={18} />
          <Skeleton w={50} h={20} />
        </div>
        <div className="tax">
          <Skeleton w={30} h={18} />
          <Skeleton w={50} h={20} />
        </div>
      </div>
      <div className="total__total">
        <Skeleton w={48} h={24} />
        <Skeleton w={90} h={24} />
      </div>
    </div>
  );
}

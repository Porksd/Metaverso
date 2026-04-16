import { __ } from '@wordpress/i18n';
import React from 'react';
import {
  decodeHTMLEntities,
  formatItemPrice,
  formatItemTotalPrice,
} from '../utils';
import { Skeleton } from '@mantine/core';

function OrderSummaryItems({ cartItems, includesTax }) {
  return (
    <div className="order-summary__items">
      {cartItems.map((item) => {
        const onSale = item.prices.regular_price !== item.prices.sale_price;
        const billingPeriod = item?.extensions?.subscriptions?.billing_period;
        const billingInterval =
          item?.extensions?.subscriptions?.billing_interval;
        const subscriptionLength =
          item?.extensions?.subscriptions?.subscription_length;

        return (
          <div key={item.key} className="item">
            <div className="item__description">
              <div className="item__title-wrapper">
                <h3 className="item__title">
                  {__(decodeHTMLEntities(item.name))}
                </h3>
                <div className="item__quantity-badge">{item.quantity}</div>
              </div>
              <div>
                {onSale && (
                  <span className="item__regular-price">
                    {__(formatItemPrice(item.prices, 'regular'))}
                  </span>
                )}
                <span className="item__price">
                  {__(formatItemPrice(item.prices, 'sale'))}
                </span>
                {(item.type === 'subscription' ||
                  item.type === 'subscription_variation') && (
                  <span className="item__subscription-interval">
                    {subscriptionLength === billingInterval ? 'for ' : 'every'}{' '}
                    {subscriptionLength === billingInterval
                      ? billingInterval
                      : billingInterval > 1 && billingInterval}{' '}
                    {billingPeriod}
                    {billingInterval > 1 && 's'}
                  </span>
                )}
              </div>
              {item.item_data.length > 0 && (
                <div className="item__subscriptions-data">
                  {item.item_data.map((data, index) => (
                    <div key={index} className="subscription-data">
                      <span className="label">{__(data.name)}:</span>{' '}
                      <span className="value">
                        {__(decodeHTMLEntities(data.value))}
                      </span>
                    </div>
                  ))}
                </div>
              )}
              {item.variation.length > 0 && (
                <div className="item__variations-data">
                  {item.variation.map((variation, index) => (
                    <div key={index} className="variation-data">
                      <span className="label">{__(variation.attribute)}:</span>{' '}
                      <span className="value">{__(variation.value)}</span>
                    </div>
                  ))}
                </div>
              )}
              {item?.meta?.self_enroll_enabled && (
                <div className="item__group-purchase-tag">
                  Group purchase enabled
                </div>
              )}
            </div>
            <div className="item__subtotal">
              {(item.type === 'subscription' ||
                item.type === 'subscription_variation') && (
                <span>Due today</span>
              )}
              <span className="item__subtotal-price">
                {__(formatItemTotalPrice(item.totals, includesTax))}
              </span>
            </div>
          </div>
        );
      })}
    </div>
  );
}

export default OrderSummaryItems;

export function OrderSummaryItemsSkeleton() {
  return (
    <div className="order-summary__items">
      {Array.from({ length: 2 }).map((_, index) => (
        <div key={index} className="item">
          <div className="item__description">
            <div className="item__title-wrapper">
              <Skeleton w={200} h={22} />
              <Skeleton w={22} h={22} />
            </div>
            <Skeleton w={160} h={18} />
            <div className="item__variations-data">
              <Skeleton w={60} h={16} />
              <Skeleton w={60} h={16} mt={4} />
            </div>
          </div>
          <div className="item__subtotal">
            <Skeleton w={50} h={22} />
          </div>
        </div>
      ))}
    </div>
  );
}

import { Skeleton } from '@mantine/core';
import { __, _n, _x, sprintf } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react';
import itemPlaceholderImg from '../../../../images/placeholder-course-thumbnail.jpeg';
import {
  decodeHTMLEntities,
  formatItemPrice,
  formatItemTotalPrice,
} from '../utils';
import { Icons } from './icons';

function CartItem({
  item,
  itemIndex,
  onQuantityUpdate,
  onRemoveItem,
  includesTax,
}) {
  const [quantity, setQuantity] = useState(item.quantity);
  const [debouncedQuantity, setDebouncedQuantity] = useState(item.quantity);
  const [isUpdating, setIsUpdating] = useState(false);
  const onSale = item.prices.regular_price !== item.prices.sale_price;

  const decreaseQuantity = () => {
    setQuantity((prev) =>
      prev > item.quantity_limits.minimum
        ? prev - item.quantity_limits.multiple_of
        : item.quantity_limits.minimum
    );
  };

  const increaseQuantity = () => {
    setQuantity((prev) =>
      prev < item.quantity_limits.maximum
        ? prev + item.quantity_limits.multiple_of
        : item.quantity_limits.maximum
    );
  };

  const handleQuantityChange = (e) => {
    if (!item?.meta?.group_purchase_enabled) {
      return;
    }

    const newValue = parseInt(e.target.value, 10);
    if (!isNaN(newValue)) {
      if (newValue < item.quantity_limits.minimum) {
        setQuantity(item.quantity_limits.minimum);
      } else if (newValue > item.quantity_limits.maximum) {
        setQuantity(item.quantity_limits.maximum);
      } else {
        setQuantity(newValue);
      }
    }
  };

  const removeItem = async () => {
    await onRemoveItem(item.key, itemIndex);
  };

  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedQuantity(quantity);
    }, 500);

    return () => clearTimeout(timer);
  }, [quantity]);

  useEffect(() => {
    async function updateCartItem() {
      setIsUpdating(true);
      await onQuantityUpdate(item.key, debouncedQuantity, itemIndex);
      setIsUpdating(false);
    }

    if (debouncedQuantity !== item.quantity) {
      updateCartItem();
    }
  }, [debouncedQuantity, item]);

  const billingPeriod = item?.extensions?.subscriptions?.billing_period;
  const billingInterval = item?.extensions?.subscriptions?.billing_interval;
  const subscriptionLength =
    item?.extensions?.subscriptions?.subscription_length;

  const billingPeriodMap = {
    day: [__('day', 'edwiser-bridge-pro'), __('days', 'edwiser-bridge-pro')],
    week: [__('week', 'edwiser-bridge-pro'), __('weeks', 'edwiser-bridge-pro')],
    month: [
      __('month', 'edwiser-bridge-pro'),
      __('months', 'edwiser-bridge-pro'),
    ],
    year: [__('year', 'edwiser-bridge-pro'), __('years', 'edwiser-bridge-pro')],
  };

  const [labelSingular, labelPlural] = billingPeriodMap[billingPeriod] || [
    billingPeriod,
    `${billingPeriod}s`,
  ];

  return (
    <div className="eb-cart__item">
      <div className="eb-cart__item-content-wrapper">
        <div className="eb-cart__item-image">
          <a href={item.permalink}>
            <img
              src={item.images[0]?.src || itemPlaceholderImg}
              alt={item.images[0]?.alt}
            />
          </a>
        </div>
        <div className="eb-cart__item-content">
          <div className="eb-cart__item-category">
            <Icons.grid />
            <span>{__(item?.meta?.category, 'edwiser-bridge-pro')}</span>
          </div>
          <div>
            <a href={item.permalink} className="eb-cart__item-title">
              {__(decodeHTMLEntities(item.name), 'edwiser-bridge-pro')}
            </a>
            <div style={{ marginTop: '2px' }}>
              {onSale && (
                <span className="eb-cart__item-regular-price">
                  {__(
                    formatItemPrice(item.prices, 'regular'),
                    'edwiser-bridge-pro'
                  )}
                </span>
              )}
              <span className="eb-cart__item-price">
                {__(formatItemPrice(item.prices, 'sale'), 'edwiser-bridge-pro')}
              </span>
              {(item.type === 'subscription' ||
                item.type === 'subscription_variation') && (
                <span className="eb-cart__item-billing-period">
                  {subscriptionLength === billingInterval
                    ? /* translators: %1$d = billing interval number, %2$s = billing period (e.g. month, week) */
                      sprintf(
                        __('for %1$d %2$s', 'edwiser-bridge-pro'),
                        billingInterval,
                        _n(
                          labelSingular,
                          labelPlural,
                          billingInterval,
                          'edwiser-bridge-pro'
                        )
                      )
                    : /* translators: %1$d = billing interval number, %2$s = billing period (e.g. month, week), pluralized if needed */
                      sprintf(
                        __('every %1$d %2$s', 'edwiser-bridge-pro'),
                        billingInterval,
                        _n(
                          labelSingular,
                          labelPlural,
                          billingInterval,
                          'edwiser-bridge-pro'
                        )
                      )}
                </span>
              )}
              {onSale && (
                <div
                  className="eb-cart__price-savings"
                  style={{ marginTop: '8px' }}
                >
                  {__('Save', 'edwiser-bridge-pro')}{' '}
                  {formatItemPrice(item.prices, 'savings')}
                  {(item.type === 'subscription' ||
                    item.type === 'subscription_variation') && (
                    <>
                      {' / '}
                      {/* translators: %1$d = billing interval number, %2$s = billing period (e.g. month, week), pluralized if needed */}
                      {sprintf(
                        __('%1$d %2$s', 'edwiser-bridge-pro'),
                        billingInterval,
                        _n(
                          labelSingular,
                          labelPlural,
                          billingInterval,
                          'edwiser-bridge-pro'
                        )
                      )}
                    </>
                  )}
                </div>
              )}
            </div>
          </div>
          {item.item_data.length > 0 && (
            <div className="eb-cart__subscriptions-data">
              {item.item_data.map((data, index) => (
                <div key={index} className="eb-cart__subscription-data">
                  <span className="label">
                    {__(data.name, 'edwiser-bridge-pro')}:
                  </span>{' '}
                  <span className="value">
                    {__(decodeHTMLEntities(data.value, 'edwiser-bridge-pro'))}
                  </span>
                </div>
              ))}
            </div>
          )}
          {item.variation.length > 0 && (
            <div className="eb-cart__item-variations">
              {item.variation.map((variation, index) => (
                <div key={index} className="eb-cart__item-variation">
                  <span className="label">
                    {__(variation.attribute, 'edwiser-bridge-pro')}:
                  </span>{' '}
                  <span className="value">
                    {__(variation.value, 'edwiser-bridge-pro')}
                  </span>
                </div>
              ))}
            </div>
          )}
          <div className="eb-cart__item-info">
            <button className="eb-cart__item-remove" onClick={removeItem}>
              {__('Remove', 'edwiser-bridge-pro')}
            </button>
            {item?.meta?.self_enroll_enabled && (
              <div className="eb-cart__item-tag">
                {__('Group purchase enabled', 'edwiser-bridge-pro')}
              </div>
            )}
          </div>
        </div>
      </div>
      <div className="eb-cart__item-actions">
        <div className="eb-cart__item-quantity">
          {(item?.meta?.group_purchase_enabled ||
            item?.meta?.non_course_product) && (
            <button
              className="eb-cart__item-quantity-btn"
              onClick={decreaseQuantity}
              disabled={quantity <= item.quantity_limits.minimum || isUpdating}
            >
              -
            </button>
          )}
          <input
            type="number"
            className="eb-cart__item-quantity-input"
            value={quantity}
            onChange={handleQuantityChange}
            disabled={
              !item?.meta?.group_purchase_enabled &&
              !item?.meta?.non_course_product
            }
            min={item.quantity_limits.minimum}
            max={item.quantity_limits.maximum}
          />
          {(item?.meta?.group_purchase_enabled ||
            item?.meta?.non_course_product) && (
            <button
              className="eb-cart__item-quantity-btn"
              onClick={increaseQuantity}
              disabled={isUpdating}
            >
              +
            </button>
          )}
        </div>
        <div className="eb-cart__item-subtotal">
          {(item.type === 'subscription' ||
            item.type === 'subscription_variation') && (
            <span className="eb-cart__item-subscription-price">
              {__('Due today', 'edwiser-bridge-pro')}
            </span>
          )}
          <div>
            {onSale && (
              <span className="eb-cart__item-regular-price">
                {__(
                  formatItemPrice(item.prices, 'regular', item.quantity),
                  'edwiser-bridge-pro'
                )}
              </span>
            )}
            <span className="eb-cart__item-price">
              {__(
                formatItemTotalPrice(item.totals, includesTax),
                'edwiser-bridge-pro'
              )}
            </span>
          </div>
          {onSale && (
            <div className="eb-cart__price-savings">
              {__('Save', 'edwiser-bridge-pro')}{' '}
              {formatItemPrice(item.prices, 'savings', item.quantity)}
              {(item.type === 'subscription' ||
                item.type === 'subscription_variation') && (
                <>
                  {' / '}
                  {/* translators: %1$d = billing interval number, %2$s = billing period (e.g. month, week), pluralized if needed */}
                  {/* {sprintf(
                    __('%1$d %2$s', 'edwiser-bridge-pro'),
                    billingInterval,
                    _n(
                      billingPeriod,
                      `${billingPeriod}s`,
                      billingInterval,
                      'edwiser-bridge-pro'
                    )
                  )} */}
                  {sprintf(
                    __('%1$d %2$s', 'edwiser-bridge-pro'),
                    billingInterval,
                    _n(
                      labelSingular,
                      labelPlural,
                      billingInterval,
                      'edwiser-bridge-pro'
                    )
                  )}
                </>
              )}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

export default CartItem;

export function CartItemSkeleton() {
  return (
    <div className="eb-cart__item">
      <div className="eb-cart__item-content-wrapper">
        <div className="eb-cart__item-image">
          <Skeleton width="100%" height="100%" radius="sm" />
        </div>
        <div className="eb-cart__item-content">
          <Skeleton width={75} height={16} radius="sm" />
          <Skeleton width={300} height={18} radius="sm" />
          <div className="eb-cart__item-info">
            <Skeleton width={42} height={24} radius="sm" />
            <Skeleton width={160} height={24} radius="sm" />
          </div>
        </div>
      </div>
      <div className="eb-cart__item-actions">
        <div className="eb-cart__item-quantity">
          <Skeleton width={28} height={28} radius="sm" />
          <Skeleton width={42} height={28} radius="sm" />
          <Skeleton width={28} height={28} radius="sm" />
        </div>
        <Skeleton width={30} height={24} radius="sm" />
      </div>
    </div>
  );
}

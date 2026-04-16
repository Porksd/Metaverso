import { Skeleton } from '@mantine/core';
import { __ } from '@wordpress/i18n';
import React from 'react';

function CourseQuantity({
  decreaseQuantity,
  increaseQuantity,
  quantity,
  setQuantity,
  course,
  enableGroupPurchase,
}) {
  return (
    <div className="eb-product-desc__quantity">
      <button
        className="eb-product-desc__quantity-btn"
        disabled={
          quantity <= 1 ||
          (!course?.non_course_product &&
            ((course?.type === 'simple' && !course?.group_purchase_enabled) ||
              !enableGroupPurchase))
        }
        onClick={decreaseQuantity}
      >
        -
      </button>
      <input
        type="number"
        min="1"
        max="9999"
        value={quantity}
        onChange={(e) => setQuantity(e.target.value)}
        className="eb-product-desc__quantity-input"
        disabled={
          !course?.non_course_product &&
          ((course?.type === 'simple' && !course?.group_purchase_enabled) ||
            !enableGroupPurchase)
        }
      />
      <button
        className="eb-product-desc__quantity-btn"
        disabled={
          !course?.non_course_product &&
          ((course?.type === 'simple' && !course?.group_purchase_enabled) ||
            !enableGroupPurchase)
        }
        onClick={increaseQuantity}
      >
        +
      </button>
      <a href={course?.cart_url} className="btn__view-cart">
        {__('View cart', 'edwiser-bridge-pro')}
      </a>
    </div>
  );
}

export default CourseQuantity;

export function CourseQuantitySkeleton() {
  return (
    <div className="eb-product-desc__quantity">
      <Skeleton width={32} height={32} />
      <Skeleton width={60} height={32} />
      <Skeleton width={32} height={32} />
      <Skeleton width={75} height={32} ml={10} />
    </div>
  );
}

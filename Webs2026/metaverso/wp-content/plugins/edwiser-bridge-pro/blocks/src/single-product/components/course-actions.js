import { Skeleton } from '@mantine/core';
import { __, sprintf } from '@wordpress/i18n';
import React, { useState } from 'react';
import { Icons } from './icons';
import SuccessMessage from './success-message';
import { decodeHTMLEntities } from '../utils';

function CourseActions({
  quantity,
  course,
  variation,
  productId,
  inCart,
  enableGroupPurchase,
}) {
  const [addedToCart, setAddedToCart] = useState(false);
  const [showCartSuccess, setShowCartSuccess] = useState(false);

  const getWcAjaxUrl = () => {
    if (window.ebSettings && window.ebSettings.siteUrl) {
      return `${window.ebSettings.siteUrl}/?wc-ajax=add_to_cart`;
    }

    return '/?wc-ajax=add_to_cart';
  };

  const addToCart = async (e) => {
    e.preventDefault();

    if (!course.is_purchasable) return;
    const wcAjaxUrl = getWcAjaxUrl();

    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
    form.action = wcAjaxUrl;

    // Add product ID
    const productInput = document.createElement('input');
    productInput.type = 'hidden';
    productInput.name = 'add-to-cart';
    productInput.value = productId;
    form.appendChild(productInput);

    // Add quantity
    const quantityInput = document.createElement('input');
    quantityInput.type = 'hidden';
    quantityInput.name = 'quantity';
    quantityInput.value = quantity;
    form.appendChild(quantityInput);

    // Handle variable products
    if (
      (course.type === 'variable' || course.type === 'variable-subscription') &&
      variation
    ) {
      // Add variation ID
      const variationInput = document.createElement('input');
      variationInput.type = 'hidden';
      variationInput.name = 'variation_id';
      variationInput.value = variation.id;
      form.appendChild(variationInput);

      // Add all attributes
      if (variation.selected_attributes) {
        Object.entries(variation.selected_attributes).forEach(
          ([name, value]) => {
            if (value) {
              const attrKey = `attribute_${name
                .toLowerCase()
                .replace(/\s+/g, '-')}`;
              const attrInput = document.createElement('input');
              attrInput.type = 'hidden';
              attrInput.name = attrKey;
              attrInput.value = value;
              form.appendChild(attrInput);
            }
          }
        );
      }
    }

    // Add custom field for group purchase if needed
    if (enableGroupPurchase) {
      const customInput = document.createElement('input');
      customInput.type = 'hidden';
      customInput.name = 'wdm_edwiser_self_enroll';
      customInput.value = 'on';
      form.appendChild(customInput);
    }

    document.body.appendChild(form);

    try {
      const formData = new FormData(form);
      const response = await fetch(wcAjaxUrl, {
        method: 'POST',
        body: formData,
      });

      if (response.ok) {
        setAddedToCart(true);
        setShowCartSuccess(true);

        // if (typeof jQuery !== 'undefined' && jQuery(document.body).trigger) {
        //   jQuery(document.body).trigger('added_to_cart');
        // }
      }
    } catch (error) {
      console.error('Error submitting form:', error);
    } finally {
      document.body.removeChild(form);
    }
  };

  return (
    <>
      <div className="eb-product-desc__actions">
        <button
          className="btn__add-to-cart"
          onClick={addToCart}
          disabled={
            (course?.type === 'variable' ||
              course?.type === 'variable-subscription') &&
            !variation
          }
        >
          {course?.type !== 'variable-subscription' &&
            course?.type !== 'subscription' && (
              <span>
                <Icons.cart />
                {(addedToCart || inCart) && <Icons.check />}
              </span>
            )}
          {course?.type === 'variable-subscription' ||
          course?.type === 'subscription'
            ? __(course?.subscription_add_to_cart_text, 'edwiser-bridge-pro')
            : __('Add to cart', 'edwiser-bridge-pro')}
        </button>
        {course?.type === 'simple' && course?.buy && (
          <a href={course?.buy?.url} className="btn__buy-now">
            {__(course?.buy?.text, 'edwiser-bridge-pro')}
          </a>
        )}
      </div>

      {/* Add to Cart Success Message */}
      <SuccessMessage
        message={
          quantity > 1
            ? sprintf(
                __(
                  '%1$d × "%2$s" has been added to the cart',
                  'edwiser-bridge-pro'
                ),
                quantity,
                decodeHTMLEntities(course.name)
              )
            : sprintf(
                __('"%s" has been added to the cart', 'edwiser-bridge-pro'),
                decodeHTMLEntities(course.name)
              )
        }
        showSuccess={showCartSuccess}
        setShowSuccess={setShowCartSuccess}
        duration={4000}
      />
    </>
  );
}

export default CourseActions;

export function CourseActionsSkeleton() {
  return (
    <div className="eb-product-desc__actions">
      <Skeleton width={'100%'} height={44} />
      <Skeleton width={'100%'} height={44} />
    </div>
  );
}

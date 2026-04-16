import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import { Icons } from './icons';
import { Checkbox } from '@mantine/core';
import SuccessMessage from './success-message';

function GroupedProduct({ onSale, courseType, price, products }) {
  const [quantities, setQuantities] = useState({});
  const [addedToCart, setAddedToCart] = useState(false);
  const [showCartSuccess, setShowCartSuccess] = useState(false);
  const [groupPurchaseEnabled, setGroupPurchaseEnabled] = useState({});

  const getWcAjaxUrl = () => {
    if (window.ebSettings && window.ebSettings.siteUrl) {
      return `${window.ebSettings.siteUrl}/?wc-ajax=add_to_cart`;
    }

    return '/?wc-ajax=add_to_cart';
  };

  const decreaseQuantity = (productId) => {
    setQuantities((prev) => {
      const currentQuantity = prev[productId] || 0;

      return {
        ...prev,
        [productId]: currentQuantity > 0 ? currentQuantity - 1 : 0,
      };
    });
  };

  const increaseQuantity = (productId, groupPurchaseAvailable) => {
    setQuantities((prev) => {
      const currentQuantity = prev[productId] || 0;

      if (groupPurchaseAvailable && groupPurchaseEnabled[productId]) {
        return {
          ...prev,
          [productId]: currentQuantity < 9999 ? currentQuantity + 1 : 9999,
        };
      } else {
        return {
          ...prev,
          [productId]: currentQuantity === 0 ? 1 : 1,
        };
      }
    });
  };

  const handleQuantityChange = (productId, value, groupPurchaseAvailable) => {
    const numValue = parseInt(value, 10) || 0;

    setQuantities((prev) => {
      if (groupPurchaseAvailable && groupPurchaseEnabled[productId]) {
        return {
          ...prev,
          [productId]: numValue > 9999 ? 9999 : numValue < 0 ? 0 : numValue,
        };
      } else {
        return {
          ...prev,
          [productId]: numValue > 1 ? 1 : numValue < 0 ? 0 : numValue,
        };
      }
    });
  };

  const toggleGroupPurchase = (productId, checked) => {
    setGroupPurchaseEnabled((prev) => ({
      ...prev,
      [productId]: checked,
    }));

    // If disabling group purchase and quantity >= 1, set to 0
    if (!checked && quantities[productId] >= 1) {
      setQuantities((prev) => ({
        ...prev,
        [productId]: 0,
      }));
    }
  };

  const addToCart = async (e) => {
    e.preventDefault();

    const productsToAdd = products.filter(
      (product) =>
        product.type === 'simple' && (quantities[product.id] || 0) > 0
    );

    if (productsToAdd.length === 0) return;

    const wcAjaxUrl = getWcAjaxUrl();
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
    form.action = wcAjaxUrl;

    let requestSuccessful = true;
    document.body.appendChild(form);

    try {
      // For each product with quantity > 0, make a separate fetch request
      for (const product of productsToAdd) {
        form.innerHTML = ''; // Clear previous inputs

        // Add product ID
        const productInput = document.createElement('input');
        productInput.type = 'hidden';
        productInput.name = 'add-to-cart';
        productInput.value = product.id;
        form.appendChild(productInput);

        // Add quantity
        const quantityInput = document.createElement('input');
        quantityInput.type = 'hidden';
        quantityInput.name = 'quantity';
        quantityInput.value = quantities[product.id];
        form.appendChild(quantityInput);

        // Add custom field for group purchase if needed
        if (
          groupPurchaseEnabled[product.id] &&
          product.group_purchase_enabled
        ) {
          const customInput = document.createElement('input');
          customInput.type = 'hidden';
          customInput.name = 'wdm_edwiser_self_enroll';
          customInput.value = 'on';
          form.appendChild(customInput);
        }

        const formData = new FormData(form);
        const response = await fetch(wcAjaxUrl, {
          method: 'POST',
          body: formData,
        });

        if (!response.ok) {
          requestSuccessful = false;
          break;
        }
      }

      if (requestSuccessful) {
        setAddedToCart(true);
        setShowCartSuccess(true);

        // if (typeof jQuery !== 'undefined' && jQuery(document.body).trigger) {
        //   jQuery(document.body).trigger('added_to_cart');
        // }
      }
    } catch (error) {
      console.error('Error submitting form:', error);
      requestSuccessful = false;
    } finally {
      document.body.removeChild(form);
    }
  };

  return (
    <>
      <div className="eb-product-desc__course-pricing">
        {onSale && (
          <div className="eb-product-desc__sale-badge">
            {__('Sale', 'edwiser-bridge-pro')}
          </div>
        )}
        <div
          className={`eb-product-desc__price ${courseType} ${
            onSale ? 'sale' : ''
          }`}
          dangerouslySetInnerHTML={{ __html: price }}
        ></div>
      </div>
      {products?.length > 0 && (
        <div className="eb-product-desc__grouped-products">
          {products.map((product) => (
            <div key={product.id} className="eb-product-desc__grouped-product">
              <a href={product.link} className="grouped-product__title">
                {__(product.name, 'edwiser-bridge-pro')}
              </a>
              {product.price_html && (
                <div
                  className={`product-price ${product.type} ${
                    product.on_sale ? 'sale' : ''
                  }`}
                  dangerouslySetInnerHTML={{ __html: product.price_html }}
                ></div>
              )}
              {product?.type === 'simple' &&
                product?.group_purchase_enabled && (
                  <Checkbox
                    label={__('Enable group purchase', 'edwiser-bridge-pro')}
                    className="eb-product-desc__enable-group-purchase"
                    checked={groupPurchaseEnabled[product.id] || false}
                    onChange={(e) => {
                      toggleGroupPurchase(product.id, e.currentTarget.checked);
                    }}
                  />
                )}
              {product.type === 'simple' ? (
                <div className="eb-product-desc__quantity">
                  <button
                    className="eb-product-desc__quantity-btn"
                    disabled={(quantities[product.id] || 0) <= 0}
                    onClick={() => decreaseQuantity(product.id)}
                  >
                    -
                  </button>
                  <input
                    type="number"
                    min="0"
                    max={
                      product?.group_purchase_enabled &&
                      groupPurchaseEnabled[product.id]
                        ? '9999'
                        : '1'
                    }
                    value={quantities[product.id] || 0}
                    onChange={(e) =>
                      handleQuantityChange(
                        product.id,
                        e.target.value,
                        product?.group_purchase_enabled
                      )
                    }
                    className="eb-product-desc__quantity-input"
                  />
                  <button
                    className="eb-product-desc__quantity-btn"
                    disabled={
                      (quantities[product.id] || 0) >= 9999 ||
                      (!(
                        product?.group_purchase_enabled &&
                        groupPurchaseEnabled[product.id]
                      ) &&
                        (quantities[product.id] || 0) >= 1)
                    }
                    onClick={() =>
                      increaseQuantity(
                        product.id,
                        product?.group_purchase_enabled
                      )
                    }
                  >
                    +
                  </button>
                </div>
              ) : (
                <a href={product.link} className="btn__view">
                  <span>
                    {product.type === 'variable'
                      ? __('Select options', 'edwiser-bridge-pro')
                      : product.type === 'subscription'
                      ? __('Sign up now', 'edwiser-bridge-pro')
                      : product.type === 'variable-subscription'
                      ? __('Select options', 'edwiser-bridge-pro')
                      : product.type === 'grouped'
                      ? __('View products', 'edwiser-bridge-pro')
                      : __('View', 'edwiser-bridge-pro')}
                  </span>
                </a>
              )}
            </div>
          ))}
        </div>
      )}
      <div className="eb-product-desc__actions">
        <button
          className="btn__add-to-cart"
          onClick={addToCart}
          disabled={
            !products.some(
              (product) =>
                product.type === 'simple' && (quantities[product.id] || 0) > 0
            )
          }
        >
          <span>
            <Icons.cart />
            {addedToCart && <Icons.check />}
          </span>
          {__('Add to cart', 'edwiser-bridge-pro')}
        </button>
      </div>

      {/* Add to Cart Success Message */}
      <SuccessMessage
        message={__('Products added to the cart', 'edwiser-bridge-pro')}
        showSuccess={showCartSuccess}
        setShowSuccess={setShowCartSuccess}
        duration={4000}
      />
    </>
  );
}

export default GroupedProduct;

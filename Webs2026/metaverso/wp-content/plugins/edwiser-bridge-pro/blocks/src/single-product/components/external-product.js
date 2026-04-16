import { __ } from '@wordpress/i18n';
import React from 'react';

function ExternalProduct({ onSale, courseType, price, addToCart }) {
  return (
    <>
      <div
        className="eb-product-desc__course-pricing"
        style={{ marginBottom: '2em' }}
      >
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
      <div className="eb-product-desc__actions">
        <form
          action={addToCart?.url}
          method="get"
          style={{ display: 'flex', width: '100%' }}
        >
          <button type="submit" className="btn__buy-now">
            {__(addToCart.text, 'edwiser-bridge-pro')}
          </button>
        </form>
      </div>
    </>
  );
}

export default ExternalProduct;

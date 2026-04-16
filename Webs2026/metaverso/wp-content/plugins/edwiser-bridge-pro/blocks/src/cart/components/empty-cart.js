import { __ } from '@wordpress/i18n';
import React from 'react';
import emptyCartImage from '../../../../images/eb-empty-cart.png';
import { Icons } from './icons';

function EmptyCart({ shopPageUrl }) {
  return (
    <div className="eb-cart__empty">
      <div className="eb-cart__empty-image">
        <img src={emptyCartImage} alt="Empty cart" draggable="false" />
      </div>
      <div className="eb-cart__empty-content">
        <h4>{__('Your cart is empty', 'edwiser-bridge-pro')}</h4>
        <p>
          {__(
            'Looks like you have not added anything to your cart. Go ahead & explore to fill your cart',
            'edwiser-bridge-pro'
          )}
        </p>
        <a href={shopPageUrl}>
          {__('Explore products', 'edwiser-bridge-pro')}
          <Icons.chevronRight />
        </a>
      </div>
    </div>
  );
}

export default EmptyCart;

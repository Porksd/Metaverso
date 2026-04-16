import React from 'react';
import { __ } from '@wordpress/i18n';

const EmptyState = () => {
  return (
    <div className="eb-product-desc__empty-state">
      <div className="eb-product-desc__empty-state-content">
        <h2>{__('Product Not Found', 'edwiser-bridge-pro')}</h2>
        <p>
          {__(
            'Sorry, the product you are looking for is not available or may have been removed.',
            'edwiser-bridge-pro'
          )}
        </p>
      </div>
    </div>
  );
};

export default EmptyState;

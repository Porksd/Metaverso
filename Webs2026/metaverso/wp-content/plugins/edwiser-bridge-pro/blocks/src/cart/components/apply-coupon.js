import { Accordion } from '@mantine/core';
import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import { Icons } from './icons';
import { decodeHTMLEntities } from '../utils';

function ApplyCoupon({ onApplyCoupon, applyCouponError }) {
  const [coupon, setCoupon] = useState('');
  const [applyingCoupon, setApplyingCoupon] = useState(false);
  const [isApplyError, setIsApplyError] = useState(false);
  const [value, setValue] = useState(null);

  async function applyCoupon() {
    if (!coupon.trim()) return;

    setApplyingCoupon(true);
    setIsApplyError(false);

    const success = await onApplyCoupon(coupon);

    if (!success) {
      setIsApplyError(true);
      setApplyingCoupon(false);
      return;
    }

    setCoupon('');
    setApplyingCoupon(false);
    setValue(null);
  }

  return (
    <div className="eb-cart__apply-coupon">
      <Accordion
        value={value}
        onChange={setValue}
        variant="contained"
        className="apply-coupon-accordion"
      >
        <Accordion.Item value="apply-coupon">
          <Accordion.Control>
            {__('Apply coupon', 'edwiser-bridge-pro')}
          </Accordion.Control>
          <Accordion.Panel>
            <div style={{ display: 'grid', gap: '0.25em' }}>
              <input
                type="text"
                className="eb-cart__coupon-input"
                placeholder={__('Enter coupon code here', 'edwiser-bridge-pro')}
                value={coupon}
                onChange={(e) => {
                  setIsApplyError('');
                  setCoupon(e.currentTarget.value.toUpperCase());
                }}
              />
              {isApplyError && (
                <span className="eb-cart__apply-coupon-error">
                  {__(
                    decodeHTMLEntities(applyCouponError),
                    'edwiser-bridge-pro'
                  )}
                </span>
              )}
            </div>
            <button
              className="eb-btn eb-btn__apply"
              onClick={applyCoupon}
              disabled={applyingCoupon}
            >
              {applyingCoupon ? (
                <Icons.loader />
              ) : (
                __('Apply', 'edwiser-bridge-pro')
              )}
            </button>
          </Accordion.Panel>
        </Accordion.Item>
      </Accordion>
    </div>
  );
}

export default ApplyCoupon;

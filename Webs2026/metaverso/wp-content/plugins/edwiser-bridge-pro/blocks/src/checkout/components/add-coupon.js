import { Accordion, Skeleton, TextInput } from '@mantine/core';
import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import { Icons } from './icons';
import { decodeHTMLEntities } from '../utils';

function AddCoupon({ handleApplyCoupon, applyCouponError }) {
  const [coupon, setCoupon] = useState('');
  const [applyingCoupon, setApplyingCoupon] = useState(false);
  const [isApplyError, setIsApplyError] = useState(false);
  const [accordionValue, setAccordionValue] = useState(null);

  const onApplyCoupon = async () => {
    if (!coupon.trim()) return;

    setApplyingCoupon(true);
    setIsApplyError(false);

    const success = await handleApplyCoupon(coupon);

    if (!success) {
      setIsApplyError(true);
      setApplyingCoupon(false);
      return;
    }

    setCoupon('');
    setApplyingCoupon(false);
    setAccordionValue(null);
  };

  return (
    <div className="order-summary__add-coupon">
      <Accordion
        variant="contained"
        value={accordionValue}
        onChange={setAccordionValue}
      >
        <Accordion.Item value="add-coupon">
          <Accordion.Control>{__('Add a coupon')}</Accordion.Control>
          <Accordion.Panel>
            <TextInput
              type="text"
              placeholder="Enter coupon code here"
              value={coupon}
              onChange={(e) => {
                setIsApplyError('');
                setCoupon(e.currentTarget.value.toUpperCase());
              }}
              error={isApplyError && decodeHTMLEntities(applyCouponError)}
            />
            <button
              className="add-coupon__btn-apply"
              onClick={onApplyCoupon}
              disabled={applyingCoupon}
            >
              {applyingCoupon ? <Icons.loader /> : __('Apply')}
            </button>
          </Accordion.Panel>
        </Accordion.Item>
      </Accordion>
    </div>
  );
}

export default AddCoupon;

export function AddCouponSkeleton() {
  return (
    <div className="order-summary__add-coupon">
      <Skeleton w="100%" h={40} />
    </div>
  );
}

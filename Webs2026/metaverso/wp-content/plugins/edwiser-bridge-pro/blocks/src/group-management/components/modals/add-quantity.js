import React, { useEffect, useState } from 'react';
import { Modal, Skeleton, TextInput } from '@mantine/core';
import { __ } from '@wordpress/i18n';
import { decodeHTMLEntities } from '../../utils';
import { Icons } from '../icons';

function AddQuantity({
  quantityOpened,
  closeQuantity,
  groupId,
  fetchAddQuantityData,
  addQuantityData,
  isAddQuantityLoading,
  addProductsToCart,
}) {
  const [quantity, setQuantity] = useState(0);
  const [isProceedingToCheckout, setIsProceedingToCheckout] = useState(false);
  const [proceedToCheckoutError, setProceedToCheckoutError] = useState('');

  useEffect(() => {
    if (quantityOpened && groupId) {
      fetchAddQuantityData(groupId);
    }
  }, [quantityOpened, groupId]);

  useEffect(() => {
    if (!quantityOpened) {
      setQuantity(0);
    }
  }, [quantityOpened]);

  const grossTotal =
    addQuantityData?.products &&
    addQuantityData?.products.reduce(
      (sum, product) => sum + quantity * product.price,
      0
    );

  const handleProceedToCheckout = async () => {
    setIsProceedingToCheckout(true);
    setProceedToCheckoutError('');

    const productsObj = {};
    addQuantityData?.products?.forEach((product) => {
      productsObj[product.product_id] = Number(quantity);
    });

    const result = await addProductsToCart(groupId, productsObj);
    setIsProceedingToCheckout(false);
    if (result.success) {
      window.location.href = result.checkoutUrl;
    } else {
      setProceedToCheckoutError(result.message);
    }
  };

  return (
    <Modal
      opened={quantityOpened}
      onClose={closeQuantity}
      title={__('Add seats to group', 'edwiser-bridge-pro')}
      withinPortal={false}
      closeOnClickOutside={false}
      size="xl"
    >
      <div className="actions__quantity-modal">
        {proceedToCheckoutError && (
          <div className="quantity-modal__error">{proceedToCheckoutError}</div>
        )}
        <div className="quantity-modal__content">
          <TextInput
            type="number"
            className="content__quantity"
            min={0}
            value={quantity}
            onChange={(e) => {
              let val = e.currentTarget.value.replace(/\D/g, '');
              val = val === '' ? 0 : Math.abs(val);
              setQuantity(val < 0 ? 0 : val);
            }}
            placeholder={__('0', 'edwiser-bridge-pro')}
            label={__('Enter no. of seats', 'edwiser-bridge-pro')}
            disabled={isAddQuantityLoading || isProceedingToCheckout}
            inputMode="numeric"
            pattern="[0-9]*"
          />
          {isAddQuantityLoading ? (
            <div className="content__products-list">
              <div className="products-list__header">
                <div>{__('Product name', 'edwiser-bridge-pro')}</div>
                <div>{__('Price', 'edwiser-bridge-pro')}</div>
                <div>{__('Seats', 'edwiser-bridge-pro')}</div>
                <div>{__('Total price', 'edwiser-bridge-pro')}</div>
              </div>
              {Array.from({ length: 3 }).map((_, index) => (
                <React.Fragment key={index}>
                  <div className="product-list__separator" />
                  <div className="products-list__item-row">
                    <div>
                      <Skeleton w={220} h={20} />
                    </div>
                    <div style={{ placeItems: 'center' }}>
                      <Skeleton w={50} h={20} />
                    </div>
                    <div style={{ placeItems: 'center' }}>
                      <Skeleton w={50} h={20} />
                    </div>
                    <div style={{ placeItems: 'center' }}>
                      <Skeleton w={50} h={20} />
                    </div>
                  </div>
                </React.Fragment>
              ))}
            </div>
          ) : (
            addQuantityData?.products?.length > 0 && (
              <div className="content__products-list">
                <div className="products-list__header">
                  <div>{__('Product name', 'edwiser-bridge-pro')}</div>
                  <div>{__('Price', 'edwiser-bridge-pro')}</div>
                  <div>{__('Seats', 'edwiser-bridge-pro')}</div>
                  <div>{__('Total price', 'edwiser-bridge-pro')}</div>
                </div>
                {addQuantityData?.products?.map((product) => (
                  <React.Fragment key={product.product_id}>
                    <div className="product-list__separator" />
                    <div className="products-list__item-row">
                      <div>{decodeHTMLEntities(product.product_name)}</div>
                      <div>
                        {addQuantityData?.currency_symbol}
                        {product.price}
                      </div>
                      <div>{quantity}</div>
                      <div>
                        {addQuantityData?.currency_symbol}
                        {quantity * product.price}
                      </div>
                    </div>
                  </React.Fragment>
                ))}
              </div>
            )
          )}
          <div className="content__products-total">
            <span className="products-total__label">
              {__('Total :', 'edwiser-bridge-pro')}
            </span>
            <span className="products-total__value">
              {addQuantityData?.currency_symbol}
              {grossTotal}
            </span>
          </div>
        </div>
        <div className="quantity-modal__action">
          <button
            className="btn__action-confirm"
            disabled={
              isAddQuantityLoading || isProceedingToCheckout || quantity == 0
            }
            onClick={handleProceedToCheckout}
          >
            {isProceedingToCheckout && <Icons.loader />}
            {__('Proceed to checkout', 'edwiser-bridge-pro')}
          </button>
          <button
            className="btn__action-cancel"
            onClick={closeQuantity}
            disabled={isProceedingToCheckout}
          >
            {__('Cancel', 'edwiser-bridge-pro')}
          </button>
        </div>
      </div>
    </Modal>
  );
}

export default AddQuantity;

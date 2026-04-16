import React, { useEffect, useState } from 'react';
import { Icons } from '../icons';
import { Checkbox, Modal, Skeleton, TextInput } from '@mantine/core';
import { __ } from '@wordpress/i18n';
import { decodeHTMLEntities } from '../../utils';

function AddProduct({
  productOpened,
  closeProduct,
  groupId,
  fetchAddProductData,
  addProductData,
  isAddProductLoading,
  addProductsToCart,
}) {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedProducts, setSelectedProducts] = useState({});
  const [isProceedingToCheckout, setIsProceedingToCheckout] = useState(false);
  const [proceedToCheckoutError, setProceedToCheckoutError] = useState('');

  useEffect(() => {
    if (productOpened && groupId) {
      fetchAddProductData(groupId);
    }
  }, [productOpened, groupId]);

  useEffect(() => {
    if (addProductData?.available_products) {
      const initialSelection = {};
      addProductData.available_products.forEach((product) => {
        initialSelection[product.product_id] = product.selected || false;
      });
      setSelectedProducts(initialSelection);
    }
  }, [addProductData?.available_products]);

  // Reset search and selection when modal closes
  useEffect(() => {
    if (!productOpened) {
      setSearchTerm('');
      setSelectedProducts({});
    }
  }, [productOpened]);

  // Filter products based on search term
  const filteredProducts =
    addProductData?.available_products?.filter((product) =>
      product.product_name.toLowerCase().includes(searchTerm.toLowerCase())
    ) || [];

  const handleProductToggle = (productId) => {
    setSelectedProducts((prev) => ({
      ...prev,
      [productId]: !prev[productId],
    }));
  };

  // Calculate total for all selected products, not just filtered
  const grossTotal = (addProductData?.available_products || []).reduce(
    (total, product) => {
      if (selectedProducts[product.product_id]) {
        return total + product.price * product.min_quantity;
      }
      return total;
    },
    0
  );

  const selectedCount = Object.values(selectedProducts).filter(Boolean).length;

  const handleProceedToCheckout = async () => {
    setIsProceedingToCheckout(true);
    setProceedToCheckoutError('');

    const productsObj = {};
    addProductData?.available_products?.forEach((product) => {
      if (selectedProducts[product.product_id]) {
        productsObj[product.product_id] = Number(product.min_quantity);
      }
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
      opened={productOpened}
      onClose={closeProduct}
      title={__('Add new products to group', 'edwiser-bridge-pro')}
      withinPortal={false}
      closeOnClickOutside={false}
      size="xl"
    >
      <div className="actions__product-modal">
        {proceedToCheckoutError && (
          <div className="product-modal__error">{proceedToCheckoutError}</div>
        )}
        <div className="product-modal__content">
          <TextInput
            className="content__search"
            leftSectionPointerEvents="none"
            leftSection={<Icons.search />}
            placeholder={__('Search product', 'edwiser-bridge-pro')}
            disabled={isAddProductLoading || isProceedingToCheckout}
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
          />
          {isAddProductLoading ? (
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
          ) : filteredProducts.length > 0 ? (
            <div className="content__products-list">
              <div className="products-list__header">
                <div>{__('Product name', 'edwiser-bridge-pro')}</div>
                <div>{__('Price', 'edwiser-bridge-pro')}</div>
                <div>{__('Seats', 'edwiser-bridge-pro')}</div>
                <div>{__('Total price', 'edwiser-bridge-pro')}</div>
              </div>
              {filteredProducts.map((product) => (
                <React.Fragment key={product.product_id}>
                  <div className="product-list__separator" />
                  <div
                    className={`products-list__item-row ${
                      selectedProducts[product.product_id] ? 'selected' : ''
                    }`}
                  >
                    <div>
                      <Checkbox
                        label={decodeHTMLEntities(product.product_name)}
                        checked={selectedProducts[product.product_id] || false}
                        onChange={() => handleProductToggle(product.product_id)}
                      />
                    </div>
                    <div>
                      {addProductData.currency_symbol}
                      {product.price}
                    </div>
                    <div>{product.min_quantity}</div>
                    <div>
                      {addProductData.currency_symbol}
                      {selectedProducts[product.product_id]
                        ? product.price * product.min_quantity
                        : 0}
                    </div>
                  </div>
                </React.Fragment>
              ))}
            </div>
          ) : (
            <div className="content__products-list-empty">
              <div className="products-list-empty__title">
                {__('No products available', 'edwiser-bridge-pro')}
              </div>
              <div className="products-list-empty__description">
                {searchTerm
                  ? `${__(
                      'No products found matching',
                      'edwiser-bridge-pro'
                    )} "${searchTerm}"`
                  : __(
                      'There are no products available to add to this group.',
                      'edwiser-bridge-pro'
                    )}
              </div>
            </div>
          )}
          <div className="content__products-total">
            <span className="products-total__label">
              {__('Total :', 'edwiser-bridge-pro')}
            </span>
            <span className="products-total__value">
              {addProductData?.currency_symbol}
              {grossTotal}
            </span>
          </div>
        </div>
        <div className="product-modal__action">
          <button
            className="btn__action-confirm"
            disabled={selectedCount === 0 || isAddProductLoading}
            onClick={handleProceedToCheckout}
          >
            {isProceedingToCheckout && <Icons.loader />}
            {__('Proceed to checkout', 'edwiser-bridge-pro')}
          </button>
          <button className="btn__action-cancel" onClick={closeProduct}>
            {__('Cancel', 'edwiser-bridge-pro')}
          </button>
        </div>
      </div>
    </Modal>
  );
}

export default AddProduct;

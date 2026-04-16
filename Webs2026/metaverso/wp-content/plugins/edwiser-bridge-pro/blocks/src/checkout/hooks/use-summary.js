import apiFetch from '@wordpress/api-fetch';
import { useCallback, useEffect, useState } from 'react';

export function useSummary() {
  const [cartItems, setCartItems] = useState([]);
  const [cartCoupons, setCartCoupons] = useState([]);
  const [cartTotals, setCartTotals] = useState({});
  const [needsShipping, setNeedsShipping] = useState(false);
  const [shippingRates, setShippingRates] = useState([]);
  const [subscriptions, setSubscriptions] = useState([]);
  const [isLoadingSummary, setIsLoadingSummary] = useState(false);
  const [applyCouponError, setApplyCouponError] = useState('');

  const getCart = useCallback(async (showLoading = true) => {
    if (showLoading) setIsLoadingSummary(true);

    try {
      const cart = await apiFetch({ path: `/wc/store/v1/cart` });

      setCartItems(cart.items);
      setCartCoupons(cart.coupons);
      setCartTotals(cart.totals);
      setNeedsShipping(cart.needs_shipping);
      setShippingRates(cart.shipping_rates);
      setSubscriptions(cart.extensions?.subscriptions || []);

      const productIds = cart.items.map((item) => ({
        id: item.id,
        key: item.key,
      }));
      if (productIds.length > 0) {
        await getProductMeta(productIds, cart.items);
      } else {
        setCartItems([]);
      }
    } catch (error) {
      console.error('Error fetching cart:', error);
    } finally {
      setIsLoadingSummary(false);
    }
  }, []);

  // Fetch product metadata
  const getProductMeta = useCallback(async (productIds, cartItems) => {
    try {
      const productsMeta = await apiFetch({
        path: `/eb/api/v1/cart/products-meta`,
        method: 'POST',
        data: { productIds },
      });

      const updatedCartItems = cartItems.map((item) => ({
        ...item,
        meta: productsMeta.find((meta) => meta.key === item.key) || {},
      }));

      setCartItems(updatedCartItems);
    } catch (error) {
      console.error('Error fetching product meta:', error);
    }
  }, []);

  // Apply coupon
  const handleApplyCoupon = useCallback(
    async (code) => {
      try {
        await apiFetch({
          path: `/wc/store/v1/cart/apply-coupon?code=${code}`,
          method: 'POST',
          headers: { Nonce: ebStoreApiNonce.nonce },
        });

        getCart(false);

        return true;
      } catch (error) {
        console.error('Error applying coupon:', error);
        setApplyCouponError(error.message);

        return false;
      }
    },
    [getCart]
  );

  // Remove coupon
  const handleRemoveCoupon = useCallback(
    async (appliedCouponCode) => {
      try {
        await apiFetch({
          path: `/wc/store/v1/cart/remove-coupon?code=${appliedCouponCode}`,
          method: 'POST',
          headers: { Nonce: ebStoreApiNonce.nonce },
        });

        getCart(false);

        return true;
      } catch (error) {
        console.error('Error removing coupon:', error);
        return false;
      }
    },
    [getCart]
  );

  // Fetch initial data on component mount
  useEffect(() => {
    getCart();
  }, []);

  return {
    isLoadingSummary,
    cartItems,
    cartTotals,
    cartCoupons,
    needsShipping,
    shippingRates,
    subscriptions,
    applyCouponError,
    handleApplyCoupon,
    handleRemoveCoupon,
    getCart,
  };
}

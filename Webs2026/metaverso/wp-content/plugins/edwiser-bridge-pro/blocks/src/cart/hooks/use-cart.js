import { useState, useCallback, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';

export function useCart() {
  const [cartItems, setCartItems] = useState([]);
  const [cartTotal, setCartTotal] = useState({});
  const [itemCount, setItemCount] = useState(0);
  const [couponsEnabled, setCouponsEnabled] = useState(false);
  const [cartCoupon, setCartCoupon] = useState({});
  const [loadingCartItems, setLoadingCartItems] = useState(false);
  const [loadingCartTotal, setLoadingCartTotal] = useState(false);
  const [shopPageUrl, setShopPageUrl] = useState('');
  const [checkoutUrl, setCheckoutUrl] = useState('');
  const [includesTax, setIncludesTax] = useState(false);
  const [singleTaxTotal, setSingleTaxTotal] = useState(false);
  const [shippingAddress, setShippingAddress] = useState({});
  const [shippingRates, setShippingRates] = useState([]);
  const [needsShipping, setNeedsShipping] = useState(false);
  const [subscriptions, setSubscriptions] = useState([]);
  const [enableShippingCalculator, setEnableShippingCalculator] =
    useState(true);
  const [applyCouponError, setApplyCouponError] = useState('');

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

  // Fetch cart items
  const fetchCartItems = useCallback(
    async (showLoading = true) => {
      if (showLoading) setLoadingCartItems(true);

      try {
        const cart = await apiFetch({ path: `/wc/store/v1/cart` });

        setCartItems(cart.items);
        setItemCount(cart.items_count);

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
        setLoadingCartItems(false);
      }
    },
    [getProductMeta]
  );

  // Fetch cart total
  const fetchCartTotal = useCallback(async (showLoading = true) => {
    if (showLoading) setLoadingCartTotal(true);

    try {
      const cart = await apiFetch({ path: `/wc/store/v1/cart` });

      setCartTotal(cart.totals);
      setCartCoupon(cart.coupons);
      setShippingAddress(cart.shipping_address);
      setShippingRates(cart.shipping_rates);
      setNeedsShipping(cart.needs_shipping);
      setSubscriptions(cart.extensions?.subscriptions || []);
    } catch (error) {
      console.error('Error fetching cart:', error);
    } finally {
      setLoadingCartTotal(false);
    }
  }, []);

  // Update item quantity
  const handleQuantityUpdate = async (
    itemKey,
    newQuantity,
    currentItemIndex
  ) => {
    const updatedItems = [...cartItems];
    const item = updatedItems[currentItemIndex];
    const originalQuantity = item.quantity;

    // Optimistically update UI
    updatedItems[currentItemIndex] = {
      ...item,
      quantity: newQuantity,
      totals: {
        ...item.totals,
        line_subtotal:
          (item.totals.line_subtotal / originalQuantity) * newQuantity,
      },
    };

    setCartItems(updatedItems);
    setItemCount((prevCount) => prevCount + (newQuantity - originalQuantity));

    try {
      await apiFetch({
        path: `/wc/store/v1/cart/update-item?key=${itemKey}&quantity=${newQuantity}`,
        method: 'POST',
        headers: { Nonce: ebStoreApiNonce.nonce },
      });

      fetchCartItems(false);
      fetchCartTotal(false);
    } catch (error) {
      console.error('Error updating cart item:', error);
      fetchCartItems(false);
      fetchCartTotal(false);
    }
  };

  // Remove item from cart
  const handleRemoveItem = async (itemKey, currentItemIndex) => {
    const itemToRemove = cartItems[currentItemIndex];
    const updatedItems = cartItems.filter(
      (_, index) => index !== currentItemIndex
    );

    setCartItems(updatedItems);
    setItemCount((prevCount) => prevCount - itemToRemove.quantity);

    try {
      await apiFetch({
        path: `/wc/store/v1/cart/remove-item?key=${itemKey}`,
        method: 'POST',
        headers: { Nonce: ebStoreApiNonce.nonce },
      });

      fetchCartItems(false);
      fetchCartTotal(false);
    } catch (error) {
      console.error('Error removing item:', error);
      fetchCartItems(false);
      fetchCartTotal(false);
    }
  };

  // Apply coupon
  const handleApplyCoupon = async (code) => {
    try {
      await apiFetch({
        path: `/wc/store/v1/cart/apply-coupon?code=${code}`,
        method: 'POST',
        headers: { Nonce: ebStoreApiNonce.nonce },
      });

      fetchCartTotal();
      return true;
    } catch (error) {
      console.error('Error applying coupon:', error);
      setApplyCouponError(error.message);
      return false;
    }
  };

  // Remove coupon
  const handleRemoveCoupon = async (appliedCouponCode) => {
    try {
      await apiFetch({
        path: `/wc/store/v1/cart/remove-coupon?code=${appliedCouponCode}`,
        method: 'POST',
        headers: { Nonce: ebStoreApiNonce.nonce },
      });

      fetchCartTotal();
      return true;
    } catch (error) {
      console.error('Error removing coupon:', error);
      return false;
    }
  };

  // Fetch cart URLs
  const getCartMeta = useCallback(async () => {
    const cartMeta = await apiFetch({ path: `/eb/api/v1/cart/meta` });

    setCouponsEnabled(cartMeta.coupons_enabled);
    setShopPageUrl(cartMeta.shop_page_url);
    setCheckoutUrl(cartMeta.checkout_url);
    setIncludesTax(cartMeta.includes_tax);
    setSingleTaxTotal(cartMeta.single_tax_total);
    setEnableShippingCalculator(cartMeta.enable_shipping_calculator);
  }, []);

  // Fetch initial data on component mount
  useEffect(() => {
    fetchCartItems();
    fetchCartTotal();
    getCartMeta();
  }, [fetchCartItems, fetchCartTotal, getCartMeta]);

  return {
    cartItems,
    cartTotal,
    itemCount,
    couponsEnabled,
    cartCoupon,
    loadingCartItems,
    loadingCartTotal,
    shopPageUrl,
    checkoutUrl,
    includesTax,
    singleTaxTotal,
    handleQuantityUpdate,
    handleRemoveItem,
    handleApplyCoupon,
    handleRemoveCoupon,
    shippingAddress,
    shippingRates,
    needsShipping,
    fetchCartTotal,
    subscriptions,
    enableShippingCalculator,
    applyCouponError,
  };
}

import apiFetch from '@wordpress/api-fetch';
import { useCallback, useEffect, useState } from 'react';

export function useProduct(productId) {
  const [course, setCourse] = useState(null);
  const [courseReviews, setCourseReviews] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [associatedCourses, setAssociatedCourses] = useState([]);
  const [variations, setVariations] = useState([]);
  const [attributeNames, setAttributeNames] = useState([]);
  const [attributeOptions, setAttributeOptions] = useState({});
  const [cartItems, setCartItems] = useState([]);
  const [inCart, setInCart] = useState(false);

  const fetchProductData = useCallback(async () => {
    setIsLoading(true);
    try {
      // Fetch main product details
      const productDetails = await apiFetch({
        path: `/wc/store/v1/products/${productId}`,
      });

      // Fetch additional product data
      const additionalData = await apiFetch({
        path: `/eb/api/v1/shop/product/${productId}`,
      });

      // Fetch product reviews
      const reviews = await apiFetch({
        path: `/wc/store/v1/products/reviews/?product_id=${productId}`,
      });

      // Fetch cart items
      const cartItems = await apiFetch({
        path: `/wc/store/v1/cart/items`,
      });

      // Process course variations
      if (additionalData?.course_variations) {
        const attributeKeys = Object.keys(
          additionalData.course_variations.attributes
        );

        setAttributeNames(attributeKeys);
        setAttributeOptions(additionalData.course_variations.attributes);
        setVariations(additionalData.course_variations.variations);
      }

      // Update state
      setCourse({
        ...productDetails,
        ...additionalData,
      });
      setCourseReviews(reviews);
      setAssociatedCourses(additionalData?.associated_courses || []);
      setCartItems(cartItems.map((item) => item.id));
      setInCart(cartItems.some((item) => item.id === productId));
    } catch (error) {
      console.error('Error fetching product data:', error);
    } finally {
      setIsLoading(false);
    }
  }, [productId]);

  useEffect(() => {
    fetchProductData();
  }, [fetchProductData]);

  return {
    course,
    courseReviews,
    isLoading,
    associatedCourses,
    setAssociatedCourses,
    variations,
    attributeOptions,
    attributeNames,
    cartItems,
    inCart,
  };
}

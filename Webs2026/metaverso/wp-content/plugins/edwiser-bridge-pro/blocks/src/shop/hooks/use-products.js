import { useState, useCallback, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';

export function useProducts(options) {
  const {
    perPage = 8,
    category = 'all',
    sortBy = 'default',
    currentPage = 1,
  } = options;

  const [products, setProducts] = useState([]);
  const [cartItems, setCartItems] = useState([]);
  const [categories, setCategories] = useState([]);
  const [totalProducts, setTotalProducts] = useState(0);
  const [totalPages, setTotalPages] = useState(0);
  const [isLoading, setIsLoading] = useState(false);

  // Fetch cart items
  const getCartItems = useCallback(async () => {
    try {
      const data = await apiFetch({ path: '/wc/store/v1/cart/items' });
      setCartItems(data.map((item) => item.id));
    } catch (error) {
      console.error('Error fetching cart items:', error);
    }
  }, []);

  // Fetch product metadata
  const getProductMeta = useCallback(async (productIds, products) => {
    try {
      const productsMeta = await apiFetch({
        path: `/eb/api/v1/shop/products-meta`,
        method: 'POST',
        data: { productIds },
      });

      const updatedProducts = products.map((item) => ({
        ...item,
        meta: productsMeta.find((meta) => meta.id === item.id) || {},
      }));

      setProducts(updatedProducts);
    } catch (error) {
      console.error('Error fetching products meta:', error);
    }
  }, []);

  // Fetch categories
  const fetchCategories = useCallback(async () => {
    try {
      const data = await apiFetch({ path: '/wc/store/v1/products/categories' });
      setCategories(data);
    } catch (error) {
      console.error('Error fetching categories:', error);
    }
  }, []);

  // Fetch products with dynamic filtering
  const fetchProducts = useCallback(async () => {
    setIsLoading(true);
    try {
      const queryParams = new URLSearchParams({
        per_page: perPage.toString(),
        page: currentPage.toString(),
      });

      if (category && category !== 'all') {
        queryParams.append('category', category);
      }

      if (sortBy && sortBy !== 'default') {
        const sortMapping = {
          popularity: { orderby: 'popularity' },
          rating: { orderby: 'rating' },
          date: { orderby: 'date' },
          price: { orderby: 'price', order: 'asc' },
          'price-desc': { orderby: 'price', order: 'desc' },
        };

        const { orderby, order } = sortMapping[sortBy] || {};
        if (orderby) queryParams.append('orderby', orderby);
        if (order) queryParams.append('order', order);
      }

      const response = await apiFetch({
        path: `/wc/store/v1/products?${queryParams.toString()}`,
        parse: false,
      });

      setTotalPages(parseInt(response.headers.get('X-WP-TotalPages')));
      setTotalProducts(parseInt(response.headers.get('X-WP-Total')));

      const data = await response.json();

      await getCartItems();
      await getProductMeta(
        data.map((item) => item.id),
        data
      );
    } catch (error) {
      console.error('Error fetching products:', error);
    } finally {
      setIsLoading(false);
    }
  }, [perPage, currentPage, category, sortBy]);

  useEffect(() => {
    fetchProducts();
    fetchCategories();
  }, [fetchProducts, fetchCategories]);

  return {
    products,
    cartItems,
    categories,
    totalProducts,
    totalPages,
    isLoading,
  };
}

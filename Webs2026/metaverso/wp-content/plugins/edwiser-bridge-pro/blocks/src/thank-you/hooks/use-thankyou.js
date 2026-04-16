import apiFetch from '@wordpress/api-fetch';
import { useCallback, useEffect, useState } from 'react';

export function useThankYou({ orderKey }) {
  const [orderItems, setOrderItems] = useState([]);
  const [recommendedCourses, setRecommendedCourses] = useState([]);
  const [coursePageUrl, setCoursePageUrl] = useState('');
  const [myCoursePageUrl, setMyCoursePageUrl] = useState('');
  const [redirectEnabled, setRedirectEnabled] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [remainingTime, setRemainingTime] = useState(10);
  const [isRedirectPaused, setIsRedirectPaused] = useState(false);

  const getOrderDetails = useCallback(async () => {
    try {
      setIsLoading(true);
      const data = await apiFetch({
        path: `/eb/api/v1/shop/order/${orderKey}`,
      });

      if (!data || !data.items) {
        throw new Error('Invalid order data');
      }

      setOrderItems(data.items);
      setRecommendedCourses(data.recommended_courses || []);
      setCoursePageUrl(data.course_page_url);
      setMyCoursePageUrl(data.my_course_page_url);
      setRedirectEnabled(data.redirect_after_success);
    } catch (err) {
      setCoursePageUrl(err.course_page_url);
      setOrderItems([]);
      setRedirectEnabled(false);
      setRecommendedCourses([]);
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    getOrderDetails();
  }, [orderKey, getOrderDetails]);

  useEffect(() => {
    let timerId;
    if (redirectEnabled && !isRedirectPaused && remainingTime > 0) {
      timerId = setInterval(() => {
        setRemainingTime((prev) => prev - 1);
      }, 1000);
    }

    if (remainingTime === 0 && redirectEnabled) {
      window.location.href = myCoursePageUrl;
    }

    return () => {
      if (timerId) clearInterval(timerId);
    };
  }, [redirectEnabled, remainingTime, isRedirectPaused, myCoursePageUrl]);

  return {
    orderItems,
    recommendedCourses,
    coursePageUrl,
    myCoursePageUrl,
    redirectEnabled,
    isLoading,
    remainingTime,
    isRedirectPaused,
    setIsRedirectPaused,
  };
}

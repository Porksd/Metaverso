import { useEffect, useState } from 'react';
import { __ } from '@wordpress/i18n';

export function useGroupPurchase(cartItems) {
  const [isGroupPurchase, setIsGroupPurchase] = useState(false);
  const [groupPurchaseError, setGroupPurchaseError] = useState('');
  const [groupPurchaseWarning, setGroupPurchaseWarning] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [previousQuantities, setPreviousQuantities] = useState({});

  const groupPurchaseEnabledItems = cartItems.filter(
    (item) => item?.meta?.self_enroll_enabled
  );

  const showCheckbox =
    cartItems.length > 1 && groupPurchaseEnabledItems.length >= 2;

  const hasEqualQuantities = () => {
    const quantities = groupPurchaseEnabledItems.map((item) => item.quantity);
    return new Set(quantities).size === 1;
  };

  const updateGroupPurchaseStatus = async (checked) => {
    if (checked && !hasEqualQuantities()) {
      setGroupPurchaseError(
        __(
          'All group purchase enabled products must have the same quantity',
          'edwiser-bridge-pro'
        )
      );
      setGroupPurchaseWarning('');
      return;
    }

    setIsLoading(true);
    setGroupPurchaseError('');
    setGroupPurchaseWarning('');

    try {
      // Create form data for the AJAX request
      const formData = new FormData();
      formData.append('action', 'eb_update_group_purchase_status');
      formData.append('security', window.eb_ajax_object.nonce);
      formData.append('single_group', checked ? '1' : '0');

      // Make the AJAX request
      const response = await fetch(window.eb_ajax_object.ajax_url, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
      });

      const data = await response.json();

      if (data.success) {
        setIsGroupPurchase(checked);
        // Update previous quantities when successfully enabling group purchase
        if (checked) {
          const newQuantities = {};
          groupPurchaseEnabledItems.forEach((item) => {
            newQuantities[item.key] = item.quantity;
          });
          setPreviousQuantities(newQuantities);
        }
      } else {
        if (data.data.type === 'warning') {
          setIsGroupPurchase(true);
          setGroupPurchaseWarning(data.data.message);
          setGroupPurchaseError('');
        } else {
          setIsGroupPurchase(false);
          setGroupPurchaseError(data.data.message);
          setGroupPurchaseWarning('');
        }
      }
    } catch (error) {
      console.error('Error updating group purchase status:', error);
      setGroupPurchaseWarning('');
      setGroupPurchaseError(
        __(
          'Failed to update group purchase status. Please try again.',
          'edwiser-bridge-pro'
        )
      );
      setIsGroupPurchase(false);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    const fetchGroupPurchaseStatus = async () => {
      try {
        // Create form data for the AJAX request
        const formData = new FormData();
        formData.append('action', 'eb_group_purchase_status');
        formData.append('security', window.eb_ajax_object.nonce);

        // Make the AJAX request
        const response = await fetch(window.eb_ajax_object.ajax_url, {
          method: 'POST',
          body: formData,
          credentials: 'same-origin',
        });

        const data = await response.json();

        if (data.success) {
          setIsGroupPurchase(data.data.same_group_purchase_enabled);

          // Initialize previous quantities when loading the component
          if (data.data.same_group_purchase_enabled) {
            const newQuantities = {};
            groupPurchaseEnabledItems.forEach((item) => {
              newQuantities[item.key] = item.quantity;
            });
            setPreviousQuantities(newQuantities);
          }
        }
      } catch (error) {
        console.error('Error fetching group purchase status:', error);
      }
    };

    fetchGroupPurchaseStatus();
  }, []);

  useEffect(() => {
    // Check if quantities are equal
    if (!hasEqualQuantities()) {
      // If group purchase was enabled but quantities are now unequal, disable it
      if (isGroupPurchase) {
        setGroupPurchaseError(
          'All group purchase enabled products must have the same quantity'
        );
        setGroupPurchaseWarning('');

        // Asynchronously update the server state
        const updateServerState = async () => {
          try {
            const formData = new FormData();
            formData.append('action', 'eb_update_group_purchase_status');
            formData.append('security', window.eb_ajax_object.nonce);
            formData.append('single_group', '0');

            await fetch(window.eb_ajax_object.ajax_url, {
              method: 'POST',
              body: formData,
              credentials: 'same-origin',
            });

            // Update local state after server update
            setIsGroupPurchase(false);
          } catch (error) {
            console.error('Error updating group purchase status:', error);
          }
        };

        updateServerState();
      }
    } else {
      setGroupPurchaseError('');
    }

    // Check if quantities have changed since last time
    let quantitiesChanged = false;
    if (Object.keys(previousQuantities).length > 0) {
      groupPurchaseEnabledItems.forEach((item) => {
        if (
          previousQuantities[item.key] !== undefined &&
          previousQuantities[item.key] !== item.quantity
        ) {
          quantitiesChanged = true;
        }
      });
    }

    // Update previous quantities after checking for changes
    if (quantitiesChanged || Object.keys(previousQuantities).length === 0) {
      const newQuantities = {};
      groupPurchaseEnabledItems.forEach((item) => {
        newQuantities[item.key] = item.quantity;
      });
      setPreviousQuantities(newQuantities);
    }
  }, [cartItems, isGroupPurchase]);

  return {
    isGroupPurchase,
    groupPurchaseError,
    groupPurchaseWarning,
    isLoading,
    showCheckbox,
    updateGroupPurchaseStatus,
  };
}

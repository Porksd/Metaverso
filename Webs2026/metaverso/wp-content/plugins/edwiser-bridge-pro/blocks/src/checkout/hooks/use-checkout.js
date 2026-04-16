import apiFetch from '@wordpress/api-fetch';
import { useCallback, useEffect, useRef, useState } from 'react';

export function useCheckout(getCart) {
  const [initialShippingAddress, setInitialShippingAddress] = useState({});
  const [initialBillingAddress, setInitialBillingAddress] = useState({});
  const [customerId, setCustomerId] = useState(0);

  const [cartUrl, setCartUrl] = useState('');

  const [loginUrl, setLoginUrl] = useState('');
  const [enableGuestCheckout, setEnableGuestCheckout] = useState(false);
  const [enableLogin, setEnableLogin] = useState(false);
  const [enableSignup, setEnableSignup] = useState(false);
  const [enableSubscriptionsSignup, setEnableSubscriptionsSignup] =
    useState(false);

  const [placeOrderBtnText, setPlaceOrderBtnText] = useState('Place Order');
  const [privacyPolicy, setPrivacyPolicy] = useState('');
  const [couponsEnabled, setCouponsEnabled] = useState(false);
  const [includesTax, setIncludesTax] = useState(false);
  const [singleTaxTotal, setSingleTaxTotal] = useState(false);
  const [customFields, setCustomFields] = useState([]);
  const [groups, setGroups] = useState([]);

  const [countries, setCountries] = useState([]);
  const [countriesData, setCountriesData] = useState({});
  const [countriesLocaleData, setCountriesLocaleData] = useState({});
  const [localeFields, setLocaleFields] = useState({});
  const [states, setStates] = useState([]);
  const [billingStates, setBillingStates] = useState([]);
  const [billingLocaleFields, setBillingLocaleFields] = useState({});
  const [postcodeValid, setPostcodeValid] = useState(true);
  const [countryName, setCountryName] = useState('');
  const [stateName, setStateName] = useState('');

  const [showCustomerNote, setShowCustomerNote] = useState(false);
  const [customerNote, setCustomerNote] = useState('');
  const [enableGiftPurchase, setEnableGiftPurchase] = useState(false);
  const [giftPurchase, setGiftPurchase] = useState(false);
  const [giftPurchaseData, setGiftPurchaseData] = useState({
    recipient_first_name: '',
    recipient_last_name: '',
    recipient_email: '',
    recipient_phone: '',
  });
  const [groupsData, setGroupsData] = useState({});
  const [createSameGroup, setCreateSameGroup] = useState(false);
  const [customFieldsData, setCustomFieldsData] = useState({});
  const [customerPassword, setCustomerPassword] = useState('');
  const [validationErrors, setValidationErrors] = useState({
    password: '',
    customerNote: '',
    giftPurchase: {},
    groupsData: {},
    customFields: {},
  });

  const [isLoadingCheckout, setIsLoadingCheckout] = useState(false);

  const [paymentOptions, setPaymentOptions] = useState([]);
  const [selectedPaymentMethod, setSelectedPaymentMethod] = useState('');
  const [paymentError, setPaymentError] = useState('');
  const [isPlacingOrder, setIsPlacingOrder] = useState(false);

  const shippingAddressRef = useRef(null);
  const billingAddressRef = useRef(null);
  const contactInformationRef = useRef(null);

  const updatePaymentMethod = (value) => {
    setSelectedPaymentMethod(value);
  };

  // Update customer password
  const updateCustomerPassword = (pass) => {
    setCustomerPassword(pass);

    if (pass.trim()) {
      setValidationErrors({
        ...validationErrors,
        password: '',
      });
    }
  };

  // Update customer note
  const updateCustomerNote = (note) => {
    setCustomerNote(note);

    if (note.trim()) {
      setValidationErrors({
        ...validationErrors,
        customerNote: '',
      });
    }
  };

  const toggleCustomerNote = (value) => {
    setShowCustomerNote(value);

    if (!value) {
      setValidationErrors({
        ...validationErrors,
        customerNote: '',
      });
    }
  };

  // Update gift purchase data
  const updateGiftPurchaseData = (data) => {
    setGiftPurchaseData({
      ...giftPurchaseData,
      ...data,
    });

    const fieldName = Object.keys(data)[0];
    if (data[fieldName]?.trim()) {
      setValidationErrors({
        ...validationErrors,
        giftPurchase: {
          ...validationErrors.giftPurchase,
          [fieldName]: '',
        },
      });
    }
  };

  const toggleGiftPurchase = (value) => {
    setGiftPurchase(value);

    if (!value) {
      setValidationErrors({
        ...validationErrors,
        giftPurchase: {},
      });
    }
  };

  // Update groups data
  const updateGroupsData = (id, value) => {
    setGroupsData({
      ...groupsData,
      [id]: value,
    });

    if (value.trim()) {
      setValidationErrors({
        ...validationErrors,
        groupsData: {
          ...validationErrors.groupsData,
          [id]: '',
        },
      });
    }
  };

  // Update custom fields data
  const updateCustomFieldsData = (name, value) => {
    if (value === undefined) {
      const updatedData = { ...customFieldsData };
      delete updatedData[name];
      setCustomFieldsData(updatedData);
    } else {
      setCustomFieldsData({
        ...customFieldsData,
        [name]: value,
      });
    }

    if (value === true || (typeof value === 'string' && value.trim())) {
      setValidationErrors({
        ...validationErrors,
        customFields: {
          ...validationErrors.customFields,
          [name]: '',
        },
      });
    }
  };

  // Validate checkout data
  const validateCheckoutData = () => {
    let isValid = true;
    const newErrors = {
      customerNote: '',
      giftPurchase: {},
      groupsData: {},
      customFields: {},
    };

    // Validate order note if shown
    if (showCustomerNote && !customerNote.trim()) {
      newErrors.customerNote = 'Please enter an order note';
      isValid = false;
    }

    // Validate gift purchase data if enabled
    if (giftPurchase) {
      if (!giftPurchaseData.recipient_first_name.trim()) {
        newErrors.giftPurchase.recipient_first_name = 'First name is required';
        isValid = false;
      }

      if (!giftPurchaseData.recipient_last_name.trim()) {
        newErrors.giftPurchase.recipient_last_name = 'Last name is required';
        isValid = false;
      }

      if (!giftPurchaseData.recipient_email.trim()) {
        newErrors.giftPurchase.recipient_email = 'Email is required';
        isValid = false;
      } else if (!/^\S+@\S+\.\S+$/.test(giftPurchaseData.recipient_email)) {
        newErrors.giftPurchase.recipient_email = 'Please enter a valid email';
        isValid = false;
      }
    }

    // Validate groups data - all fields are required
    if (groups?.length > 0) {
      groups.forEach((group) => {
        if (!groupsData[group.id] || !groupsData[group.id].trim()) {
          newErrors.groupsData[group.id] = `${group.label} is required`;
          isValid = false;
        }
      });
    }

    // Validate custom fields - only validate required fields
    if (customFields?.length > 0) {
      customFields.forEach((field) => {
        if (field.required) {
          const value = customFieldsData[field.name];

          if (field.type === 'checkbox' && !value) {
            newErrors.customFields[
              field.name
            ] = `${field.label} must be checked`;
            isValid = false;
          } else if (
            typeof value !== 'boolean' &&
            (!value || !String(value).trim())
          ) {
            newErrors.customFields[field.name] = `${field.label} is required`;
            isValid = false;
          }
        }
      });
    }

    setValidationErrors(newErrors);
    return isValid;
  };

  // Fetch all available countries
  const getCountries = useCallback(
    async (countryCode, stateCode, billingCountryCode, billingStateCode) => {
      try {
        const response = await apiFetch({
          path: '/eb/api/v1/cart/countries',
        });

        const formattedCountries = response.map((country) => ({
          value: country.code,
          label: country.name,
        }));

        const countriesWithStates = {};
        const countriesLocale = {};

        response.forEach((country) => {
          countriesWithStates[country.code] = country;
          countriesLocale[country.code] = country.locale;
        });

        setCountries(formattedCountries);
        setCountriesData(countriesWithStates);
        setCountriesLocaleData(countriesLocale);

        setLocaleFields(countriesLocale[countryCode]);
        updateStates(countryCode, countriesWithStates);

        setBillingLocaleFields(countriesLocale[billingCountryCode]);
        updateBillingStates(billingCountryCode, countriesWithStates);

        const country = response.find((c) => c.code === countryCode);
        const state = country?.states.find((s) => s.code === stateCode);

        if (country) setCountryName(country.name);
        if (state) {
          setStateName(state.name);
        } else {
          setStateName('');
        }
      } catch (error) {
        console.error('Error fetching countries:', error);
      }
    },
    []
  );

  const updateStates = (countryCode, countryDataSource = null) => {
    const dataSource = countryDataSource || countriesData;

    if (!countryCode || !dataSource[countryCode]) {
      setStates([]);
      return;
    }

    const countryData = dataSource[countryCode];

    if (countryData?.states?.length > 0) {
      const formattedStates = countryData.states.map((state) => ({
        value: state.code,
        label: state.name,
      }));
      setStates(formattedStates);
    } else {
      setStates([]);
    }
  };

  const updateBillingStates = (countryCode, countryDataSource = null) => {
    const dataSource = countryDataSource || countriesData;

    if (!countryCode || !dataSource[countryCode]) {
      setBillingStates([]);
      return;
    }

    const countryData = dataSource[countryCode];

    if (countryData?.states?.length > 0) {
      const formattedStates = countryData.states.map((state) => ({
        value: state.code,
        label: state.name,
      }));
      setBillingStates(formattedStates);
    } else {
      setBillingStates([]);
    }
  };

  const updateLocaleFields = (countryCode) => {
    if (!countryCode || !countriesLocaleData[countryCode]) {
      setLocaleFields({});
      return;
    }

    setLocaleFields(countriesLocaleData[countryCode]);
  };

  const updateBillingLocaleFields = (countryCode) => {
    if (!countryCode || !countriesLocaleData[countryCode]) {
      setBillingLocaleFields({});
      return;
    }

    setBillingLocaleFields(countriesLocaleData[countryCode]);
  };

  // Validate postcode based on country
  const validatePostcode = async (postcode, countryCode) => {
    if (!postcode || !countryCode) {
      setPostcodeValid(true);
      return true;
    }

    try {
      const response = await apiFetch({
        path: `/eb/api/v1/cart/validate-postcode?country=${countryCode}&postcode=${postcode}`,
      });

      setPostcodeValid(response.valid);

      if (!response.valid) {
        return false;
      }

      return true;
    } catch (error) {
      console.error('Error validating postcode:', error);
      setPostcodeValid(false);
      return false;
    }
  };

  const getCheckout = useCallback(async (showLoading = true) => {
    if (showLoading) setIsLoadingCheckout(true);

    try {
      const checkout = await apiFetch({
        path: `/wc/store/v1/checkout`,
        headers: { Nonce: ebStoreApiNonce.nonce },
      });
      setInitialShippingAddress(checkout.shipping_address);
      setInitialBillingAddress(checkout.billing_address);
      setCustomerNote(checkout.customer_note);
      setShowCustomerNote(!!checkout.customer_note);
      setCustomerId(checkout.customer_id);

      getCountries(
        checkout.shipping_address.country,
        checkout.shipping_address.state,
        checkout.billing_address.country,
        checkout.billing_address.state
      );
    } catch (error) {
      console.error('Error fetching checkout:', error);

      if (
        error.code === 'woocommerce_rest_cart_empty' ||
        error.data.status === 400
      ) {
        window.location.href = `/cart`;
      }
    } finally {
      setIsLoadingCheckout(false);
    }
  }, []);

  // Fetch checkout meta
  const getCheckoutMeta = useCallback(async (showLoading = true) => {
    if (showLoading) setIsLoadingCheckout(true);

    try {
      const checkoutMeta = await apiFetch({ path: `/eb/api/v1/checkout/meta` });

      setPaymentOptions(checkoutMeta.payment_options);
      setSelectedPaymentMethod(checkoutMeta.payment_options[0].id);
      setGroups(checkoutMeta.groups);
      setCreateSameGroup(checkoutMeta.create_same_group);
      setCustomFields(checkoutMeta.custom_fields);
      setPlaceOrderBtnText(checkoutMeta.place_order_btn_text);
      setPrivacyPolicy(checkoutMeta.privacy_policy);
      setCouponsEnabled(checkoutMeta.coupons_enabled);
      setIncludesTax(checkoutMeta.includes_tax);
      setSingleTaxTotal(checkoutMeta.single_tax_total);
      setEnableGuestCheckout(checkoutMeta.enable_guest_checkout);
      setEnableLogin(checkoutMeta.enable_login);
      setEnableSignup(checkoutMeta.enable_signup);
      setEnableSubscriptionsSignup(checkoutMeta.enable_subscriptions_signup);
      setLoginUrl(checkoutMeta.login_url);
      setCartUrl(checkoutMeta.cart_page_url);
      setEnableGiftPurchase(checkoutMeta.enable_gift_purchase);

      if (checkoutMeta.custom_fields?.length > 0) {
        const initialCustomFieldsData = {};
        checkoutMeta.custom_fields.forEach((field) => {
          if (field.type === 'checkbox') {
            if (
              field.value === true ||
              field.value === 'on' ||
              field.value === '1' ||
              field.value === 1
            ) {
              initialCustomFieldsData[field.name] = 'on';
            }
          } else {
            initialCustomFieldsData[field.name] = field.value;
          }
        });
        setCustomFieldsData(initialCustomFieldsData);
      }
    } catch (error) {
      console.error('Error fetching checkout details:', error);
    } finally {
      setIsLoadingCheckout(false);
    }
  }, []);

  // Update shipping address in cart
  const updateAddress = async ({ shippingAddress, billingAddress }) => {
    try {
      await apiFetch({
        path: `/wc/store/v1/cart/update-customer`,
        method: 'POST',
        headers: { Nonce: ebStoreApiNonce.nonce },
        data: {
          shipping_address: shippingAddress,
          billing_address: billingAddress,
        },
      });

      getCart(false);
    } catch (error) {
      console.error('Error updating shipping address:', error);
    }
  };

  // Update shipping rate
  const updateShippingRate = async (option, packageId) => {
    try {
      await apiFetch({
        path: `/wc/store/v1/cart/select-shipping-rate?package_id=${packageId}&rate_id=${option.rate_id}`,
        method: 'POST',
        headers: { Nonce: ebStoreApiNonce.nonce },
      });

      getCart(false);

      return true;
    } catch (error) {
      console.error('Error updating shipping rate:', error);
      return false;
    }
  };

  const handleCheckout = async () => {
    let isContactInformationValid = true;
    if (contactInformationRef.current) {
      isContactInformationValid =
        contactInformationRef.current?.validateContactInfo();
    }

    let isShippingAddressValid = true;
    if (shippingAddressRef.current) {
      isShippingAddressValid = shippingAddressRef.current?.validateAddress();
    }

    let isBillingAddressValid = true;
    if (billingAddressRef.current) {
      isBillingAddressValid = billingAddressRef.current.validateAddress();
    }

    const isCheckoutDataValid = validateCheckoutData();

    if (
      isContactInformationValid &&
      isShippingAddressValid &&
      isBillingAddressValid &&
      isCheckoutDataValid
    ) {
      setIsPlacingOrder(true);
      const formData = new FormData();

      formData.append('billing_address', JSON.stringify(initialBillingAddress));
      formData.append(
        'shipping_address',
        JSON.stringify(initialShippingAddress)
      );
      formData.append('payment_method', selectedPaymentMethod);

      if (customerNote) {
        formData.append('customer_note', customerNote);
      }

      if (customerPassword) {
        formData.append('customer_password', customerPassword);
        formData.append('create_account', '1');
      }

      if (createSameGroup) {
        formData.append('cohort_name', groupsData.cohort_name);
      } else {
        for (const [key, value] of Object.entries(groupsData)) {
          formData.append(`diff_cohort_name[${key}]`, value);
        }
      }

      if (giftPurchase) {
        formData.append('purchase_for_someone_else', '1');
        formData.append(
          'recipient_first_name',
          giftPurchaseData.recipient_first_name
        );
        formData.append(
          'recipient_last_name',
          giftPurchaseData.recipient_last_name
        );
        formData.append('recipient_email', giftPurchaseData.recipient_email);
      }

      if (Object.keys(customFieldsData).length > 0) {
        for (const [key, value] of Object.entries(customFieldsData)) {
          formData.append(key, value);
        }
      }

      const response = await fetch('/wp-json/wc/store/v1/checkout', {
        method: 'POST',
        headers: {
          Nonce: ebStoreApiNonce.nonce,
        },
        body: formData,
      });

      if (!response.ok) {
        const error = await response.json();
        console.error('Unable to process order:', error?.message);
        setPaymentError(error?.message);
        setIsPlacingOrder(false);
        return;
      }

      const res = await response.json();

      if (
        res.payment_result &&
        res.payment_result.payment_status === 'success'
      ) {
        window.location.href = res.payment_result.redirect_url;
      } else if (
        res.payment_result &&
        res.payment_result.payment_status === 'failure'
      ) {
        const errorDetail = res.payment_result.payment_details?.find(
          (detail) => detail.key === 'error'
        );
        setPaymentError(
          errorDetail?.value || 'Payment failed. Please try again.'
        );
      }

      setIsPlacingOrder(false);
    }
  };

  // Fetch initial data on component mount
  useEffect(() => {
    getCheckoutMeta();
    getCheckout();
  }, [getCheckoutMeta, getCheckout]);

  return {
    isLoadingCheckout,
    initialShippingAddress,
    initialBillingAddress,
    customerId,
    loginUrl,
    enableGuestCheckout,
    enableLogin,
    enableSignup,
    enableSubscriptionsSignup,
    privacyPolicy,
    placeOrderBtnText,
    couponsEnabled,
    includesTax,
    singleTaxTotal,
    customFields,
    groups,
    countries,
    states,
    postcodeValid,
    countryName,
    stateName,
    localeFields,
    updateStates,
    updateLocaleFields,
    validatePostcode,
    updateShippingRate,
    updateAddress,
    billingStates,
    billingLocaleFields,
    updateBillingStates,
    updateBillingLocaleFields,

    customerNote,
    updateCustomerNote,
    giftPurchaseData,
    updateGiftPurchaseData,
    groupsData,
    updateGroupsData,
    customFieldsData,
    updateCustomFieldsData,
    customerPassword,
    updateCustomerPassword,

    showCustomerNote,
    toggleCustomerNote,
    giftPurchase,
    toggleGiftPurchase,
    validationErrors,

    handleCheckout,
    shippingAddressRef,
    billingAddressRef,
    contactInformationRef,

    paymentOptions,
    selectedPaymentMethod,
    updatePaymentMethod,

    isPlacingOrder,
    paymentError,
    enableGiftPurchase,
  };
}

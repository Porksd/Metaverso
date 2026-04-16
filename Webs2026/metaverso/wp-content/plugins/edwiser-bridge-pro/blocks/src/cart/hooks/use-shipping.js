import { useState, useEffect, useCallback } from 'react';
import apiFetch from '@wordpress/api-fetch';

export function useShipping(countryCode, stateCode, fetchCartTotal) {
  const [countries, setCountries] = useState([]);
  const [countriesData, setCountriesData] = useState({});
  const [states, setStates] = useState([]);
  const [postcodeValid, setPostcodeValid] = useState(true);
  const [validationError, setValidationError] = useState('');
  const [countryName, setCountryName] = useState('');
  const [stateName, setStateName] = useState('');
  const [countriesLocaleData, setCountriesLocaleData] = useState({});
  const [localeFields, setLocaleFields] = useState({});

  // Fetch all available countries
  const fetchCountries = useCallback(async () => {
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
  }, [countryCode, stateCode]);

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

  const updateLocaleFields = (countryCode) => {
    if (!countryCode || !countriesLocaleData[countryCode]) {
      setLocaleFields({});
      return;
    }

    setLocaleFields(countriesLocaleData[countryCode]);
  };

  // Validate postcode based on country
  const validatePostcode = async (postcode, countryCode) => {
    if (!postcode || !countryCode) {
      setPostcodeValid(true);
      setValidationError('');
      return true;
    }

    try {
      const response = await apiFetch({
        path: `/eb/api/v1/cart/validate-postcode?country=${countryCode}&postcode=${postcode}`,
      });

      setPostcodeValid(response.valid);

      if (!response.valid) {
        setValidationError(
          response.message || 'Invalid postcode for the selected country'
        );
        return false;
      }

      setValidationError('');
      return true;
    } catch (error) {
      console.error('Error validating postcode:', error);
      setPostcodeValid(false);
      setValidationError('Please enter a valid postcode');
      return false;
    }
  };

  // Update shipping address in cart
  const updateShippingAddress = async ({ country, city, state, postcode }) => {
    try {
      await apiFetch({
        path: `/wc/store/v1/cart/update-customer`,
        method: 'POST',
        headers: { Nonce: ebStoreApiNonce.nonce },
        data: {
          shipping_address: {
            country,
            city,
            state,
            postcode,
          },
        },
      });

      if (fetchCartTotal) {
        fetchCartTotal(false);
      }

      return true;
    } catch (error) {
      console.error('Error updating shipping address:', error);
      return false;
    }
  };

  // Update selected shipping rate
  const updateShippingRate = async (option, packageId) => {
    try {
      await apiFetch({
        path: `/wc/store/v1/cart/select-shipping-rate?package_id=${packageId}&rate_id=${option.rate_id}`,
        method: 'POST',
        headers: { Nonce: ebStoreApiNonce.nonce },
      });

      if (fetchCartTotal) {
        fetchCartTotal(false);
      }

      return true;
    } catch (error) {
      console.error('Error updating shipping rate:', error);
      return false;
    }
  };

  useEffect(() => {
    fetchCountries();
  }, [fetchCountries]);

  return {
    countries,
    states,
    postcodeValid,
    validationError,
    countryName,
    stateName,
    localeFields,
    updateStates,
    updateLocaleFields,
    updateShippingAddress,
    updateShippingRate,
    validatePostcode,
  };
}

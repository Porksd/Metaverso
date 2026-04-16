import { Accordion, Select, TextInput } from '@mantine/core';
import { __ } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react';
import { useShipping } from '../hooks/use-shipping';
import { formatShippingPrices } from '../utils';
import { Icons } from './icons';
import ShipmentRates from './shipment-rates';

function ShippingDetails({
  shippingAddress,
  shippingRates,
  includesTax,
  fetchCartTotal,
  subscriptions,
  enableShippingCalculator,
}) {
  const {
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
  } = useShipping(
    shippingAddress.country,
    shippingAddress.state,
    fetchCartTotal
  );
  const [address, setAddress] = useState({
    country: shippingAddress.country || '',
    state: shippingAddress.state || '',
    city: shippingAddress.city || '',
    postcode: shippingAddress.postcode || '',
  });
  const [accordionValue, setAccordionValue] = useState(null);
  const [errors, setErrors] = useState({
    country: '',
    state: '',
    city: '',
    postcode: '',
  });

  const primaryShippingOption = shippingRates[0]?.shipping_rates.find(
    (option) => option.selected === true
  );

  // Format address for display in accordion header
  const formatDisplayAddress = () => {
    const parts = [];

    if (shippingAddress.postcode) parts.push(shippingAddress.postcode);
    if (shippingAddress.city) parts.push(shippingAddress.city);
    if (stateName) parts.push(stateName);
    if (countryName) parts.push(countryName);

    return parts.length > 0 && parts.join(', ');
  };

  const getFieldProps = (fieldName) => {
    const defaultProps = {
      city: {
        label: __('City', 'edwiser-bridge-pro'),
        required: true,
        visible: true,
      },
      state: {
        label: __('State', 'edwiser-bridge-pro'),
        required: states.length > 0,
        visible: states.length > 0,
      },
      postcode: {
        label: __('PIN Code', 'edwiser-bridge-pro'),
        required: true,
        visible: true,
      },
    };

    if (!localeFields || !localeFields[fieldName]) {
      return defaultProps[fieldName];
    }

    const field = localeFields[fieldName];

    return {
      label: field.label || defaultProps[fieldName].label,
      required:
        field.required !== undefined
          ? field.required
          : defaultProps[fieldName].required,
      visible: field.hidden !== true && defaultProps[fieldName].visible,
    };
  };

  const validateForm = () => {
    const newErrors = {};

    // Check country
    if (!address.country) {
      newErrors.country = __('Country is required', 'edwiser-bridge-pro');
    }

    // Check state if applicable
    const stateProps = getFieldProps('state');
    if (stateProps.visible && stateProps.required && !address.state) {
      newErrors.state = __(
        `${stateProps.label} is required`,
        'edwiser-bridge-pro'
      );
    }

    // Check city
    const cityProps = getFieldProps('city');
    if (cityProps.visible && cityProps.required && !address.city) {
      newErrors.city = __(
        `${cityProps.label} is required`,
        'edwiser-bridge-pro'
      );
    }

    // Check postcode
    const postcodeProps = getFieldProps('postcode');
    if (postcodeProps.visible && postcodeProps.required && !address.postcode) {
      newErrors.postcode = __(
        `${postcodeProps.label} is required`,
        'edwiser-bridge-pro'
      );
    } else if (
      postcodeProps.visible &&
      postcodeProps.required &&
      address.postcode &&
      !postcodeValid
    ) {
      newErrors.postcode = validationError;
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleUpdateAddress = async (e) => {
    e.preventDefault();

    const isValid = validateForm();

    if (isValid) {
      const success = await updateShippingAddress(address);
      if (success) {
        setAccordionValue(null);
      }
    }
  };

  const handleValidatePostcode = async () => {
    if (address.postcode && address.country) {
      await validatePostcode(address.postcode, address.country);
    }
  };

  useEffect(() => {
    setAddress({
      country: shippingAddress.country || '',
      state: shippingAddress.state || '',
      city: shippingAddress.city || '',
      postcode: shippingAddress.postcode || '',
    });

    formatDisplayAddress();
  }, [shippingAddress]);

  useEffect(() => {
    validateForm();
  }, [address, postcodeValid]);

  return (
    <div className="eb-cart__delivery-details">
      {primaryShippingOption && (
        <div className="delivery-price">
          <div>
            <p className="label">{__('Delivery', 'edwiser-bridge-pro')}</p>
            <p className="label-sm">
              {__(primaryShippingOption?.name, 'edwiser-bridge-pro')}
            </p>
          </div>
          <p className="price">
            {__(
              formatShippingPrices(primaryShippingOption, includesTax),
              'edwiser-bridge-pro'
            )}
          </p>
        </div>
      )}
      {enableShippingCalculator && (
        <Accordion
          variant="contained"
          className="delivery-details-accordion"
          value={accordionValue}
          onChange={setAccordionValue}
        >
          <Accordion.Item value="delivery-details">
            <Accordion.Control>
              {primaryShippingOption
                ? __('Delivers to ', 'edwiser-bridge-pro')
                : __('No delivery options available for ', 'edwiser-bridge-pro')}
              <span className="delivery-address">{formatDisplayAddress()}</span>
            </Accordion.Control>
            <Accordion.Panel>
              <form onSubmit={handleUpdateAddress}>
                <Select
                  checkIconPosition="right"
                  label={__('Country/Region', 'edwiser-bridge-pro')}
                  placeholder={__('Country/Region', 'edwiser-bridge-pro')}
                  data={countries}
                  value={address.country}
                  onChange={(value) => {
                    setAddress((prev) => ({
                      ...prev,
                      country: value,
                      state: null,
                      postcode: '',
                      city: '',
                    }));

                    updateStates(value);
                    updateLocaleFields(value);
                  }}
                  comboboxProps={{
                    withinPortal: false,
                  }}
                  maxDropdownHeight={400}
                  rightSection={<Icons.chevronDown />}
                  allowDeselect={false}
                  searchable
                  error={errors.country}
                />
                {getFieldProps('city').visible && (
                  <TextInput
                    label={
                      <>
                        {getFieldProps('city').label}{' '}
                        {!getFieldProps('city').required && (
                          <span className="optional-label">
                            {__('(optional)', 'edwiser-bridge-pro')}
                          </span>
                        )}
                      </>
                    }
                    placeholder={getFieldProps('city').label}
                    value={address.city}
                    onChange={(e) =>
                      setAddress((prev) => ({
                        ...prev,
                        city: e.currentTarget.value,
                      }))
                    }
                    error={errors.city}
                  />
                )}
                {states.length > 0 && getFieldProps('state').visible && (
                  <Select
                    checkIconPosition="right"
                    label={
                      <>
                        {getFieldProps('state').label}{' '}
                        {!getFieldProps('state').required && (
                          <span className="optional-label">
                            {__('(optional)', 'edwiser-bridge-pro')}
                          </span>
                        )}
                      </>
                    }
                    placeholder={getFieldProps('state').label}
                    data={states}
                    value={address.state}
                    onChange={(value) =>
                      setAddress((prev) => ({ ...prev, state: value }))
                    }
                    comboboxProps={{
                      withinPortal: false,
                    }}
                    maxDropdownHeight={400}
                    rightSection={<Icons.chevronDown />}
                    searchable
                    disabled={!address.country || states.length === 0}
                    error={errors.state}
                  />
                )}

                {getFieldProps('postcode').visible && (
                  <TextInput
                    label={
                      <>
                        {getFieldProps('postcode').label}{' '}
                        {!getFieldProps('postcode').required && (
                          <span className="optional-label">
                            {__('(optional)', 'edwiser-bridge-pro')}
                          </span>
                        )}
                      </>
                    }
                    placeholder={getFieldProps('postcode').label}
                    value={address.postcode}
                    onBlur={handleValidatePostcode}
                    onChange={(e) =>
                      setAddress((prev) => ({
                        ...prev,
                        postcode: e.currentTarget.value,
                      }))
                    }
                    error={
                      errors.postcode || (!postcodeValid && validationError)
                    }
                  />
                )}
                <button
                  type="submit"
                  className="eb-btn eb-btn__check-delivery-options"
                >
                  {__('Check delivery options', 'edwiser-bridge-pro')}
                </button>
              </form>
            </Accordion.Panel>
          </Accordion.Item>
        </Accordion>
      )}

      {shippingRates.length > 0 && (
        <ShipmentRates
          shippingRates={shippingRates}
          updateShippingRate={updateShippingRate}
          includesTax={includesTax}
          simpleView={shippingRates.length + subscriptions.length === 1}
        />
      )}

      {subscriptions &&
        subscriptions.length > 0 &&
        subscriptions.map((subscription) => (
          <ShipmentRates
            shippingRates={subscription.shipping_rates}
            updateShippingRate={updateShippingRate}
            includesTax={includesTax}
            simpleView={shippingRates.length + subscriptions.length === 1}
          />
        ))}
    </div>
  );
}

export default ShippingDetails;

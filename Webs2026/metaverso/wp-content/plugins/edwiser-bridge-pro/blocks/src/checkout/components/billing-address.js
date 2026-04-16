import { Select, TextInput } from '@mantine/core';
import React, {
  forwardRef,
  useEffect,
  useImperativeHandle,
  useState,
} from 'react';
import { Icons } from './icons';

const BillingAddress = forwardRef(
  (
    {
      billingAddress,
      shippingAddress,
      countries,
      postcodeValid,
      validatePostcode,
      updateAddress,
      billingStates,
      billingLocaleFields,
      updateBillingStates,
      updateBillingLocaleFields,
    },
    ref
  ) => {
    const [address, setAddress] = useState({
      first_name: billingAddress.first_name || '',
      last_name: billingAddress.last_name || '',
      country: billingAddress.country || '',
      state: billingAddress.state || '',
      address_1: billingAddress.address_1 || '',
      address_2: billingAddress.address_2 || '',
      city: billingAddress.city || '',
      postcode: billingAddress.postcode || '',
      phone: billingAddress.phone || '',
    });
    const [errors, setErrors] = useState({
      first_name: '',
      last_name: '',
      country: '',
      state: '',
      address_1: '',
      address_2: '',
      city: '',
      postcode: '',
      phone: '',
    });

    const getFieldProps = (fieldName) => {
      const defaultProps = {
        first_name: {
          label: 'First name',
          required: true,
        },
        last_name: {
          label: 'Last name',
          required: true,
        },
        country: {
          label: 'Country / Region',
          required: true,
        },
        city: { label: 'City', required: true },
        state: {
          label: 'State',
          required: billingStates.length > 0,
        },
        address_1: {
          label: 'Address',
          required: true,
        },
        address_2: {
          label: 'Apartment, suite, etc.',
          required: false,
        },
        postcode: { label: 'PIN Code', required: true },
        phone: { label: 'Phone', required: false },
      };

      if (!billingLocaleFields || !billingLocaleFields[fieldName]) {
        return defaultProps[fieldName];
      }

      const field = billingLocaleFields[fieldName];

      return {
        label: field.label || defaultProps[fieldName].label,
        required:
          field.required !== undefined
            ? field.required
            : defaultProps[fieldName].required,
      };
    };

    const validateForm = () => {
      const newErrors = {};

      // Check first name
      const firstNameProps = getFieldProps('first_name');
      if (firstNameProps.required && !address.first_name) {
        newErrors.first_name = `${firstNameProps.label} is required`;
      }

      // Check last name
      const lastNameProps = getFieldProps('last_name');
      if (lastNameProps.required && !address.last_name) {
        newErrors.last_name = `${lastNameProps.label} is required`;
      }

      // Check country
      const countryProps = getFieldProps('country');
      if (countryProps.required && !address.country) {
        newErrors.country = `${countryProps.label} is required`;
      }

      // Check address 1
      const address1Props = getFieldProps('address_1');
      if (address1Props.required && !address.address_1) {
        newErrors.address_1 = `${address1Props.label} is required`;
      }

      // Check address 2
      const address2Props = getFieldProps('address_2');
      if (address2Props.required && !address.address_2) {
        newErrors.address_2 = `${address2Props.label} is required`;
      }

      // Check state
      const stateProps = getFieldProps('state');
      if (stateProps.required && !address.state) {
        newErrors.state = `${stateProps.label} is required`;
      }

      // Check city
      const cityProps = getFieldProps('city');
      if (cityProps.required && !address.city) {
        newErrors.city = `${cityProps.label} is required`;
      }

      // Check postcode
      const postcodeProps = getFieldProps('postcode');
      if (postcodeProps.required && !address.postcode) {
        newErrors.postcode = `${postcodeProps.label} is required`;
      } else if (postcodeProps.required && address.postcode && !postcodeValid) {
        newErrors.postcode = `${postcodeProps.label} is not valid`;
      }

      // Check phone
      const phoneProps = getFieldProps('phone');
      if (phoneProps.required && !address.phone) {
        newErrors.phone = `${phoneProps.label} is required`;
      }

      setErrors(newErrors);
      return Object.keys(newErrors).length === 0;
    };

    const validatePostcodeField = (validPostcode) => {
      const fieldProps = getFieldProps('postcode');
      let error = '';

      if (fieldProps.required && !address.postcode) {
        error = `${fieldProps.label} is required`;
      } else if (fieldProps.required && address.postcode && !validPostcode) {
        error = `${fieldProps.label} is not valid`;
      }

      setErrors((prev) => ({
        ...prev,
        postcode: error,
      }));

      return !error;
    };

    const handleFieldBlur = async () => {
      const isValid = validateForm();

      if (!isValid) return;

      const updatedAddress = { ...address };
      await handleAddressUpdate(updatedAddress);
    };

    const handleAddressUpdate = async (updatedAddress) => {
      let updatedBillingAddress = { ...billingAddress, ...updatedAddress };
      const updatedShippingAddress = shippingAddress;

      await updateAddress({
        shippingAddress: updatedShippingAddress,
        billingAddress: updatedBillingAddress,
      });
    };

    const handleValidatePostcode = async () => {
      const validPostcode = await validatePostcode(
        address.postcode,
        address.country
      );

      const isValidField = validatePostcodeField(validPostcode);

      if (!isValidField || !validPostcode) return;

      await handleAddressUpdate(address);
    };

    const validateAddressForCheckout = () => {
      return validateForm();
    };

    useImperativeHandle(ref, () => ({
      validateAddress: validateAddressForCheckout,
    }));

    useEffect(() => {
      setAddress({
        first_name: billingAddress.first_name || '',
        last_name: billingAddress.last_name || '',
        country: billingAddress.country || '',
        state: billingAddress.state || '',
        address_1: billingAddress.address_1 || '',
        address_2: billingAddress.address_2 || '',
        city: billingAddress.city || '',
        postcode: billingAddress.postcode || '',
        phone: billingAddress.phone || '',
      });
    }, [billingAddress]);

    return (
      <div className="eb-checkout__billing-address">
        <div className="billing-address__header">
          <h3 className="billing-address__title">Billing address</h3>
        </div>
        <div className="billing-address__content">
          <div className="billing-address__col-2">
            <TextInput
              label={
                <>
                  {getFieldProps('first_name').label}{' '}
                  {!getFieldProps('first_name').required && (
                    <span className="optional-label">(optional)</span>
                  )}
                </>
              }
              placeholder={
                getFieldProps('first_name').placeholder ||
                getFieldProps('first_name').label
              }
              autoComplete={
                getFieldProps('first_name').autocomplete || 'given-name'
              }
              value={address.first_name}
              onChange={(e) =>
                setAddress((prev) => ({
                  ...prev,
                  first_name: e.currentTarget.value,
                }))
              }
              onBlur={(e) =>
                handleFieldBlur('first_name', e.currentTarget.value)
              }
              error={errors.first_name}
              required={getFieldProps('first_name').required}
            />
            <TextInput
              label={
                <>
                  {getFieldProps('last_name').label}{' '}
                  {!getFieldProps('last_name').required && (
                    <span className="optional-label">(optional)</span>
                  )}
                </>
              }
              placeholder={
                getFieldProps('last_name').placeholder ||
                getFieldProps('last_name').label
              }
              autoComplete={
                getFieldProps('last_name').autocomplete || 'family-name'
              }
              value={address.last_name}
              onChange={(e) =>
                setAddress((prev) => ({
                  ...prev,
                  last_name: e.currentTarget.value,
                }))
              }
              onBlur={(e) =>
                handleFieldBlur('last_name', e.currentTarget.value)
              }
              error={errors.last_name}
              required={getFieldProps('last_name').required}
            />
          </div>
          <Select
            checkIconPosition="right"
            label={
              <>
                {getFieldProps('country').label}{' '}
                {!getFieldProps('country').required && (
                  <span className="optional-label">(optional)</span>
                )}
              </>
            }
            placeholder={
              getFieldProps('country').placeholder ||
              getFieldProps('country').label
            }
            autoComplete={getFieldProps('country').autocomplete || 'country'}
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

              updateBillingStates(value);
              updateBillingLocaleFields(value);
            }}
            onBlur={() => handleFieldBlur('country', address.country)}
            comboboxProps={{
              withinPortal: false,
            }}
            maxDropdownHeight={400}
            rightSection={<Icons.chevronDown />}
            allowDeselect={false}
            searchable
            error={errors.country}
            required={getFieldProps('country').required}
          />
          <TextInput
            label={
              <>
                {getFieldProps('address_1').label}{' '}
                {!getFieldProps('address_1').required && (
                  <span className="optional-label">(optional)</span>
                )}
              </>
            }
            placeholder={
              getFieldProps('address_1').placeholder ||
              getFieldProps('address_1').label
            }
            autoComplete={
              getFieldProps('address_1').autocomplete || 'address-line1'
            }
            value={address.address_1}
            onChange={(e) =>
              setAddress((prev) => ({
                ...prev,
                address_1: e.currentTarget.value,
              }))
            }
            onBlur={(e) => handleFieldBlur('address_1', e.currentTarget.value)}
            error={errors.address_1}
            required={getFieldProps('address_1').required}
          />
          <TextInput
            label={
              <>
                {getFieldProps('address_2').label}{' '}
                {!getFieldProps('address_2').required && (
                  <span className="optional-label">(optional)</span>
                )}
              </>
            }
            placeholder={
              getFieldProps('address_2').placeholder ||
              getFieldProps('address_2').label
            }
            autoComplete={
              getFieldProps('address_2').autocomplete || 'address-line2'
            }
            value={address.address_2}
            onChange={(e) =>
              setAddress((prev) => ({
                ...prev,
                address_2: e.currentTarget.value,
              }))
            }
            onBlur={(e) => handleFieldBlur('address_2', e.currentTarget.value)}
            error={errors.address_2}
            required={getFieldProps('address_2').required}
          />
          <div
            className={
              billingStates.length > 0
                ? 'billing-address__col-2'
                : 'billing-address__col-1'
            }
          >
            <TextInput
              label={
                <>
                  {getFieldProps('city').label}{' '}
                  {!getFieldProps('city').required && (
                    <span className="optional-label">(optional)</span>
                  )}
                </>
              }
              placeholder={
                getFieldProps('city').placeholder || getFieldProps('city').label
              }
              autoComplete={
                getFieldProps('city').autocomplete || 'address-level2'
              }
              value={address.city}
              onChange={(e) =>
                setAddress((prev) => ({
                  ...prev,
                  city: e.currentTarget.value,
                }))
              }
              onBlur={(e) => handleFieldBlur('city', e.currentTarget.value)}
              error={errors.city}
              required={getFieldProps('city').required}
            />
            {billingStates.length > 0 && (
              <Select
                checkIconPosition="right"
                label={
                  <>
                    {getFieldProps('state').label}{' '}
                    {!getFieldProps('state').required && (
                      <span className="optional-label">(optional)</span>
                    )}
                  </>
                }
                placeholder={
                  getFieldProps('state').placeholder ||
                  getFieldProps('state').label
                }
                autoComplete={
                  getFieldProps('state').autocomplete || 'address-level1'
                }
                data={billingStates}
                value={address.state}
                onChange={(value) =>
                  setAddress((prev) => ({ ...prev, state: value }))
                }
                onBlur={() => handleFieldBlur('state', address.state)}
                comboboxProps={{
                  withinPortal: false,
                }}
                maxDropdownHeight={400}
                rightSection={<Icons.chevronDown />}
                searchable
                error={errors.state}
                required={getFieldProps('state').required}
              />
            )}
          </div>
          <div className="billing-address__col-2">
            <TextInput
              label={
                <>
                  {getFieldProps('postcode').label}{' '}
                  {!getFieldProps('postcode').required && (
                    <span className="optional-label">(optional)</span>
                  )}
                </>
              }
              placeholder={
                getFieldProps('postcode').placeholder ||
                getFieldProps('postcode').label
              }
              autoComplete={
                getFieldProps('postcode').autocomplete || 'postal-code'
              }
              value={address.postcode}
              onChange={(e) =>
                setAddress((prev) => ({
                  ...prev,
                  postcode: e.currentTarget.value,
                }))
              }
              onBlur={handleValidatePostcode}
              error={errors.postcode}
              required={getFieldProps('postcode').required}
            />
            <TextInput
              label={
                <>
                  Phone <span className="optional-label">(optional)</span>
                </>
              }
              placeholder="Phone"
              type="tel"
              value={address.phone}
              onChange={(e) =>
                setAddress((prev) => ({
                  ...prev,
                  phone: e.currentTarget.value,
                }))
              }
              onBlur={(e) => handleFieldBlur('phone', e.currentTarget.value)}
              error={errors.phone}
              required={getFieldProps('phone').required}
            />
          </div>
        </div>
      </div>
    );
  }
);

export default BillingAddress;

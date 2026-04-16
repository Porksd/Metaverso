import { Skeleton, TextInput } from '@mantine/core';
import React, {
  forwardRef,
  useEffect,
  useImperativeHandle,
  useState,
} from 'react';

const ContactInformation = forwardRef(
  (
    {
      customerEmail,
      customerId,
      enableGuestCheckout,
      enableLogin,
      enableSignup,
      enableSubscriptionsSignup,
      loginUrl,
      updateAddress,
      shippingAddress,
      billingAddress,
      customerPassword,
      updateCustomerPassword,
      isSubscriptionProduct,
    },
    ref
  ) => {
    const [email, setEmail] = useState(customerEmail);
    const [emailError, setEmailError] = useState('');
    const [passwordError, setPasswordError] = useState('');

    const showPasswordField =
      !customerId &&
      !enableGuestCheckout &&
      enableSignup &&
      (!isSubscriptionProduct || enableSubscriptionsSignup);

    const handleEmailUpdate = async () => {
      if (!email) {
        setEmailError('Email address is required');
      } else if (!validateEmail(email)) {
        setEmailError('Email address is not valid');
      }

      await updateAddress({
        shippingAddress,
        billingAddress: {
          ...billingAddress,
          email,
        },
      });
    };

    const validateEmail = (val) => {
      const emailRegex = /^[\w-\.]+@([\w-]+\.)+[\w-]{2,63}$/;
      return emailRegex.test(val);
    };

    const validateContactInfo = () => {
      if (!email) {
        setEmailError('Email address is required');
      } else if (!validateEmail(email)) {
        setEmailError('Email address is not valid');
      }
      if (showPasswordField && !customerPassword) {
        setPasswordError('Password is required');
      }

      return !emailError && !passwordError;
    };

    const validateContactInfoForCheckout = () => {
      return validateContactInfo();
    };

    useImperativeHandle(ref, () => ({
      validateContactInfo: validateContactInfoForCheckout,
    }));

    useEffect(() => {
      setEmail(customerEmail);
    }, [customerEmail]);

    return (
      <div className="eb-checkout__contact-info">
        <div className="contact-info__header-wrapper">
          <div className="contact-info__header">
            <h2 className="contact-info__title">Contact information</h2>
            <p className="contact-info__sub-title">
              We'll use this email to send you details and updates about your
              order.
            </p>
          </div>
          {enableLogin && !customerId && (
            <div className="eb-checkout__login">
              <a href={loginUrl}>Log in</a>
            </div>
          )}
        </div>
        <div className="contact-info__content">
          <TextInput
            label="Email address"
            placeholder="Enter email address"
            type="email"
            value={email}
            onChange={(e) => {
              setEmailError('');
              setEmail(e.currentTarget.value);
            }}
            onBlur={handleEmailUpdate}
            error={emailError}
            required
          />
          {showPasswordField && (
            <TextInput
              type="password"
              label="Create a password"
              placeholder="Enter Password"
              value={customerPassword}
              onChange={(e) => {
                setPasswordError('');
                updateCustomerPassword(e.currentTarget.value);
              }}
              onBlur={() =>
                !customerPassword && setPasswordError('Password is required')
              }
              error={passwordError}
              required
            />
          )}
        </div>
      </div>
    );
  }
);

export default ContactInformation;

export function ContactInformationSkeleton() {
  return (
    <div className="eb-checkout__contact-info">
      <div className="contact-info__header-wrapper">
        <div className="contact-info__header">
          <Skeleton w={180} h={28} />
          <Skeleton w={420} h={16} />
        </div>
        <div className="eb-checkout__login">
          <Skeleton w={80} h={20} />
        </div>
      </div>
      <div className="contact-info__content">
        <Skeleton w={'100%'} h={40} />
        <Skeleton w={'100%'} h={40} />
      </div>
    </div>
  );
}

import { Modal, TextInput } from '@mantine/core';
import React, { useState } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { Icons } from '../icons';

function EnrollUser({
  enrollSingleOpened,
  closeEnrollSingle,
  cohortId,
  enrollUsers,
  selectedGroup,
  fetchGroupEnrollmentDetails,
  onEnrollmentDetailsUpdate,
  addNotification,
}) {
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [email, setEmail] = useState('');
  const [firstNameError, setFirstNameError] = useState('');
  const [lastNameError, setLastNameError] = useState('');
  const [emailError, setEmailError] = useState('');
  const [isEnrolling, setIsEnrolling] = useState(false);

  const handleEnrollment = async (e) => {
    e.preventDefault();
    setFirstNameError('');
    setLastNameError('');
    setEmailError('');
    let hasError = false;
    if (!firstName.trim()) {
      setFirstNameError(__('First name is required.', 'edwiser-bridge-pro'));
      hasError = true;
    }
    if (!lastName.trim()) {
      setLastNameError(__('Last name is required.', 'edwiser-bridge-pro'));
      hasError = true;
    }
    if (!email.trim()) {
      setEmailError(__('Email is required.', 'edwiser-bridge-pro'));
      hasError = true;
    } else {
      const emailRegex =
        /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
      if (!emailRegex.test(email)) {
        setEmailError(
          __('Please enter a valid email address.', 'edwiser-bridge-pro')
        );
        hasError = true;
      }
    }
    if (hasError) return;

    setIsEnrolling(true);
    const result = await enrollUsers(cohortId, [
      {
        first_name: firstName.trim(),
        last_name: lastName.trim(),
        email: email.trim(),
      },
    ]);

    // Silently update enrollment details
    if (fetchGroupEnrollmentDetails && onEnrollmentDetailsUpdate) {
      const data = await fetchGroupEnrollmentDetails(selectedGroup);
      onEnrollmentDetailsUpdate(data);
    }

    setIsEnrolling(false);

    if (result.success && result.enrollment_result) {
      const { enroll_success, already_enrolled, enroll_errors } =
        result.enrollment_result;

      if (enroll_success.length > 0) {
        addNotification(
          sprintf(
            __(
              'User with the email "%s" has been enrolled successfully',
              'edwiser-bridge-pro'
            ),
            enroll_success[0]
          ),
          'success'
        );
      } else if (already_enrolled.length > 0) {
        addNotification(
          sprintf(
            __(
              'User with the email "%s" is already enrolled',
              'edwiser-bridge-pro'
            ),
            already_enrolled[0]
          ),
          'error'
        );
      } else if (enroll_errors.length > 0) {
        addNotification(
          sprintf(
            __(
              'Failed to enroll the user with email "%s".',
              'edwiser-bridge-pro'
            ),
            enroll_errors[0]
          ),
          'error'
        );
      }

      setFirstName('');
      setLastName('');
      setEmail('');
      closeEnrollSingle();
    }
  };

  return (
    <Modal
      opened={enrollSingleOpened}
      onClose={closeEnrollSingle}
      title={__('Enroll new user', 'edwiser-bridge-pro')}
      withinPortal={false}
      size="md"
      closeOnClickOutside={false}
    >
      <form
        className="enrollment-actions__enroll-single-modal"
        onSubmit={handleEnrollment}
      >
        <div className="enroll-single-modal__content">
          <TextInput
            label={__('First name', 'edwiser-bridge-pro')}
            placeholder={__('Enter first name', 'edwiser-bridge-pro')}
            required
            value={firstName}
            onChange={(e) => setFirstName(e.currentTarget.value)}
            disabled={isEnrolling}
            error={firstNameError}
          />
          <TextInput
            label={__('Last name', 'edwiser-bridge-pro')}
            placeholder={__('Enter last name', 'edwiser-bridge-pro')}
            required
            value={lastName}
            onChange={(e) => setLastName(e.currentTarget.value)}
            disabled={isEnrolling}
            error={lastNameError}
          />
          <TextInput
            type="email"
            label={__('Email', 'edwiser-bridge-pro')}
            placeholder={__('Enter email', 'edwiser-bridge-pro')}
            required
            value={email}
            onChange={(e) => setEmail(e.currentTarget.value)}
            disabled={isEnrolling}
            error={emailError}
          />
        </div>
        <div className="enroll-single-modal__action">
          <button
            className="btn__action-confirm"
            type="submit"
            disabled={isEnrolling}
          >
            {isEnrolling && <Icons.loader />}
            {__('Enroll user', 'edwiser-bridge-pro')}
          </button>
          <button
            className="btn__action-cancel"
            type="button"
            onClick={closeEnrollSingle}
            disabled={isEnrolling}
          >
            {__('Cancel', 'edwiser-bridge-pro')}
          </button>
        </div>
      </form>
    </Modal>
  );
}

export default EnrollUser;

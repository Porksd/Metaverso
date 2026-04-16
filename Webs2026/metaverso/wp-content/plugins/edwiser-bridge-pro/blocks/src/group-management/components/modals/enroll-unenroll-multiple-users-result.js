import React from 'react';
import { Modal } from '@mantine/core';
import { __ } from '@wordpress/i18n';

function EnrollUnenrollMultipleResult({
  enrollUnenrollMultipleResultOpened,
  closeEnrollUnenrollMultipleResult,
  type,
  emails = [],
  mode = 'delete',
}) {
  const isSuccess = type === 'success';
  const isDelete = mode === 'delete';
  const isEnroll = mode === 'enroll';

  // Title logic
  let title = '';
  if (isDelete) {
    title = isSuccess
      ? __('Successfully removed users', 'edwiser-bridge-pro')
      : __('Failed to remove users', 'edwiser-bridge-pro');
  } else if (isEnroll) {
    if (isSuccess) {
      title = __('Successfully enrolled users', 'edwiser-bridge-pro');
    } else if (type === 'error') {
      title = __('Already enrolled users', 'edwiser-bridge-pro');
    } else {
      title = __('Failed to enroll users', 'edwiser-bridge-pro');
    }
  }

  // Description logic
  let description = '';
  if (isDelete) {
    description = isSuccess
      ? __(
          'Users with the following emails have been removed successfully',
          'edwiser-bridge-pro'
        )
      : __(
          'Users with the following emails could not be removed',
          'edwiser-bridge-pro'
        );
  } else if (isEnroll) {
    if (isSuccess) {
      description = __(
        'Users with the following emails have been enrolled successfully',
        'edwiser-bridge-pro'
      );
    } else if (type === 'error') {
      description = __(
        'Users with the following emails were already enrolled',
        'edwiser-bridge-pro'
      );
    } else {
      description = __(
        'Users with the following emails could not be enrolled',
        'edwiser-bridge-pro'
      );
    }
  }

  return (
    <Modal
      opened={enrollUnenrollMultipleResultOpened}
      onClose={closeEnrollUnenrollMultipleResult}
      title={title}
      size="md"
      withinPortal={false}
      closeOnClickOutside={false}
    >
      <div className={`enrollment-list__enroll-multiple-result-modal ${type}`}>
        <p>{description}</p>
        <ol className="enroll-multiple-result-modal__emails-list">
          {emails && emails.length > 0 ? (
            emails.map((email, idx) => <li key={email + idx}>{email}</li>)
          ) : (
            <li>{__('No users in this list.', 'edwiser-bridge-pro')}</li>
          )}
        </ol>
      </div>
    </Modal>
  );
}

export default EnrollUnenrollMultipleResult;

import { Modal } from '@mantine/core';
import { __, sprintf } from '@wordpress/i18n';
import React from 'react';
import { Icons } from '../icons';

function RemoveUser({
  removeUserOpened,
  closeRemoveUser,
  users = [],
  onConfirm,
  isDeleting,
}) {
  const isMultiple = users.length > 1;
  const userName =
    !isMultiple && users[0]
      ? users[0].full_name || users[0].display_name || users[0].email
      : '';

  let description;
  if (isMultiple) {
    description = (
      <>
        {__('Are you sure you want to remove ', 'edwiser-bridge-pro')}
        <strong>{users.length}</strong>{' '}
        <strong>{__('selected users', 'edwiser-bridge-pro')}</strong>
        {__(' from the group?', 'edwiser-bridge-pro')}
      </>
    );
  } else {
    description = (
      <>
        {__('Are you sure you want to remove ', 'edwiser-bridge-pro')}
        <strong>"{userName}"</strong>
        {__(' from the group?', 'edwiser-bridge-pro')}
      </>
    );
  }

  const confirmText = isMultiple
    ? __('Remove users', 'edwiser-bridge-pro')
    : __('Remove user', 'edwiser-bridge-pro');

  return (
    <Modal
      opened={removeUserOpened}
      onClose={closeRemoveUser}
      title={
        isMultiple
          ? __('Remove users from the group', 'edwiser-bridge-pro')
          : __('Remove user from the group', 'edwiser-bridge-pro')
      }
      withinPortal={false}
      closeOnClickOutside={false}
      size="md"
    >
      <div className="enrollment-list__remove-user-modal">
        <p className="remove-user-modal__desc">{description}</p>
        <div className="remove-user-modal__action">
          <button
            className="btn__action-confirm"
            onClick={onConfirm}
            disabled={isDeleting}
          >
            {isDeleting && <Icons.loader />} {confirmText}
          </button>
          <button
            className="btn__action-cancel"
            onClick={closeRemoveUser}
            disabled={isDeleting}
          >
            {__('Cancel', 'edwiser-bridge-pro')}
          </button>
        </div>
      </div>
    </Modal>
  );
}

export default RemoveUser;

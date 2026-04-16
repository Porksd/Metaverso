import { Modal, TextInput } from '@mantine/core';
import React, { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { Icons } from '../icons';

function EditUser({
  editUserOpened,
  closeEditUser,
  user = {},
  onSubmit,
  isUpdating,
}) {
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [email, setEmail] = useState('');

  useEffect(() => {
    setFirstName(user?.first_name || '');
    setLastName(user?.last_name || '');
    setEmail(user?.email || '');
  }, [user, editUserOpened]);

  const handleSubmit = (e) => {
    e.preventDefault();
    if (onSubmit) {
      onSubmit({
        ...user,
        first_name: firstName,
        last_name: lastName,
        email,
      });
    }
  };

  return (
    <Modal
      opened={editUserOpened}
      onClose={closeEditUser}
      title={__('Edit user', 'edwiser-bridge-pro')}
      withinPortal={false}
      size="md"
      closeOnClickOutside={false}
    >
      <form
        className="enrollment-list__edit-user-modal"
        onSubmit={handleSubmit}
      >
        <div className="edit-user-modal__content">
          <TextInput
            label={__('First name', 'edwiser-bridge-pro')}
            placeholder={__('Enter first name', 'edwiser-bridge-pro')}
            required
            value={firstName}
            onChange={(e) => setFirstName(e.currentTarget.value)}
          />
          <TextInput
            label={__('Last name', 'edwiser-bridge-pro')}
            placeholder={__('Enter last name', 'edwiser-bridge-pro')}
            required
            value={lastName}
            onChange={(e) => setLastName(e.currentTarget.value)}
          />
          <TextInput
            type="email"
            label={__('Email', 'edwiser-bridge-pro')}
            placeholder={__('Enter email', 'edwiser-bridge-pro')}
            required
            value={email}
            onChange={(e) => setEmail(e.currentTarget.value)}
          />
        </div>
        <div className="edit-user-modal__action">
          <button
            className="btn__action-confirm"
            type="submit"
            disabled={isUpdating}
          >
            {isUpdating && <Icons.loader />}
            {__('Update user', 'edwiser-bridge-pro')}
          </button>
          <button
            className="btn__action-cancel"
            type="button"
            onClick={closeEditUser}
            disabled={isUpdating}
          >
            {__('Cancel', 'edwiser-bridge-pro')}
          </button>
        </div>
      </form>
    </Modal>
  );
}

export default EditUser;

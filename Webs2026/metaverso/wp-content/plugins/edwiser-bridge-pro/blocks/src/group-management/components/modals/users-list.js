import React, { useState, useEffect } from 'react';
import { Modal, TextInput } from '@mantine/core';
import { __ } from '@wordpress/i18n';
import { Icons } from '../icons';

// Helper to generate a unique ID
const generateId = () =>
  `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;

const validateEmail = (email) => {
  const re =
    /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
  return re.test(String(email).toLowerCase());
};

function ensureUserIds(users) {
  return users.map((user) => ({ ...user, id: user.id || generateId() }));
}

function UsersList({
  usersListOpened,
  closeUsersList,
  users,
  onEnroll,
  isEnrolling,
}) {
  const CSV_HEADERS = [
    __('First name', 'edwiser-bridge-pro'),
    __('Last name', 'edwiser-bridge-pro'),
    __('Email', 'edwiser-bridge-pro'),
  ];
  const [localUsers, setLocalUsers] = useState(ensureUserIds(users));
  const [errors, setErrors] = useState({});

  useEffect(() => {
    const usersWithIds = ensureUserIds(users);
    setLocalUsers(usersWithIds);
    // Validate all users for missing/invalid fields
    let initialErrors = {};
    usersWithIds.forEach((user) => {
      let userErrors = {};
      if (!user.first_name) {
        userErrors.first_name = __(
          'First name is required',
          'edwiser-bridge-pro'
        );
      }
      if (!user.last_name) {
        userErrors.last_name = __(
          'Last name is required',
          'edwiser-bridge-pro'
        );
      }
      if (!user.email) {
        userErrors.email = __('Email is required', 'edwiser-bridge-pro');
      } else if (!validateEmail(user.email)) {
        userErrors.email = __(
          'Please enter a valid email',
          'edwiser-bridge-pro'
        );
      }
      if (Object.keys(userErrors).length > 0) {
        initialErrors[user.id] = userErrors;
      }
    });
    setErrors(initialErrors);
  }, [users]);

  const handleFieldChange = (id, field, value) => {
    const updatedUsers = localUsers.map((user) =>
      user.id === id ? { ...user, [field]: value } : user
    );
    setLocalUsers(updatedUsers);

    if (field === 'email') {
      if (!validateEmail(value)) {
        setErrors((prev) => ({
          ...prev,
          [id]: {
            ...prev[id],
            email: __('Please enter a valid email', 'edwiser-bridge-pro'),
          },
        }));
      } else {
        setErrors((prev) => ({
          ...prev,
          [id]: { ...prev[id], email: null },
        }));
      }
    }
  };

  const handleRemoveUser = (id) => {
    const updatedUsers = localUsers.filter((user) => user.id !== id);
    setLocalUsers(updatedUsers);
    setErrors((prev) => {
      const newErrors = { ...prev };
      delete newErrors[id];
      return newErrors;
    });
  };

  const hasErrors = () => {
    return (
      Object.values(errors).some((userErrors) =>
        Object.values(userErrors).some((error) => error)
      ) || localUsers.some((u) => !u.first_name || !u.last_name || !u.email)
    );
  };

  const handleEnroll = async () => {
    let newErrors = {};
    localUsers.forEach((user) => {
      let userErrors = {};
      if (!user.first_name) {
        userErrors.first_name = __(
          'First name is required',
          'edwiser-bridge-pro'
        );
      }
      if (!user.last_name) {
        userErrors.last_name = __(
          'Last name is required',
          'edwiser-bridge-pro'
        );
      }
      if (!user.email) {
        userErrors.email = __('Email is required', 'edwiser-bridge-pro');
      } else if (!validateEmail(user.email)) {
        userErrors.email = __(
          'Please enter a valid email',
          'edwiser-bridge-pro'
        );
      }
      if (Object.keys(userErrors).length > 0) {
        newErrors[user.id] = userErrors;
      }
    });
    setErrors(newErrors);
    if (Object.keys(newErrors).length > 0) {
      return;
    }

    try {
      const result = await onEnroll(localUsers);
      if (result && result.success) {
        closeUsersList();
      }
    } catch (err) {
      console.log('Error while bulk enrollment', err);
    }
  };

  return (
    <Modal
      opened={usersListOpened}
      onClose={closeUsersList}
      title={__('Enroll new users', 'edwiser-bridge-pro')}
      size="xl"
      withinPortal={false}
      closeOnClickOutside={false}
    >
      <div className="enrollment-actions__users-list-modal">
        <div
          className="users-list-modal__content"
          aria-label={__('Users list', 'edwiser-bridge-pro')}
        >
          <div className="content__users-list">
            {localUsers?.length > 0 && (
              <div className="users-list__header">
                {CSV_HEADERS.map((h) => (
                  <div key={h}>{h}</div>
                ))}
              </div>
            )}
            {localUsers?.length > 0 ? (
              localUsers.map((user) => (
                <div key={user.id} className="users-list__user-row">
                  <TextInput
                    type="text"
                    value={user.first_name}
                    onChange={(e) =>
                      handleFieldChange(user.id, 'first_name', e.target.value)
                    }
                    placeholder={__('First name', 'edwiser-bridge-pro')}
                    error={
                      errors[user.id]?.first_name ||
                      (!user.first_name
                        ? __('First name is required', 'edwiser-bridge-pro')
                        : null)
                    }
                  />
                  <TextInput
                    type="text"
                    value={user.last_name}
                    onChange={(e) =>
                      handleFieldChange(user.id, 'last_name', e.target.value)
                    }
                    placeholder={__('Last name', 'edwiser-bridge-pro')}
                    error={
                      errors[user.id]?.last_name ||
                      (!user.last_name
                        ? __('Last name is required', 'edwiser-bridge-pro')
                        : null)
                    }
                  />
                  <div className="user-row__email-group">
                    <TextInput
                      type="email"
                      value={user.email}
                      onChange={(e) =>
                        handleFieldChange(user.id, 'email', e.target.value)
                      }
                      placeholder={__('Email ID', 'edwiser-bridge-pro')}
                      error={
                        errors[user.id]?.email ||
                        (!user.email
                          ? __('Email is required', 'edwiser-bridge-pro')
                          : !validateEmail(user.email)
                          ? __(
                              'Please enter a valid email',
                              'edwiser-bridge-pro'
                            )
                          : null)
                      }
                    />
                    <button
                      type="button"
                      className="user-row__delete"
                      onClick={() => handleRemoveUser(user.id)}
                      aria-label={__('Remove user', 'edwiser-bridge-pro')}
                    >
                      <Icons.trash />
                    </button>
                  </div>
                </div>
              ))
            ) : (
              <div className="users-list__empty">
                <h3>{__('No users found.', 'edwiser-bridge-pro')}</h3>
                <p>
                  {__(
                    'Please upload the correct file, try a different file, or check that your file format matches the required template.',
                    'edwiser-bridge-pro'
                  )}
                </p>
              </div>
            )}
          </div>
        </div>
        <div className="users-list-modal__action">
          <button
            className="btn__action-confirm"
            onClick={handleEnroll}
            disabled={!localUsers?.length || hasErrors() || isEnrolling}
          >
            {isEnrolling && <Icons.loader />}
            {__('Enroll user', 'edwiser-bridge-pro')}
          </button>
          <button
            className="btn__action-cancel"
            onClick={closeUsersList}
            disabled={isEnrolling}
          >
            {__('Cancel', 'edwiser-bridge-pro')}
          </button>
        </div>
      </div>
    </Modal>
  );
}

export default UsersList;

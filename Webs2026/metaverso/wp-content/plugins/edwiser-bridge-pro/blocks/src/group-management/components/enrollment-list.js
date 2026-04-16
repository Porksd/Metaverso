import React, { useState, useEffect } from 'react';
import { Icons } from './icons';
import { Checkbox, Pagination, Skeleton, TextInput } from '@mantine/core';
import { __ } from '@wordpress/i18n';
import EmptyEnrolmentList from './empty-enrollment-list';
import RemoveUser from './modals/remove-user';
import { useDisclosure } from '@mantine/hooks';
import EditUser from './modals/edit-user';
import UserProgress from './modals/user-progress';
import EnrollUnenrollMultipleResult from './modals/enroll-unenroll-multiple-users-result';

function EnrollmentList({
  enrolledUsers = [],
  selectedGroup,
  fetchCourseProgress,
  courseProgress,
  isCourseProgressLoading,
  deleteUserFromGroup,
  deleteMultipleUsersFromGroup,
  isDeletingUser,
  updateUser,
  isUpdatingUser,
  fetchGroupEnrollmentDetails,
  onEnrollmentDetailsUpdate,
}) {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedUsers, setSelectedUsers] = useState(new Set());
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 10;
  const [notification, setNotification] = useState({ message: '', type: '' });

  const [removeUserOpened, { open: openRemoveUser, close: closeRemoveUser }] =
    useDisclosure(false);
  const [usersToRemove, setUsersToRemove] = useState([]);

  const [editUserOpened, { open: openEditUser, close: closeEditUser }] =
    useDisclosure(false);
  const [userToEdit, setUserToEdit] = useState(null);

  const [
    userProgressOpened,
    { open: openUserProgress, close: closeUserProgress },
  ] = useDisclosure(false);
  const [userToViewProgress, setUserToViewProgress] = useState(null);

  const [
    enrollUnenrollMultipleResultOpened,
    {
      open: openEnrollUnenrollMultipleResult,
      close: closeEnrollUnenrollMultipleResult,
    },
  ] = useDisclosure(false);

  const [successEmails, setSuccessEmails] = useState([]);
  const [errorEmails, setErrorEmails] = useState([]);
  const [modalType, setModalType] = useState('success');
  const [modalEmails, setModalEmails] = useState([]);

  const filteredUsers = enrolledUsers.filter(
    (user) =>
      user.full_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      user.display_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      user.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
      user.first_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      user.last_name.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const totalPages = Math.ceil(filteredUsers.length / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const currentUsers = filteredUsers.slice(
    startIndex,
    startIndex + itemsPerPage
  );

  const allVisibleSelected =
    currentUsers.length > 0 &&
    currentUsers.every((user) => selectedUsers.has(user.user_id));

  const someVisibleSelected =
    currentUsers.some((user) => selectedUsers.has(user.user_id)) &&
    !allVisibleSelected;

  const handleSelectAll = () => {
    const newSelected = new Set(selectedUsers);

    if (allVisibleSelected) {
      currentUsers.forEach((user) => newSelected.delete(user.user_id));
    } else {
      currentUsers.forEach((user) => newSelected.add(user.user_id));
    }

    setSelectedUsers(newSelected);
  };

  const handleSelectUser = (userId) => {
    const newSelected = new Set(selectedUsers);

    if (newSelected.has(userId)) {
      newSelected.delete(userId);
    } else {
      newSelected.add(userId);
    }

    setSelectedUsers(newSelected);
  };

  const handleDeleteSelected = () => {
    if (selectedUsers.size === 0) return;
    const selectedUsersList = enrolledUsers.filter((user) =>
      selectedUsers.has(user.user_id)
    );
    setUsersToRemove(selectedUsersList);
    openRemoveUser();
  };

  const handleDeleteUser = (user) => {
    setUsersToRemove([user]);
    openRemoveUser();
  };

  const handleConfirmRemove = async () => {
    const isMultiple = usersToRemove.length > 1;
    setNotification({ message: '', type: '' });
    setSuccessEmails([]);
    setErrorEmails([]);
    setModalEmails([]);

    try {
      if (isMultiple) {
        const userIds = usersToRemove.map((user) => user.user_id);
        const response = await deleteMultipleUsersFromGroup(
          userIds,
          selectedGroup
        );

        if (response && response.success) {
          const { successful_removals = [], failed_removals = [] } =
            response.data;
          setSuccessEmails(successful_removals.map((u) => u.email));
          setErrorEmails(failed_removals.map((u) => u.email));

          // Silently update enrollment details
          if (fetchGroupEnrollmentDetails && onEnrollmentDetailsUpdate) {
            const data = await fetchGroupEnrollmentDetails(selectedGroup);
            onEnrollmentDetailsUpdate(data);
          }
        }
      } else if (usersToRemove.length === 1) {
        const response = await deleteUserFromGroup(
          usersToRemove[0].user_id,
          selectedGroup
        );

        setNotification({
          message: response.message,
          type: response.success ? 'success' : 'error',
        });

        // Silently update enrollment details
        if (fetchGroupEnrollmentDetails && onEnrollmentDetailsUpdate) {
          const data = await fetchGroupEnrollmentDetails(selectedGroup);
          onEnrollmentDetailsUpdate(data);
        }
      }
    } catch (error) {
      console.error('Failed to remove user(s):', error);
      setNotification({
        message: error.message || 'An unknown error occurred.',
        type: 'error',
      });
      setErrorEmails(usersToRemove.map((u) => u.email));
    } finally {
      closeRemoveUser();
      setUsersToRemove([]);
      setSelectedUsers((prev) => {
        const newSet = new Set(prev);
        usersToRemove.forEach((u) => newSet.delete(u.user_id));
        return newSet;
      });
    }
  };

  const handleEditUser = (user) => {
    setUserToEdit(user);
    openEditUser();
  };

  const handleUpdateUser = async (updatedUser) => {
    setNotification({ message: '', type: '' });
    try {
      const response = await updateUser(updatedUser);
      setNotification({
        message: response.message,
        type: response.success ? 'success' : 'error',
      });
      // Silently update enrollment details
      if (fetchGroupEnrollmentDetails && onEnrollmentDetailsUpdate) {
        const data = await fetchGroupEnrollmentDetails(selectedGroup);
        onEnrollmentDetailsUpdate(data);
      }
    } catch (error) {
      setNotification({
        message:
          error.message || 'An unknown error occurred while updating user.',
        type: 'error',
      });
    } finally {
      closeEditUser();
      setUserToEdit(null);
    }
  };

  const handleViewProgress = (user) => {
    setUserToViewProgress(user);
    openUserProgress();
  };

  useEffect(() => {
    setCurrentPage(1);
  }, [searchTerm]);

  useEffect(() => {
    setSelectedUsers(new Set());
  }, [currentPage, searchTerm]);

  const hasUsers = enrolledUsers.length > 0;
  const hasFilteredUsers = filteredUsers.length > 0;
  const showEmptyState = !hasUsers || (!hasFilteredUsers && searchTerm);

  return (
    <div className="enrollment-details__enrollment-list">
      {/* Notices for bulk success and error with modal buttons */}
      {successEmails.length > 0 && (
        <div className="enrollment-list__notice success">
          <div>
            {__(
              'Check successfully removed users from group.',
              'edwiser-bridge-pro'
            )}
            <button
              className="enrollment-list__notice-link"
              onClick={() => {
                setModalType('success');
                setModalEmails(successEmails);
                openEnrollUnenrollMultipleResult();
              }}
            >
              {__('View list', 'edwiser-bridge-pro')}
            </button>
          </div>
          <button
            className="btn__notice-close"
            onClick={() => setSuccessEmails([])}
          >
            <Icons.cross />
          </button>
        </div>
      )}
      {errorEmails.length > 0 && (
        <div className="enrollment-list__notice error">
          <div>
            {__('Some users could not be removed.', 'edwiser-bridge-pro')}
            <button
              className="enrollment-list__notice-link"
              onClick={() => {
                setModalType('error');
                setModalEmails(errorEmails);
                openEnrollUnenrollMultipleResult();
              }}
            >
              {__('View list', 'edwiser-bridge-pro')}
            </button>
          </div>
          <button
            className="btn__notice-close"
            onClick={() => setErrorEmails([])}
          >
            <Icons.cross />
          </button>
        </div>
      )}
      {/* Existing notification for other cases */}
      {notification.message && !successEmails.length && !errorEmails.length && (
        <div className={`enrollment-list__notice ${notification.type}`}>
          <div>{notification.message}</div>
          <button
            className="btn__notice-close"
            onClick={() => setNotification({ message: '', type: '' })}
          >
            <Icons.cross />
          </button>
        </div>
      )}
      <h3 className="enrollment-list__title">
        {__('Enrollment list', 'edwiser-bridge-pro')}
      </h3>
      <div className="enrollment-list__header">
        <div className="enrollment-list__header-action">
          {/* <Checkbox
            className="enrollment-list__select-all"
            checked={allVisibleSelected}
            indeterminate={someVisibleSelected}
            onChange={handleSelectAll}
            disabled={!hasUsers}
            label={
              allVisibleSelected
                ? __('Deselect all users', 'edwiser-bridge-pro')
                : __('Select all users', 'edwiser-bridge-pro')
            }
          /> */}
          <button
            className="enrollment-list__select-all"
            onClick={handleSelectAll}
            disabled={!hasUsers}
          >
            {allVisibleSelected ? (
              <>
                <Icons.deselect />
                {__('Deselect all users', 'edwiser-bridge-pro')}
              </>
            ) : (
              <>
                <Icons.select /> {__('Select all users', 'edwiser-bridge-pro')}
              </>
            )}
          </button>
          {selectedUsers.size !== 0 && (
            <button
              className="enrollment-list__action-btn btn__delete-selected"
              onClick={handleDeleteSelected}
              disabled={selectedUsers.size === 0}
            >
              <Icons.trash />
              {sprintf(
                /* translators: %d: number of selected users */
                __('Delete selected (%d)', 'edwiser-bridge-pro'),
                selectedUsers.size
              )}
            </button>
          )}
        </div>
        <TextInput
          className="enrollment-list__search"
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.currentTarget.value)}
          leftSectionPointerEvents="none"
          leftSection={<Icons.search />}
          placeholder={__('Search users...', 'edwiser-bridge-pro')}
          disabled={!hasUsers}
        />
      </div>

      {showEmptyState ? (
        <EmptyEnrolmentList searchTerm={searchTerm} />
      ) : (
        <>
          <div className="enrollment-list__items">
            {currentUsers.map((user) => (
              <div key={user.user_id} className="enrollment-list__item">
                <Checkbox
                  checked={selectedUsers.has(user.user_id)}
                  onChange={() => handleSelectUser(user.user_id)}
                  label={
                    <div className="enrollment-list__user-info">
                      <div className="enrollment-list__user-name">
                        {user.full_name || user.display_name}
                      </div>
                      <div className="enrollment-list__user-email">
                        {user.email}
                      </div>
                    </div>
                  }
                />

                <div className="enrollment-list__actions">
                  <button
                    className="enrollment-list__action-btn btn__view-progress"
                    onClick={() => handleViewProgress(user)}
                  >
                    {__('View progress', 'edwiser-bridge-pro')}
                  </button>
                  <span className="enrollment-list__action-separator"></span>
                  <button
                    className="enrollment-list__action-btn btn__delete"
                    onClick={() => handleDeleteUser(user)}
                  >
                    <Icons.trash />
                  </button>
                  <button
                    className="enrollment-list__action-btn btn__edit"
                    onClick={() => handleEditUser(user)}
                  >
                    <Icons.edit />
                  </button>
                </div>
              </div>
            ))}
          </div>

          <div className="enrollment-list__pagination">
            <div className="pagination__users-result-count">
              {currentUsers.length > 0
                ? sprintf(
                    /* translators: %1$d = starting user number, %2$d = ending user number, %3$d = total number of users */
                    __(
                      'Showing %1$d - %2$d of %3$d users',
                      'edwiser-bridge-pro'
                    ),
                    (currentPage - 1) * itemsPerPage + 1,
                    (currentPage - 1) * itemsPerPage + currentUsers.length,
                    enrolledUsers.length
                  )
                : __('No users found', 'edwiser-bridge-pro')}
            </div>
            <Pagination
              total={totalPages}
              value={currentPage}
              onChange={setCurrentPage}
            />
          </div>
        </>
      )}
      <RemoveUser
        removeUserOpened={removeUserOpened}
        closeRemoveUser={closeRemoveUser}
        users={usersToRemove}
        onConfirm={handleConfirmRemove}
        isDeleting={isDeletingUser}
      />
      <EditUser
        editUserOpened={editUserOpened}
        closeEditUser={closeEditUser}
        user={userToEdit}
        onSubmit={handleUpdateUser}
        isUpdating={isUpdatingUser}
      />
      <UserProgress
        userProgressOpened={userProgressOpened}
        closeUserProgress={closeUserProgress}
        user={userToViewProgress}
        selectedGroup={selectedGroup}
        fetchCourseProgress={fetchCourseProgress}
        courseProgress={courseProgress}
        isCourseProgressLoading={isCourseProgressLoading}
      />
      <EnrollUnenrollMultipleResult
        enrollUnenrollMultipleResultOpened={enrollUnenrollMultipleResultOpened}
        closeEnrollUnenrollMultipleResult={closeEnrollUnenrollMultipleResult}
        type={modalType}
        emails={modalEmails}
      />
    </div>
  );
}

export default EnrollmentList;

export function EnrollmentListSkeleton() {
  return (
    <div className="enrollment-details__enrollment-list">
      <Skeleton w={90} h={20} />
      <div className="enrollment-list__header">
        <Skeleton w={110} h={20} />
        <Skeleton w={310} h={36} />
      </div>
      <div className="enrollment-list__items">
        {Array.from({ length: 8 }).map((_, index) => (
          <div key={index} className="enrollment-list__item">
            <div className="enrollment-list__user-info">
              <Skeleton w={120} h={20} />
              <Skeleton w={160} h={16} />
            </div>
            <div className="enrollment-list__actions">
              <Skeleton w={90} h={32} />
              <span className="enrollment-list__action-separator"></span>
              <Skeleton w={32} h={32} />
              <Skeleton w={32} h={32} />
            </div>
          </div>
        ))}
      </div>
      <div className="enrollment-list__pagination">
        <Skeleton w={150} h={20} />
        <Skeleton w={250} h={32} />
      </div>
    </div>
  );
}

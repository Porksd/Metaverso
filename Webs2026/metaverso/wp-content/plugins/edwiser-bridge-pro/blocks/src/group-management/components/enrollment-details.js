import { useDisclosure } from '@mantine/hooks';
import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import EnrollmentList, { EnrollmentListSkeleton } from './enrollment-list';
import { Icons } from './icons';
import EnrollMultipleUsers from './modals/enroll-multiple-users';
import EnrollUser from './modals/enroll-user';
import UsersList from './modals/users-list';
import EnrollUnenrollMultipleResult from './modals/enroll-unenroll-multiple-users-result';
import { Skeleton } from '@mantine/core';
import EnrollmentProgressModal from './modals/enrollment-progress';

function EnrollmentDetails({
  enrollmentDetails,
  selectedGroup,
  fetchCourseProgress,
  courseProgress,
  isCourseProgressLoading,
  deleteUserFromGroup,
  isDeletingUser,
  deleteMultipleUsersFromGroup,
  updateUser,
  isUpdatingUser,
  enrollUsers,
  fetchGroupEnrollmentDetails,
  onEnrollmentDetailsUpdate,
}) {
  const [
    enrollSingleOpened,
    { open: openEnrollSingle, close: closeEnrollSingle },
  ] = useDisclosure(false);
  const [
    enrollMultipleOpened,
    { open: openEnrollMultiple, close: closeEnrollMultiple },
  ] = useDisclosure(false);
  const [progressOpened, { open: openProgress, close: closeProgress }] =
    useDisclosure(false);
  const [
    enrollUnenrollMultipleResultOpened,
    {
      open: openEnrollUnenrollMultipleResult,
      close: closeEnrollUnenrollMultipleResult,
    },
  ] = useDisclosure(false);
  const [usersListOpened, { open: openUsersList, close: closeUsersList }] =
    useDisclosure(false);

  const [notifications, setNotifications] = useState([]);
  const [usersToEnroll, setUsersToEnroll] = useState([]);
  const [isEnrolling, setIsEnrolling] = useState(false);
  const [enrollSuccessEmails, setEnrollSuccessEmails] = useState([]);
  const [alreadyEnrolledEmails, setAlreadyEnrolledEmails] = useState([]);
  const [enrollModalType, setEnrollModalType] = useState('success');
  const [enrollModalEmails, setEnrollModalEmails] = useState([]);
  const [progressProcessed, setProgressProcessed] = useState(0);
  const [progressTotal, setProgressTotal] = useState(0);
  const [progressPercent, setProgressPercent] = useState(0);

  const addNotification = (message, type) => {
    const newNotification = { message, type };
    setNotifications((prevNotifications) => [
      ...prevNotifications,
      newNotification,
    ]);
  };

  // Bulk enrollment handler for UsersList
  const handleBulkEnroll = async (users) => {
    setIsEnrolling(true);
    setEnrollSuccessEmails([]);
    setAlreadyEnrolledEmails([]);
    setProgressProcessed(0);
    setProgressTotal(users.length);
    setProgressPercent(0);

    // Show progress modal if chunking is needed
    if (users.length > 5) {
      closeUsersList();
      openProgress();
    }

    try {
      const result = await enrollUsers(
        selectedGroup,
        users,
        (processed, total) => {
          setProgressProcessed(processed);
          setProgressTotal(total);
          setProgressPercent(Math.round((processed / total) * 100));
          if (processed >= total) setTimeout(() => closeProgress(), 500);
        }
      );
      if (result && result.success) {
        const successList = result.enrollment_result?.enroll_success || [];
        const alreadyList = result.enrollment_result?.already_enrolled || [];
        setEnrollSuccessEmails(successList);
        setAlreadyEnrolledEmails(alreadyList);

        if (fetchGroupEnrollmentDetails && onEnrollmentDetailsUpdate) {
          const data = await fetchGroupEnrollmentDetails(selectedGroup);
          onEnrollmentDetailsUpdate(data);
        }
      } else {
        addNotification(
          __('Failed to enroll users.', 'edwiser-bridge-pro'),
          'error'
        );
      }
      return result;
    } catch (err) {
      closeProgress();
      addNotification(
        err.message || __('Enrollment failed.', 'edwiser-bridge-pro'),
        'error'
      );
      return { success: false, error: err.message };
    } finally {
      setIsEnrolling(false);
    }
  };

  return (
    <div className="eb-group-management__enrollment-details">
      <div className="enrollment-details__enrollment-status">
        <span className="enrollment-status__label">
          {__('Enrolled Users ( Remaining Seats ) : ', 'edwiser-bridge-pro')}
        </span>
        <span className="enrollment-status__value">
          {enrollmentDetails.enrolled_users_count} ({' '}
          {enrollmentDetails.available_seats} )
        </span>
      </div>
      <div className="enrollment-details__enrollment-actions">
        <button
          className="btn__enrollment"
          onClick={openEnrollSingle}
          disabled={enrollmentDetails.available_seats <= 0}
        >
          <Icons.user />
          {__('Enroll a User', 'edwiser-bridge-pro')}
        </button>
        <button
          className="btn__enrollment"
          onClick={openEnrollMultiple}
          disabled={enrollmentDetails.available_seats <= 0}
        >
          <Icons.users />
          {__('Enroll multiple users', 'edwiser-bridge-pro')}
        </button>
      </div>
      {/* Enroll a user Modal */}
      <EnrollUser
        enrollSingleOpened={enrollSingleOpened}
        closeEnrollSingle={closeEnrollSingle}
        cohortId={selectedGroup}
        enrollUsers={enrollUsers}
        selectedGroup={selectedGroup}
        fetchGroupEnrollmentDetails={fetchGroupEnrollmentDetails}
        onEnrollmentDetailsUpdate={onEnrollmentDetailsUpdate}
        addNotification={addNotification}
      />

      {/* Enroll multiple users Modal */}
      <EnrollMultipleUsers
        enrollMultipleOpened={enrollMultipleOpened}
        closeEnrollMultiple={closeEnrollMultiple}
        cohortId={selectedGroup}
        enrollUsers={enrollUsers}
        availableSeats={enrollmentDetails.available_seats}
        onUploadSuccess={(users) => {
          closeEnrollMultiple();
          openUsersList();
          setUsersToEnroll(users);
        }}
      />
      {/* Users list Modal */}
      <UsersList
        usersListOpened={usersListOpened}
        closeUsersList={closeUsersList}
        users={usersToEnroll}
        onEnroll={handleBulkEnroll}
        isEnrolling={isEnrolling}
      />
      <EnrollmentProgressModal
        progressOpened={progressOpened}
        processed={progressProcessed}
        total={progressTotal}
        percent={progressPercent}
      />
      <EnrollUnenrollMultipleResult
        enrollUnenrollMultipleResultOpened={enrollUnenrollMultipleResultOpened}
        closeEnrollUnenrollMultipleResult={closeEnrollUnenrollMultipleResult}
        type={enrollModalType}
        emails={enrollModalEmails}
        mode={'enroll'}
      />
      {/* Bulk enrollment notifications */}
      <div className="enrollment-details__notices">
        {enrollSuccessEmails.length > 0 && (
          <div className="notice success">
            <div>
              {__('Users enrolled successfully.', 'edwiser-bridge-pro')}
              <button
                className="enrollment-details__notice-link"
                onClick={() => {
                  setEnrollModalType('success');
                  setEnrollModalEmails(enrollSuccessEmails);
                  openEnrollUnenrollMultipleResult();
                }}
              >
                {__('View list', 'edwiser-bridge-pro')}
              </button>
            </div>
            <button
              className="btn__notice-close"
              onClick={() => setEnrollSuccessEmails([])}
            >
              <Icons.cross />
            </button>
          </div>
        )}
        {alreadyEnrolledEmails.length > 0 && (
          <div className="notice error">
            <div>
              {__('Some users were already enrolled.', 'edwiser-bridge-pro')}
              <button
                className="enrollment-details__notice-link"
                onClick={() => {
                  setEnrollModalType('error');
                  setEnrollModalEmails(alreadyEnrolledEmails);
                  openEnrollUnenrollMultipleResult();
                }}
              >
                {__('View list', 'edwiser-bridge-pro')}
              </button>
            </div>
            <button
              className="btn__notice-close"
              onClick={() => setAlreadyEnrolledEmails([])}
            >
              <Icons.cross />
            </button>
          </div>
        )}
      </div>
      {notifications.length > 0 && (
        <div className="enrollment-details__notices">
          {notifications.map((notification, index) => (
            <div key={index} className={`notice ${notification.type}`}>
              <div>{notification.message}</div>
              <button
                className="btn__notice-close"
                onClick={() =>
                  setNotifications(notifications.filter((_, i) => i !== index))
                }
              >
                <Icons.cross />
              </button>
            </div>
          ))}
        </div>
      )}
      <EnrollmentList
        enrolledUsers={enrollmentDetails.enrolled_users}
        selectedGroup={selectedGroup}
        fetchCourseProgress={fetchCourseProgress}
        courseProgress={courseProgress}
        isCourseProgressLoading={isCourseProgressLoading}
        deleteUserFromGroup={deleteUserFromGroup}
        isDeletingUser={isDeletingUser}
        deleteMultipleUsersFromGroup={deleteMultipleUsersFromGroup}
        updateUser={updateUser}
        isUpdatingUser={isUpdatingUser}
        fetchGroupEnrollmentDetails={fetchGroupEnrollmentDetails}
        onEnrollmentDetailsUpdate={onEnrollmentDetailsUpdate}
      />
    </div>
  );
}

export default EnrollmentDetails;

export function EnrollmentDetailsSkeleton() {
  return (
    <div className="eb-group-management__enrollment-details">
      <Skeleton width={250} h={20} />
      <div className="enrollment-details__enrollment-actions">
        <Skeleton width={120} h={34} />
        <Skeleton width={160} h={34} />
      </div>
      <EnrollmentListSkeleton />
    </div>
  );
}

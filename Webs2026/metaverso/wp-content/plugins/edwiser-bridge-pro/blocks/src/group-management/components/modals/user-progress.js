import { Modal, Skeleton } from '@mantine/core';
import React, { useEffect } from 'react';
import { decodeHTMLEntities } from '../../utils';
import { __, sprintf } from '@wordpress/i18n';

function UserProgress({
  userProgressOpened,
  closeUserProgress,
  user,
  selectedGroup,
  fetchCourseProgress,
  courseProgress,
  isCourseProgressLoading,
}) {
  const userName = user
    ? user.full_name || user.display_name || user.email
    : '';

  useEffect(() => {
    if (user && selectedGroup) {
      fetchCourseProgress(selectedGroup, user.user_id);
    }
  }, [user, selectedGroup]);

  return (
    <Modal
      opened={userProgressOpened}
      onClose={closeUserProgress}
      title={
        userName
          ? sprintf(__("%s's course progress", 'edwiser-bridge-pro'), userName)
          : __('Course progress', 'edwiser-bridge-pro')
      }
      withinPortal={false}
      closeOnClickOutside={false}
      size="lg"
    >
      <div className="enrollment-list__user-progress-modal">
        {isCourseProgressLoading ? (
          Array.from({ length: 3 }).map((_, index) => (
            <div className="user-progress-modal__course" key={index}>
              <Skeleton w={190} h={20} />
              <div className="course__progress">
                <div className="progress__bar">
                  <Skeleton w={210} h={12} />
                </div>
                <div className="progress__value">
                  <Skeleton w={24} h={20} />
                </div>
              </div>
            </div>
          ))
        ) : courseProgress && courseProgress.length >= 0 ? (
          courseProgress.map((course) => (
            <div className="user-progress-modal__course" key={course.id}>
              <div className="course__title">
                {decodeHTMLEntities(course.course_title)}
              </div>
              <div className="course__progress">
                <div className="progress__bar">
                  <div
                    style={{
                      width: `${course.progress}%`,
                    }}
                  />
                </div>
                <div className="progress__value">{course.progress}%</div>
              </div>
            </div>
          ))
        ) : (
          <div className="user-progress-modal__no-progress">
            {__(
              'No course progress found for this user.',
              'edwiser-bridge-pro'
            )}
          </div>
        )}
      </div>
    </Modal>
  );
}

export default UserProgress;

import { TextInput } from '@mantine/core';
import { __ } from '@wordpress/i18n';
import React, { useState, useEffect } from 'react';
import { decodeHTMLEntities } from '../utils';
import { Icons } from './icons';

function GroupDetails({
  groupDetails,
  updateGroupName,
  isUpdatingName,
  onGroupNameUpdate,
}) {
  const [groupName, setGroupName] = useState(groupDetails.name || '');
  const [currentGroupName, setCurrentGroupName] = useState(
    groupDetails.name || ''
  );
  const [notification, setNotification] = useState({ message: '', type: '' });

  useEffect(() => {
    setGroupName(groupDetails.name || '');
    setCurrentGroupName(groupDetails.name || '');
  }, [groupDetails.name]);

  const handleUpdateName = async () => {
    if (!groupName.trim()) {
      setNotification({
        message: __('Group name cannot be empty', 'edwiser-bridge-pro'),
        type: 'error',
      });
      return;
    }

    if (groupName.trim() === currentGroupName) {
      setNotification({
        message: __('Please enter a different name', 'edwiser-bridge-pro'),
        type: 'error',
      });
      return;
    }

    setNotification({ message: '', type: '' });

    try {
      await updateGroupName(groupDetails.cohort_id, groupName);
      setNotification({
        message: __('Group name updated successfully', 'edwiser-bridge-pro'),
        type: 'success',
      });
      setCurrentGroupName(groupName);
      if (onGroupNameUpdate) {
        onGroupNameUpdate(groupDetails.cohort_id, groupName);
      }
    } catch (err) {
      setNotification({
        message:
          err.message ||
          __('Failed to update group name', 'edwiser-bridge-pro'),
        type: 'error',
      });
    }
  };

  const handleInputChange = (event) => {
    setGroupName(event.target.value);
    if (notification.message) setNotification({ message: '', type: '' });
  };

  return (
    <div className="eb-group-management__group-details">
      {notification.message && (
        <div className={`group-details__notice ${notification.type}`}>
          <span>{notification.message}</span>
          <button
            className="btn__notice-close"
            onClick={() => setNotification({ message: '', type: '' })}
          >
            <Icons.cross />
          </button>
        </div>
      )}
      <div className="group-details__group-name">
        <h3 className="group-name__title">
          {__('Group name', 'edwiser-bridge-pro')}
        </h3>
        <div className="group-name__group-form">
          <TextInput
            className="group-name__input"
            value={groupName}
            onChange={handleInputChange}
            disabled={isUpdatingName}
          />
          <button
            className="btn__update-name"
            onClick={handleUpdateName}
            disabled={
              isUpdatingName ||
              !groupName.trim() ||
              groupName.trim() === currentGroupName
            }
          >
            {isUpdatingName && <Icons.loader />}
            {__('Update name', 'edwiser-bridge-pro')}
          </button>
        </div>
      </div>

      {groupDetails.products?.length > 0 && (
        <div className="group-details__associated-courses">
          <h3 className="associated-courses__title">
            {__('Product-wise associated courses', 'edwiser-bridge-pro')}
          </h3>
          <div className="associated-courses__course-bundles">
            {groupDetails.products.map((product) => (
              <div className="course-bundle" key={product.product_id}>
                <div className="course-bundle__title">
                  {decodeHTMLEntities(product.product_name)}
                </div>
                {product.courses?.length > 0 && (
                  <ol
                    className="course-bundle__list"
                    type="a"
                    style={{ listStyle: 'lower-alpha' }}
                  >
                    {product.courses.map((course) => (
                      <li key={course.course_id}>
                        {decodeHTMLEntities(course.course_title)}
                      </li>
                    ))}
                  </ol>
                )}
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}

export default GroupDetails;

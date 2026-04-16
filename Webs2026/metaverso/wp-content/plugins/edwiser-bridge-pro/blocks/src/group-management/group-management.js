import { MantineProvider, Tabs } from '@mantine/core';
import { __ } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react';
import GroupActions, { GroupActionsSkeleton } from './components/group-actions';
import EnrollmentDetails, {
  EnrollmentDetailsSkeleton,
} from './components/enrollment-details';
import GroupDetails from './components/group-details';
import useGroup from './hooks/use-group';

function GroupManagement(attributes) {
  const {
    groups: groupsFromHook,
    isGroupsLoading,
    isDetailsLoading,
    enrollmentDetails: enrollmentDetailsFromHook,
    groupDetails,
    selectedGroup,
    setSelectedGroup,
    updateGroupName,
    isUpdatingName,
    fetchCourseProgress,
    courseProgress,
    isCourseProgressLoading,
    deleteUserFromGroup,
    isDeletingUser,
    deleteMultipleUsersFromGroup,
    updateUser,
    isUpdatingUser,
    fetchAddProductData,
    addProductData,
    isAddProductLoading,
    fetchAddQuantityData,
    addQuantityData,
    isAddQuantityLoading,
    addProductsToCart,
    enrollUsers,
    fetchGroupEnrollmentDetails,
    authError,
    deleteGroup,
    isDeletingGroup,
  } = useGroup();

  // Local groups state for instant UI updates
  const [groups, setGroups] = useState(groupsFromHook);
  // Local enrollmentDetails state for silent updates
  const [enrollmentDetails, setEnrollmentDetails] = useState(
    enrollmentDetailsFromHook
  );

  // Sync local groups with hook groups
  useEffect(() => {
    setGroups(groupsFromHook);
  }, [groupsFromHook]);

  // Sync local enrollmentDetails with hook enrollmentDetails
  useEffect(() => {
    setEnrollmentDetails(enrollmentDetailsFromHook);
  }, [enrollmentDetailsFromHook]);

  // Handler to update group name in local state
  const handleGroupNameUpdate = (cohortId, newName) => {
    setGroups((prevGroups) =>
      prevGroups.map((group) =>
        group.mdl_cohort_id === cohortId
          ? {
              ...group,
              display_text: `${newName} (${group.available_seats})`,
            }
          : group
      )
    );
  };

  // Handler to update enrollment details and group name if changed
  const handleEnrollmentDetailsUpdate = (data) => {
    if (!data) return;
    setEnrollmentDetails(data.enrollment_details);
    if (data.group_name) {
      setGroups((prevGroups) =>
        prevGroups.map((group) =>
          group.mdl_cohort_id === selectedGroup
            ? { ...group, display_text: data.group_name }
            : group
        )
      );
    }
  };

  return (
    <MantineProvider>
      <div className="eb-group-management__wrapper">
        {!attributes.hideTitle && (
          <h2 className="eb-group-management__page-title">
            {attributes.customTitle
              ? attributes.customTitle
              : __('Enroll Students', 'edwiser-bridge-pro')}
          </h2>
        )}
        {authError ? (
          <div className="eb-group-management__sign-in-notice">
            <p>{authError.message}</p>
            <a
              href={`${authError.signInUrl}?redirect_to=${window.location.href}`}
              className="eb-group-management__sign-in-link"
            >
              {__('Sign in', 'edwiser-bridge-pro')}
            </a>
          </div>
        ) : isGroupsLoading ? (
          <GroupActionsSkeleton />
        ) : (
          <GroupActions
            groups={groups}
            selectedGroup={selectedGroup}
            setSelectedGroup={setSelectedGroup}
            fetchAddProductData={fetchAddProductData}
            addProductData={addProductData}
            isAddProductLoading={isAddProductLoading}
            fetchAddQuantityData={fetchAddQuantityData}
            addQuantityData={addQuantityData}
            isAddQuantityLoading={isAddQuantityLoading}
            addProductsToCart={addProductsToCart}
            deleteGroup={deleteGroup}
            isDeletingGroup={isDeletingGroup}
          />
        )}
        {!authError &&
          selectedGroup &&
          (isDetailsLoading ? (
            <EnrollmentDetailsSkeleton />
          ) : (
            <Tabs defaultValue="enrollment">
              <Tabs.List>
                <Tabs.Tab value="enrollment">
                  {__('Enrollment details', 'edwiser-bridge-pro')}
                </Tabs.Tab>
                <Tabs.Tab value="group">
                  {__('Group details', 'edwiser-bridge-pro')}
                </Tabs.Tab>
              </Tabs.List>
              <Tabs.Panel value="enrollment">
                <EnrollmentDetails
                  enrollmentDetails={enrollmentDetails}
                  selectedGroup={selectedGroup}
                  fetchCourseProgress={fetchCourseProgress}
                  courseProgress={courseProgress}
                  isCourseProgressLoading={isCourseProgressLoading}
                  deleteUserFromGroup={deleteUserFromGroup}
                  isDeletingUser={isDeletingUser}
                  deleteMultipleUsersFromGroup={deleteMultipleUsersFromGroup}
                  updateUser={updateUser}
                  isUpdatingUser={isUpdatingUser}
                  enrollUsers={enrollUsers}
                  fetchGroupEnrollmentDetails={fetchGroupEnrollmentDetails}
                  onEnrollmentDetailsUpdate={handleEnrollmentDetailsUpdate}
                />
              </Tabs.Panel>
              <Tabs.Panel value="group">
                <GroupDetails
                  groupDetails={groupDetails}
                  updateGroupName={updateGroupName}
                  isUpdatingName={isUpdatingName}
                  onGroupNameUpdate={handleGroupNameUpdate}
                />
              </Tabs.Panel>
            </Tabs>
          ))}
      </div>
    </MantineProvider>
  );
}

export default GroupManagement;

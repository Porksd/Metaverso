import { useState, useCallback, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';

const useGroup = () => {
  const [groups, setGroups] = useState([]);
  const [isGroupsLoading, setIsGroupsLoading] = useState(false);

  const [selectedGroup, setSelectedGroup] = useState(null);

  const [isDetailsLoading, setIsDetialsLoading] = useState(false);
  const [groupDetails, setGroupDetails] = useState({});
  const [enrollmentDetails, setEnrollmentDetails] = useState({});

  const [isUpdatingName, setIsUpdatingName] = useState(false);
  const [isDeletingUser, setIsDeletingUser] = useState(false);
  const [isUpdatingUser, setIsUpdatingUser] = useState(false);

  const [courseProgress, setCourseProgress] = useState([]);
  const [isCourseProgressLoading, setIsCourseProgressLoading] = useState(false);

  const [addProductData, setAddProductData] = useState(null);
  const [isAddProductLoading, setIsAddProductLoading] = useState(false);

  const [addQuantityData, setAddQuantityData] = useState(null);
  const [isAddQuantityLoading, setIsAddQuantityLoading] = useState(false);

  const [authError, setAuthError] = useState(null);
  const [isEnrolling, setIsEnrolling] = useState(false);

  const [isDeletingGroup, setIsDeletingGroup] = useState(false);

  const fetchGroups = useCallback(async () => {
    setIsGroupsLoading(true);
    setAuthError(null);

    try {
      const data = await apiFetch({
        path: '/eb/api/v1/group-management/groups',
      });

      if (data.auth_required) {
        setAuthError({
          message: data.message,
          signInUrl: data.sign_in_url,
        });
        setGroups([]);
        return;
      }

      setGroups(data.groups || []);
    } catch (error) {
      console.error('Error fetching groups:', error);
      setGroups([]);
    } finally {
      setIsGroupsLoading(false);
    }
  }, []);

  const fetchGroupById = useCallback(
    async (groupId) => {
      if (!groupId) return;

      setIsDetialsLoading(true);

      try {
        const data = await apiFetch({
          path: `/eb/api/v1/group-management/groups/${groupId}`,
        });

        setEnrollmentDetails(data.enrollment_details);
        setGroupDetails(data.group_details);
      } catch (error) {
        console.error('Error fetching group by id:', error);
      } finally {
        setIsDetialsLoading(false);
      }
    },
    [selectedGroup]
  );

  const updateGroupName = useCallback(async (cohortId, cohortName) => {
    if (!cohortId || !cohortName.trim()) {
      throw new Error('Cohort ID and name are required');
    }

    setIsUpdatingName(true);

    try {
      const response = await apiFetch({
        path: '/eb/api/v1/group-management/groups/update-name',
        method: 'POST',
        data: {
          nonce: ebbpPublic.nonce_gp_mng,
          cohort_id: cohortId,
          cohort_name: cohortName.trim(),
        },
      });

      return response;
    } catch (error) {
      console.error('Error updating group name:', error);
      throw error;
    } finally {
      setIsUpdatingName(false);
    }
  }, []);

  const updateUser = useCallback(async (userData) => {
    if (!userData || !userData.user_id) {
      throw new Error('User data is required');
    }
    setIsUpdatingUser(true);
    try {
      const response = await apiFetch({
        path: '/eb/api/v1/group-management/users/update',
        method: 'POST',
        data: {
          nonce: ebbpPublic.nonce_gp_mng,
          user_id: userData.user_id,
          first_name: userData.first_name,
          last_name: userData.last_name,
          email: userData.email,
        },
      });

      return response;
    } catch (error) {
      console.error('Error updating user:', error);
      throw error;
    } finally {
      setIsUpdatingUser(false);
    }
  }, []);

  const deleteUserFromGroup = useCallback(async (userId, cohortId) => {
    if (!userId || !cohortId) {
      throw new Error('User ID and Cohort ID are required');
    }

    setIsDeletingUser(true);

    try {
      const response = await apiFetch({
        path: '/eb/api/v1/group-management/groups/delete-user',
        method: 'POST',
        data: {
          nonce: ebbpPublic.nonce_bp_enroll,
          user_id: userId,
          cohort_id: cohortId,
        },
      });

      return response;
    } catch (error) {
      console.error('Error deleting user:', error);
      throw error;
    } finally {
      setIsDeletingUser(false);
    }
  }, []);

  const deleteMultipleUsersFromGroup = useCallback(
    async (userIds, cohortId) => {
      if (!userIds || userIds.length === 0 || !cohortId) {
        throw new Error('User IDs and Cohort ID are required');
      }

      setIsDeletingUser(true);

      try {
        const totalUsers = userIds.length;
        const chunkSize = 20; // Process in chunks of 20
        let finalResponse = null;

        for (let i = 0; i < totalUsers; i += chunkSize) {
          const chunk = userIds.slice(i, i + chunkSize);

          const response = await apiFetch({
            path: '/eb/api/v1/group-management/groups/delete-users',
            method: 'POST',
            data: {
              nonce: ebbpPublic.nonce_gp_mng,
              user_ids: chunk,
              cohort_id: cohortId,
              total: totalUsers,
              processed_users: i,
            },
          });

          if (response.data.is_final_batch) {
            finalResponse = response;
          }
        }

        return finalResponse;
      } catch (error) {
        console.error('Error deleting multiple users:', error);
      } finally {
        setIsDeletingUser(false);
      }
    },
    []
  );

  const fetchCourseProgress = useCallback(async (groupId, userId) => {
    if (!groupId || !userId) {
      return;
    }

    setIsCourseProgressLoading(true);
    try {
      const response = await apiFetch({
        path: '/eb/api/v1/group-management/groups/course-progress',
        method: 'POST',
        data: {
          nonce: ebbpPublic.nonce_gp_mng,
          group_id: groupId,
          user_id: userId,
        },
      });

      if (response.success) {
        setCourseProgress(response.courses_progress || []);
      } else {
        throw new Error(response.message || 'Failed to fetch course progress');
      }
    } catch (error) {
      console.error('Error fetching course progress:', error);
      setCourseProgress([]);
    } finally {
      setIsCourseProgressLoading(false);
    }
  }, []);

  const fetchAddProductData = useCallback(async (cohortId) => {
    setIsAddProductLoading(true);
    setAddProductData(null);

    try {
      const formData = new FormData();
      formData.append('action', 'eb_add_product');
      formData.append('nonce', window.ebbpPublic.nonce_gp_mng);
      formData.append('cohort_id', cohortId);

      const response = await fetch(window.eb_ajax_object.ajax_url, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
      });

      const { data } = await response.json();
      setAddProductData(data);
    } catch (error) {
      console.log('Error fetching add product data: ', error.message);
    } finally {
      setIsAddProductLoading(false);
    }
  }, []);

  const fetchAddQuantityData = useCallback(async (cohortId) => {
    setIsAddQuantityLoading(true);
    setAddQuantityData(null);

    try {
      const formData = new FormData();
      formData.append('action', 'eb_add_quantity');
      formData.append('nonce', window.ebbpPublic.nonce_gp_mng);
      formData.append('cohort_id', cohortId);

      // Make the AJAX request
      const response = await fetch(window.eb_ajax_object.ajax_url, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
      });

      const { data } = await response.json();

      setAddQuantityData(data);
    } catch (error) {
      console.log('Error fetching add quantity data: ', error.message);
    } finally {
      setIsAddQuantityLoading(false);
    }
  }, []);

  const addProductsToCart = useCallback(async (cohortId, products) => {
    try {
      const formData = new FormData();
      formData.append('action', 'eb_add_to_cart');
      formData.append('nonce', window.ebbpPublic.nonce_gp_mng);
      formData.append('cohort_id', cohortId);
      formData.append('products', JSON.stringify(products));

      const response = await fetch(window.eb_ajax_object.ajax_url, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
      });

      const result = await response.json();
      if (result.success) {
        return { success: true, checkoutUrl: result.data };
      }
      return {
        success: false,
        message: result.data || 'Failed to add to cart',
      };
    } catch (error) {
      return {
        success: false,
        message: error.message || 'Error adding to cart',
      };
    }
  }, []);

  const enrollUsers = async (cohortId, users, onProgress) => {
    setIsEnrolling(true);
    try {
      const totalUsers = users.length;
      if (totalUsers === 0) {
        return { success: true };
      }

      const chunkSize = 5;
      let finalResponse = null;

      for (let i = 0; i < totalUsers; i += chunkSize) {
        const chunk = users.slice(i, i + chunkSize);
        const payload = {
          nonce: ebbpPublic.nonce_bp_enroll,
          cohort_id: cohortId,
          firstname: chunk.map((u) => u.first_name),
          lastname: chunk.map((u) => u.last_name),
          email: chunk.map((u) => u.email),
          total: totalUsers,
          processed_users: i,
        };

        const response = await apiFetch({
          path: '/eb/api/v1/group-management/groups/enroll-user',
          method: 'POST',
          data: payload,
        });

        if (onProgress) {
          onProgress(i + chunk.length, totalUsers);
        }

        finalResponse = response;

        if (!response.success) {
          return response; // Stop on first error
        }
      }
      return finalResponse;
    } catch (error) {
      return { success: false, error: error.message || 'Enrollment failed.' };
    } finally {
      setIsEnrolling(false);
    }
  };

  const fetchGroupEnrollmentDetails = useCallback(async (groupId) => {
    if (!groupId) return null;
    try {
      const data = await apiFetch({
        path: `/eb/api/v1/group-management/groups/enrollment-details?id=${groupId}`,
        method: 'GET',
      });
      return data;
    } catch (error) {
      console.error('Error fetching group enrollment details:', error);
      return null;
    }
  }, []);

  const deleteGroup = useCallback(
    async (cohortId) => {
      if (!cohortId) throw new Error('Cohort ID is required');
      setIsDeletingGroup(true);
      try {
        const response = await apiFetch({
          path: '/eb/api/v1/group-management/groups/delete',
          method: 'DELETE',
          data: {
            nonce: ebbpPublic.nonce_gp_mng,
            cohort_id: cohortId,
          },
        });

        if (response && response.redirect) {
          window.location.href = response.redirect;
          return response;
        }

        // Refetch groups after successful deletion if no redirect
        setSelectedGroup(null);
        await fetchGroups();
        return response;
      } catch (error) {
        console.error('Error deleting group:', error);
      } finally {
        setIsDeletingGroup(false);
      }
    },
    [fetchGroups]
  );

  useEffect(() => {
    fetchGroups();
  }, [fetchGroups]);

  useEffect(() => {
    if (selectedGroup) {
      fetchGroupById(selectedGroup);
    }
  }, [selectedGroup, fetchGroupById]);

  return {
    groups,
    isGroupsLoading,

    isDetailsLoading,
    enrollmentDetails,
    groupDetails,

    selectedGroup,
    setSelectedGroup,

    updateGroupName,
    isUpdatingName,

    updateUser,
    isUpdatingUser,

    deleteUserFromGroup,
    isDeletingUser,
    deleteMultipleUsersFromGroup,

    fetchCourseProgress,
    courseProgress,
    isCourseProgressLoading,

    fetchAddProductData,
    addProductData,
    isAddProductLoading,

    fetchAddQuantityData,
    addQuantityData,
    isAddQuantityLoading,

    addProductsToCart,
    enrollUsers,
    isEnrolling,

    fetchGroupEnrollmentDetails,

    authError,

    deleteGroup,
    isDeletingGroup,
  };
};

export default useGroup;

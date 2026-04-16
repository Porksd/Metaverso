import { Modal } from '@mantine/core';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Icons } from '../icons';

function DeleteGroup({
  deleteOpened,
  closeDelete,
  cohortId,
  onDelete,
  isDeletingGroup,
}) {
  const handleDelete = async () => {
    if (onDelete && cohortId) {
      const result = await onDelete(cohortId);
      if (result && result.success) {
        closeDelete();
      }
    }
  };

  return (
    <Modal
      opened={deleteOpened}
      onClose={closeDelete}
      title={__(
        'Are you sure you want to delete this group?',
        'edwiser-bridge-pro'
      )}
      withinPortal={false}
      closeOnClickOutside={false}
      size="md"
    >
      <div className="actions__delete-modal">
        <p className="delete-modal__desc">
          {__(
            'This will unenroll all the users from group and also from the courses assigned to the group.',
            'edwiser-bridge-pro'
          )}
        </p>
        <div className="delete-modal__action">
          <button
            className="btn__action-confirm"
            disabled={isDeletingGroup}
            onClick={handleDelete}
          >
            {isDeletingGroup && <Icons.loader />}
            {__('Delete Group', 'edwiser-bridge-pro')}
          </button>
          <button
            className="btn__action-cancel"
            onClick={closeDelete}
            disabled={isDeletingGroup}
          >
            {__('Cancel', 'edwiser-bridge-pro')}
          </button>
        </div>
      </div>
    </Modal>
  );
}

export default DeleteGroup;

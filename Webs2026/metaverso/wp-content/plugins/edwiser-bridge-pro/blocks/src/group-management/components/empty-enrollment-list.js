import React from 'react';
import { __, sprintf } from '@wordpress/i18n';

function EmptyEnrolmentList({ searchTerm }) {
  return (
    <div className="enrollment-list__empty-state">
      <div className="empty-state__title">
        {__('No enrolled users', 'edwiser-bridge-pro')}
      </div>
      <div className="empty-state__description">
        {searchTerm
          ? sprintf(
              /* translators: %s: search term */
              __('No users found matching "%s"', 'edwiser-bridge-pro'),
              searchTerm
            )
          : __(
              'There are no users enrolled in this course yet.',
              'edwiser-bridge-pro'
            )}
      </div>
    </div>
  );
}

export default EmptyEnrolmentList;

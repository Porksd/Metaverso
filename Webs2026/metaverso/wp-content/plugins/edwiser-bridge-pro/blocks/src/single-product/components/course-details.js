import React from 'react';
import { __, _n, sprintf } from '@wordpress/i18n';
import { Icons } from './icons';
import { Skeleton } from '@mantine/core';

function CourseDetails({ createdAt, courseAccess, enrolledCount, attributes }) {
  return (
    <div className="eb-product-desc__details">
      {attributes.showCreated && (
        <div className="eb-product-desc__detail">
          <div className="eb-product-desc__detail-label">
            <span className="eb-product-desc__details-label-icon">
              <Icons.calendar />
            </span>
            {__('Created', 'edwiser-bridge-pro')}
          </div>
          <div className="eb-product-desc__detail-value">
            {sprintf(
              /* translators: %s is the formatted date */
              __('%s', 'edwiser-bridge-pro'),
              createdAt
            )}
          </div>
        </div>
      )}
      {attributes.showCourseAccess && (
        <div className="eb-product-desc__detail">
          <div className="eb-product-desc__detail-label">
            <span className="eb-product-desc__details-label-icon">
              <Icons.clock />
            </span>
            {__('Course access', 'edwiser-bridge-pro')}
          </div>
          <div className="eb-product-desc__detail-value">
            {courseAccess
              ? sprintf(
                  /* translators: %d is the number of days of course access */
                  _n('%d Day', '%d Days', courseAccess, 'edwiser-bridge-pro'),
                  courseAccess
                )
              : __('Lifetime', 'edwiser-bridge-pro')}
          </div>
        </div>
      )}
      {attributes.showEnrolled && enrolledCount && (
        <div className="eb-product-desc__detail">
          <div className="eb-product-desc__detail-label">
            <span className="eb-product-desc__details-label-icon">
              <Icons.users />
            </span>
            {__('Enrolled', 'edwiser-bridge-pro')}
          </div>
          <div className="eb-product-desc__detail-value">{enrolledCount}</div>
        </div>
      )}
    </div>
  );
}

export default CourseDetails;

export function CourseDetailsSkeleton() {
  return (
    <div className="eb-product-desc__details">
      <div className="eb-product-desc__detail">
        <Skeleton width={120} height={24} />
        <Skeleton width={60} height={24} />
      </div>
      <div className="eb-product-desc__detail">
        <Skeleton width={120} height={24} />
        <Skeleton width={60} height={24} />
      </div>
      <div className="eb-product-desc__detail">
        <Skeleton width={120} height={24} />
        <Skeleton width={60} height={24} />
      </div>
    </div>
  );
}

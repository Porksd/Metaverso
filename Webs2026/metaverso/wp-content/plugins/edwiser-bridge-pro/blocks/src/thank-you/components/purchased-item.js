import { __, _n, sprintf } from '@wordpress/i18n';
import React from 'react';
import { Icons } from './icons';
import { Skeleton } from '@mantine/core';
import placeholderImg from '../../../../images/placeholder-course-thumbnail.jpeg';

function PurchasedCourse({ item }) {
  return (
    <div className="eb-thank-you__purchased-course-card">
      <div className="eb-thank-you__purchased-course-image">
        <img
          src={item.thumbnail || placeholderImg}
          alt={item.alt_text || item.name}
        />
      </div>
      <div className="eb-thank-you__purchased-course-details">
        <div className="eb-thank-you__purchased-course-category">
          <Icons.grid />
          <span>
            {__(item.category?.name || 'Uncategorized', 'edwiser-bridge-pro')}
          </span>
        </div>
        <h3 className="eb-thank-you__purchased-course-title">
          {__(item.name, 'edwiser-bridge-pro')}
        </h3>
        {item.course_expires_after_days >= 0 && (
          <div className="eb-thank-you__purchased-course-duration">
            <Icons.clock />
            <span>
              {item.course_expires_after_days !== 0
                ? sprintf(
                    /* translators: %d is the number of days */
                    _n(
                      '%d Day',
                      '%d Days',
                      item.course_expires_after_days,
                      'edwiser-bridge-pro'
                    ),
                    item.course_expires_after_days
                  )
                : __('Lifetime', 'edwiser-bridge-pro')}
            </span>
          </div>
        )}
      </div>
      <div className="eb-thank-you__purchased-course-link">
        <a href={item.permalink}>
          <span>{__('View', 'edwiser-bridge-pro')}</span> <Icons.chevronRight />
        </a>
      </div>
    </div>
  );
}

export default PurchasedCourse;

export function PurchasedItemSkeleton() {
  return (
    <div className="eb-thank-you__purchased-course-card">
      <div className="eb-thank-you__purchased-course-image">
        <Skeleton width={'100%'} height={'100%'} />
      </div>
      <div className="eb-thank-you__purchased-course-details">
        <Skeleton width={90} height={16} />
        <Skeleton width={230} height={24} />
        <Skeleton width={60} height={16} />
      </div>
      <div className="eb-thank-you__purchased-course-link">
        <Skeleton width={90} height={16} />
      </div>
    </div>
  );
}

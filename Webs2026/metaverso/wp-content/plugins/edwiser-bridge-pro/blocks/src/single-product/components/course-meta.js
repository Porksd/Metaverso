import { Skeleton } from '@mantine/core';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { decodeHTMLEntities } from '../utils';
import { Icons } from './icons';

function CourseMeta({ showCategory, showRatings, course }) {
  return (
    <div className="eb-product-desc__course-meta">
      {showCategory && (
        <div className="eb-product-desc__course-category">
          <span className="course-category__label">
            {__('Category', 'edwiser-bridge-pro')}
          </span>
          <span className="course-category__value">
            {course?.categories?.length > 0
              ? course?.categories.map((category, index) => (
                  <React.Fragment key={category?.id || index}>
                    {__(
                      decodeHTMLEntities(category?.name),
                      'edwiser-bridge-pro'
                    )}
                    {index < course.categories.length - 1 ? ', ' : ''}
                  </React.Fragment>
                ))
              : __('Uncategorized', 'edwiser-bridge-pro')}
          </span>
        </div>
      )}
      {showRatings && (
        <div className="eb-product-desc__course-review">
          <span className="course-review__label">
            {__('Review', 'edwiser-bridge-pro')}
          </span>
          <span className="course-review__value">
            <span>
              <Icons.star />
            </span>
            <span>
              {__(
                parseFloat(course?.average_rating).toFixed(1),
                'edwiser-bridge-pro'
              )}{' '}
              ({__(course?.review_count, 'edwiser-bridge-pro')})
            </span>
          </span>
        </div>
      )}
    </div>
  );
}

export default CourseMeta;

export function CourseMetaSkeleton() {
  return (
    <div className="eb-product-desc__course-meta">
      <div className="eb-product-desc__course-category">
        <Skeleton width={60} height={21} />
        <Skeleton width={80} height={21} />
      </div>
      <div className="eb-product-desc__course-review">
        <Skeleton width={60} height={21} />
        <Skeleton width={80} height={21} />
      </div>
    </div>
  );
}

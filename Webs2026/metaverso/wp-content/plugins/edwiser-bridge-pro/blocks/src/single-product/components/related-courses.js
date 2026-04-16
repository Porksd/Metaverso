import React from 'react';
import { __ } from '@wordpress/i18n';
import CourseCard, { CourseCardSkeleton } from './course-card';
import { Skeleton } from '@mantine/core';

function RelatedCourses({ courses, viewAllUrl, cartItems }) {
  return (
    <div className="eb-product-desc__related-courses">
      <h2 className="eb-product-desc__related-title">
        {__('Related Products', 'edwiser-bridge-pro')}
      </h2>
      <div className="related-courses">
        {courses?.map((course) => (
          <CourseCard
            key={course.id}
            course={course}
            inCart={cartItems?.includes(course.id)}
          />
        ))}
      </div>
      {viewAllUrl && (
        <a href={viewAllUrl} className="btn__view-all">
          {__('View All', 'edwiser-bridge-pro')}
        </a>
      )}
    </div>
  );
}

export default RelatedCourses;

export function RelatedCoursesSkeleton() {
  return (
    <div className="eb-product-desc__related-courses">
      <Skeleton width={160} height={22} />
      <div className="related-courses">
        {[...Array(4)].map((_, index) => (
          <CourseCardSkeleton key={index} />
        ))}
      </div>
      <Skeleton width={52} height={24} ml={'auto'} mr={'auto'} />
    </div>
  );
}

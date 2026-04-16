import { Skeleton } from '@mantine/core';
import { __ } from '@wordpress/i18n';
import React from 'react';
import Course, { CourseSkeleton } from './course';

function RecommendedCourses({ recommendedCourses }) {
  return (
    <div className="eb-thank-you__recom-course-wrapper">
      <h2 className="eb-thank-you__recom-courses-title">
        {__('You may also like', 'edwiser-bridge-pro')}
      </h2>
      <div className="eb-thank-you__recom-courses">
        {recommendedCourses.map((course) => (
          <Course key={course.id} course={course} />
        ))}
      </div>
    </div>
  );
}

export default RecommendedCourses;

export function RecommendedCoursesSkeleton() {
  return (
    <div className="eb-thank-you__recom-course-wrapper">
      <Skeleton width={200} height={32} />
      <div className="eb-thank-you__recom-courses">
        {[...Array(3)].map((_, index) => (
          <CourseSkeleton key={index} />
        ))}
      </div>
    </div>
  );
}

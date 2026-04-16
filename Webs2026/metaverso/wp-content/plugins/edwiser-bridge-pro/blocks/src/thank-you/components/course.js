import { __ } from '@wordpress/i18n';
import React from 'react';
import { Skeleton } from '@mantine/core';
import coursePlaceholderImg from '../../../../images/placeholder-course-thumbnail.jpeg';

function Course({ course }) {
  return (
    <a
      href={course.link}
      target="_blank"
      className="eb-thank-you__course-anchor"
    >
      <div className="eb-thank-you__course-card">
        <div className="course-thumbnail-container">
          <img
            src={course.thumbnail ?? coursePlaceholderImg}
            alt={course.title}
            className="course-thumbnail"
          />
          <div className="course-category">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <rect width="7" height="9" x="3" y="3" rx="1" />
              <rect width="7" height="5" x="14" y="3" rx="1" />
              <rect width="7" height="9" x="14" y="12" rx="1" />
              <rect width="7" height="5" x="3" y="16" rx="1" />
            </svg>
            <span>{__(course.category)}</span>
          </div>
        </div>
        <div className="course-meta">
          <div className="course-content">
            <h3 className="course-title">
              {__(course.title, 'edwiser-bridge-pro')}
            </h3>
            <p className="course-excerpt">
              {__(course.excerpt, 'edwiser-bridge-pro')}
            </p>
          </div>
          <div className="course-details">
            <div className="course-price">
              {course?.suspended ? (
                <span className="suspended">
                  {__('Suspended', 'edwiser-bridge-pro')}
                </span>
              ) : (
                <CoursePrice price={course.price} />
              )}
            </div>
            <a href={course.link} className="btn">
              {__('View Details', 'edwiser-bridge-pro')}
            </a>
          </div>
        </div>
      </div>
    </a>
  );
}

export default Course;

export function CoursePrice({ price }) {
  if (price?.enrolled) {
    return (
      <span className="enrolled">{__('Enrolled', 'edwiser-bridge-pro')}</span>
    );
  }
  if (price?.type === 'subscription') {
    return (
      <>
        <span className="price">
          {__('₹' + price?.amount, 'edwiser-bridge-pro')}
        </span>
        <span className="recurring">{__('/month', 'edwiser-bridge-pro')}</span>
      </>
    );
  }
  if (price?.type === 'closed') {
    return (
      <>
        <span></span>
      </>
    );
  }
  if (price?.amount === 0) {
    return <span className="price">{__('Free', 'edwiser-bridge-pro')}</span>;
  }
  if (price?.originalAmount !== null) {
    return (
      <>
        <span className="price">
          {__(price?.currency + price?.amount, 'edwiser-bridge-pro')}
        </span>
        <span className="original-price">
          {__(price?.currency + price?.originalAmount, 'edwiser-bridge-pro')}
        </span>
      </>
    );
  }

  return (
    <span className="price">
      {__(price?.currency + price?.amount, 'edwiser-bridge-pro')}
    </span>
  );
}

export function CourseSkeleton() {
  return (
    <div className="eb-thank-you__course-card">
      <div className="course-thumbnail-container">
        <Skeleton height={140} style={{ borderRadius: 0 }} />
      </div>
      <div className="course-meta">
        <div className="course-content">
          <Skeleton height={20} width="90%" />
          <div>
            <Skeleton height={12} />
            <Skeleton height={12} mt={6} width="70%" />
          </div>
        </div>
        <div className="course-details">
          <div className="course-price">
            <Skeleton height={28} width={80} />
          </div>
          <Skeleton height={18} width={80} />
        </div>
      </div>
    </div>
  );
}

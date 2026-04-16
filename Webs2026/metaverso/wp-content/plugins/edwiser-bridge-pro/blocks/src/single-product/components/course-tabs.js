import { Skeleton, Tabs } from '@mantine/core';
import { __ } from '@wordpress/i18n';
import React from 'react';
import CourseRating from './course-rating';
import CourseReviewForm from './course-review-form';
import CourseReviews from './course-reviews';

function CourseTabs({ course, courseReviews }) {
  return (
    <Tabs defaultValue={course?.description ? 'overview' : 'reviews'}>
      <Tabs.List>
        {course?.description && (
          <Tabs.Tab value="overview">
            {__('Overview', 'edwiser-bridge-pro')}
          </Tabs.Tab>
        )}
        {course?.reviews_enabled && (
          <Tabs.Tab value="reviews">
            {__('Reviews', 'edwiser-bridge-pro')} ({course?.review_count})
          </Tabs.Tab>
        )}
      </Tabs.List>
      {course?.description && (
        <Tabs.Panel value="overview" className="eb-product-desc__overview-tab">
          <h2 className="eb-product-desc__overview-tab-title">
            {__('Overview', 'edwiser-bridge-pro')}
          </h2>
          <div
            className="eb-product-desc__overview-tab-content"
            dangerouslySetInnerHTML={{
              __html: course?.description,
            }}
          ></div>
        </Tabs.Panel>
      )}
      {course?.reviews_enabled && (
        <Tabs.Panel value="reviews" className="eb-product-desc__reviews-tab">
          {!courseReviews || courseReviews?.length === 0 ? (
            <div className="eb-product-desc__reviews-empty">
              {__('There are no reviews yet.', 'edwiser-bridge-pro')}
            </div>
          ) : (
            <>
              {course?.star_ratings_enabled && (
                <CourseRating reviews={courseReviews} />
              )}
              <CourseReviews
                reviews={courseReviews}
                ratingsEnabled={course?.star_ratings_enabled}
                verifiedLabelEnabled={course?.verified_owner_labels_enabled}
              />
            </>
          )}
          <CourseReviewForm
            isLoggedIn={!!window.ebCurrentUser?.ID}
            currentUser={window.ebCurrentUser || null}
            productId={course?.id}
            reviewCount={course?.review_count}
            productTitle={course?.name}
            ratingsEnabled={course?.star_ratings_enabled}
            ratingsOptional={course?.ratings_optional}
          />
        </Tabs.Panel>
      )}
    </Tabs>
  );
}

export default CourseTabs;

export function CourseTabsSkeleton() {
  return (
    <>
      <Skeleton width={100} height={22} />
      <Skeleton width={'100%'} height={22} mt={24} />
      <Skeleton width={'100%'} height={22} mt={6} />
      <Skeleton width={'50%'} height={22} mt={6} />
    </>
  );
}

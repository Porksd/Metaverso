import { MantineProvider, Skeleton } from '@mantine/core';
import React from 'react';
import { __ } from '@wordpress/i18n';
import defaultThankYouImg from '../../../images/thank-you.png';
import { Icons } from './components/icons';
import PurchasedItem, {
  PurchasedItemSkeleton,
} from './components/purchased-item';
import RecommendedCourses, {
  RecommendedCoursesSkeleton,
} from './components/recommended-courses';
import { useThankYou } from './hooks/use-thankyou';

function ThankYou(attributes) {
  const urlParams = new URLSearchParams(window.location.search);
  const orderKey = urlParams.get('key');

  const {
    orderItems,
    recommendedCourses,
    coursePageUrl,
    myCoursePageUrl,
    redirectEnabled,
    isLoading,
    remainingTime,
    isRedirectPaused,
    setIsRedirectPaused,
  } = useThankYou({ orderKey });

  const thankYouImage = attributes.thankYouImageUrl || defaultThankYouImg;
  const imageAlt = attributes.thankYouImageAlt;

  const dummyCourses = [
    {
      id: 301,
      title: 'Test Course 1',
      link: 'http://localhost/courses/lorem-ipsum-basics/',
      excerpt: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
      category: 'Dummy Category',
      createdAt: '2025-05-01 10:00:00',
      suspended: false,
      price: {
        amount: 0,
        currency: '$',
        type: 'free',
        enrolled: true,
        originalAmount: null,
      },
    },
    {
      id: 302,
      title: 'Test Course 2',
      link: 'http://localhost/courses/dolor-sit-amet/',
      excerpt:
        'Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
      category: 'Test Category',
      createdAt: '2025-05-02 14:30:00',
      suspended: false,
      price: {
        amount: 49,
        currency: '$',
        type: 'paid',
        enrolled: false,
        originalAmount: null,
      },
    },
    {
      id: 303,
      title: 'Test Course 3',
      link: 'http://localhost/courses/consectetur-adipiscing/',
      excerpt: 'Ut enim ad minim veniam, quis nostrud exercitation ullamco.',
      category: 'Sample Category',
      createdAt: '2025-05-03 09:15:00',
      suspended: false,
      price: {
        amount: 120,
        currency: '$',
        type: 'paid',
        enrolled: false,
        originalAmount: null,
      },
    },
  ];

  return (
    <MantineProvider>
      <div className="eb-thank-you">
        <div className="eb-thank-you__wrapper">
          {/* <div className="eb-thank-you__back-link">
            {isLoading ? (
              <Skeleton width={120} height={24} />
            ) : (
              orderItems.length > 0 && (
                <a href={coursePageUrl}>
                  <Icons.chevronLeft />{' '}
                  <span>{__('Back to courses', 'edwiser-bridge-pro')}</span>
                </a>
              )
            )}
          </div> */}
          <div
            className={`eb-thank-you__content ${
              orderItems.length <= 0 ? 'eb-thank-you__content--empty' : ''
            }`}
          >
            {attributes.showThankYouImage && (
              <div className="eb-thank-you__image-container">
                {isLoading ? (
                  <Skeleton width={'100%'} height={'100%'} />
                ) : (
                  <img src={thankYouImage} alt={imageAlt} />
                )}
              </div>
            )}
            <div className="eb-thank-you__info">
              {isLoading ? (
                <>
                  <Skeleton width={400} height={32} />
                  <Skeleton width={320} height={24} mt={6} />
                </>
              ) : (
                <>
                  <h1 className="eb-thank-you__title">
                    {__('Thank you for your purchase', 'edwiser-bridge-pro')}
                  </h1>
                  <p className="eb-thank-you__subtitle">
                    {__(
                      'Explore the purchased courses and start learning',
                      'edwiser-bridge-pro'
                    )}
                  </p>
                </>
              )}
              {isLoading ? (
                <Skeleton width={550} height={24} mt={24} />
              ) : (
                redirectEnabled && (
                  <div className="eb-thank-you__redirect-message">
                    <p>
                      {__('You will be redirected to', 'edwiser-bridge-pro')}{' '}
                      <a href={myCoursePageUrl}>
                        {__('My course page', 'edwiser-bridge-pro')}
                      </a>{' '}
                      {__('within next', 'edwiser-bridge-pro')}{' '}
                      <span className="eb-thank-you__seconds">
                        {remainingTime} {__('seconds', 'edwiser-bridge-pro')}
                      </span>
                    </p>
                    <button
                      onClick={() => setIsRedirectPaused(!isRedirectPaused)}
                      className="eb-thank-you__cancel-button"
                    >
                      {isRedirectPaused ? (
                        <span>{__('Resume', 'edwiser-bridge-pro')}</span>
                      ) : (
                        <>
                          <span>{__('Cancel', 'edwiser-bridge-pro')}</span>{' '}
                          <Icons.cross />
                        </>
                      )}
                    </button>
                  </div>
                )
              )}
              <div className="eb-thank-you__purchased-courses">
                {
                  isLoading
                    ? [...Array(1)].map((_, index) => (
                        <PurchasedItemSkeleton key={index} />
                      ))
                    : orderItems.length > 0 &&
                      orderItems.map((item) => (
                        <PurchasedItem key={item.id} item={item} />
                      ))
                  // : (
                  //   <a
                  //     href={coursePageUrl}
                  //     className="eb-thank-you__explore-courses"
                  //   >
                  //     <span>{__('Explore courses', 'edwiser-bridge-pro')}</span>
                  //   </a>
                  // )
                }
              </div>
            </div>
          </div>
        </div>
        {attributes.showYouMayLikeCourses &&
        !attributes.isEditor &&
        isLoading ? (
          <RecommendedCoursesSkeleton />
        ) : (
          attributes.showYouMayLikeCourses &&
          recommendedCourses.length > 0 && (
            <RecommendedCourses recommendedCourses={recommendedCourses} />
          )
        )}

        {attributes.showYouMayLikeCourses &&
        attributes.isEditor &&
        isLoading ? (
          <RecommendedCoursesSkeleton />
        ) : (
          attributes.showYouMayLikeCourses &&
          attributes.isEditor && (
            <RecommendedCourses recommendedCourses={dummyCourses} />
          )
        )}
      </div>
    </MantineProvider>
  );
}

export default ThankYou;

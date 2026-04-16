import React, { useMemo } from 'react';
import { __ } from '@wordpress/i18n';

function CourseRating({ reviews }) {
  const ratingStats = useMemo(() => {
    const ratingCounts = { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 };

    let totalReviews = 0;

    // count reviews by rating
    reviews.forEach((review) => {
      if (review.rating) {
        const rating = Math.round(review.rating);
        if (rating >= 1 && rating <= 5) {
          ratingCounts[rating]++;
        }
        totalReviews++;
      }
    });

    // average rating
    const sumRatings = reviews.reduce((sum, review) => sum + review.rating, 0);
    const averageRating =
      totalReviews > 0 ? (sumRatings / totalReviews).toFixed(1) : 0;

    // percentages
    const percentages = {};
    Object.keys(ratingCounts).forEach((rating) => {
      percentages[rating] =
        totalReviews > 0
          ? Math.round((ratingCounts[rating] / totalReviews) * 100)
          : 0;
    });

    return {
      averageRating,
      totalReviews,
      ratingCounts,
      percentages,
    };
  }, [reviews]);

  const StarIcon = ({ fillPercentage = 0 }) => {
    // Create a unique ID for the gradient
    const gradientId = `partialGradient-${Math.random()
      .toString(36)
      .substr(2, 9)}`;

    return (
      <svg
        xmlns="http://www.w3.org/2000/svg"
        width="18"
        height="18"
        viewBox="0 0 24 24"
        fill={fillPercentage >= 100 ? '#F98012' : `url(#${gradientId})`}
        className="eb-product-desc__star-svg"
      >
        <defs>
          <linearGradient id={gradientId} x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset={`${fillPercentage}%`} stopColor="#F98012" />
            <stop offset={`${fillPercentage}%`} stopColor="#D9E7E8" />
          </linearGradient>
        </defs>
        <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" />
      </svg>
    );
  };

  const renderStars = (rating, maxRating = 5) => {
    const fullStars = Math.floor(rating);
    const partialValue = rating % 1;
    const partialPercentage = Math.round(partialValue * 100);

    return (
      <>
        {/* Full stars */}
        {[...Array(fullStars)].map((_, i) => (
          <StarIcon key={`full-${i}`} fillPercentage={100} />
        ))}

        {/* Partial star */}
        {partialValue > 0 && fullStars < maxRating && (
          <StarIcon key="partial" fillPercentage={partialPercentage} />
        )}

        {/* Empty stars */}
        {[...Array(maxRating - fullStars - (partialValue > 0 ? 1 : 0))].map(
          (_, i) => (
            <StarIcon key={`empty-${i}`} fillPercentage={0} />
          )
        )}
      </>
    );
  };

  const formatPercentage = (percentage) => {
    return percentage < 10 ? `${percentage}%` : `${percentage}%`;
  };

  return (
    <>
      <div className="eb-product-desc__course-rating">
        <div className="eb-product-desc__course-rating-summary">
          <div className="eb-product-desc__course-rating-score">
            {ratingStats.averageRating}
          </div>
          <div className="eb-product-desc__course-rating-label">
            {__('Course rating', 'edwiser-bridge-pro')}
          </div>
          <div className="eb-product-desc__course-rating-stars">
            {renderStars(parseFloat(ratingStats.averageRating))}
          </div>
          <div className="eb-product-desc__course-rating-reviews-count">
            {ratingStats.averageRating} ({reviews?.length}{' '}
            {__('Reviews', 'edwiser-bridge-pro')})
          </div>
        </div>

        <div className="eb-product-desc__course-rating-breakdown">
          {/* 5 Stars Row */}
          <div className="eb-product-desc__course-rating-breakdown-item">
            <div className="eb-product-desc__course-rating-bar-container">
              <div
                className="eb-product-desc__course-rating-bar"
                style={{ width: `${ratingStats.percentages[5]}%` }}
              />
            </div>
            <div
              style={{
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                flexShrink: 0,
              }}
            >
              <div className="eb-product-desc__course-rating-breakdown-stars">
                {renderStars(5)}
              </div>
              <div className="eb-product-desc__course-rating-breakdown-percentage">
                {formatPercentage(ratingStats.percentages[5])}
              </div>
            </div>
          </div>

          {/* 4 Stars Row */}
          <div className="eb-product-desc__course-rating-breakdown-item">
            <div className="eb-product-desc__course-rating-bar-container">
              <div
                className="eb-product-desc__course-rating-bar"
                style={{ width: `${ratingStats.percentages[4]}%` }}
              />
            </div>
            <div
              style={{
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                flexShrink: 0,
              }}
            >
              <div className="eb-product-desc__course-rating-breakdown-stars">
                {renderStars(4)}
              </div>
              <div className="eb-product-desc__course-rating-breakdown-percentage">
                {formatPercentage(ratingStats.percentages[4])}
              </div>
            </div>
          </div>

          {/* 3 Stars Row */}
          <div className="eb-product-desc__course-rating-breakdown-item">
            <div className="eb-product-desc__course-rating-bar-container">
              <div
                className="eb-product-desc__course-rating-bar"
                style={{ width: `${ratingStats.percentages[3]}%` }}
              />
            </div>
            <div
              style={{
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                flexShrink: 0,
              }}
            >
              <div className="eb-product-desc__course-rating-breakdown-stars">
                {renderStars(3)}
              </div>
              <div className="eb-product-desc__course-rating-breakdown-percentage">
                {formatPercentage(ratingStats.percentages[3])}
              </div>
            </div>
          </div>

          {/* 2 Stars Row */}
          <div className="eb-product-desc__course-rating-breakdown-item">
            <div className="eb-product-desc__course-rating-bar-container">
              <div
                className="eb-product-desc__course-rating-bar"
                style={{ width: `${ratingStats.percentages[2]}%` }}
              />
            </div>
            <div
              style={{
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                flexShrink: 0,
              }}
            >
              <div className="eb-product-desc__course-rating-breakdown-stars">
                {renderStars(2)}
              </div>
              <div className="eb-product-desc__course-rating-breakdown-percentage">
                {formatPercentage(ratingStats.percentages[2])}
              </div>
            </div>
          </div>

          {/* 1 Star Row */}
          <div className="eb-product-desc__course-rating-breakdown-item">
            <div className="eb-product-desc__course-rating-bar-container">
              <div
                className="eb-product-desc__course-rating-bar"
                style={{ width: `${ratingStats.percentages[1]}%` }}
              />
            </div>
            <div
              style={{
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                flexShrink: 0,
              }}
            >
              <div className="eb-product-desc__course-rating-breakdown-stars">
                {renderStars(1)}
              </div>
              <div className="eb-product-desc__course-rating-breakdown-percentage">
                {formatPercentage(ratingStats.percentages[1])}
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}

export default CourseRating;

import React from 'react';
import { Icons } from './icons';
import { __ } from '@wordpress/i18n';

const CourseReviews = ({ reviews, ratingsEnabled, verifiedLabelEnabled }) => {
  return (
    <div className="eb-product-desc__reviews">
      <ul className="eb-product-desc__reviews-list">
        {reviews.map((review) => (
          <Review
            key={review.id}
            review={review}
            ratingsEnabled={ratingsEnabled}
            verifiedLabelEnabled={verifiedLabelEnabled}
          />
        ))}
      </ul>
    </div>
  );
};

export default CourseReviews;

const Review = ({ review, ratingsEnabled, verifiedLabelEnabled }) => {
  function formatDate(dateString) {
    const date = new Date(dateString);

    const options = {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    };
    let formattedDate = date.toLocaleDateString('en-GB', options);

    const day = date.getDate();
    const suffix =
      day % 10 === 1 && day !== 11
        ? 'st'
        : day % 10 === 2 && day !== 12
        ? 'nd'
        : day % 10 === 3 && day !== 13
        ? 'rd'
        : 'th';

    formattedDate = formattedDate.replace(/\d+/, day + suffix);

    const formattedTime = date.toLocaleTimeString('en-US', {
      hour: '2-digit',
      minute: '2-digit',
      hour12: true,
    });

    return `${formattedDate}, ${formattedTime}`;
  }

  return (
    <li className="eb-product-desc__review-item">
      <div className="eb-product-desc__review-header">
        <div className="eb-product-desc__review-avatar">
          <img src={review.reviewer_avatar_urls[48]} alt={review.reviewer} />
        </div>
        <div className="eb-product-desc__review-meta">
          <div className="eb-product-desc__review-author">
            <span className="eb-product-desc__review-author-name">
              {review.reviewer}
            </span>
            {verifiedLabelEnabled && review.verified && (
              <span className="eb-product-desc__review-author-verified">
                <Icons.checkBadge />
                {__('Verified Owner', 'edwiser-bridge-pro')}
              </span>
            )}
          </div>
          <div className="eb-product-desc__review-date-rating">
            <span
              className="eb-product-desc__review-date"
              datetime="2022-05-23T17:46:00"
            >
              {formatDate(review.date_created)}
            </span>
            {ratingsEnabled && review.rating && (
              <div className="eb-product-desc__review-rating">
                <Icons.star />
                <span>{review.rating}.0</span>
              </div>
            )}
          </div>
        </div>
      </div>
      <div
        className="eb-product-desc__review-content"
        dangerouslySetInnerHTML={{ __html: review.review }}
      ></div>
    </li>
  );
};

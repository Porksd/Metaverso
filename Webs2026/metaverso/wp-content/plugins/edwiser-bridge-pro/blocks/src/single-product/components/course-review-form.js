import { Rating, Textarea, TextInput } from '@mantine/core';
import apiFetch from '@wordpress/api-fetch';
import React, { useEffect, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { decodeHTMLEntities } from '../utils';

function CourseReviewForm({
  isLoggedIn,
  productId,
  currentUser,
  reviewCount,
  productTitle,
  ratingsEnabled,
  ratingsOptional,
}) {
  const [rating, setRating] = useState(0);
  const [review, setReview] = useState('');
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitMessage, setSubmitMessage] = useState(null);

  useEffect(() => {
    if (isLoggedIn && currentUser) {
      setName(currentUser.display_name || '');
      setEmail(currentUser.user_email || '');
    } else {
      setName(localStorage.getItem('course_reviewer_name') || '');
      setEmail(localStorage.getItem('course_reviewer_email') || '');
    }
  }, [isLoggedIn, currentUser]);

  const handleSubmit = async (e) => {
    e.preventDefault();

    // Only validate rating if ratings are enabled and required
    if (ratingsEnabled && !ratingsOptional && rating === 0) {
      setSubmitMessage({ type: 'error', text: 'Please add a rating' });
      return;
    }

    setIsSubmitting(true);
    setSubmitMessage(null);

    if (!isLoggedIn) {
      localStorage.setItem('course_reviewer_name', name);
      localStorage.setItem('course_reviewer_email', email);
    }

    const reviewData = {
      product_id: productId,
      review,
    };

    // Only include rating in the request if ratings are enabled
    if (ratingsEnabled && rating > 0) {
      reviewData.rating = rating;
    }

    // Add reviewer info for guest users
    if (!isLoggedIn) {
      reviewData.reviewer = name;
      reviewData.reviewer_email = email;
    }

    try {
      const data = await apiFetch({
        path: '/eb/api/v1/shop/submit-review',
        method: 'POST',
        credentials: 'same-origin',
        data: reviewData,
      });

      setSubmitMessage({
        type: 'success',
        text: data.message || 'Your review was submitted successfully!',
      });

      setRating(0);
      setReview('');
    } catch (error) {
      setSubmitMessage({
        type: 'error',
        text:
          error.message ||
          'An error occurred while submitting your review. Please try again.',
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <form className="eb-product-desc__review-form" onSubmit={handleSubmit}>
      {reviewCount > 0 ? (
        <h3 className="eb-product-desc__review-title">
          {__('Add a review', 'edwiser-bridge-pro')}
        </h3>
      ) : (
        <h3 className="eb-product-desc__review-title">
          {__('Be the first to review', 'edwiser-bridge-pro')} “
          {decodeHTMLEntities(productTitle)}”
        </h3>
      )}

      {submitMessage && (
        <div
          className={`eb-product-desc__message eb-product-desc__message--${submitMessage.type}`}
        >
          {__(submitMessage.text, 'edwiser-bridge-pro')}
        </div>
      )}

      {ratingsEnabled && (
        <div className="eb-product-desc__rating">
          <label className="eb-product-desc__label">
            {__('Add your ratings', 'edwiser-bridge-pro')}{' '}
            {!ratingsOptional && <span style={{ color: '#fa5252' }}>*</span>}
          </label>
          <Rating value={rating} onChange={setRating} />
        </div>
      )}

      <Textarea
        label={__('Add your review', 'edwiser-bridge-pro')}
        value={review}
        onChange={(e) => setReview(e.target.value)}
        required
        className="eb-product-desc__textarea"
      />

      {!isLoggedIn && (
        <div className="eb-product-desc__user-fields">
          <TextInput
            label={__('Name', 'edwiser-bridge-pro')}
            value={name}
            onChange={(e) => setName(e.target.value)}
            required
            className="eb-product-desc__input"
          />
          <TextInput
            label={__('Email', 'edwiser-bridge-pro')}
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            required
            className="eb-product-desc__input"
          />
        </div>
      )}
      <button className="eb-product-desc__submit" disabled={isSubmitting}>
        {isSubmitting
          ? __('Submitting...', 'edwiser-bridge-pro')
          : __('Submit', 'edwiser-bridge-pro')}
      </button>
    </form>
  );
}

export default CourseReviewForm;

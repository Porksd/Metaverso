import { Skeleton } from '@mantine/core';
import { __, sprintf } from '@wordpress/i18n';
import React, { useState } from 'react';
import coursePlaceholderImg from '../../../../images/placeholder-course-thumbnail.jpeg';
import { decodeHTMLEntities } from '../utils';
import { Icons } from './icons';
import SuccessMessage from './success-message';

function CourseCard({ course, inCart }) {
  const [addedToCart, setAddedToCart] = useState(false);
  const [showCartSuccess, setShowCartSuccess] = useState(false);

  const handleAddToCart = async (e) => {
    e.preventDefault();
    if (!course.is_purchasable) return;

    // if already in cart, redirect to cart page
    if (inCart || addedToCart) {
      window.location.href = course?.cart_url;
      return;
    }

    const response = await fetch(course.add_to_cart.url);

    if (response.ok) {
      setAddedToCart(true);
      setShowCartSuccess(true);

      // if (typeof jQuery !== 'undefined' && jQuery(document.body).trigger) {
      //   jQuery(document.body).trigger('added_to_cart');
      // }
    }
  };

  return (
    <>
      <div className="eb-product-desc__course-card">
        <div className="eb-product-desc__course-image">
          <img
            src={course.image?.src ?? coursePlaceholderImg}
            alt={course.image?.alt}
          />
          {course?.is_enrolled && (
            <div className="eb-product-desc__enrolled-badge">
              {__('Already enrolled', 'edwiser-bridge-pro')}
            </div>
          )}
          {course.on_sale && (
            <div className="eb-product-desc__sale-badge">
              {__('Sale', 'edwiser-bridge-pro')}
            </div>
          )}
        </div>
        <div className="eb-product-desc__course-info">
          <div className="eb-product-desc__info-header">
            {course.categories.length > 0 && (
              <div className="eb-product-desc__course-category">
                {course?.categories.map((category, index) => (
                  <React.Fragment key={category?.id || index}>
                    {__(
                      decodeHTMLEntities(category?.name),
                      'edwiser-bridge-pro'
                    )}
                    {index < course.categories.length - 1 ? ', ' : ''}
                  </React.Fragment>
                ))}
              </div>
            )}
            <a
              href={course.permalink}
              className="eb-product-desc__course-title"
            >
              {__(course.name, 'edwiser-bridge-pro')}
            </a>
            <p
              className="eb-product-desc__course-desc"
              dangerouslySetInnerHTML={{ __html: course.description }}
            ></p>
          </div>
          <div className="eb-product-desc__info-footer">
            <div className="eb-product-desc__course-ratings-enrollments">
              <div className="eb-product-desc__course-ratings">
                <span>
                  <Icons.star />
                </span>
                {parseFloat(course.average_rating).toFixed(1)} (
                {course.review_count})
              </div>
              <div className="eb-product-desc__course-enrollments">
                <span>
                  <Icons.users />
                </span>
                {/* translators: %d = number of enrolled students */}
                {sprintf(
                  __('%d Enrolled', 'edwiser-bridge-pro'),
                  course?.meta?.enrolled_students || 0
                )}
              </div>
            </div>
            <div className="eb-product-desc__course-price-actions">
              <div
                className="eb-product-desc__course-price"
                dangerouslySetInnerHTML={{ __html: course.price_html }}
              ></div>
              <div className="eb-product-desc__course-actions">
                {course.is_purchasable &&
                  course.type !== 'variable' &&
                  course.type !== 'subscription' &&
                  course.type !== 'variable-subscription' && (
                    <button
                      onClick={handleAddToCart}
                      className="btn__add-to-cart"
                    >
                      <span className="sr-only">{course.add_to_cart.text}</span>
                      <Icons.cart />
                      {(addedToCart || inCart) && (
                        <>
                          <Icons.check />
                          <span>{__('Show', 'edwiser-bridge-pro')}</span>
                        </>
                      )}
                    </button>
                  )}
                {course.is_purchasable && course.type === 'simple' && (
                  <a href={`${course?.buy.url}`} className="btn__buy">
                    <span>{__('Buy', 'edwiser-bridge-pro')}</span>
                  </a>
                )}
                <a href={course.permalink} className="btn__view">
                  <span>
                    {course.type === 'variable'
                      ? __('Select options', 'edwiser-bridge-pro')
                      : course.type === 'subscription'
                      ? __('Sign up now', 'edwiser-bridge-pro')
                      : course.type === 'variable-subscription'
                      ? __('Select options', 'edwiser-bridge-pro')
                      : !course.is_purchasable
                      ? __('Read more', 'edwiser-bridge-pro')
                      : __('View', 'edwiser-bridge-pro')}
                  </span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Add to Cart Success Message */}
      <SuccessMessage
        message={__('Product added to the cart', 'edwiser-bridge-pro')}
        showSuccess={showCartSuccess}
        setShowSuccess={setShowCartSuccess}
        duration={4000}
      />
    </>
  );
}

export default CourseCard;

export function CourseCardSkeleton() {
  return (
    <div className="eb-product-desc__course-card">
      <div className="eb-product-desc__course-image">
        <Skeleton width={'100%'} height={'100%'} />
      </div>
      <div className="eb-product-desc__course-info">
        <div className="eb-product-desc__info-header">
          <Skeleton width={220} height={22} />
          <div style={{ marginTop: '14px' }}>
            <Skeleton width={'100%'} height={22} />
            <Skeleton width={'100%'} height={22} mt={4} />
          </div>
        </div>
        <div className="eb-product-desc__info-footer">
          <div className="eb-product-desc__course-ratings-enrollments">
            <div className="eb-product-desc__course-ratings">
              <Skeleton width={60} height={20} />
            </div>
            <div className="eb-product-desc__course-enrollments">
              <Skeleton width={90} height={20} />
            </div>
          </div>
          <div className="eb-product-desc__course-price-actions">
            <Skeleton width={56} height={24} />
            <div className="eb-product-desc__course-actions">
              <Skeleton width={32} height={32} />
              <Skeleton width={60} height={32} />
              <Skeleton width={60} height={32} />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

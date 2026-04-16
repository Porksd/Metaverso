import { Checkbox, MantineProvider, Skeleton } from '@mantine/core';
import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import AssociatedCourses, {
  AssociatedCoursesSkeleton,
} from './components/associated-courses';
import CourseActions, {
  CourseActionsSkeleton,
} from './components/course-actions';
import CourseDetails, {
  CourseDetailsSkeleton,
} from './components/course-details';
import CourseGallery, {
  CourseGallerySkeleton,
} from './components/course-gallery';
import CourseMeta, { CourseMetaSkeleton } from './components/course-meta';
import CourseQuantity, {
  CourseQuantitySkeleton,
} from './components/course-quantity';
import CourseTabs, { CourseTabsSkeleton } from './components/course-tabs';
import CourseVariations from './components/course-variations';
import RelatedCourses, {
  RelatedCoursesSkeleton,
} from './components/related-courses';
import { useProduct } from './hooks/use-product';
import { decodeHTMLEntities } from './utils';
import ExternalProduct from './components/external-product';
import GroupedProduct from './components/grouped-product';
import EmptyState from './components/empty-state';

function Product(attributes) {
  const [quantity, setQuantity] = useState(1);
  const [enableGroupPurchase, setEnableGroupPurchase] = useState(false);
  const [variation, setVariation] = useState(null);
  const productId = attributes.productId; // 280, 221, 397, 279

  const {
    course,
    courseReviews,
    isLoading,
    associatedCourses,
    setAssociatedCourses,
    variations,
    attributeOptions,
    attributeNames,
    cartItems,
    inCart,
  } = useProduct(productId);

  const decreaseQuantity = () => {
    setQuantity((prev) => (prev > 1 ? prev - 1 : 1));
  };

  const increaseQuantity = () => {
    setQuantity((prev) => (prev < 9999 ? prev + 1 : 9999));
  };

  let galleryImages =
    course?.images && course.images.length > 0 ? [...course.images] : [];
  if (variation && variation.image) {
    galleryImages[0] = variation.image;
  }

  if (!isLoading && !course) {
    return (
      <div className="eb-product-desc">
        <div className="eb-product-desc--wrapper">
          <EmptyState />
        </div>
      </div>
    );
  }

  return (
    <MantineProvider>
      <div className="eb-product-desc">
        <div className="eb-product-desc--wrapper">
          {isLoading ? (
            <Skeleton width={350} height={20} />
          ) : (
            <nav className="eb-product-desc__breadcrumb">
              <a
                href={course?.shop_page_url}
                className="eb-product-desc__breadcrumb-link"
              >
                {__('Shop', 'edwiser-bridge-pro')}
              </a>
              /
              {course?.categories?.length > 0 ? (
                course?.categories?.map((category) => (
                  <>
                    <a
                      key={category?.id}
                      href={`${course.shop_page_url}?category=${category?.id}`}
                      className="eb-product-desc__breadcrumb-link"
                    >
                      {__(
                        decodeHTMLEntities(category?.name || 'Uncategorized'),
                        'edwiser-bridge-pro'
                      )}
                    </a>
                    /
                  </>
                ))
              ) : (
                <>
                  <a
                    href={`${course.shop_page_url}?category=${course?.uncategorized_id}`}
                    className="eb-product-desc__breadcrumb-link"
                  >
                    {__('Uncategorized', 'edwiser-bridge-pro')}
                  </a>
                  /
                </>
              )}
              <span className="eb-product-desc__breadcrumb-current">
                {__(decodeHTMLEntities(course?.name), 'edwiser-bridge-pro')}
              </span>
            </nav>
          )}
          <div className="eb-product-desc__course">
            <div className="eb-product-desc__overview">
              {isLoading ? (
                <Skeleton width={400} height={40} />
              ) : (
                <h1 className="eb-product-desc__course-title">
                  {__(decodeHTMLEntities(course?.name), 'edwiser-bridge-pro')}
                </h1>
              )}
              {isLoading ? (
                <CourseMetaSkeleton />
              ) : (
                <CourseMeta
                  course={course}
                  showCategory={attributes.showCategory}
                  showRatings={attributes.showRatings}
                />
              )}
              {isLoading ? (
                <CourseGallerySkeleton />
              ) : (
                <CourseGallery
                  images={galleryImages}
                  courseName={course?.name}
                />
              )}
            </div>
            <aside className="eb-product-desc__sidebar">
              {course?.is_purchasable &&
                (isLoading ? (
                  <Skeleton width={120} height={42} />
                ) : (
                  <div className="eb-product-desc__course-pricing">
                    {course?.on_sale && (
                      <div className="eb-product-desc__sale-badge">
                        {__('Sale', 'edwiser-bridge-pro')}
                      </div>
                    )}
                    <div
                      className={`eb-product-desc__price ${course.type} ${
                        course.on_sale ? 'sale' : ''
                      }`}
                      dangerouslySetInnerHTML={{ __html: course?.price_html }}
                    ></div>
                  </div>
                ))}
              {course?.type === 'external' && (
                <ExternalProduct
                  onSale={course?.on_sale}
                  courseType={course?.type}
                  price={course?.price_html}
                  addToCart={course?.add_to_cart}
                />
              )}
              {course?.type === 'grouped' && (
                <GroupedProduct
                  onSale={course?.on_sale}
                  courseType={course?.type}
                  price={course?.price_html}
                  products={course?.grouped_products}
                />
              )}
              {course?.short_description && (
                <div
                  className="eb-product-desc__course-desc"
                  dangerouslySetInnerHTML={{
                    __html: course?.short_description,
                  }}
                ></div>
              )}
              {(course?.type === 'variable' ||
                course?.type === 'variable-subscription') && (
                <CourseVariations
                  variations={variations}
                  attributeOptions={attributeOptions}
                  attributeNames={attributeNames}
                  courseType={course?.type}
                  setAssociatedCourses={setAssociatedCourses}
                  enableGroupPurchase={enableGroupPurchase}
                  setEnableGroupPurchase={setEnableGroupPurchase}
                  setQuantity={setQuantity}
                  setVariation={setVariation}
                />
              )}
              {course?.type === 'simple' && course?.group_purchase_enabled && (
                <Checkbox
                  label={course?.group_purchase_label}
                  className="eb-product-desc__enable-group-purchase"
                  checked={enableGroupPurchase}
                  onChange={(e) => {
                    setEnableGroupPurchase(e.currentTarget.checked);
                    setQuantity(1);
                  }}
                />
              )}
              {isLoading ? (
                <CourseQuantitySkeleton />
              ) : (
                course?.is_purchasable && (
                  <CourseQuantity
                    increaseQuantity={increaseQuantity}
                    decreaseQuantity={decreaseQuantity}
                    quantity={quantity}
                    setQuantity={setQuantity}
                    course={course}
                    enableGroupPurchase={enableGroupPurchase}
                  />
                )
              )}
              {isLoading ? (
                <CourseActionsSkeleton />
              ) : (
                course?.is_purchasable && (
                  <CourseActions
                    quantity={quantity}
                    course={course}
                    productId={productId}
                    inCart={inCart}
                    variation={variation}
                    enableGroupPurchase={enableGroupPurchase}
                  />
                )
              )}
              {isLoading ? (
                <CourseDetailsSkeleton />
              ) : (
                (course?.type !== 'variable' ||
                  course?.type !== 'variable-subscription') &&
                course?.show_course_details &&
                associatedCourses?.length < 2 && (
                  <CourseDetails
                    createdAt={course?.date_created}
                    courseAccess={course?.course_expires_after_days}
                    enrolledCount={course?.enrolled_students}
                    attributes={attributes}
                  />
                )
              )}
              {isLoading ? (
                <AssociatedCoursesSkeleton />
              ) : (
                attributes.showAssociatedCourses &&
                associatedCourses?.length > 0 && (
                  <AssociatedCourses associatedCourses={associatedCourses} />
                )
              )}
            </aside>
            {(course?.description || course?.reviews_enabled) && (
              <div className="eb-product-desc__tabs">
                {isLoading ? (
                  <CourseTabsSkeleton />
                ) : (
                  <CourseTabs course={course} courseReviews={courseReviews} />
                )}
              </div>
            )}
          </div>
          {attributes.showRelatedCourses &&
            (isLoading ? (
              <RelatedCoursesSkeleton />
            ) : (
              course?.related_courses?.length > 0 && (
                <RelatedCourses
                  courses={course?.related_courses}
                  viewAllUrl={
                    course?.categories?.length > 0
                      ? `${course?.shop_page_url}?category=${course?.categories?.[0]?.id}`
                      : `${course.shop_page_url}?category=${course?.uncategorized_id}`
                  }
                  cartItems={cartItems}
                />
              )
            ))}
        </div>
      </div>
    </MantineProvider>
  );
}

export default Product;

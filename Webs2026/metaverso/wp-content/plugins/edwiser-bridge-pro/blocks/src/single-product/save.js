import { useBlockProps } from '@wordpress/block-editor';

export default function save({ attributes }) {
  return (
    <div {...useBlockProps.save()}>
      <div
        id="eb-product-desc"
        data-show-category={attributes.showCategory}
        data-show-ratings={attributes.showRatings}
        data-show-created={attributes.showCreated}
        data-show-course-access={attributes.showCourseAccess}
        data-show-enrolled={attributes.showEnrolled}
        data-show-associated-courses={attributes.showAssociatedCourses}
        data-show-related-courses={attributes.showRelatedCourses}
      ></div>
    </div>
  );
}

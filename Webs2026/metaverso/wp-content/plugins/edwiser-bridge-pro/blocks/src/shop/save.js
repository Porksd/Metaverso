import { useBlockProps } from '@wordpress/block-editor';

export default function save({ attributes }) {
  return (
    <div {...useBlockProps.save()}>
      <div
        id="eb-shop"
        data-use-background-image={attributes.useBackgroundImage}
        data-background-image={attributes.backgroundImage}
        data-background-color={attributes.backgroundColor}
        data-page-title={attributes.pageTitle}
        data-products-per-page={attributes.productsPerPage}
        data-allow-sort={attributes.allowSort}
        data-default-sort-order={attributes.defaultSortOrder}
        data-show-result-count={attributes.showResultCount}
        data-default-card-layout={attributes.defaultCardLayout}
        data-show-category={attributes.showCategory}
        data-show-course-description={attributes.showCourseDescription}
        data-show-ratings={attributes.showRatings}
        data-show-enrolled={attributes.showEnrolled}
        data-show-view={attributes.showView}
        data-show-breadcrumb={attributes.showBreadcrumb}
        data-title-color={attributes.titleColor}
      ></div>
    </div>
  );
}

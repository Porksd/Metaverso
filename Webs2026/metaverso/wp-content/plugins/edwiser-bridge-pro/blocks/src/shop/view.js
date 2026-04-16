import React from 'react';
import ReactDOM from 'react-dom/client'; // Correct import for React 18+
import Shop from './shop';

document.addEventListener('DOMContentLoaded', function () {
  const elem = document.getElementById('eb-shop');

  if (elem) {
    const attributes = {
      useBackgroundImage:
        elem.getAttribute('data-use-background-image') === 'true',
      backgroundImage: elem.getAttribute('data-background-image'),
      backgroundColor: elem.getAttribute('data-background-color'),
      pageTitle: elem.getAttribute('data-page-title'),
      productsPerPage: Number(elem.getAttribute('data-products-per-page')),
      allowSort: elem.getAttribute('data-allow-sort') === 'true',
      defaultSortOrder: elem.getAttribute('data-default-sort-order'),
      showResultCount: elem.getAttribute('data-show-result-count') === 'true',
      defaultCardLayout: elem.getAttribute('data-default-card-layout'),
      showCategory: elem.getAttribute('data-show-category') === 'true',
      showCourseDescription:
        elem.getAttribute('data-show-course-description') === 'true',
      showRatings: elem.getAttribute('data-show-ratings') === 'true',
      showEnrolled: elem.getAttribute('data-show-enrolled') === 'true',
      showView: elem.getAttribute('data-show-view') === 'true',
      showBreadcrumb: elem.getAttribute('data-show-breadcrumb') === 'true',
      titleColor: elem.getAttribute('data-title-color') || '#fff',
    };

    const root = ReactDOM.createRoot(elem);
    root.render(<Shop {...attributes} />);
  }
});

import React from 'react';
import ReactDOM from 'react-dom/client'; // Correct import for React 18+
import Product from './product';

document.addEventListener('DOMContentLoaded', function () {
  const elem = document.getElementById('eb-product-desc');

  if (elem) {
    const attributes = {
      productId: Number(elem.getAttribute('data-product-id')),
      showCategory: elem.getAttribute('data-show-category') === 'true',
      showRatings: elem.getAttribute('data-show-ratings') === 'true',
      showCreated: elem.getAttribute('data-show-created') === 'true',
      showCourseAccess: elem.getAttribute('data-show-course-access') === 'true',
      showEnrolled: elem.getAttribute('data-show-enrolled') === 'true',
      showAssociatedCourses:
        elem.getAttribute('data-show-associated-courses') === 'true',
      showRelatedCourses:
        elem.getAttribute('data-show-related-courses') === 'true',
    };

    const root = ReactDOM.createRoot(elem);
    root.render(<Product {...attributes} />);
  }
});

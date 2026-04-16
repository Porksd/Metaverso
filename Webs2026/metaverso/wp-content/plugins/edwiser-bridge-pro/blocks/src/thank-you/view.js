import React from 'react';
import ReactDOM from 'react-dom/client'; // Correct import for React 18+
import ThankYou from './thank-you';

document.addEventListener('DOMContentLoaded', function () {
  const elem = document.getElementById('eb-thank-you');

  if (elem) {
    const attributes = {
      showThankYouImage:
        elem.getAttribute('data-show-thank-you-image') === 'true',
      showYouMayLikeCourses:
        elem.getAttribute('data-show-you-may-like-courses') === 'true',
      thankYouImageUrl: elem.getAttribute('data-thank-you-image-url') || '',
      thankYouImageAlt: elem.getAttribute('data-thank-you-image-alt') || '',
    };

    const root = ReactDOM.createRoot(elem);
    root.render(<ThankYou {...attributes} />);
  }
});

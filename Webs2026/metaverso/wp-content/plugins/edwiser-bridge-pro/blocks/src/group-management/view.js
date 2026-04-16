import React from 'react';
import ReactDOM from 'react-dom/client';
import GroupManagement from './group-management';

document.addEventListener('DOMContentLoaded', function () {
  const elem = document.getElementById('eb-group-management');

  if (elem) {
    const attributes = {
      customTitle: elem.getAttribute('data-custom-title') || '',
      hideTitle: elem.getAttribute('data-hide-title') === 'true',
    };

    const root = ReactDOM.createRoot(elem);
    root.render(<GroupManagement {...attributes} />);
  }
});

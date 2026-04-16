import React from 'react';
import ReactDOM from 'react-dom/client'; // Correct import for React 18+
import Cart from './cart';

document.addEventListener('DOMContentLoaded', function () {
  const elem = document.getElementById('eb-cart');

  if (elem) {
    const root = ReactDOM.createRoot(elem);
    root.render(<Cart />);
  }
});

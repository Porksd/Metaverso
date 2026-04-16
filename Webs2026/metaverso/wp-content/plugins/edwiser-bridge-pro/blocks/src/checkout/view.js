import React from 'react';
import ReactDOM from 'react-dom/client'; // Correct import for React 18+
import Checkout from './checkout';

document.addEventListener('DOMContentLoaded', function () {
  const elem = document.getElementById('eb-checkout');

  if (elem) {
    const root = ReactDOM.createRoot(elem);
    root.render(<Checkout />);
  }
});

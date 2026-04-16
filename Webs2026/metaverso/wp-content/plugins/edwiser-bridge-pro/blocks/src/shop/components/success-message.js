import React, { useEffect } from 'react';
import { Icons } from './icons';
import { __ } from '@wordpress/i18n';

function SuccessMessage({ message, showSuccess, setShowSuccess, duration }) {
  // Auto-hide cart success message after 4 seconds
  useEffect(() => {
    let timeoutId;
    if (showSuccess) {
      timeoutId = setTimeout(() => {
        setShowSuccess(false);
      }, duration);
    }

    return () => {
      if (timeoutId) clearTimeout(timeoutId);
    };
  }, [showSuccess]);

  return (
    <div
      className={`eb-shop__success ${showSuccess ? 'slide-in' : 'slide-out'}`}
    >
      <span>{__(message, 'edwiser-bridge-pro')}</span>
      <button onClick={() => setShowSuccess(false)}>
        <Icons.cross />
      </button>
    </div>
  );
}

export default SuccessMessage;

import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import './editor.scss';
import apiFetch from '@wordpress/api-fetch';
import { useState } from 'react';
import { useEffect } from 'react';

export default function Edit() {
  const [checkoutHtml, setCheckoutHtml] = useState('');
  const [showPreview, setShowPreview] = useState(false);

  const getCheckoutHtml = async () => {
    try {
      const data = await apiFetch({
        path: '/eb/api/v1/checkout/html',
      });

      setCheckoutHtml(data.html);
    } catch (error) {
      console.error('Error fetching checkout html:', error);
    }
  };

  useEffect(() => {
    getCheckoutHtml();
  }, []);

  const togglePreview = () => {
    setShowPreview(!showPreview);
  };

  return (
    <div {...useBlockProps()}>
      <div className="eb-legacy-checkout__notice">
        <p>
          <b>{__('Note: ', 'edwiser-bridge-pro')}</b>
          {__(
            'The checkout appearance and functionality in the editor may differ from the page. Please view your page to see the accurate checkout experience.',
            'edwiser-bridge-pro'
          )}
        </p>
        <button
          className="eb-legacy-checkout__preview-button"
          onClick={togglePreview}
        >
          {showPreview
            ? __('Hide Preview', 'edwiser-bridge-pro')
            : __('Show Preview', 'edwiser-bridge-pro')}
        </button>
      </div>

      {showPreview && (
        <div
          className="eb-legacy-checkout__wrapper"
          dangerouslySetInnerHTML={{ __html: checkoutHtml }}
        ></div>
      )}
    </div>
  );
}

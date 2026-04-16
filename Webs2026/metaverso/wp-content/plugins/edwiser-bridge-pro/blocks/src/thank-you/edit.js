import {
  InspectorControls,
  useBlockProps,
  MediaUpload,
} from '@wordpress/block-editor';
import { PanelBody, ToggleControl, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import './editor.scss';
import ThankYou from './thank-you';

export default function Edit({ attributes, setAttributes }) {
  return (
    <div {...useBlockProps()}>
      <InspectorControls>
        <PanelBody
          title={__('Course Information Settings', 'edwiser-bridge-pro')}
          initialOpen={true}
        >
          <fieldset>
            <ToggleControl
              label={__('Show you may like courses', 'edwiser-bridge-pro')}
              checked={attributes.showYouMayLikeCourses}
              onChange={(value) =>
                setAttributes({ showYouMayLikeCourses: value })
              }
            />
          </fieldset>

          <fieldset style={{ marginTop: '8px' }}>
            <ToggleControl
              label={__('Show thank you image', 'edwiser-bridge-pro')}
              checked={attributes.showThankYouImage}
              onChange={(value) => setAttributes({ showThankYouImage: value })}
            />
          </fieldset>

          {attributes.showThankYouImage && (
            <fieldset style={{ marginTop: '8px' }}>
              <MediaUpload
                onSelect={(media) => {
                  setAttributes({
                    thankYouImageId: media.id,
                    thankYouImageUrl: media.url,
                    thankYouImageAlt: media.alt || __('Thank you'),
                  });
                }}
                allowedTypes={['image']}
                value={attributes.thankYouImageId}
                render={({ open }) => (
                  <div>
                    {attributes.thankYouImageUrl ? (
                      <div className="eb__image-preview-wrapper">
                        <img
                          src={attributes.thankYouImageUrl}
                          alt={attributes.thankYouImageAlt}
                          className="eb__image-preview"
                        />
                        <div className="eb__action-buttons">
                          <Button
                            onClick={open}
                            variant="secondary"
                            className="eb__edit-image"
                          >
                            {__('Replace', 'edwiser-bridge-pro')}
                          </Button>
                          <Button
                            onClick={() => {
                              setAttributes({
                                thankYouImageId: null,
                                thankYouImageUrl: null,
                                thankYouImageAlt: '',
                              });
                            }}
                            variant="secondary"
                            className="eb__remove-image"
                            isDestructive
                          >
                            {__('Remove', 'edwiser-bridge-pro')}
                          </Button>
                        </div>
                      </div>
                    ) : (
                      <Button
                        onClick={open}
                        variant="secondary"
                        className="eb__select-image"
                      >
                        {__('Set thank you image', 'edwiser-bridge-pro')}
                      </Button>
                    )}
                  </div>
                )}
              />
            </fieldset>
          )}
        </PanelBody>
      </InspectorControls>
      <ThankYou {...attributes} isEditor={true} />
    </div>
  );
}

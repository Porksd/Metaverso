import {
  InspectorControls,
  MediaUpload,
  useBlockProps,
} from '@wordpress/block-editor';
import {
  Button,
  ColorPalette,
  ColorPicker,
  ExternalLink,
  PanelBody,
  SelectControl,
  TextControl,
  ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import './editor.scss';
import Shop from './shop';

export default function Edit({ attributes, setAttributes }) {
  return (
    <div {...useBlockProps()}>
      <InspectorControls>
        <PanelBody title={__('Page Settings')} initialOpen={true}>
          <fieldset>
            <TextControl
              label={__('Page Title', 'edwiser-bridge-pro')}
              type="text"
              value={attributes.pageTitle}
              onChange={(value) => setAttributes({ pageTitle: value })}
            />
          </fieldset>
          <fieldset style={{ marginTop: '8px' }}>
            <TextControl
              label={__('Products Per Page', 'edwiser-bridge-pro')}
              type="number"
              value={attributes.productsPerPage}
              onChange={(value) =>
                setAttributes({ productsPerPage: Number(value) })
              }
            />
          </fieldset>
          <fieldset style={{ marginTop: '8px' }}>
            <ToggleControl
              label={__('Allow Sort', 'edwiser-bridge-pro')}
              checked={attributes.allowSort}
              onChange={(value) => setAttributes({ allowSort: value })}
            />
          </fieldset>
          <fieldset style={{ marginTop: '8px' }}>
            <SelectControl
              label={__('Default Sort Order', 'edwiser-bridge-pro')}
              value={attributes.defaultSortOrder}
              options={[
                {
                  label: __('Default Sorting', 'edwiser-bridge-pro'),
                  value: 'default',
                },
                {
                  label: __('Popularity', 'edwiser-bridge-pro'),
                  value: 'popularity',
                },
                {
                  label: __('Average Rating', 'edwiser-bridge-pro'),
                  value: 'rating',
                },
                { label: __('Latest', 'edwiser-bridge-pro'), value: 'date' },
                {
                  label: __('Price: Low to High', 'edwiser-bridge-pro'),
                  value: 'price',
                },
                {
                  label: __('Price: High to Low', 'edwiser-bridge-pro'),
                  value: 'price-desc',
                },
              ]}
              onChange={(value) => setAttributes({ defaultSortOrder: value })}
            />
          </fieldset>
          <fieldset style={{ marginTop: '8px' }}>
            <ToggleControl
              label={__('Show Result Count', 'edwiser-bridge-pro')}
              checked={attributes.showResultCount}
              onChange={(value) => setAttributes({ showResultCount: value })}
            />
          </fieldset>
          <fieldset style={{ marginTop: '8px' }}>
            <SelectControl
              label={__('Default Card Layout', 'edwiser-bridge-pro')}
              value={attributes.defaultCardLayout}
              options={[
                { label: __('Grid', 'edwiser-bridge-pro'), value: 'grid' },
                { label: __('List', 'edwiser-bridge-pro'), value: 'list' },
              ]}
              onChange={(value) => setAttributes({ defaultCardLayout: value })}
            />
          </fieldset>
        </PanelBody>
        <PanelBody
          title={__('Page Header Settings', 'edwiser-bridge-pro')}
          initialOpen={true}
        >
          <fieldset style={{ marginTop: '8px' }}>
            <ToggleControl
              label={__('Show Breadcrumb', 'edwiser-bridge-pro')}
              checked={attributes.showBreadcrumb}
              onChange={(value) => setAttributes({ showBreadcrumb: value })}
            />
          </fieldset>
          <fieldset style={{ marginTop: '8px' }}>
            <ToggleControl
              label={__('Use Background Image', 'edwiser-bridge-pro')}
              checked={attributes.useBackgroundImage}
              onChange={(value) => setAttributes({ useBackgroundImage: value })}
            />
          </fieldset>
          {attributes.useBackgroundImage ? (
            <fieldset style={{ marginTop: '8px' }}>
              <div className="eb__fieldset-label">
                {__('Background Image', 'edwiser-bridge-pro')}
              </div>
              <MediaUpload
                onSelect={(media) => {
                  setAttributes({
                    backgroundImage: media.url,
                  });
                }}
                allowedTypes={['image']}
                render={({ open }) => (
                  <div>
                    {attributes.backgroundImage ? (
                      <div className="eb__image-preview-wrapper">
                        <img
                          src={attributes.backgroundImage}
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
                                backgroundImage: null,
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
                        {__('Set background image', 'edwiser-bridge-pro')}
                      </Button>
                    )}
                  </div>
                )}
              />
            </fieldset>
          ) : (
            <fieldset style={{ marginTop: '8px' }} className="eb__color-picker">
              <div className="eb__fieldset-label">
                {__('Background Color', 'edwiser-bridge-pro')}
              </div>
              <ColorPalette
                value={attributes.backgroundColor}
                onChange={(value) => setAttributes({ backgroundColor: value })}
                disableCustomColors={false}
              />
            </fieldset>
          )}
          <fieldset style={{ marginTop: '16px' }} className="eb__color-picker">
            <div className="eb__fieldset-label">
              {__('Title Color', 'edwiser-bridge-pro')}
            </div>
            <ColorPalette
              value={attributes.titleColor}
              onChange={(value) => setAttributes({ titleColor: value })}
              disableCustomColors={false}
            />
          </fieldset>
        </PanelBody>
        <PanelBody
          title={__('Course Card Settings', 'edwiser-bridge-pro')}
          initialOpen={true}
        >
          <fieldset>
            <ToggleControl
              label={__('Show Category', 'edwiser-bridge-pro')}
              checked={attributes.showCategory}
              onChange={(value) => setAttributes({ showCategory: value })}
            />
          </fieldset>
          <fieldset style={{ marginTop: '8px' }}>
            <ToggleControl
              label={__('Show Course Description', 'edwiser-bridge-pro')}
              checked={attributes.showCourseDescription}
              onChange={(value) =>
                setAttributes({ showCourseDescription: value })
              }
            />
          </fieldset>
          <fieldset style={{ marginTop: '8px' }}>
            <ToggleControl
              label={__('Show Ratings', 'edwiser-bridge-pro')}
              checked={attributes.showRatings}
              onChange={(value) => setAttributes({ showRatings: value })}
            />
          </fieldset>
          <fieldset style={{ marginTop: '8px' }}>
            <ToggleControl
              label={__('Show Enrollments Count', 'edwiser-bridge-pro')}
              checked={attributes.showEnrolled}
              onChange={(value) => setAttributes({ showEnrolled: value })}
            />
          </fieldset>
          <fieldset style={{ marginTop: '8px' }}>
            <ToggleControl
              label={__('Show View button', 'edwiser-bridge-pro')}
              checked={attributes.showView}
              onChange={(value) => setAttributes({ showView: value })}
            />
          </fieldset>
          <div style={{ marginTop: '16px', fontSize: '14px' }}>
            {__("To configure 'Buy now' button click", 'edwiser-bridge-pro')}{' '}
            <ExternalLink
              href={`${window?.ebSettings?.adminUrl}admin.php?page=eb-settings&tab=woo_int_settings`}
            >
              {__('here', 'edwiser-bridge-pro')}
            </ExternalLink>
            .
          </div>
        </PanelBody>
      </InspectorControls>
      <Shop {...attributes} />
    </div>
  );
}

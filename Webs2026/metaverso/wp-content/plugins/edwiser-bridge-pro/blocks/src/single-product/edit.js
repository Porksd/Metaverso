import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
  ExternalLink,
  PanelBody,
  TextControl,
  ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import './editor.scss';
import Product from './product';

export default function Edit({ attributes, setAttributes }) {
  return (
    <div {...useBlockProps()}>
      <InspectorControls>
        <PanelBody
          title={__('Course Information Settings', 'edwiser-bridge-pro')}
          initialOpen={true}
        >
          <fieldset>
            <TextControl
              label={__('Product ID', 'edwiser-bridge-pro')}
              type="number"
              value={attributes.productId}
              onChange={(value) => setAttributes({ productId: Number(value) })}
            />
          </fieldset>
          <fieldset style={{ marginTop: '8px' }}>
            <ToggleControl
              label={__('Show Category', 'edwiser-bridge-pro')}
              checked={attributes.showCategory}
              onChange={(value) => setAttributes({ showCategory: value })}
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
              label={__('Show Created', 'edwiser-bridge-pro')}
              checked={attributes.showCreated}
              onChange={(value) => setAttributes({ showCreated: value })}
            />
          </fieldset>
          <fieldset style={{ marginTop: '8px' }}>
            <ToggleControl
              label={__('Show Course Access', 'edwiser-bridge-pro')}
              checked={attributes.showCourseAccess}
              onChange={(value) => setAttributes({ showCourseAccess: value })}
            />
          </fieldset>
          <fieldset style={{ marginTop: '8px' }}>
            <ToggleControl
              label={__('Show Enrolled', 'edwiser-bridge-pro')}
              checked={attributes.showEnrolled}
              onChange={(value) => setAttributes({ showEnrolled: value })}
            />
          </fieldset>
          <fieldset style={{ marginTop: '8px' }}>
            <ToggleControl
              label={__('Show Associated Courses', 'edwiser-bridge-pro')}
              checked={attributes.showAssociatedCourses}
              onChange={(value) =>
                setAttributes({ showAssociatedCourses: value })
              }
            />
          </fieldset>
          <fieldset style={{ marginTop: '8px' }}>
            <ToggleControl
              label={__('Show Related Products', 'edwiser-bridge-pro')}
              checked={attributes.showRelatedCourses}
              onChange={(value) => setAttributes({ showRelatedCourses: value })}
            />
          </fieldset>
          <div style={{ marginTop: '16px', fontSize: '14px' }}>
            {__("To configure 'Buy now' button click", 'edwiser-bridge-pro')}{' '}
            <ExternalLink href="/wp-admin/admin.php?page=eb-settings&tab=woo_int_settings">
              {__('here', 'edwiser-bridge-pro')}
            </ExternalLink>
            .
          </div>
        </PanelBody>
      </InspectorControls>
      <Product {...attributes} />
    </div>
  );
}

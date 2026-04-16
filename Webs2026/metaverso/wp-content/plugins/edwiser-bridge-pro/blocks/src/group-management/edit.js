import './editor.scss';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import GroupManagement from './group-management';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function Edit({ attributes, setAttributes }) {
  return (
    <div {...useBlockProps()}>
      <InspectorControls>
        <PanelBody
          title={__('Page Title Settings', 'edwiser-bridge-pro')}
          initialOpen={true}
        >
          <TextControl
            label={__('Page Title', 'edwiser-bridge-pro')}
            value={attributes.customTitle}
            onChange={(value) => setAttributes({ customTitle: value })}
          />
          <ToggleControl
            label={__('Hide Title', 'edwiser-bridge-pro')}
            checked={attributes.hideTitle}
            onChange={(value) => setAttributes({ hideTitle: value })}
          />
        </PanelBody>
      </InspectorControls>
      <GroupManagement
        customTitle={attributes.customTitle}
        hideTitle={attributes.hideTitle}
      />
    </div>
  );
}

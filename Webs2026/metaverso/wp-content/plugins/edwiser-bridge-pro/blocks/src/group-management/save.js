import { useBlockProps } from '@wordpress/block-editor';

export default function save({ attributes }) {
  return (
    <div {...useBlockProps.save()}>
      <div
        id="eb-group-management"
        data-custom-title={attributes.customTitle}
        data-hide-title={attributes.hideTitle}
      ></div>
    </div>
  );
}

import { useBlockProps } from '@wordpress/block-editor';

export default function save() {
  return (
    <div {...useBlockProps.save()}>
      <div className="eb-legacy-checkout__wrapper">
        {`[woocommerce_checkout]`}
      </div>
    </div>
  );
}

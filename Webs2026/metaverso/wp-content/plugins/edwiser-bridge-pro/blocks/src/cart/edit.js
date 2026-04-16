import './editor.scss';
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import Cart from './cart';

export default function Edit() {
  return (
    <div {...useBlockProps()}>
      <Cart />
    </div>
  );
}

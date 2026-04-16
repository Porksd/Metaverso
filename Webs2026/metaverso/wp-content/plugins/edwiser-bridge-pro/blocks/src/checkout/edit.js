import { useBlockProps } from '@wordpress/block-editor';
import Checkout from './checkout';
import './editor.scss';

export default function Edit() {
  return (
    <div {...useBlockProps()}>
      <Checkout />
    </div>
  );
}

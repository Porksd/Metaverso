import { useBlockProps } from '@wordpress/block-editor';

export default function save({ attributes }) {
  return (
    <div {...useBlockProps.save()}>
      <div
        id="eb-thank-you"
        data-show-thank-you-image={attributes.showThankYouImage}
        data-show-you-may-like-courses={attributes.showYouMayLikeCourses}
        data-thank-you-image-url={attributes.thankYouImageUrl}
        data-thank-you-image-alt={attributes.thankYouImageAlt}
      ></div>
    </div>
  );
}

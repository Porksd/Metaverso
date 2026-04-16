<?php

$post_id = get_the_ID();

if (get_post_type($post_id) !== 'product') {
    $product_id = get_post_meta($post_id, 'productId', true);
} else {
    $product_id = $post_id;
}

echo '<div ' . get_block_wrapper_attributes() . '>';
echo sprintf(
    '<div id="eb-product-desc" 
            data-product-id="%s"
            data-show-category="%s"
            data-show-ratings="%s"
            data-show-created="%s"
            data-show-course-access="%s"
            data-show-enrolled="%s"
            data-show-associated-courses="%s"
            data-show-related-courses="%s"
        ></div></div>',
    esc_attr($product_id),
    esc_attr($attributes['showCategory'] ? 'true' : 'false'),
    esc_attr($attributes['showRatings'] ? 'true' : 'false'),
    esc_attr($attributes['showCreated'] ? 'true' : 'false'),
    esc_attr($attributes['showCourseAccess'] ? 'true' : 'false'),
    esc_attr($attributes['showEnrolled'] ? 'true' : 'false'),
    esc_attr($attributes['showAssociatedCourses'] ? 'true' : 'false'),
    esc_attr($attributes['showRelatedCourses'] ? 'true' : 'false')
);

import { __ } from '@wordpress/i18n';
import React from 'react';
import CourseCard, { CourseCardSkeleton } from './course-card';

function CourseGrid({ view, products, cartItems, attributes }) {
  return (
    <div className={`eb-shop__courses ${view}`}>
      {products.length > 0 ? (
        products.map((product) => (
          <CourseCard
            key={product.id}
            course={product}
            inCart={cartItems.includes(product.id)}
            attributes={attributes}
            productName={product.name}
          />
        ))
      ) : (
        <p>{__('No products found', 'edwiser-bridge-pro')}</p>
      )}
    </div>
  );
}

export default CourseGrid;

export function CourseGridSkeleton({ view, perPage }) {
  return (
    <div className={`eb-shop__courses ${view}`}>
      {Array.from({ length: perPage }).map((_, index) => (
        <CourseCardSkeleton key={index} />
      ))}
    </div>
  );
}

import { Skeleton } from '@mantine/core';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Icons } from './icons';
import { decodeHTMLEntities } from '../utils';

function AssociatedCourses({ associatedCourses }) {
  return (
    <div className="eb-product-desc__associated-courses">
      <p className="eb-product-desc__associated-title">
        {__('Associated courses', 'edwiser-bridge-pro')}
      </p>
      <ul className="eb-product-desc__associated-list">
        {associatedCourses.map((item) => (
          <li key={item.id}>
            <Icons.chevronRight />
            <a href={item.link} target="_blank">
              {__(decodeHTMLEntities(item?.title), 'edwiser-bridge-pro')}
            </a>
          </li>
        ))}
      </ul>
    </div>
  );
}

export default AssociatedCourses;

export function AssociatedCoursesSkeleton() {
  return (
    <div className="eb-product-desc__associated-courses">
      <Skeleton width={130} height={24} />
      <ul className="eb-product-desc__associated-list">
        <Skeleton width={210} height={24} />
        <Skeleton width={280} height={24} />
      </ul>
    </div>
  );
}

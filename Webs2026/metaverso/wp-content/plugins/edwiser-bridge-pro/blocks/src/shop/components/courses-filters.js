import { Select } from '@mantine/core';
import React from 'react';
import { decodeHTMLEntities } from '../utils';
import { Icons } from './icons';
import { __ } from '@wordpress/i18n';

function CoursesFilters({
  category,
  handleCategoryChange,
  categories,
  sortBy,
  handleSortChange,
  allowSort,
  view,
  handleViewChange,
}) {
  return (
    <div className="eb-shop__courses-filters">
      <Select
        value={category}
        onChange={handleCategoryChange}
        checkIconPosition="right"
        placeholder={__('Category', 'edwiser-bridge-pro')}
        data={[
          {
            value: 'all',
            label: __('All Categories', 'edwiser-bridge-pro'),
          },
          ...categories.map((category) => {
            return {
              value: String(category.id),
              label: __(
                decodeHTMLEntities(category.name),
                'edwiser-bridge-pro'
              ),
            };
          }),
        ]}
        comboboxProps={{
          withinPortal: false,
        }}
        maxDropdownHeight={350}
        rightSection={<Icons.chevronDown />}
        allowDeselect={false}
      />
      {allowSort && (
        <Select
          value={sortBy}
          onChange={handleSortChange}
          checkIconPosition="right"
          placeholder={__('Sort by', 'edwiser-bridge-pro')}
          data={[
            {
              value: 'default',
              label: __('Default Sorting', 'edwiser-bridge-pro'),
            },
            {
              value: 'popularity',
              label: __('Popularity', 'edwiser-bridge-pro'),
            },
            {
              value: 'rating',
              label: __('Average Rating', 'edwiser-bridge-pro'),
            },
            { value: 'date', label: __('Latest', 'edwiser-bridge-pro') },
            {
              value: 'price',
              label: __('Price: Low to High', 'edwiser-bridge-pro'),
            },
            {
              value: 'price-desc',
              label: __('Price: High to Low', 'edwiser-bridge-pro'),
            },
          ]}
          comboboxProps={{
            withinPortal: false,
          }}
          maxDropdownHeight={350}
          rightSection={<Icons.chevronDown />}
          allowDeselect={false}
        />
      )}
      <div className="eb-shop__view-toggle">
        <button
          className={`btn__view-toggle ${view === 'grid' && 'active'}`}
          onClick={() => handleViewChange('grid')}
        >
          <Icons.grid />
        </button>
        <button
          className={`btn__view-toggle ${view === 'list' && 'active'}`}
          onClick={() => handleViewChange('list')}
        >
          <Icons.list />
        </button>
      </div>
    </div>
  );
}

export default CoursesFilters;

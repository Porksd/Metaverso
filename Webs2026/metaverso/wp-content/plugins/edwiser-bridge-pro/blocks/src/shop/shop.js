import { MantineProvider, Pagination, Skeleton } from '@mantine/core';
import { __, sprintf } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react';
import CourseGrid, { CourseGridSkeleton } from './components/course-grid';
import CoursesFilters from './components/courses-filters';
import { useProducts } from './hooks/use-products';
import { getQueryParams, updateQueryParams } from './utils';

function Shop(attributes) {
  const queryParams = getQueryParams();

  const [view, setView] = useState(
    queryParams.view || attributes.defaultCardLayout
  );
  const [sortBy, setSortBy] = useState(
    queryParams.sortBy || attributes.defaultSortOrder
  );
  const [category, setCategory] = useState(queryParams.category);
  const [currentPage, setCurrentPage] = useState(queryParams.currPage);
  const perPage = attributes.productsPerPage;

  const {
    products,
    cartItems,
    categories,
    totalProducts,
    totalPages,
    isLoading,
  } = useProducts({
    perPage,
    category,
    sortBy,
    currentPage,
  });

  const handleViewChange = (newView) => {
    setView(newView);
    updateQueryParams({ view: newView, curr_page: 1 });
    setCurrentPage(1);
  };

  const handleSortChange = (newSortBy) => {
    setSortBy(newSortBy);
    updateQueryParams({ sort_by: newSortBy, curr_page: 1 });
    setCurrentPage(1);
  };

  const handleCategoryChange = (newCategory) => {
    setCategory(newCategory);
    updateQueryParams({ category: newCategory, curr_page: 1 });
    setCurrentPage(1);
  };

  const handlePageChange = (newPage) => {
    setCurrentPage(newPage);
    updateQueryParams({ curr_page: newPage });
  };

  // handle browser back/forward buttons
  useEffect(() => {
    const handlePopState = () => {
      const params = getQueryParams();
      setView(params.view);
      setSortBy(params.sortBy);
      setCategory(params.category);
      setCurrentPage(params.currPage);
    };

    window.addEventListener('popstate', handlePopState);
    return () => window.removeEventListener('popstate', handlePopState);
  }, []);

  return (
    <MantineProvider>
      <div className="eb-shop--wrapper">
        <div
          className="eb-shop__header-wrapper"
          style={{
            background: attributes.useBackgroundImage
              ? attributes.backgroundImage &&
                `url(${attributes.backgroundImage})`
              : attributes.backgroundColor,
          }}
        >
          <div className="eb-shop__header">
            {attributes.showBreadcrumb && (
              <div className="eb-shop__breadcrumb">
                <a href="/" className="eb-shop__breadcrumb-link">
                  {__('Home', 'edwiser-bridge-pro')}
                </a>
                <span
                  className="eb-shop__breadcrumb-current"
                  style={{ color: attributes.titleColor, opacity: 0.7 }}
                >
                  {' '}
                  / {__(attributes.pageTitle, 'edwiser-bridge-pro')}
                </span>
              </div>
            )}
            <h1
              className="eb-shop__header-title"
              style={{ color: attributes.titleColor }}
            >
              {__(attributes.pageTitle, 'edwiser-bridge-pro')}
            </h1>
          </div>
        </div>
        <div className="eb-shop__body-wrapper">
          <div className="eb-shop__body">
            <div className="eb-shop__body-header">
              {attributes.showResultCount &&
                (isLoading ? (
                  <Skeleton width={180} height={22} />
                ) : (
                  <p className="eb-shop__courses-count">
                    {products.length > 0
                      ? /* translators: %1$d = starting course number, %2$d = ending course number, %3$d = total number of courses */
                        sprintf(
                          __(
                            'Showing %1$d–%2$d of %3$d results',
                            'edwiser-bridge-pro'
                          ),
                          (currentPage - 1) * perPage + 1,
                          (currentPage - 1) * perPage + products.length,
                          totalProducts
                        )
                      : __('No products found', 'edwiser-bridge-pro')}
                  </p>
                ))}
              <CoursesFilters
                category={category}
                categories={categories}
                handleCategoryChange={handleCategoryChange}
                sortBy={sortBy}
                handleSortChange={handleSortChange}
                view={view}
                handleViewChange={handleViewChange}
                allowSort={attributes.allowSort}
              />
            </div>
            <div className="eb-shop__body-main">
              {isLoading ? (
                <CourseGridSkeleton view={view} perPage={perPage} />
              ) : (
                <CourseGrid
                  view={view}
                  products={products}
                  cartItems={cartItems}
                  attributes={attributes}
                />
              )}
              <Pagination
                total={totalPages}
                value={currentPage}
                onChange={handlePageChange}
                disabled={isLoading}
              />
            </div>
          </div>
        </div>
      </div>
    </MantineProvider>
  );
}

export default Shop;

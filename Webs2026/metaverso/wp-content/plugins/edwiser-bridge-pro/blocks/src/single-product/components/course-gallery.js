import React, { useEffect, useState } from 'react';
import coursePlaceholderImg from '../../../../images/placeholder-course-thumbnail.jpeg';
import { Skeleton } from '@mantine/core';

function CourseGallery({ images, courseName }) {
  const [currentImage, setCurrentImage] = useState(images[0]);

  useEffect(() => {
    setCurrentImage(images[0]);
  }, [images]);

  return (
    <div className="eb-product-desc__course-gallery">
      <div className="eb-product-desc__gallery-main">
        <img
          src={currentImage?.src || coursePlaceholderImg}
          alt={currentImage?.alt || currentImage?.name || courseName}
          draggable="false"
        />
      </div>
      <div className="eb-product-desc__gallery-thumbs">
        {images?.length > 0 ? (
          images?.map((image) => (
            <div
              className={`eb-product-desc__gallery-thumb ${
                image.id === currentImage?.id ? 'active' : ''
              }`}
              onClick={() => setCurrentImage(image)}
            >
              <img
                src={image.thumbnail}
                alt={image.alt || image.name || courseName}
                draggable="false"
              />
            </div>
          ))
        ) : (
          <div className="eb-product-desc__gallery-thumb active">
            <img
              src={coursePlaceholderImg}
              alt={courseName}
              draggable="false"
            />
          </div>
        )}
      </div>
    </div>
  );
}

export default CourseGallery;

export function CourseGallerySkeleton() {
  return (
    <div className="eb-product-desc__course-gallery">
      <div className="eb-product-desc__gallery-main">
        <Skeleton width={'100%'} height={'100%'} />
      </div>
      <div className="eb-product-desc__gallery-thumbs">
        <div className="eb-product-desc__gallery-thumb active">
          <Skeleton width={'100%'} height={'100%'} />
        </div>
        {Array.from({ length: 4 }).map((_, index) => (
          <div className="eb-product-desc__gallery-thumb" key={index}>
            <Skeleton width={'100%'} height={'100%'} />
          </div>
        ))}
      </div>
    </div>
  );
}

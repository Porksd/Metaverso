import { Checkbox, Select } from '@mantine/core';
import React, { useEffect, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { Icons } from './icons';

function CourseVariations({
  variations,
  attributeOptions,
  attributeNames,
  courseType,
  setAssociatedCourses,
  enableGroupPurchase,
  setEnableGroupPurchase,
  setQuantity,
  setVariation,
}) {
  const [selectedAttributes, setSelectedAttributes] = useState({});
  const [priceHtml, setPriceHtml] = useState('');
  const [variationGroupPurchaseEnabled, setVariationGroupPurchaseEnabled] =
    useState(false);
  const [matchedVariation, setMatchedVariation] = useState(null);
  const [availableAttributeOptions, setAvailableAttributeOptions] = useState(
    {}
  );

  // Initialize selected attributes state
  useEffect(() => {
    if (
      attributeNames.length > 0 &&
      Object.keys(selectedAttributes).length === 0
    ) {
      const initialState = {};
      attributeNames.forEach((name) => {
        initialState[name] = null;
      });
      setSelectedAttributes(initialState);

      // Initialize available options with all options
      setAvailableAttributeOptions(attributeOptions);
    }
  }, [attributeNames, attributeOptions]);

  // Function to find matching variation based on selected attributes
  const findMatchingVariation = (selectedAttrs) => {
    // Only try to find a match if all attributes are selected
    const allAttributesSelected = attributeNames.every(
      (name) => selectedAttrs[name] !== null
    );

    if (allAttributesSelected) {
      // Try to find an exact match
      return variations.find((variation) => {
        return attributeNames.every((name) => {
          const attrKey = `attribute_${name
            .toLowerCase()
            .split(' ')
            .join('-')}`;
          return (
            variation.attributes[attrKey] === '' ||
            variation.attributes[attrKey] === selectedAttrs[name]
          );
        });
      });
    }
    return null;
  };

  // Update available options for remaining attributes based on current selections
  const updateAvailableOptions = (currentSelections) => {
    const availableOptions = { ...attributeOptions };

    attributeNames.forEach((attrName) => {
      if (currentSelections[attrName] === null) {
        const validOptions = variations
          .filter((variation) => {
            // Check if this variation matches all currently selected attributes
            return attributeNames.every((name) => {
              // Skip this attribute or if nothing is selected for this attribute
              if (name === attrName || currentSelections[name] === null)
                return true;

              const attrKey = `attribute_${name
                .toLowerCase()
                .split(' ')
                .join('-')}`;
              return (
                variation.attributes[attrKey] === '' ||
                variation.attributes[attrKey] === currentSelections[name]
              );
            });
          })
          .map((variation) => {
            const attrKey = `attribute_${attrName
              .toLowerCase()
              .split(' ')
              .join('-')}`;
            return variation.attributes[attrKey];
          });

        const displayOptions = validOptions.filter((option) => option !== '');

        // Update available options for this attribute
        availableOptions[attrName] = attributeOptions[attrName].filter(
          (option) =>
            displayOptions.includes(option) || validOptions.includes('')
        );
      }
    });

    setAvailableAttributeOptions(availableOptions);
  };

  // Handle attribute selection change
  const handleAttributeChange = (attributeName, value) => {
    const newSelectedAttributes = {
      ...selectedAttributes,
      [attributeName]: value,
    };

    setSelectedAttributes(newSelectedAttributes);

    // Update available options for other attributes
    updateAvailableOptions(newSelectedAttributes);

    // Find matching variation
    const matchedVariation = findMatchingVariation(newSelectedAttributes);
    setMatchedVariation(matchedVariation);

    if (matchedVariation) {
      setVariationGroupPurchaseEnabled(matchedVariation.group_purchase_enabled);
      setVariation({
        ...matchedVariation,
        selected_attributes: { ...newSelectedAttributes },
      });
      setAssociatedCourses(matchedVariation.associated_courses || []);
      setEnableGroupPurchase(false);
      setQuantity(1);
      setPriceHtml(matchedVariation.price_html);
    } else {
      // Clear variation-specific data if we don't have a match
      setVariation(null);
      setPriceHtml('');
    }
  };

  // Clear all selections
  const clearSelections = () => {
    const clearedAttributes = {};
    attributeNames.forEach((name) => {
      clearedAttributes[name] = null;
    });

    setSelectedAttributes(clearedAttributes);
    setVariation(null);
    setMatchedVariation(null);
    setVariationGroupPurchaseEnabled(false);
    setEnableGroupPurchase(false);
    setQuantity(1);
    setPriceHtml('');

    // Reset available options to all options
    setAvailableAttributeOptions(attributeOptions);
  };

  // Check if all attributes are selected
  const allAttributesSelected = attributeNames.every(
    (name) => selectedAttributes[name] !== null
  );

  return (
    <>
      <div className="eb-product-desc__variation-selector">
        {attributeNames.map((attributeName) => (
          <Select
            label={attributeName}
            placeholder={__('Choose an option', 'edwiser-bridge-pro')}
            data={availableAttributeOptions[attributeName] || []}
            value={selectedAttributes[attributeName]}
            onChange={(value) => handleAttributeChange(attributeName, value)}
            checkIconPosition="right"
            comboboxProps={{
              withinPortal: false,
            }}
            rightSection={<Icons.chevronDown />}
            allowDeselect={false}
          />
        ))}

        {Object.values(selectedAttributes).some((val) => val !== null) && (
          <button
            className="eb-product-desc__clear-button"
            onClick={clearSelections}
          >
            <Icons.cross />
            <span>{__('Clear', 'edwiser-bridge-pro')}</span>
          </button>
        )}
      </div>

      {matchedVariation && allAttributesSelected && (
        <div className="eb-product-desc__course-pricing">
          <div
            className={`eb-product-desc__price ${courseType} `}
            dangerouslySetInnerHTML={{ __html: priceHtml }}
          ></div>
        </div>
      )}

      {matchedVariation && variationGroupPurchaseEnabled && (
        <Checkbox
          label={__('Enable group purchase', 'edwiser-bridge-pro')}
          className="eb-product-desc__enable-group-purchase"
          checked={enableGroupPurchase}
          onChange={(e) => {
            setEnableGroupPurchase(e.currentTarget.checked);
            setQuantity(1);
          }}
        />
      )}
    </>
  );
}

export default CourseVariations;

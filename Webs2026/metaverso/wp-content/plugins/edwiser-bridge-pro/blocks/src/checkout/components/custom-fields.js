import { Checkbox, Select, Textarea, TextInput } from '@mantine/core';
import React from 'react';
import { Icons } from './icons';

function CustomFields({
  customFields,
  customFieldsData,
  updateCustomFieldsData,
  validationErrors,
}) {
  const renderField = (fieldData) => {
    const {
      type,
      label,
      placeholder = '',
      required,
      class: className,
      id,
      name,
    } = fieldData;

    const value =
      customFieldsData[name] !== undefined
        ? customFieldsData[name]
        : fieldData.value || '';

    const commonProps = {
      key: id,
      label: (
        <>
          {label}{' '}
          {!required && type !== 'checkbox' && (
            <span className="optional-label">(optional)</span>
          )}
        </>
      ),
      placeholder,
      value,
      className,
      name,
      error: validationErrors?.[name],
      onChange: (e) => {
        const newValue =
          type === 'checkbox'
            ? e.currentTarget.checked
              ? 'on'
              : undefined
            : e.currentTarget
            ? e.currentTarget.value
            : e;
        updateCustomFieldsData(name, newValue);
      },
      required,
    };

    switch (type) {
      case 'text':
        return <TextInput {...commonProps} />;

      case 'textarea':
        return <Textarea {...commonProps} />;

      case 'number':
        return <TextInput type="number" {...commonProps} />;

      case 'date':
        return <TextInput type="date" {...commonProps} />;

      case 'select':
        return (
          <Select
            {...commonProps}
            data={fieldData.options}
            checkIconPosition="right"
            comboboxProps={{
              withinPortal: false,
            }}
            maxDropdownHeight={400}
            rightSection={<Icons.chevronDown />}
            searchable
          />
        );

      case 'checkbox':
        return <Checkbox {...commonProps} checked={!!customFieldsData[name]} />;

      default:
        return null;
    }
  };

  return (
    <div className="eb-checkout__custom-fields">
      <div className="custom-fields__header">
        <h2 className="custom-fields__title">Additional information</h2>
      </div>
      <div className="custom-fields__content">
        {customFields.map((fieldData) => renderField(fieldData))}
      </div>
    </div>
  );
}

export default CustomFields;

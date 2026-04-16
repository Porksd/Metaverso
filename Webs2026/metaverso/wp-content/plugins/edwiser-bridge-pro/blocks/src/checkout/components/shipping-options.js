import { Accordion, Radio, RadioGroup } from '@mantine/core';
import React, { useEffect, useState } from 'react';
import { decodeHTMLEntities, formatShippingPrices } from '../utils';
import { __ } from '@wordpress/i18n';

function ShippingOptions({
  subscriptions,
  shippingRates,
  includesTax,
  updateShippingRate,
}) {
  return (
    <div className="eb-checkout__shipping-options">
      <div className="shipping-options__header">
        <h2 className="shipping-options__title">{__('Shipping options')}</h2>
      </div>
      <div className="shipping-options__content">
        {shippingRates.length > 0 && (
          <Option
            shippingRates={shippingRates}
            includesTax={includesTax}
            updateShippingRate={updateShippingRate}
            simpleView={shippingRates.length + subscriptions.length === 1}
          />
        )}

        {subscriptions &&
          subscriptions.length > 0 &&
          subscriptions.map((subscription) => (
            <Option
              shippingRates={subscription.shipping_rates}
              includesTax={includesTax}
              updateShippingRate={updateShippingRate}
              simpleView={shippingRates.length + subscriptions.length === 1}
            />
          ))}
      </div>
    </div>
  );
}

export default ShippingOptions;

function Option({
  shippingRates,
  includesTax,
  updateShippingRate,
  simpleView = false,
}) {
  const [shipmentRates, setShipmentRates] = useState([]);
  const [selectedShippingOption, setSelectedShippingOption] = useState('');
  const [packageId, setPackageId] = useState(null);
  const [shipmentName, setShipmentName] = useState('');
  const [shipmentItems, setShipmentItems] = useState([]);

  useEffect(() => {
    if (shippingRates && shippingRates.length > 0) {
      const firstPackage = shippingRates[0];
      setPackageId(firstPackage.package_id);
      setShipmentName(firstPackage.name);
      setShipmentItems(firstPackage.items);

      if (firstPackage && firstPackage.shipping_rates) {
        setShipmentRates(firstPackage.shipping_rates);

        const selectedRate = firstPackage.shipping_rates.find(
          (rate) => rate.selected
        );
        if (selectedRate) {
          setSelectedShippingOption(selectedRate);
        }
      } else {
        setShipmentRates([]);
      }
    } else {
      setShipmentRates([]);
    }
  }, [shippingRates]);

  const handleShippingRateUpdate = async (rateId) => {
    const option = shipmentRates.find((option) => option.rate_id === rateId);
    setSelectedShippingOption(option);

    await updateShippingRate(option, packageId);
  };

  if (simpleView) {
    return (
      <RadioGroup
        value={selectedShippingOption.rate_id}
        onChange={handleShippingRateUpdate}
      >
        {shipmentRates.map((option) => (
          <Radio
            key={option.rate_id}
            value={option.rate_id}
            label={
              <>
                <span>{__(option.name)}</span>
                <span className="price">
                  {formatShippingPrices(option, includesTax)}
                </span>
              </>
            }
          />
        ))}
      </RadioGroup>
    );
  }

  return (
    <>
      {shipmentRates.length > 0 && (
        <Accordion variant="contained" className="shipping-option">
          <Accordion.Item value={'shipment' + packageId}>
            <Accordion.Control>{__(shipmentName)}</Accordion.Control>
            <Accordion.Panel>
              {shipmentItems.length > 0 && (
                <div className="shipment-items">
                  {shipmentItems.map((item) => (
                    <div key={item.key} className="shipment-item">
                      <span>{__(decodeHTMLEntities(item.name))}</span>
                    </div>
                  ))}
                </div>
              )}
              {shipmentRates.length > 0 && (
                <RadioGroup
                  value={selectedShippingOption.rate_id}
                  onChange={handleShippingRateUpdate}
                >
                  {shipmentRates.map((option) => (
                    <Radio
                      key={option.rate_id}
                      value={option.rate_id}
                      label={
                        <>
                          <span>{__(option.name)}</span>
                          <span className="price">
                            {formatShippingPrices(option, includesTax)}
                          </span>
                        </>
                      }
                    />
                  ))}
                </RadioGroup>
              )}
            </Accordion.Panel>
          </Accordion.Item>
        </Accordion>
      )}
    </>
  );
}

import { Accordion, Radio, RadioGroup } from '@mantine/core';
import { __ } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react';
import { decodeHTMLEntities, formatShippingPrices } from '../utils';

function ShipmentRates({
  shippingRates,
  updateShippingRate,
  includesTax,
  simpleView = false,
}) {
  const [shippingOptions, setShippingOptions] = useState([]);
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
        setShippingOptions(firstPackage.shipping_rates);

        const selectedRate = firstPackage.shipping_rates.find(
          (rate) => rate.selected
        );
        if (selectedRate) {
          setSelectedShippingOption(selectedRate);
        }
      } else {
        setShippingOptions([]);
      }
    } else {
      setShippingOptions([]);
    }
  }, [shippingRates]);

  const handleShippingRateChange = async (rateId) => {
    const option = shippingOptions.find((option) => option.rate_id === rateId);
    setSelectedShippingOption(option);

    await updateShippingRate(option, packageId);
  };

  if (simpleView) {
    return (
      <RadioGroup
        value={selectedShippingOption.rate_id}
        onChange={handleShippingRateChange}
      >
        {shippingOptions.map((option) => (
          <Radio
            key={option.rate_id}
            value={option.rate_id}
            label={
              <>
                <span>{__(option.name, 'edwiser-bridge-pro')}</span>
                <span className="price">
                  {__(
                    formatShippingPrices(option, includesTax),
                    'edwiser-bridge-pro'
                  )}
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
      {shippingOptions.length > 0 && (
        <Accordion variant="contained" className="shipment-details-accordion">
          <Accordion.Item value={'shipment' + packageId}>
            <Accordion.Control>
              <span className="shipment-name">
                {__(shipmentName, 'edwiser-bridge-pro')}
              </span>
              <span className="shipment-rate-name">
                {__(selectedShippingOption.name, 'edwiser-bridge-pro')}
              </span>
            </Accordion.Control>
            <Accordion.Panel>
              {shipmentItems.length > 0 && (
                <div className="shipment-items">
                  {shipmentItems.map((item) => (
                    <div key={item.key} className="shipment-item">
                      <span>
                        {__(
                          decodeHTMLEntities(item.name),
                          'edwiser-bridge-pro'
                        )}
                      </span>
                    </div>
                  ))}
                </div>
              )}
              {shippingOptions.length > 0 && (
                <RadioGroup
                  value={selectedShippingOption.rate_id}
                  onChange={handleShippingRateChange}
                >
                  {shippingOptions.map((option) => (
                    <Radio
                      key={option.rate_id}
                      value={option.rate_id}
                      label={
                        <>
                          <span>{__(option.name, 'edwiser-bridge-pro')}</span>
                          <span className="price">
                            {__(
                              formatShippingPrices(option, includesTax),
                              'edwiser-bridge-pro'
                            )}
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

export default ShipmentRates;

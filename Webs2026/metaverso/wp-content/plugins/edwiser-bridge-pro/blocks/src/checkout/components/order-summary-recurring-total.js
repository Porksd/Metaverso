import { Accordion } from '@mantine/core';
import React from 'react';
import {
  formatSubscriptionShippingPrices,
  formatSubTotalPrice,
  formatTaxPrices,
  formatTotalPrice,
} from '../utils';
import { __ } from '@wordpress/i18n';

function OrderSummaryRecurringTotal({
  subscriptions,
  includesTax,
  singleTaxTotal,
  needsShipping,
}) {
  return (
    <div className="order-summary__recurring-totals">
      {subscriptions.map((subscription) => (
        <div key={subscription.key} className="recurring-total__item">
          <div className="recurring-item__title">
            <span className="label">
              {subscription.billing_interval ===
              subscription.subscription_length ? (
                'Total'
              ) : subscription.billing_interval === 1 ? (
                `${
                  subscription.billing_period.charAt(0).toUpperCase() +
                  subscription.billing_period.slice(1)
                }ly recurring total`
              ) : (
                <>
                  Recurring total every {subscription.billing_interval}
                  {subscription.billing_interval === 2
                    ? 'nd '
                    : subscription.billing_interval === 3
                    ? 'rd '
                    : subscription.billing_interval <= 6
                    ? 'th '
                    : ''}
                  {subscription.billing_period}
                </>
              )}
            </span>
            <div className="value">{formatTotalPrice(subscription.totals)}</div>
          </div>
          <div className="recurring-item__duration">
            <span className="label">
              {' '}
              Starting: {subscription.next_payment_date}
            </span>
            {subscription.subscription_length > 0 && (
              <div className="value">
                For {subscription.subscription_length}{' '}
                {subscription.billing_period}
                {subscription.subscription_length > 1 && 's'}
              </div>
            )}
          </div>
          <Accordion variant="contained">
            <Accordion.Item value="recurring">
              <Accordion.Control>Details</Accordion.Control>
              <Accordion.Panel>
                <div className="recurring__sub-total">
                  <span className="label">Subtotal</span>
                  <span className="value">
                    {' '}
                    {__(formatSubTotalPrice(subscription.totals, includesTax))}
                  </span>
                </div>
                {needsShipping && subscription?.shipping_rates.length > 0 && (
                  <div className="recurring__shipping">
                    <div>
                      <span className="label">Shipping</span>
                      <span className="label-sm">
                        {__(
                          'via ' +
                            subscription?.shipping_rates[0]?.shipping_rates.find(
                              (option) => option.selected === true
                            ).name
                        )}
                      </span>
                    </div>
                    <span className="value">
                      {__(
                        parseFloat(subscription.totals.total_shipping) > 0
                          ? formatSubscriptionShippingPrices(
                              subscription.totals,
                              includesTax
                            )
                          : 'FREE'
                      )}
                    </span>
                  </div>
                )}
                {!includesTax && (
                  <div className="recurring__taxes">
                    {!singleTaxTotal &&
                    subscription.totals.tax_lines?.length > 0 ? (
                      subscription.totals.tax_lines.map((tax, index) => (
                        <div className="tax" key={index}>
                          <p className="label">{__(tax.name)}</p>
                          <p className="value">
                            {__(
                              formatTaxPrices(subscription.totals, 'tax', index)
                            )}
                          </p>
                        </div>
                      ))
                    ) : (
                      <div className="tax">
                        <p className="label">{__('Taxes')}</p>
                        <p className="value">
                          {__(
                            formatTaxPrices(subscription.totals, 'total-tax')
                          )}
                        </p>
                      </div>
                    )}
                  </div>
                )}
                <div className="recurring__total">
                  <span className="label">Total</span>
                  <span className="value">
                    {__(formatTotalPrice(subscription.totals))}
                  </span>
                </div>
              </Accordion.Panel>
            </Accordion.Item>
          </Accordion>
        </div>
      ))}
    </div>
  );
}

export default OrderSummaryRecurringTotal;

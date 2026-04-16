import { Accordion } from '@mantine/core';
import React from 'react';
import {
  formatSubscriptionShippingPrices,
  formatSubTotalPrice,
  formatTaxPrices,
  formatTotalPrice,
} from '../utils';
import { __, _n, sprintf } from '@wordpress/i18n';

function SubscriptionTotal({
  subscriptions,
  includesTax,
  singleTaxTotal,
  needsShipping,
}) {
  const billingPeriodMap = {
    day: {
      singular: __('day', 'edwiser-bridge-pro'),
      plural: __('days', 'edwiser-bridge-pro'),
      adjective: __('Daily', 'edwiser-bridge-pro'),
    },
    week: {
      singular: __('week', 'edwiser-bridge-pro'),
      plural: __('weeks', 'edwiser-bridge-pro'),
      adjective: __('Weekly', 'edwiser-bridge-pro'),
    },
    month: {
      singular: __('month', 'edwiser-bridge-pro'),
      plural: __('months', 'edwiser-bridge-pro'),
      adjective: __('Monthly', 'edwiser-bridge-pro'),
    },
    year: {
      singular: __('year', 'edwiser-bridge-pro'),
      plural: __('years', 'edwiser-bridge-pro'),
      adjective: __('Yearly', 'edwiser-bridge-pro'),
    },
  };

  return (
    <div className="eb-cart__subscription-total">
      {subscriptions.map((subscription) => {
        const billingPeriodLabels =
          billingPeriodMap[subscription.billing_period];

        return (
          <div key={subscription.key} className="subscription-item">
            <div className="subscription-item__title">
              <span className="label">
                {subscription.billing_interval ===
                subscription.subscription_length
                  ? __('Total', 'edwiser-bridge-pro')
                  : subscription.billing_interval === 1
                  ? /* translators: %s = billing period adjective (e.g. "Monthly", "Yearly") */
                    sprintf(
                      __('%s recurring total', 'edwiser-bridge-pro'),
                      billingPeriodLabels.adjective
                    )
                  : /* translators: %1$d = interval, %2$s = billing period (e.g. "months") */
                    sprintf(
                      __(
                        'Recurring total every %1$d %2$s',
                        'edwiser-bridge-pro'
                      ),
                      subscription.billing_interval,
                      _n(
                        billingPeriodLabels.singular,
                        billingPeriodLabels.plural,
                        subscription.billing_interval,
                        'edwiser-bridge-pro'
                      )
                    )}
              </span>

              <div className="value">
                {formatTotalPrice(subscription.totals)}
              </div>
            </div>
            <div className="subscription-item__duration">
              <span className="label">
                {__('Starting:', 'edwiser-bridge-pro')}{' '}
                {subscription.next_payment_date}
              </span>
              {subscription.subscription_length > 0 && (
                <div className="value">
                  {/* translators: %1$d = subscription length (number), %2$s = billing period (e.g. month, week), pluralized based on length */}
                  {sprintf(
                    __('For %1$d %2$s', 'edwiser-bridge-pro'),
                    subscription.subscription_length,
                    _n(
                      billingPeriodLabels.singular,
                      billingPeriodLabels.plural,
                      subscription.subscription_length,
                      'edwiser-bridge-pro'
                    )
                  )}
                </div>
              )}
            </div>
            <Accordion
              variant="contained"
              className="subscription-total-accordion"
            >
              <Accordion.Item value="subscription">
                <Accordion.Control>
                  {__('Details', 'edwiser-bridge-pro')}
                </Accordion.Control>
                <Accordion.Panel>
                  <div className="sub-total">
                    <p className="label">
                      {__('Subtotal', 'edwiser-bridge-pro')}
                    </p>
                    <p className="price">
                      {__(
                        formatSubTotalPrice(subscription.totals, includesTax),
                        'edwiser-bridge-pro'
                      )}
                    </p>
                  </div>

                  {needsShipping && subscription?.shipping_rates.length > 0 && (
                    <div className="shipping">
                      <div>
                        <p className="label">
                          {__('Shipping', 'edwiser-bridge-pro')}
                        </p>
                        <p className="label-sm">
                          {/* translators: %s = selected shipping method name (e.g. "Flat Rate", "Free Shipping") */}
                          {sprintf(
                            __('via %s', 'edwiser-bridge-pro'),
                            subscription?.shipping_rates[0]?.shipping_rates.find(
                              (option) => option.selected === true
                            )?.name || ''
                          )}
                        </p>
                      </div>
                      <p className="price">
                        {parseFloat(subscription.totals.total_shipping) > 0
                          ? formatSubscriptionShippingPrices(
                              subscription.totals,
                              includesTax
                            )
                          : __('FREE', 'edwiser-bridge-pro')}
                      </p>
                    </div>
                  )}

                  {!includesTax && (
                    <div className="eb-cart__taxes">
                      {!singleTaxTotal &&
                      subscription.totals.tax_lines?.length > 0 ? (
                        subscription.totals.tax_lines.map((tax, index) => (
                          <div className="tax" key={index}>
                            <p className="label">
                              {__(tax.name, 'edwiser-bridge-pro')}
                            </p>
                            <p className="price">
                              {__(
                                formatTaxPrices(
                                  subscription.totals,
                                  'tax',
                                  index
                                ),
                                'edwiser-bridge-pro'
                              )}
                            </p>
                          </div>
                        ))
                      ) : (
                        <div className="tax">
                          <p className="label">
                            {__('Taxes', 'edwiser-bridge-pro')}
                          </p>
                          <p className="price">
                            {__(
                              formatTaxPrices(subscription.totals, 'total-tax'),
                              'edwiser-bridge-pro'
                            )}
                          </p>
                        </div>
                      )}
                    </div>
                  )}

                  <div className="total">
                    <p className="label">{__('Total', 'edwiser-bridge-pro')}</p>
                    <p className="price">
                      {__(
                        formatTotalPrice(subscription.totals),
                        'edwiser-bridge-pro'
                      )}
                    </p>
                  </div>
                </Accordion.Panel>
              </Accordion.Item>
            </Accordion>
          </div>
        );
      })}
    </div>
  );
}

export default SubscriptionTotal;

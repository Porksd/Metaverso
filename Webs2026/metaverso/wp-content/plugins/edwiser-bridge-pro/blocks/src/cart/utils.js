export const decodeHTMLEntities = (text) => {
  const textArea = document.createElement('textarea');

  textArea.innerHTML = text;
  return textArea.value;
};

export function formatCurrencyPrice(price, currencyConfig) {
  const {
    currency_minor_unit: minorUnit,
    currency_symbol: symbol,
    currency_suffix: suffix,
    currency_decimal_separator: decimalSeparator,
    currency_thousand_separator: thousandSeparator,
  } = currencyConfig;

  // Convert to a decimal number based on minor unit
  let priceValue = parseFloat(price) / Math.pow(10, minorUnit);
  if (isNaN(priceValue)) {
    priceValue = 0;
  }

  // Format the number with proper separators
  let [integerPart, decimalPart] = priceValue.toFixed(minorUnit).split('.');

  // Add thousand separators
  integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator);

  // Construct the formatted total
  const formattedPrice = decimalPart
    ? `${integerPart}${decimalSeparator}${decimalPart}`
    : integerPart;

  // Add prefix and suffix
  return `${symbol}${formattedPrice}${suffix}`;
}

export function formatItemPrice(prices, calculate, quantity = 1) {
  let price;

  if (calculate) {
    const calcType = calculate.toLowerCase();
    if (calcType === 'sale') {
      price = parseFloat(prices.sale_price);
    } else if (calcType === 'regular') {
      price = parseFloat(prices.regular_price);
    } else if (calcType === 'savings') {
      price = parseFloat(prices.regular_price) - parseFloat(prices.sale_price);
    } else {
      price = parseFloat(prices.price);
    }
  } else {
    price = parseFloat(prices.price);
  }

  // Apply quantity
  price = parseFloat(price) * quantity;

  return formatCurrencyPrice(price, prices);
}

export function formatItemTotalPrice(totals, includesTax) {
  const price = includesTax
    ? parseFloat(totals.line_subtotal) + parseFloat(totals.line_subtotal_tax)
    : parseFloat(totals.line_subtotal);

  return formatCurrencyPrice(price, totals);
}

export function formatSubTotalPrice(totals, includesTax) {
  const price = includesTax
    ? parseFloat(totals.total_items) + parseFloat(totals.total_items_tax)
    : parseFloat(totals.total_items);

  return formatCurrencyPrice(price, totals);
}

export function formatTotalPrice(totals) {
  return formatCurrencyPrice(parseFloat(totals.total_price), totals);
}

export function formatDiscountPrice(totals, includesTax) {
  const price = includesTax
    ? parseFloat(totals.total_discount) + parseFloat(totals.total_discount_tax)
    : parseFloat(totals.total_discount);

  return formatCurrencyPrice(price, totals);
}

export function formatTaxPrices(totals, calculate, taxIndex) {
  const price =
    calculate && calculate.toLowerCase() === 'total-tax'
      ? totals.total_tax
      : totals.tax_lines[taxIndex]?.price;

  return formatCurrencyPrice(price, totals);
}

export function formatShippingPrices(shippingOption, includesTax) {
  const price = includesTax
    ? parseFloat(shippingOption.price) + parseFloat(shippingOption.taxes)
    : parseFloat(shippingOption.price);

  return formatCurrencyPrice(price, shippingOption);
}

export function formatSubscriptionShippingPrices(shippingOption, includesTax) {
  const price = includesTax
    ? parseFloat(shippingOption.total_shipping) +
      parseFloat(shippingOption.total_shipping_tax)
    : parseFloat(shippingOption.total_shipping);

  return formatCurrencyPrice(price, shippingOption);
}

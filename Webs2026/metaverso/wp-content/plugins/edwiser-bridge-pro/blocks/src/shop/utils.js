export const decodeHTMLEntities = (text) => {
  const textArea = document.createElement('textarea');

  textArea.innerHTML = text;
  return textArea.value;
};

// default values for each parameter
const getDefaultValue = (paramName) => {
  switch (paramName) {
    case 'view':
      return 'grid';
    case 'sort_by':
      return 'default';
    case 'category':
      return 'all';
    case 'curr_page':
      return '1';
    default:
      return '';
  }
};

// update URL when filters change
export const updateQueryParams = (params) => {
  const searchParams = new URLSearchParams(window.location.search);

  Object.entries(params).forEach(([key, value]) => {
    if (value && value !== getDefaultValue(key)) {
      searchParams.set(key, String(value));
    } else {
      searchParams.delete(key);
    }
  });

  const newUrl = `${window.location.pathname}${
    searchParams.toString() ? '?' + searchParams.toString() : ''
  }`;
  window.history.pushState({ path: newUrl }, '', newUrl);
};

export const formatPrice = (total, calculate) => {
  const price =
    calculate && calculate.toLowerCase() === 'regular'
      ? total?.regular_price
      : calculate && calculate.toLowerCase() === 'sale'
      ? total?.sale_price
      : total?.price;
  const minorUnit = total?.currency_minor_unit;
  const symbol = total?.currency_symbol;
  const suffix = total?.currency_suffix;
  const decimalSeparator = total?.currency_decimal_separator;
  const thousandSeparator = total?.currency_thousand_separator;

  // Convert to a decimal number based on minor unit
  const priceValue = parseFloat(price) / Math.pow(10, minorUnit);

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
};

export const getQueryParams = () => {
  const searchParams = new URLSearchParams(window.location.search);

  return {
    view: searchParams.get('view'),
    sortBy: searchParams.get('sort_by'),
    category: searchParams.get('category') || 'all',
    currPage: parseInt(searchParams.get('curr_page'), 10) || 1,
  };
};

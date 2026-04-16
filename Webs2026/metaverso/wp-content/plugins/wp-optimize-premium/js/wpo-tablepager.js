/**
 * Creates a new instance of the WPO_TablePager.
 *
 * @param {Object} settings - Configuration settings for table pagination.
 * @returns {Object} - Methods to manage table pagination.
 */
WPO_TablePager = function(settings) {
	var defaults = {
		size: 10,
		page: 0,
		totalRows: 0,
		totalPages: 0,
		container: null,
		cssNext: '.next',
		cssPrev: '.prev',
		cssFirst: '.first',
		cssLast: '.last',
		cssPageDisplay: '.pagedisplay',
		cssPageDisplayCount: '.pagedisplay-count',
		cssPageSize: '.pagesize',
		separator: '/',
		positionFixed: true,
		dataSource: null,
	};

	var config = Object.assign({}, defaults, settings);
	var table = settings.table;

	/**
	 * Initializes the pager and sets up event listeners.
	 *
	 * @return {void}
	 */
	function init() {
		config.size = parseInt(document.querySelector(config.cssPageSize).value);

		document.querySelector(config.cssPageDisplay).addEventListener('change', function (e) {
			e.preventDefault();
			var input = e.target;
			if ('' === input.value || isNaN(parseInt(input.value))) return;

			config.page = parseInt(input.value) - 1;
			moveToPage();
		});

		document.querySelector(config.cssFirst).addEventListener('click', function (e) {
			e.preventDefault();
			moveToFirstPage();
		});

		document.querySelector(config.cssPrev).addEventListener('click', function (e) {
			e.preventDefault();
			moveToPrevPage();
		});

		document.querySelector(config.cssNext).addEventListener('click', function (e) {
			e.preventDefault();
			moveToNextPage();
		});

		document.querySelector(config.cssLast).addEventListener('click', function (e) {
			e.preventDefault();
			moveToLastPage();
		});

		document.querySelector(config.cssPageSize).addEventListener('change', function (e) {
			e.preventDefault();
			setPageSize(parseInt(e.target.value));
		});
	}

	/**
	 * Updates the page display with the current page number.
	 *
	 * @return {void}
	 */
	function updatePageDisplay() {
		document.querySelector(config.cssPageDisplay).value = config.page + 1;
		document.querySelector(config.cssPageDisplayCount).textContent = [config.separator, config.totalPages].join('');
	}

	/**
	 * Sets the page size and recalculates total pages.
	 *
	 * @param {number} size - The number of rows to display per page.
	 * @return {void}
	 */
	function setPageSize(size) {
		config.size = size;
		config.totalPages = Math.ceil(config.totalRows / config.size);
		config.pagerPositionSet = false;
		moveToPage();
		fixPosition();
	}

	/**
	 * Fixes the position of the pager.
	 *
	 * @return {void}
	 */
	function fixPosition() {
		if (!config.pagerPositionSet && config.positionFixed) {
			var offsetTop = table.getBoundingClientRect().top + window.scrollY;
			var tableHeight = table.offsetHeight;
			config.container.style.top = [(offsetTop + tableHeight), 'px'].join('');
			config.container.style.position = 'absolute';
			config.pagerPositionSet = true;
		}
	}

	/**
	 * Moves to the first page.
	 *
	 * @return {void}
	 */
	function moveToFirstPage() {
		config.page = 0;
		moveToPage();
	}

	/**
	 * Moves to the last page.
	 *
	 * @return {void}
	 */
	function moveToLastPage() {
		config.page = config.totalPages - 1;
		moveToPage();
	}

	/**
	 * Moves to the next page.
	 *
	 * @return {void}
	 */
	function moveToNextPage() {
		config.page++;
		moveToPage();
	}

	/**
	 * Moves to the previous page.
	 *
	 * @return {void}
	 */
	function moveToPrevPage() {
		config.page--;
		moveToPage();
	}

	/**
	 * Switches to the page defined in config and renders the table.
	 *
	 * @return {void}
	 */
	function moveToPage() {
		validatePage();

		if (hasDataSource()) {
			loadTableData();
		} else {
			renderTable(config.rowsCopy);
		}
	}

	/**
	 * Checks if the page defined in config has a valid value and change it if need
	 * 
	 * @return {void}
	 */
	function validatePage() {
		if (config.page >= config.totalPages) {
			config.page = config.totalPages - 1;
		}
		if (config.page < 0) {
			config.page = 0;
		}
	}

	/**
	 * Clears the table body content.
	 *
	 * @return {void}
	 */
	function clearTableBody() {
		table.tBodies[0].innerHTML = '';
	}

	/**
	 * Renders the table content based on the provided rows.
	 *
	 * @param {Array} rows - Array of table rows to render.
	 * @return {void}
	 */
	function renderTable(rows) {
		var tableBody = table.tBodies[0];
		clearTableBody();

		if (hasDataSource()) {
			rows.data.forEach(function (row) {
				var id_key = rows.hasOwnProperty('id_key') ? rows.id_key : "ID";
				tableBody.appendChild(renderRow(id_key, row, rows.columns));
			});
		} else {
			var start = config.page * config.size;
			var end = Math.min(start + config.size, rows.length);
			for (var i = start; i < end; i++) {
				rows[i].forEach(function (cell) { tableBody.appendChild(cell) });
			}
		}

		fixPosition();
		if (config.page >= config.totalPages && config.totalPages > 0) {
			moveToLastPage();
		}

		updatePageDisplay();
	}

	/**
	 * Checks if a data source is defined.
	 *
	 * @return {boolean} True if a data source is defined, false otherwise.
	 */
	function hasDataSource() {
		return 'object' === typeof config.dataSource && config.dataSource.hasOwnProperty('fetch');
	}

	/**
	 * Loads table data from the defined data source.
	 *
	 * @return {void}
	 */
	function loadTableData() {
		table.dispatchEvent(new CustomEvent("load_start", {}));
		config.dataSource
			.fetch(config.page * config.size, config.size)
			.then(function (response) {
				try {
					response = JSON.parse(response);
				} catch (e) {
					alert(wpoptimize.error_unexpected_response);
					return;
				}

				if (response && response.errors && response.errors.length) {
					alert(wpoptimize.error_unexpected_response);
					return;
				} else {
					table.dispatchEvent(new CustomEvent("load_end", {
						detail: {
							response: response
						}
					}));

					config.totalRows = parseInt(response.result.total);
					config.totalPages = Math.ceil(config.totalRows / config.size);
					renderTable(response.result);
				}
			})
			.catch(function () {
				alert(wpoptimize.error_unexpected_response);
			});
	}

	/**
	 * Renders an HTML row based on data object.
	 *
	 * @param {string} id_key - The key for the row ID.
	 * @param {Object} data - The data object for the row.
	 * @param {Object} columns - The columns definition.
	 * @return {HTMLElement} The table row element.
	 */
	function renderRow(id_key, data, columns) {
		var row = document.createElement('tr');
		var checkboxCell = document.createElement('td');
		checkboxCell.innerHTML = ['<input type="checkbox" value="', data[id_key], '">'].join('');
		row.appendChild(checkboxCell);

		for (var i in columns) {
			if (columns.hasOwnProperty(i)) {
				var cell = document.createElement('td');
				if (data.hasOwnProperty(i)) {
					if ('object' === typeof data[i]) {
						cell.innerHTML = ['<a href="', data[i].url, '" target="_blank">', data[i].text, '</a>'].join('');
					} else {
						var txt = document.createElement("textarea");
						txt.innerHTML = data[i];
						cell.textContent = txt.value
					}
				} else {
					cell.textContent = '';
				}
				row.appendChild(cell);
			}
		}

		return row;
	}

	init();

	return {
		loadTableData: loadTableData
	}
};

/**
 * Defines method fetch(offset, limit) to load data by pages.
 *
 * @param options
 *
 * @return {{set_option: set_option, fetch: fetch}}
 */
WPO_Table_DataSource = function(options) {

	/**
	 * Set option.
	 *
	 * @param option
	 * @param value
	 *
	 * @return {void}
	 */
	function set_option(option, value) {
		options[option] = value;
	}

	/**
	 * Call ajax preview command and return deferred object.
	 *
	 * @param {integer} offset
	 * @param {integer} limit
	 *
	 * @return {JSON}
	 */
	function fetch(offset, limit) {
		options.offset = 'undefined' == typeof offset ? options.offset : offset;
		options.limit = 'undefined' == typeof limit ? options.limit : limit;

		return wp_optimize.send_command('preview', options);
	}

	return {
		set_option: set_option,
		fetch: fetch
	}
}
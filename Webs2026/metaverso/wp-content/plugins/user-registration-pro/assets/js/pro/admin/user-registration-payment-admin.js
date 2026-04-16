(function ($) {
	var UR_Payment_Admin = {
		paymentFields: Object(),

		init: function () {
			// Save payment condition to $_POST
			$(document).on(
				"user_registration_admin_before_form_submit",
				function (event, data) {
					var paypal_conditional_settings =
						UR_Payment_Admin.save_conditional_logic_settings();
					var invalid_currency =
						UR_Payment_Admin.check_supported_currency();
					if (
						typeof invalid_currency !== "undefined" &&
						invalid_currency.length > 0
					) {
						data.data["ur_invalid_currency_status"] =
							invalid_currency;
					}
					if (paypal_conditional_settings.length > 0) {
						data.data["ur_paypal_conditional_integration"] =
							paypal_conditional_settings;
					}
				}
			);

			$("#user_registration_enable_paypal_standard_subscription")
				.on("change", function () {
					var enable_subscription = $(
						"#user_registration_enable_paypal_standard_subscription"
					);
					var plan_name = $("#user_registration_paypal_plan_name");
					var interval_count = $(
						"#user_registration_paypal_interval_count"
					);
					var recurring_period = $(
						"#user_registration_paypal_recurring_period"
					);
					if (enable_subscription.prop("checked")) {
						plan_name.parent().parent().show();
						interval_count.parent().parent().show();
						recurring_period.parent().show();
					} else {
						plan_name.parent().parent().hide();
						interval_count.parent().parent().hide();
						recurring_period.parent().hide();
					}
				})
				.trigger("change");

			UR_Payment_Admin.init_payment_field_triggers();
		},
		/**
		 * save conditional logic from form builder
		 */
		save_conditional_logic_settings: function () {
			var paypal_conditional_settings = new Array();
			var conditional_logic_element = $(
				"div[data-source='paypal']"
			).closest(".ur_conditional_logic_container");

			var form_fields = $(conditional_logic_element).find(
				".ur-conditional-wrapper"
			);

			var enable_conditional_logic = $(conditional_logic_element)
				.find("#ur_use_conditional_logic")
				.val();
			var enable_conditional_logic = $(conditional_logic_element)
				.find("#ur_use_conditional_logic")
				.is(":checked");
			var conditional_logic_data = {};
			$.each(form_fields, function (key, field) {
				conditional_logic_data["conditional_field"] = $(this)
					.find(".ur_conditional_field")
					.val();
				conditional_logic_data["conditional_operator"] = $(this)
					.find(".ur-conditional-condition")
					.val();
				conditional_logic_data["conditional_value"] = $(this)
					.find(".ur-conditional-input")
					.val();
			});
			var integration = {
				enable_conditional_logic: enable_conditional_logic,
				conditional_logic_data: conditional_logic_data,
			};
			paypal_conditional_settings.push(integration);
			return paypal_conditional_settings;
		},

		/**
		 * Initialize triggers for payment fields.
		 */
		init_payment_field_triggers: function () {
			UR_Payment_Admin.init_quantity_fields();
			UR_Payment_Admin.sanitize_price_inputs();
			UR_Payment_Admin.sanitize_inputs();
		},

		/**
		 * Initialize events on the quantity fields.
		 */
		init_quantity_fields: function () {
			// Rerender payment field options everytime target field is clicked.
			$(document).on("focus", ".ur-settings-target_field", function () {
				UR_Payment_Admin.get_payment_fields();
				UR_Payment_Admin.render_payment_fields($(this));
			});
		},
		/**
		 * Render payment field options for quantity field target setting.
		 */
		render_payment_fields: function (targetField) {
			var wrapper = $(".ur-item-active .ur-advance-setting-block");

			var selectedOption = wrapper
				.find(".ur-settings-target_field")
				.val();

			targetField.empty();

			targetField.append(
				$("<option></option>")
					.attr("value", "")
					.text(ur_payment_params.select_field_text)
			);
			var all_selected_target_fields =
				this.get_selected_all_target_fields();

			$.each(UR_Payment_Admin.paymentFields, function (value, key) {
				if ($.inArray(value, all_selected_target_fields) == -1) {
					targetField.append(
						$("<option></option>").attr("value", value).text(key)
					);
				} else {
					targetField.append(
						$("<option class='disabled-option'></option>")
							.attr("value", value)
							.text(key)
							.attr("disabled", "disabled")
					);
					targetField
						.find(".disabled-option")
						.css("background-color", "#ccc");
				}
			});

			targetField
				.find("option[value='" + selectedOption + "']")
				.attr("selected", "selected")
				.removeAttr("disabled");

			targetField
				.find("option[value='" + selectedOption + "']")
				.attr("selected", "selected")
				.css("background-color", "transparent");

			var selectFieldHidden = wrapper.find(
				"select.ur-settings-target_field"
			);

			selectFieldHidden.html(targetField.html()).val(targetField.val());

			selectFieldHidden.find(":selected").attr("selected", "selected");

			targetField.on("change", function () {
				selectedOption = $(this).val();

				UR_Payment_Admin.render_payment_fields($(this));
			});
		},
		get_selected_all_target_fields: function () {
			var wrapper = $(".ur-selected-item .ur-advance-setting-block");
			var selectedTargetFields = [];

			if (wrapper.length) {
				wrapper.each(function () {
					var selectedOption = $(this)
						.find(".ur-settings-target_field")
						.val();

					if (
						$.inArray(selectedOption, selectedTargetFields) == -1 &&
						selectedOption !== undefined &&
						selectedOption !== ""
					) {
						selectedTargetFields.push(selectedOption);
					}
				});
			}
			return selectedTargetFields;
		},
		/**
		 * Scans the form builder for payment fields and generate PaymentFields object.
		 */
		get_payment_fields: function () {
			var paymentItems = $(".ur-input-grids").find(
				".ur-input-type-single-item, .ur-input-type-multiple_choice, .ur-payment-enabled-slider"
			);

			UR_Payment_Admin.paymentFields = {};

			if (paymentItems.length) {
				paymentItems.each(function () {
					var fieldName = $(this)
						.parent()
						.find(".ur-general-setting-block")
						.find("input[data-field='field_name'")
						.val();

					UR_Payment_Admin.paymentFields[fieldName] = $(this)
						.find(".ur-label label")
						.text();
				});
			}
			return UR_Payment_Admin.paymentFields;
		},

		/**
		 * Prevent negative or invalid input in price field.
		 */
		sanitize_price_inputs: function () {
			$(document).on(
				"input",
				".ur-price-input, .ur-type-checkbox-money-input, .ur-selling-price-input, .ur-checkbox-selling-price-input",
				function (e) {
					var $this = $(this);
					var amount = $this.val();
					amount = amount.replace(/[^0-9.]/g, "");
					$this.val(amount);
				}
			);

			$(document).on(
				"focusout",
				".ur-price-input, .ur-type-checkbox-money-input, .ur-selling-price-input, .ur-checkbox-selling-price-input",
				function (e) {
					var amount = $(this).val();

					if (isNaN(amount) || "" === amount) {
						amount = "0.00";
					}

					$(this).val(amount);
				}
			);

			$(document).on("input", ".ur-selling-price-input", function (e) {
				var regular_price = $(this)
					.closest(".ur-toggle-content")
					.find(".ur-price-input");
				UR_Payment_Admin.compare_price_value($(this), regular_price);
			});

			$(document).on("input", ".ur-price-input", function (e) {
				var selling_price = $(this)
					.closest(".ur-toggle-content")
					.find(".ur-selling-price-input");
				UR_Payment_Admin.compare_price_value(selling_price, $(this));
			});

			$(document).on(
				"input",
				".ur-checkbox-selling-price-input",
				function (e) {
					var regular_price = $(this)
						.closest("li.ur-multiple-choice ")
						.find(".ur-type-checkbox-money-input");
					UR_Payment_Admin.compare_price_value(
						$(this),
						regular_price
					);
				}
			);

			$(document).on(
				"input",
				".ur-type-checkbox-money-input",
				function (e) {
					var selling_price = $(this)
						.closest("li.ur-multiple-choice ")
						.find(".ur-checkbox-selling-price-input");
					UR_Payment_Admin.compare_price_value(
						selling_price,
						$(this)
					);
				}
			);
		},

		/**
		 * Prevent invalid inputs for fields.
		 */
		sanitize_inputs: function () {
			$(document).on(
				"input",
				"#user_registration_paypal_interval_count",
				function () {
					var $this = $(this);
					var inputValue = $this.val();
					inputValue = inputValue.replace(/[^0-9]/g, "");
					inputValue = parseInt(inputValue); // Remove prefixing zeros(0).

					if (isNaN(inputValue) || 0 === inputValue) {
						inputValue = 1;
					}

					$this.val(inputValue);
				}
			);
		},

		/**
		 * Check supported currency by paypal.
		 */
		check_supported_currency: function () {
			var validation_status = [];
			var paypal = $("#user_registration_enable_paypal_standard");
			var stripe = $("#user_registration_enable_stripe");
			if (paypal.is(":checked") && !stripe.is(":checked")) {
				var is_invalid = ur_payment_params.is_valid_currency.is_invalid;
				var currency_code =
					ur_payment_params.is_valid_currency.currency;
				var message = ur_payment_params.invalid_currency_message;
				invalid_message = message.replace("%CODE%", currency_code);
				if (is_invalid) {
					var status_setting = {
						validation_status: false,
						validation_message: invalid_message,
					};
					validation_status.push(status_setting);
					return validation_status;
				}
			}
		},
		compare_price_value: function ($selling_div, $regular_div) {
			var selling_price = parseInt($selling_div.val()),
				regular_price = parseInt($regular_div.val());
			$selling_div.tooltipster({
				trigger: "custom",
				triggerOpen: {
					keyup: true,
				},
				triggerClose: {
					keyup: true,
				},
				content:
					ur_payment_params.compare_selling_regular_price_message,
				multiple: true,
				theme: "ur-selling-price-tooltip",
			});

			// Show the tooltip immediately.
			if (selling_price > regular_price) {
				$selling_div.tooltipster("open");
				$selling_div.css("border-color", "#f99494");
			} else {
				$selling_div.tooltipster("close");
				$selling_div.css("border-color", "#e1e1e1");
			}
		},
	};

	$(document).ready(function () {
		UR_Payment_Admin.init();
	});
})(jQuery);

<?php

namespace Objectiv\Plugins\Checkout\Managers;

use _WP_Dependency;
use function WordpressEnqueueChunksPlugin\get as cfwChunkedScriptsConfigGet;

/**
 * Manage stylesheet and script assets
 *
 * @link checkoutwc.com
 * @since 5.0.0
 * @package Objectiv\Plugins\Checkout\Managers
 */
class AssetManager {
	protected $front;
	protected $version;
	protected $manifest;

	public function __construct() {
		$this->front = trailingslashit( CFW_PATH_ASSETS );

		// Version extension
		$this->version = CFW_VERSION;

		$this->manifest = cfwChunkedScriptsConfigGet( 'manifest' );
	}

	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'set_global_assets' ) );
		add_action( 'wp_enqueue_scripts', array(
			$this,
			'set_cfw_page_assets'
		), 16 ); // 16 is 1 after 15, which is where Divi loads their stuff. It's also after 6, which is where WooCommerce loads their stuff.
	}

	public function enqueue_style( $handle, $src, $ver = null ) {
		if ( is_rtl() ) {
			$src    = str_replace( '.css', '-rtl.css', $src );
			$handle = "{$handle}_rtl";
		}

		wp_enqueue_style( $handle, $src, array(), $ver ?? CFW_VERSION );
	}

	public function set_global_assets() {
		if ( is_cfw_page() ) {
			return;
		}

		if ( PlanManager::can_access_feature( 'enable_side_cart' ) ) {
			cfw_register_chunked_scripts( array( 'side-cart' ) );
			$this->enqueue_style( 'cfw_grid_css', "{$this->front}/{$this->manifest['chunks']['cfw-grid']['file']}", $this->manifest['chunks']['cfw-grid']['hash'] );
			$this->enqueue_style( 'cfw_side_cart_css', "{$this->front}/{$this->manifest['chunks']['side-cart-styles']['file']}", $this->manifest['chunks']['side-cart-styles']['hash'] );

			wp_enqueue_script( 'cfw-side-cart' );

			wp_localize_script(
				'cfw-side-cart',
				'cfwEventData',
				$this->get_data()
			);

			$this->set_api_info_object( 'cfw-side-cart' );
		}
	}

	public function set_api_info_object( string $handle ) {
		wp_localize_script( $handle, 'wpApiSettings', array(
			'root' => esc_url_raw( rest_url() ),
			'nonce' => wp_create_nonce( 'wp_rest' )
		) );

	}

	public function set_cfw_page_assets() {
		if ( ! is_cfw_page() ) {
			return;
		}

		$google_api_key = SettingsManager::instance()->get_setting( 'google_places_api_key' );

		/**
		 * Dequeue Native Scripts
		 */
		// Many plugins enqueue their scripts with 'woocommerce' and 'wc-checkout' as a dependent scripts
		// So, instead of modifying these scripts we dequeue WC's native scripts and then
		// queue our own scripts using the same handles. Magic!

		// Don't load our scripts when the form has been replaced.
		// This works because WP won't let you replace registered scripts
		/** This filter is documented in templates/default/content.php */
		if ( apply_filters( 'cfw_replace_form', false ) === false ) { // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment
			wp_dequeue_script( 'woocommerce' );
			wp_deregister_script( 'woocommerce' );
			wp_dequeue_script( 'wc-checkout' );
			wp_deregister_script( 'wc-checkout' );
			wp_dequeue_style( 'woocommerce-general' );
			wp_dequeue_style( 'woocommerce-layout' );
			wp_deregister_script( 'selectWoo' );
			wp_deregister_script( 'select2' );
			wp_dequeue_style( 'select2' );
			wp_deregister_style( 'select2' );
		}

		/**
		 * Dequeue Emojis
		 */
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );

		$this->enqueue_style( 'cfw_grid_css', "{$this->front}/{$this->manifest['chunks']['cfw-grid']['file']}", $this->manifest['chunks']['cfw-grid']['hash'] );

		if ( cfw_is_checkout() ) {
			cfw_register_chunked_scripts( array( 'checkout' ) );

			wp_enqueue_script( 'woocommerce' );

			if ( isset( $this->manifest['chunks']['checkout-styles']['file'] ) ) {
				$this->enqueue_style( 'cfw_front_css', "{$this->front}/{$this->manifest['chunks']['checkout-styles']['file']}", $this->manifest['chunks']['checkout-styles']['hash'] );
			}
		} elseif ( is_checkout_pay_page() ) {
			cfw_register_chunked_scripts( array( 'order-pay' ) );

			wp_enqueue_script( 'woocommerce' );

			if ( isset( $this->manifest['chunks']['order-pay-styles']['file'] ) ) {
				$this->enqueue_style( 'cfw_front_css', "{$this->front}/{$this->manifest['chunks']['order-pay-styles']['file']}", $this->manifest['chunks']['order-pay-styles']['hash']  );
			}
		} elseif ( is_order_received_page() ) {
			cfw_register_chunked_scripts( array( 'thank-you' ) );

			wp_enqueue_script( 'woocommerce' );

			if ( isset( $this->manifest['chunks']['thank-you-styles']['file'] ) ) {
				$this->enqueue_style( 'cfw_front_css', "{$this->front}/{$this->manifest['chunks']['thank-you-styles']['file']}", $this->manifest['chunks']['thank-you-styles']['hash'] );
			}

			wp_enqueue_style( 'cfw-fontawesome', 'https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css', array(), '4.7.0' );

			if ( PlanManager::can_access_feature( 'enable_map_embed' ) && PlanManager::can_access_feature( 'enable_thank_you_page' ) ) {
				wp_enqueue_script( 'cfw-google-places', "https://maps.googleapis.com/maps/api/js?key=$google_api_key&callback=cfw_google_maps_loaded", array( 'woocommerce' ), CFW_VERSION, true );
			}
		}

		/**
		 * Fires after script setup
		 *
		 * @since 5.0.0
		 */
		do_action( 'cfw_enqueue_scripts' );

		/**
		 * Fires to trigger Templates to load their assets
		 *
		 * @since 3.0.0
		 */
		do_action( 'cfw_load_template_assets' );

		$cfw_event_data = $this->get_data();

		if ( is_order_received_page() ) {
			$order = cfw_get_order_received_order();

			if ( $order ) {
				/**
				 * Filter thank you page map address
				 *
				 * @param array $address The address for the map
				 * @param \WC_Order $order The order
				 *
				 * @since 5.3.9
				 */
				$address = apply_filters( 'cfw_thank_you_page_map_address', $order->get_address( 'shipping' ), $order );

				// Remove name and company before generating the Google Maps URL.
				unset( $address['first_name'], $address['last_name'], $address['company'] );

				// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment
				$address = apply_filters( 'woocommerce_shipping_address_map_url_parts', $address, $order );
				// phpcs:enable WooCommerce.Commenting.CommentHooks.MissingHookComment

				$address = array_filter( $address );
				$address = implode( ', ', $address );

				$cfw_event_data['settings']['thank_you_shipping_address'] = $address;
			}
		}

		wp_localize_script(
			'woocommerce',
			'cfwEventData',
			$cfw_event_data
		);

		$this->set_api_info_object( 'woocommerce' );

		// Some plugins (WooCommerce Square for example?) want to use wc_cart_fragments_params on the checkout page
		wp_localize_script(
			'woocommerce',
			'wc_cart_fragments_params',
			array(
				'ajax_url'    => WC()->ajax_url(),
				'wc_ajax_url' => \WC_AJAX::get_endpoint( '%%endpoint%%' ),
			)
		);

		// Used by our copy of address-i18n.js which is called AddressInternationalizationService
		wp_localize_script(
			'woocommerce',
			'wc_address_i18n_params',
			array(
				'locale'             => wp_json_encode( WC()->countries->get_country_locale() ),
				'locale_fields'      => wp_json_encode( WC()->countries->get_country_locale_field_selectors() ),
				'i18n_required_text' => cfw_esc_attr__( 'required', 'woocommerce' ),
				'i18n_optional_text' => cfw_esc_html__( 'optional', 'woocommerce' ),
			)
		);

		if ( cfw_is_checkout() || cfw_is_checkout_pay_page() ) {
			global $wp_scripts;
			$wp_scripts->registered['wc-country-select']->deps = array( 'jquery' );

			if ( isset( $this->manifest['chunks']['selectwoo']['file'] ) ) {
				wp_enqueue_script( 'selectWoo', "{$this->front}/{$this->manifest['chunks']['selectwoo']['file']}", array( 'jquery' ), CFW_VERSION, true );
				$this->nuke_script( 'select2' );
				$this->nuke_script( 'selectWoo_js' ); // Coderockz what are you doing?
			}

			if ( isset( $this->manifest['chunks']['selectwoo-styles']['file'] ) ) {
				wp_enqueue_style( 'select2', "{$this->front}/{$this->manifest['chunks']['selectwoo-styles']['file']}", array(), $this->manifest['chunks']['selectwoo-styles']['hash'] );
			}

			global $wp_styles;

			wp_enqueue_script( 'wc-country-select' );
			wp_dequeue_script( 'wc-address-i18n' );

			// Make sure that we don't load the same script twice
			if ( wp_script_is( 'select2', 'enqueued' ) ) {
				wp_dequeue_script( 'selectWoo' );
			}
		}
	}

	public function nuke_script( $handle_to_be_nuked ): bool {
		$wp_scripts = wp_scripts();
		$result     = false;

		/**
		 * Go through scripts and unregister if they are dependencies of the script we want to nuke
		 *
		 * @var string $handle
		 * @var _WP_Dependency $script
		 */
		foreach ( $wp_scripts->registered as $handle => $script ) {
			$key = array_search( $handle_to_be_nuked, $script->deps, true );

			if ( false !== $key ) {
				unset( $wp_scripts->registered[ $handle ]->deps[ $key ] );
				$result = true;
			}
		}

		$key = array_search( $handle_to_be_nuked, $wp_scripts->queue, true );

		if ( false !== $key ) {
			unset( $wp_scripts->queue[ $key ] );
			$result = true;
		}

		return $result;
	}

	public function get_parsley_locale() {
		$raw_locale = determine_locale();

		// Handle special raw locale cases
		switch ( $raw_locale ) {
			case 'pt_BR':
				$locale = 'pt-br';
				break;
			case 'pt_PT':
			case 'pt_AO':
				$locale = 'pt-pt';
				break;
			default:
				$locale = defined( 'ICL_LANGUAGE_CODE' ) ? ICL_LANGUAGE_CODE : strstr( $raw_locale, '_', true );
		}

		// Handle special locale cases
		switch ( $locale ) {
			case 'nb':
			case 'nn':
				$locale = 'no';
		}

		// Fallback to the raw locale
		if ( ! $locale ) {
			$locale = $raw_locale;
		}

		/**
		 * Filter Parsley validation service locale
		 *
		 * @param string $locale Parsley validation service locale
		 *
		 * @since 3.0.0
		 *
		 */
		return apply_filters( 'cfw_parsley_locale', $locale );
	}

	protected function get_data() {
		$shipping_countries = WC()->countries->get_shipping_countries();
		unset( $shipping_countries['shim'] );

		$allowed_countries = WC()->countries->get_allowed_countries();
		unset( $allowed_countries['shim'] );

		/**
		 * Filter cfw_event_data array
		 *
		 * Localized data available via DataService
		 *
		 * @param array $cfw_event_data The data
		 *
		 * @since 1.0.0
		 *
		 */
		return apply_filters(
			'cfw_event_data',
			array(
				'elements'        => array(
					/**
					 * Filter breadcrumb element ID
					 *
					 * @param string $breadCrumbElId Breadcrumb element ID
					 *
					 * @since 1.0.0
					 *
					 */
					'breadCrumbElId'       => apply_filters( 'cfw_template_breadcrumb_id', '#cfw-breadcrumb' ),

					/**
					 * Filter customer info tab ID
					 *
					 * @param string $customerInfoElId Customer info tab ID
					 *
					 * @since 1.0.0
					 *
					 */
					'customerInfoElId'     => apply_filters( 'cfw_template_customer_info_el', '#cfw-customer-info' ),

					/**
					 * Filter shipping method tab ID
					 *
					 * @param string $shippingMethodElId Shipping method tab ID
					 *
					 * @since 1.0.0
					 *
					 */
					'shippingMethodElId'   => apply_filters( 'cfw_template_shipping_method_el', '#cfw-shipping-method' ),

					/**
					 * Filter payment method tab ID
					 *
					 * @param string $paymentMethodElId Payment method tab ID
					 *
					 * @since 1.0.0
					 *
					 */
					'paymentMethodElId'    => apply_filters( 'cfw_template_payment_method_el', '#cfw-payment-method' ),

					/**
					 * Filter tab container element ID
					 *
					 * @param string $tabContainerElId Tab container element ID
					 *
					 * @since 1.0.0
					 *
					 */
					'tabContainerElId'     => apply_filters( 'cfw_template_tab_container_el', '#cfw' ),

					/**
					 * Filter alert container element ID
					 *
					 * @param string $alertContainerId Alert container element ID
					 *
					 * @since 1.0.0
					 *
					 */
					'alertContainerId'     => apply_filters( 'cfw_template_alert_container_el', '#cfw-alert-container' ),

					/**
					 * Filter checkout form selector
					 *
					 * @param string $checkoutFormSelector Checkout form selector
					 *
					 * @since 1.0.0
					 *
					 */
					'checkoutFormSelector' => apply_filters( 'cfw_checkout_form_selector', 'form.checkout' ),
				),
				/**
				 * Filter extra data available to JavaScript
				 *
				 * @since 8.0.0
				 *
				 */
				'data'   => apply_filters( 'cfw_checkout_data', array() ),

				/**
				 * Filter TypeScript compatibility classes and params
				 *
				 * @param array $compatibility TypeScript compatibility classes and params
				 *
				 * @since 3.0.0
				 *
				 */
				'compatibility'   => apply_filters( 'cfw_typescript_compatibility_classes_and_params', array() ),
				'settings'        => array(
					'base_country'                           => WC()->countries->get_base_country(),
					'parsley_locale'                         => $this->get_parsley_locale(),
					// required for parsley localization
					'user_logged_in'                         => is_user_logged_in(),
					'login_allowed_at_checkout'              => cfw_is_login_at_checkout_allowed(),

					/**
					 * Filter whether to validate required registration
					 *
					 * @param bool $validate_required_registration Validate required registration
					 *
					 * @since 3.0.0
					 *
					 */
					'validate_required_registration'         => apply_filters( 'cfw_validate_required_registration', true ),
					'default_address_fields'                 => array_keys( WC()->countries->get_default_address_fields() ),

					/**
					 * Filter whether to enable zip autocomplete
					 *
					 * @param bool $enable_zip_autocomplete Enable zip autocomplete
					 *
					 * @since 2.0.0
					 *
					 */
					'enable_zip_autocomplete'                => (bool) apply_filters( 'cfw_enable_zip_autocomplete', true ),

					/**
					 * Filter whether to enable field peristence with Garlic.js
					 *
					 * @param bool $cfw_enable_field_persistence Enable field persistence
					 *
					 * @since 7.1.10
					 *
					 */
					'enable_field_persistence'               => (bool) apply_filters( 'cfw_enable_field_persistence', true ),

					/**
					 * Filter whether to auto open the side cart on add to cart
					 *
					 * @param bool $disable_side_cart_auto_open Disable side cart auto open
					 *
					 * @since 7.1.5
					 *
					 */
					'disable_side_cart_auto_open'            => (bool) apply_filters( 'cfw_disable_side_cart_auto_open', SettingsManager::instance()->get_setting( 'shake_floating_cart_button' ) === 'yes' ),
					'enable_floating_cart_button'            => SettingsManager::instance()->get_setting( 'enable_floating_cart_button' ) === 'yes',

					/**
					 * Filter whether to check create account by default
					 *
					 * @param bool $check_create_account_by_default Check create account by default
					 *
					 * @since 3.0.0
					 *
					 */
					'check_create_account_by_default'        => (bool) apply_filters( 'cfw_check_create_account_by_default', true ),

					/**
					 * Filter whether to check whether an existing account matches provided email address
					 *
					 * @param bool $enable_account_exists_check Enable account exists check when billing email field changed
					 *
					 * @since 5.3.7
					 *
					 */
					'enable_account_exists_check'      => apply_filters( 'cfw_enable_account_exists_check', ! is_user_logged_in() ),
					'needs_shipping_address'           => WC()->cart && WC()->cart->needs_shipping_address(),
					'show_shipping_tab'                => cfw_show_shipping_tab(),
					'enable_map_embed'                 => PlanManager::can_access_feature( 'enable_map_embed' ),

					/**
					 * Filter whether to load tabs
					 *
					 * @param bool $load_tabs Load tabs
					 *
					 * @since 3.0.0
					 *
					 */
					'load_tabs'                              => apply_filters( 'cfw_load_tabs', cfw_is_checkout() ),
					'is_checkout_pay_page'                   => is_checkout_pay_page(),
					'is_order_received_page'                 => is_order_received_page(),

					/**
					 * Filter list of billing country restrictions for Google Maps address autocomplete
					 *
					 * @param array $address_autocomplete_billing_countries List of country restrictions for Google Maps address autocomplete
					 *
					 * @since 3.0.0
					 *
					 */
					'address_autocomplete_billing_countries' => apply_filters( 'cfw_address_autocomplete_billing_countries', array() ),
					'is_registration_required'               => WC()->checkout()->is_registration_required(),

					/**
					 * Filter whether to automatically generate password for new accounts
					 *
					 * @param bool $registration_generate_password Automatically generate password for new accounts
					 *
					 * @since 3.0.0
					 *
					 */
					'registration_generate_password'         => SettingsManager::instance()->get_setting( 'registration_style' ) !== 'woocommerce',
					'thank_you_shipping_address'             => false,
					'shipping_countries'                     => $shipping_countries,
					'allowed_countries'                      => $allowed_countries,

					/**
					 * Filter whether to enable discrete address 1 fields
					 *
					 * @param boolean $discrete_address_1_fields Whether to enable discrete address 1 fields
					 *
					 * @since 7.0.17
					 */
					'enable_discreet_address_1_fields'       => apply_filters( 'cfw_enable_discrete_address_1_fields', 'yes' === SettingsManager::instance()->get_setting( 'enable_discreet_address_1_fields' ) ),

					/**
					 * Filter whether to enable fullname field
					 *
					 * @param boolean $enable_fullname_field Whether to enable fullname field
					 *
					 * @since 7.0.17
					 */
					'use_fullname_field'                     => apply_filters( 'cfw_enable_fullname_field', 'yes' === SettingsManager::instance()->get_setting( 'use_fullname_field' ) ),

					/**
					 * Filter whether to automatically generate password for new accounts
					 *
					 * @param string $additional_side_cart_trigger_selectors CSS selector for additional side cart open buttons / links
					 *
					 * @since 5.4.0
					 *
					 */
					'additional_side_cart_trigger_selectors' => apply_filters( 'cfw_additional_side_cart_trigger_selectors', false ),

					/**
					 * Filter list of field persistence service excludes
					 *
					 * @param array $field_persistence_excludes List of field persistence service excludes
					 *
					 * @since 3.0.0
					 *
					 */
					'field_persistence_excludes'             => apply_filters(
						'cfw_field_data_persistence_excludes',
						array(
							'input[type="button"]',
							'input[type="file"]',
							'input[type="hidden"]',
							'input[type="submit"]',
							'input[type="reset"]',
							'#cfw-promo-code',
							'.cfw-create-account-checkbox',
							'input[name="payment_method"]',
							'input[name="paypal_pro-card-number"]',
							'input[name="paypal_pro-card-cvc"]',
							'input[name="wc-authorize-net-aim-account-number"]',
							'input[name="wc-authorize-net-aim-csc"]',
							'input[name="paypal_pro_payflow-card-number"]',
							'input[name="paypal_pro_payflow-card-cvc"]',
							'input[name="paytrace-card-number"]',
							'input[name="paytrace-card-cvc"]',
							'input[id="stripe-card-number"]',
							'input[id="stripe-card-cvc"]',
							'input[name="creditCard"]',
							'input[name="cvv"]',
							'input.wc-credit-card-form-card-number',
							'input[name="wc-authorize-net-cim-credit-card-account-number"]',
							'input[name="wc-authorize-net-cim-credit-card-csc"]',
							'input.wc-credit-card-form-card-cvc',
							'input.js-sv-wc-payment-gateway-credit-card-form-account-number',
							'input.js-sv-wc-payment-gateway-credit-card-form-csc',
							'input.shipping_method',
							'#order_comments',
							'input[name^="tocheckoutcw"]',
							'#_sumo_pp_enable_order_payment_plan',
							'.gift-certificate-show-form input',
							'.cfw_order_bump_check',
							'#shipping_fetchify_search',
							'#billing_fetchify_search',
							'[data-persist="false"]', // catch-all, used in cfw_form_field() for non-empty values
						)
					),
				),
				'messages'        => array(
					/**
					 * Filter the invalid phone number error message
					 *
					 * @param string $invalid_phone_number_message Invalid phone number error message
					 *
					 * @since 5.3.5
					 */
					'invalid_phone_message'                  => apply_filters( 'cfw_invalid_phone_validation_error_message', __( 'Please enter a valid phone number.', 'checkout-wc' ) ),

					/**
					 * Filter the invalid fullname error message
					 *
					 * @param string $invalid_fullname_message Invalid fullname error message
					 *
					 * @since 6.2.4
					 */
					'invalid_full_name_message'              => apply_filters( 'cfw_invalid_full_name_validation_error_message', __( 'Please enter your first and last name.', 'checkout-wc' ) ),
					'shipping_address_label'                 => __( 'Shipping address', 'checkout-wc' ),
					'quantity_prompt_message'                => __( 'Please enter a new quantity:', 'checkout-wc' ),
					'cvv_tooltip_message'                    => __( '3-digit security code usually found on the back of your card. American Express cards have a 4-digit code located on the front.', 'checkout-wc' ),
					'delete_confirm_message'                 => __( 'Are you sure you want to remove this item from your cart?', 'checkout-wc' ),
					'account_already_registered_notice'      => apply_filters( 'woocommerce_registration_error_email_exists', cfw__( 'An account is already registered with your email address. <a href="#" class="showlogin">Please log in.</a>', 'woocommerce' ), '' ),
					'generic_field_validation_error_message' => cfw__( '%s is a required field.', 'woocommerce' ),
					'view_cart'                              => cfw__( 'View cart', 'woocommerce' ),
					'update_checkout_error'                  => cfw__( 'There was a problem checking out. Please try again. If the problem persists, please get in touch with us so we can assist.', 'woocommerce' ),
					'invalid_postcode'                       => __( 'Please enter a valid postcode / ZIP.', 'checkout-wc' ),
					'pickup_label'                           => __( 'Pickup', 'checkout-wc' ),
					'update_cart_item_variation_button'      => cfw__( 'Update', 'woocommerce' ),
					'ok_button_label'                        => cfw__( 'Add to cart', 'woocommerce' ),
					'cancel_button_label'                    => cfw__( 'No thank you', 'checkout-wc' ),
				),
				'checkout_params' => array(
					'ajax_url'                  => WC()->ajax_url(),
					'wc_ajax_url'               => \WC_AJAX::get_endpoint( '%%endpoint%%' ),
					'update_order_review_nonce' => wp_create_nonce( 'update-order-review' ),
					'update_side_cart_nonce'    => wp_create_nonce( 'cfw-update-side-cart' ),
					'apply_coupon_nonce'        => wp_create_nonce( 'apply-coupon' ),
					'remove_coupon_nonce'       => wp_create_nonce( 'remove-coupon' ),
					'option_guest_checkout'     => get_option( 'woocommerce_enable_guest_checkout' ),
					'checkout_url'              => \WC_AJAX::get_endpoint( 'checkout' ),
					'is_checkout'               => is_checkout() && empty( $wp->query_vars['order-pay'] ) && ! isset( $wp->query_vars['order-received'] ) ? 1 : 0,
					'debug_mode'                => defined( 'WP_DEBUG' ) && WP_DEBUG,
					// phpcs:disable WordPress.Security.NonceVerification.Missing
					'cfw_debug_mode'            => isset( $_GET['cfw-debug'] ),
					// phpcs:enable WordPress.Security.NonceVerification.Missing
					'i18n_checkout_error'       => cfw_esc_attr__( 'Error processing checkout. Please try again.', 'woocommerce' ),
					'dist_path'                 => trailingslashit( CFW_PATH_ASSETS ),
					'is_rtl'					=> is_rtl(),

				),
				'runtime_params'  => array(
					'runtime_email_matched_user' => false, // default to false
				),
			)
		);

	}
}

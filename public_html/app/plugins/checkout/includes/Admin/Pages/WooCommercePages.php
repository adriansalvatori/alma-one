<?php

namespace Objectiv\Plugins\Checkout\Admin\Pages;

use Objectiv\Plugins\Checkout\Admin\TabNavigation;
use Objectiv\Plugins\Checkout\Admin\Pages\Traits\TabbedAdminPageTrait;
use Objectiv\Plugins\Checkout\Managers\PlanManager;
use Objectiv\Plugins\Checkout\Managers\SettingsManager;
use function WordpressEnqueueChunksPlugin\registerScripts as cfwRegisterChunkedScripts;

/**
 * @link checkoutwc.com
 * @since 5.0.0
 * @package Objectiv\Plugins\Checkout\Admin\Pages
 */
class WooCommercePages extends PageAbstract {
	use TabbedAdminPageTrait;

	public function __construct() {

		parent::__construct( cfw__( 'Pages', 'checkout-wc' ), 'manage_options', 'checkout' );
	}

	public function init() {
		parent::init();

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 1001 );

		$this->tabbed_navigation = new TabNavigation( 'Pages', 'subpage' );

		$this->tabbed_navigation->add_tab( 'Checkout', add_query_arg( array( 'subpage' => 'checkout' ), $this->get_url() ), 'checkout' );
		$this->tabbed_navigation->add_tab( 'Thank You', add_query_arg( array( 'subpage' => 'thankyou' ), $this->get_url() ), 'thankyou' );
		$this->tabbed_navigation->add_tab( 'Cart Summary', add_query_arg( array( 'subpage' => 'cartsummary' ), $this->get_url() ), 'cartsummary' );
		$this->tabbed_navigation->add_tab( 'Store Policies', add_query_arg( array( 'subpage' => 'store_policies' ), $this->get_url() ), 'store_policies' );
	}

	public function output() {
		if ( $this->get_current_tab() === false ) {
			$_GET['subpage'] = 'checkout';
		}

		$this->tabbed_navigation->display_tabs();

		$current_tab_function = $this->get_current_tab() === false ? 'checkout_tab' : $this->get_current_tab() . '_tab';
		$callable             = array( $this, $current_tab_function );

		if ( is_callable( $callable ) ) {
			call_user_func( $callable );
		}
	}

	public function checkout_tab() {
		$this->output_form_open();
		?>
		<div class="space-y-6">
			<?php cfw_admin_page_section( 'Steps', 'Control the checkout steps.', $this->get_steps_fields() ); ?>
			<?php cfw_admin_page_section( 'Login and Registration', 'Control how login and registration function on your checkout page.', $this->get_login_and_registration_fields() ); ?>
			<?php cfw_admin_page_section( 'Field Options', 'Control how different checkout fields appear.', $this->get_field_option_fields() ); ?>
			<?php cfw_admin_page_section( 'Address Options', 'Control address fields.', $this->get_address_options_fields() ); ?>
			<?php cfw_admin_page_section( 'Address Completion and Validation', 'Control some mobile only checkout behaviors.', $this->get_address_completion_and_validation_fields() ); ?>
			<?php cfw_admin_page_section( 'Mobile Options', 'Control mobile specific features.', $this->get_mobile_options_fields() ); ?>
			<?php cfw_admin_page_section( 'Order Pay', 'Enable CheckoutWC template for the Order Pay / Customer Payment Page endpoint.', $this->get_order_pay_settings() ); ?>
		</div>
		<?php
		$this->output_form_close();
	}

	public function store_policies_tab() {
		cfw_admin_page_section(
			cfw__( 'Store Policies', 'checkout-wc' ),
			cfw__( 'Store Policies are displayed as links in the footer of the checkout, order pay, and thank you pages. Clicking them displays the policy (page) in a modal window.', 'checkout-wc' ),
			$this->get_store_policies_output()
		);
	}

	public function get_store_policies_output() {
		ob_start();
		?>
		<div id="cfw-store-policies"></div>
		<?php
		return ob_get_clean();
	}

	protected function get_order_pay_settings() {
		ob_start();

		if ( ! PlanManager::has_premium_plan() ) {
			$notice = $this->get_upgrade_required_notice( PlanManager::get_english_list_of_required_plans_html() );
		}

		$this->output_checkbox_row(
			'enable_order_pay',
			cfw__( 'Enable Order Pay Page', 'checkout-wc' ),
			cfw__( 'Use CheckoutWC templates for Order Pay page.', 'checkout-wc' ),
			array(
				'enabled' => PlanManager::has_premium_plan(),
				'notice'  => $notice ?? '',
			)
		);

		return ob_get_clean();
	}

	public function thankyou_tab() {
		$this->output_form_open();
		?>
		<div class="space-y-6">
			<?php
			cfw_admin_page_section(
				'Thank You',
				'Control the Order Received / Thank You endpoint.',
				$this->get_thank_you_settings()
			);
			?>
		</div>
		<?php
		$this->output_form_close();
	}

	protected function get_thank_you_settings() {
		$settings                 = SettingsManager::instance();
		$thank_you_order_statuses = false === $settings->get_setting( 'thank_you_order_statuses' ) ? array() : (array) $settings->get_setting( 'thank_you_order_statuses' );

		ob_start();

		if ( ! PlanManager::has_premium_plan() ) {
			$notice = $this->get_upgrade_required_notice( PlanManager::get_english_list_of_required_plans_html() );
		}

		$this->output_checkbox_row(
			'enable_thank_you_page',
			cfw__( 'Enable Thank You Page Template', 'checkout-wc' ),
			cfw__( 'Enable thank you page / order received template.', 'checkout-wc' ),
			array(
				'enabled' => PlanManager::has_premium_plan(),
				'notice'  => $notice ?? '',
			)
		);

		$this->output_checkbox_group(
			'thank_you_order_statuses',
			cfw__( 'Order Statuses', 'checkout-wc' ),
			cfw__( 'Choose which Order Statuses are shown as a progress bar on the Thank You page.', 'checkout-wc' ),
			wc_get_order_statuses(),
			$thank_you_order_statuses,
			array(
				'enabled' => PlanManager::has_premium_plan(),
				'nested'  => true,
			)
		);

		$this->output_checkbox_row(
			'enable_map_embed',
			cfw__( 'Enable Map Embed', 'checkout-wc' ),
			cfw__( 'Enable or disable Google Maps embed on Thank You page. Requires Google API key.', 'checkout-wc' ),
			array(
				'enabled' => PlanManager::has_premium_plan(),
				'nested'  => true,
			)
		);

		$this->output_checkbox_row(
			'override_view_order_template',
			cfw__( 'Enable Thank You Page Template For Viewing Orders in My Account', 'checkout-wc' ),
			cfw__( 'When checked, viewing orders in My Account will use the Thank You page template.', 'checkout-wc' ),
			array(
				'enabled' => PlanManager::has_premium_plan(),
				'nested'  => true,
			)
		);

		return ob_get_clean();
	}

	public function cartsummary_tab() {
		$this->output_form_open();
		?>
		<div class="space-y-6">
			<?php
			cfw_admin_page_section(
				'Cart Summary',
				'Control the Cart Summary at checkout.',
				$this->get_cart_summary_settings()
			);

			/**
			 * Fires at the top of the cart summary admin page settings table inside <tbody>
			 *
			 * @since 7.0.0
			 *
			 * @param CartSummary $cart_summary_admin_page The cart summary admin page
			 */
			do_action( 'cfw_cart_summary_after_admin_page_settings', $this );
			?>
		</div>
		<?php
		$this->output_form_close();
	}

	/**
	 * @return string
	 */
	protected function get_cart_summary_settings() : string {
		ob_start();

		/**
		 * Fires at the top of the cart summary admin page settings table inside <tbody>
		 *
		 * @since 5.0.0
		 *
		 * @param CartSummary $cart_summary_admin_page The cart summary admin page
		 */
		do_action( 'cfw_cart_summary_before_admin_page_controls', $this );

		$this->output_checkbox_row(
			'show_cart_item_discount',
			cfw__( 'Enable Sale Prices', 'checkout-wc' ),
			cfw__( 'Enable sale price under on cart item labels at checkout. Example: <s>$10.00</s> $5.00', 'checkout-wc' )
		);

		$this->output_radio_group_row(
			'cart_item_link',
			cfw__( 'Cart Item Links', 'checkout-wc' ),
			cfw__( 'Choose whether or not cart items link to the single product page.', 'checkout-wc' ),
			'disabled',
			array(
				'disabled' => cfw__( 'Disabled (Recommended)', 'checkout-wc' ),
				'enabled'  => cfw__( 'Enabled', 'checkout-wc' ),
			),
			array(
				'disabled' => cfw__( 'Do not link cart items to single product page. (Recommended)', 'checkout-wc' ),
				'enabled'  => cfw__( 'Link each cart item to product page.', 'checkout-wc' ),
			)
		);

		$this->output_radio_group_row(
			'cart_item_data_display',
			cfw__( 'Cart Item Data Display', 'checkout-wc' ),
			cfw__( 'Choose how to display cart item data.', 'checkout-wc' ),
			'short',
			array(
				'short'       => cfw__( 'Short (Recommended)', 'checkout-wc' ),
				'woocommerce' => cfw__( 'WooCommerce Default', 'checkout-wc' ),
			),
			array(
				'short'       => cfw__( 'Display only variation values. For example, Size: XL, Color: Red is displayed as XL / Red. (Recommended)', 'checkout-wc' ),
				'woocommerce' => cfw__( 'Each variation is displayed on a separate line using this format: Label: Value', 'checkout-wc' ),
			)
		);

		/**
		 * Fires at the top of the cart summary admin page settings table inside <tbody>
		 *
		 * @since 5.0.0
		 *
		 * @param CartSummary $cart_summary_admin_page The cart summary admin page
		 */
		do_action( 'cfw_cart_summary_after_admin_page_controls', $this );

		return ob_get_clean();
	}

	public function get_steps_fields() {
		ob_start();

		$this->output_checkbox_row(
			'disable_express_checkout',
			cfw__( 'Disable Express Checkout', 'checkout-wc' ),
			cfw__( 'Prevent Express Checkout options from loading, such as Apple Pay and PayPal.', 'checkout-wc' ) . ' <a href="https://www.checkoutwc.com/documentation/supported-express-payment-gateways/" target="_blank" class="ml-1 text-blue-600 hover:text-blue-800">' . cfw__( 'Learn More', 'checkout-wc' ) . '</a>'
		);

		$this->output_checkbox_row(
			'skip_cart_step',
			cfw__( 'Disable Cart Step', 'checkout-wc' ),
			cfw__( 'Disable to skip the cart and redirect customers directly to checkout after adding a product to the cart. (Incompatible with Side Cart)', 'checkout-wc' )
		);

		$this->output_checkbox_row(
			'skip_shipping_step',
			cfw__( 'Disable Shipping Step', 'checkout-wc' ),
			cfw__( 'Disable to hide the shipping method step. Useful if you only have one shipping option for all orders.', 'checkout-wc' )
		);

		/**
		 * Fires at the bottom steps settings container
		 *
		 * @param WooCommercePages $checkout_admin_page The checkout settings admin page
		 *
		 *@since 7.0.0
		 *
		 */
		do_action( 'cfw_after_admin_page_checkout_steps_section', $this );

		return ob_get_clean();
	}

	public function get_login_and_registration_fields() {
		ob_start();

		$this->output_radio_group_row(
			'registration_style',
			cfw__( 'Registration', 'checkout-wc' ),
			cfw__( 'Choose how customers obtain a password when registering an account.' ),
			'enhanced',
			array(
				'enhanced'    => cfw__( 'Enhanced (Recommended)', 'checkout-wc' ),
				'woocommerce' => cfw__( 'WooCommerce Default', 'checkout-wc' ),
			),
			array(
				'enhanced'    => cfw__( 'Automatically generate a username and password and email it to the customer using the native WooCommerce functionality. (Recommended)', 'checkout-wc' ),
				'woocommerce' => cfw__( 'A password field is provided for the customer to select their own password. Not recommended.', 'checkout-wc' ),
			)
		);

		$this->output_radio_group_row(
			'user_matching',
			cfw__( 'User Matching', 'checkout-wc' ),
			cfw__( 'Choose how to handle guest orders and accounts.' ),
			'enabled',
			array(
				'enabled'     => cfw__( 'Enabled (Recommended)', 'checkout-wc' ),
				'woocommerce' => cfw__( 'WooCommerce Default', 'checkout-wc' ),
			),
			array(
				'enabled'     => cfw__( 'Automatically matches guest orders to user accounts on new purchase as well as on registration of a new user. (Recommended)', 'checkout-wc' ),
				'woocommerce' => cfw__( 'Guest orders will not be linked to matching accounts.', 'checkout-wc' ),
			)
		);

		return ob_get_clean();
	}

	public function get_field_option_fields() {
		$settings           = SettingsManager::instance();
		$order_notes_enable = ! has_filter( 'woocommerce_enable_order_notes_field' ) || ( $settings->get_setting( 'enable_order_notes' ) === 'yes' && 1 === cfw_count_filters( 'woocommerce_enable_order_notes_field' ) );

		$order_notes_notice_replacement_text = '';

		if ( ! $order_notes_enable && defined( 'WC_CHECKOUT_FIELD_EDITOR_VERSION' ) ) {
			$order_notes_notice_replacement_text = cfw__( 'This setting is overridden by WooCommerce Checkout Field Editor.', 'checkout-wc' );
		}

		ob_start();

		$this->output_checkbox_row(
			'enable_order_notes',
			cfw__( 'Enable Order Notes Field', 'checkout-wc' ),
			cfw__( 'Enable or disable WooCommerce Order Notes field. (Default: Disabled)', 'checkout-wc' ),
			array(
				'enabled'                => $order_notes_enable,
				'show_overridden_notice' => false === $order_notes_enable,
				'overridden_notice'      => $order_notes_notice_replacement_text,
			)
		);

		$this->output_checkbox_row(
			'enable_coupon_code_link',
			cfw__( 'Hide Coupon Code Field Behind Link', 'checkout-wc' ),
			cfw__( 'Initially hide coupon field until "Have a coupon code?" link is clicked.', 'checkout-wc' )
		);

		$this->output_checkbox_row(
			'enable_discreet_address_1_fields',
			cfw__( 'Enable Discreet House Number and Street Name Address Fields', 'checkout-wc' ),
			cfw__( 'Values are combined into a single address_1 field based on country selected by customer.', 'checkout-wc' )
		);

		$this->output_radio_group_row(
			'discreet_address_1_fields_order',
			'Discreet Address Fields Display Order',
			cfw__( 'Choose how display discreet address 1 fields.' ),
			'default',
			array(
				'default'   => cfw__( '[House Number] [Street Name]', 'checkout-wc' ),
				'alternate' => cfw__( '[Street Name] [House Number]', 'checkout-wc' ),
			),
			array(
				'default'   => cfw__( 'Display the House Number before the Street Name. (Default)', 'checkout-wc' ),
				'alternate' => cfw__( 'Display the Street Name before the House Number.', 'checkout-wc' ),
			),
			array(
				'nested' => true,
			)
		);

		$this->output_checkbox_row(
			'hide_optional_address_fields_behind_link',
			cfw__( 'Hide Optional Address Fields Behind Links', 'checkout-wc' ),
			cfw__( 'Recommended to increase conversions. Example link text: Add Company (optional)', 'checkout-wc' )
		);

		$this->output_checkbox_row(
			'use_fullname_field',
			cfw__( 'Enable Full Name Field', 'checkout-wc' ),
			cfw__( 'Enable to replace first and last name fields with a single full name field.', 'checkout-wc' )
		);

		$this->output_checkbox_row(
			'enable_highlighted_countries',
			cfw__( 'Enable Highlighted Countries', 'checkout-wc' ),
			cfw__( 'Promote selected countries to the top of the countries list in country dropdowns.', 'checkout-wc' )
		);

		$this->output_countries_multiselect(
			'highlighted_countries',
			'Highlighted Countries',
			'The countries to show first in country dropdowns.',
			(array) SettingsManager::instance()->get_setting( 'highlighted_countries' ),
			array(
				'nested' => true,
			)
		);

		/**
		 * Fires at the bottom steps settings container
		 *
		 * @param WooCommercePages $checkout_admin_page The checkout settings admin page
		 *
		 *@since 7.0.0
		 *
		 */
		do_action( 'cfw_after_admin_page_field_options_section', $this );

		return ob_get_clean();
	}

	public function get_address_options_fields() {
		ob_start();

		$this->output_checkbox_row(
			'force_different_billing_address',
			cfw__( 'Force Different Billing Address', 'checkout-wc' ),
			cfw__( 'Remove option to use shipping address as billing address.', 'checkout-wc' )
		);

		$this->output_checkbox_group(
			'enabled_billing_address_fields',
			cfw__( 'Enabled Billing Address Fields', 'checkout-wc' ),
			cfw__( 'Determine which billing address fields are visible for customers.', 'checkout-wc' ),
			array(
				'billing_first_name' => 'First name',
				'billing_last_name'  => 'Last name',
				'billing_address_1'  => 'Address 1',
				'billing_address_2'  => 'Address 2',
				'billing_company'    => 'Company',
				'billing_country'    => 'Country',
				'billing_postcode'   => 'Postcode',
				'billing_state'      => 'State',
				'billing_city'       => 'City',
				'billing_phone'      => 'Phone',
			),
			(array) SettingsManager::instance()->get_setting( 'enabled_billing_address_fields' )
		);

		return ob_get_clean();
	}

	public function get_address_completion_and_validation_fields() {
		ob_start();

		/**
		 * Fires at the bottom steps settings container
		 *
		 * @param WooCommercePages $checkout_admin_page The checkout settings admin page
		 *
		 *@since 7.0.0
		 *
		 */
		do_action( 'cfw_after_admin_page_address_options_section', $this );

		return ob_get_clean();
	}

	public function get_mobile_options_fields() {
		ob_start();

		$this->output_checkbox_row(
			'show_mobile_coupon_field',
			cfw__( 'Enable Mobile Coupon Field', 'checkout-wc' ),
			cfw__( 'Show coupon field above payment gateways on mobile devices. Helps customers find the coupon field without expanding the cart summary.', 'checkout-wc' )
		);

		$this->output_checkbox_row(
			'show_logos_mobile',
			cfw__( 'Enable Mobile Credit Card Logos', 'checkout-wc' ),
			cfw__( 'Show the credit card logos on mobile. Note: Many gateway logos cannot be rendered properly on mobile. It is recommended you test before enabling. Default: Off', 'checkout-wc' )
		);

		$this->output_text_input_row(
			'cart_summary_mobile_label',
			cfw__( 'Cart Summary Mobile Label', 'checkout-wc' ),
			cfw__( 'Example: Show order summary and coupons', 'checkout-wc' ) . '<br/>' . cfw__( 'If left blank, this default will be used: ', 'checkout-wc' ) . cfw__( 'Show order summary', 'checkout-wc' )
		);

		return ob_get_clean();
	}

	public function enqueue_scripts() {
		if ( ! $this->is_current_page() || 'store_policies' !== $this->get_current_tab() ) {
			return;
		}
		cfwRegisterChunkedScripts( array( 'admin-settings' ) );

		$store_policies = SettingsManager::instance()->get_setting( 'store_policies' );

		// Seed store policies with internal ID that won't change
		foreach ( $store_policies as $index => $policy ) {
			$policy->id = 'policy-' . $index;
		}

		wp_localize_script( 'cfw-admin-settings', 'cfw_admin_settings_data', array(
			'store_policies' => $store_policies,
		) );

		wp_enqueue_script( 'cfw-admin-settings' );
	}
}

<?php

namespace Objectiv\Plugins\Checkout\Features;

use Objectiv\Plugins\Checkout\Admin\Pages\PageAbstract;
use Objectiv\Plugins\Checkout\Managers\SettingsManager;

class OrderReviewStep extends FeaturesAbstract {
	protected function run_if_cfw_is_enabled() {
		add_action( 'template_redirect', array( $this, 'order_review_tab_layout' ), 0 );
		add_filter( 'cfw_event_data', array( $this, 'add_localized_settings' ) );
	}

	public function unhook() {
		remove_action( 'template_redirect', array( $this, 'order_review_tab_layout' ), 0 );
	}

	public function order_review_tab_layout() {
		if ( defined( 'CFW_SUPPRESS_ORDER_REVIEW_TAB' ) ) {
			return;
		}

		// Move payment tab nav and terms and conditions to order review
		remove_action( 'cfw_checkout_payment_method_tab', 'cfw_payment_tab_nav', 50 );
		remove_action( 'cfw_checkout_payment_method_tab', 'cfw_payment_tab_content_terms_and_conditions', 40 );

		// Add new payment tab nav
		add_action( 'cfw_checkout_payment_method_tab', 'cfw_payment_method_tab_review_nav', 50, 0 );

		// Add order review tab
		add_filter( 'cfw_get_checkout_tabs', array( $this, 'add_order_review_step_tab' ), 40 );

		/**
		 * Order Review Tab Content
		 */
		add_action( 'cfw_checkout_order_review_tab', 'cfw_order_review_tab_heading', 10 );
		add_action( 'cfw_checkout_order_review_tab', 'cfw_order_review_step_review_pane', 20 );
		add_action( 'cfw_checkout_order_review_tab', 'cfw_order_review_step_totals_review_pane', 30 );
		add_action( 'cfw_checkout_order_review_tab', 'cfw_payment_tab_content_terms_and_conditions', 40, 0 );
		add_action( 'cfw_checkout_order_review_tab', 'cfw_order_review_tab_nav', 50, 0 );
	}

	public function add_localized_settings( $event_data ): array {
		$event_data['settings']['order_review_step_enabled'] = $this->enabled;

		return $event_data;
	}

	public function init() {
		parent::init();

		add_action( 'cfw_do_plugin_activation', array( $this, 'run_on_plugin_activation' ) );
		add_action( 'cfw_after_admin_page_checkout_steps_section', array( $this, 'output_admin_settings' ) );
	}

	public function output_admin_settings( PageAbstract $checkout_admin_page ) {
		$checkout_admin_page->output_checkbox_row(
			'enable_order_review_step',
			cfw__( 'Enable Order Review Step.', 'checkout-wc' ),
			cfw__( 'Adds a review step after payment information before finalizing order. Useful for jurisdictions which require additional confirmation before order submission. (Cannot be used with One Page Checkout)', 'checkout-wc' )
		);
	}

	public function run_on_plugin_activation() {
		SettingsManager::instance()->add_setting( 'enable_order_review_step', 'no' );
	}

	public function add_order_review_step_tab( $tabs ) {
		$tabs['cfw-order-review'] = array(
			/**
			 * Filter review step breadcrumb label
			 *
			 * @since 7.5.0
			 * @param string $label The review step breadcrumb label
			 */
			'label'            => apply_filters( 'cfw_breadcrumb_review_step_label', esc_html__( 'Review', 'checkout-wc' ) ),
			'classes'          => array(),
			'priority'         => 50,
			'enabled'          => true,
			'display_callback' => function() {
				/**
				 * Outputs order review step content
				 *
				 * @since 4.0.0
				 */
				do_action( 'cfw_checkout_order_review_tab' );
			},
		);

		return $tabs;
	}
}

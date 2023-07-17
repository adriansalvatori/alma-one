<?php

namespace Objectiv\Plugins\Checkout\Compatibility\Plugins;

class FreeGiftsforWooCommerce {
	protected $side_cart_enabled = false;

	public function init( bool $side_cart_enabled ) {
		add_action( 'cfw_cart_updated', array( $this, 'update_cart_gifts' ) );
		add_action( 'wp', array( $this, 'run_on_checkout' ), 0 );

		if ( $side_cart_enabled ) {
			add_action( 'wp', array( $this, 'prevent_redirect' ), 0 );
		}
	}

	public function run_on_checkout() {
		if ( ! defined( 'FGF_PLUGIN_FILE' ) ) {
			return;
		}

		if ( ! method_exists( '\\FGF_Gift_Products_Handler', 'get_gift_display_checkout_page_current_location' ) ) {
			return;
		}

		if ( ! cfw_is_checkout() && ! is_checkout_pay_page() ) {
			return;
		}

		$customize_hook = \FGF_Gift_Products_Handler::get_gift_display_checkout_page_current_location();

		if ( 'woocommerce_checkout_order_review' === $customize_hook['hook'] ) {
			// Hook for the gift display in the checkout page.
			remove_action( 'woocommerce_checkout_order_review', array(
				'\\FGF_Gift_Products_Handler',
				'render_gift_products_checkout_page'
			), $customize_hook['priority'] );

			add_action( 'cfw_checkout_main_container_start', array(
				'\\FGF_Gift_Products_Handler',
				'render_gift_products_checkout_page'
			) );
		}
	}

	public function prevent_redirect() {
		// Fix for Free Gifts for WooCommerce that causes add to cart output to be hijacked with side cart
		remove_action( 'wp', array( 'FGF_Gift_Products_Handler', 'add_to_cart_automatic_gift_product' ) );
	}

	public function update_cart_gifts() {
		if ( ! defined( 'FGF_PLUGIN_FILE' ) ) {
			return;
		}

		try {
			\FGF_Rule_Handler::reset();
		} catch ( \Exception $e ) {
			wc_get_logger()->error( $e->getMessage(), array( 'source' => 'checkout-wc' ) );
		}

		\FGF_Gift_Products_Handler::automatic_gift_product( false );
		\FGF_Gift_Products_Handler::bogo_gift_product( false );
		\FGF_Gift_Products_Handler::remove_gift_products();
	}
}

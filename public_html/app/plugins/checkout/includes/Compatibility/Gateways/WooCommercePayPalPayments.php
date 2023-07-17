<?php

namespace Objectiv\Plugins\Checkout\Compatibility\Gateways;

use Objectiv\Plugins\Checkout\Compatibility\CompatibilityAbstract;
class WooCommercePayPalPayments extends CompatibilityAbstract {
	public function is_available(): bool {
		return defined( 'PAYPAL_API_URL' );
	}

	public function pre_init() {
		add_filter( 'cfw_is_checkout', array( $this, 'is_checkout' ) );
	}

	public function is_checkout( $is_checkout ): bool {
		if ( isset( $_GET['wc-ajax'] ) && 'ppc-create-order' === $_GET['wc-ajax'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return true;
		}

		return (bool) $is_checkout;
	}
}

<?php

namespace Objectiv\Plugins\Checkout\Compatibility\Plugins;

use Objectiv\Plugins\Checkout\Compatibility\CompatibilityAbstract;

class WooCommercePriceBasedOnCountry extends CompatibilityAbstract {
	public function is_available(): bool {
		return defined( 'WCPBC_PLUGIN_FILE' );
	}

	public function run() {
		add_filter( 'cfw_order_bump_get_price_context', array( $this, 'change_bump_price_context' ), 10, 1 );
		add_filter( 'cfw_order_bump_captured_revenue', array( $this, 'protect_captured_revenue_from_currency_conversion' ), 10 );
	}

	public function change_bump_price_context(): string {
		return 'view';
	}

	/**
	 * @throws \Exception
	 */
	public function protect_captured_revenue_from_currency_conversion( $revenue ): float {
		if ( ! $this->is_available() ) {
			return $revenue;
		}

		$default_zone = \WCPBC_Pricing_Zones::create();
		$default_zone->set_name( __( 'Countries not covered by your other zones', 'woocommerce-product-price-based-on-countries' ) );
		$default_zone->set_currency( wcpbc_get_base_currency() );

		return $default_zone->get_exchange_rate_price( $revenue );
	}
}

<?php

namespace Objectiv\Plugins\Checkout\Compatibility\Plugins;

use Objectiv\Plugins\Checkout\Compatibility\CompatibilityAbstract;

class WooCommerceProductRecommendations extends CompatibilityAbstract {
	protected $side_cart_feature_active = false;

	public function is_available(): bool {
		return function_exists( 'WC_PRL' );
	}

	public function pre_init() {
		add_filter( 'woocommerce_prl_locations', array( $this, 'add_deployment_location' ), 8 );
	}

	public function add_deployment_location( $locations ) {
		$locations[] = '\\Objectiv\\Plugins\\Checkout\\Compatibility\\Plugins\\Helpers\\WooCommerceProductRecommendationsSideCartLocation';

		return $locations;
	}

	public function setup( bool $side_cart_feature_active ): WooCommerceProductRecommendations {
		$this->side_cart_feature_active = $side_cart_feature_active;

		return $this;
	}
}

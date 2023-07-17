<?php

namespace Objectiv\Plugins\Checkout\Compatibility\Gateways;

use Objectiv\Plugins\Checkout\Compatibility\CompatibilityAbstract;

class PaymentPluginsPayPal extends CompatibilityAbstract {
	public function is_available(): bool {
		return true; // filter is enough
	}

	public function run() {
		add_filter( 'wc_ppcp_add_payment_method_data', array( $this, 'add_payment_method_data' ), 10  );
	}

	public function add_payment_method_data( $data  ): array {
		if ( ! is_cfw_page() ) {
			return $data;
		}

		if ( ! isset( $data['buttons'] ) ) {
			return $data;
		}

		foreach ( $data['buttons'] as $index => $button ) {
			$data['buttons'][ $index ]['height'] = '42';
		}

		return $data;
	}
}

<?php

namespace Objectiv\Plugins\Checkout\Compatibility\Gateways;

use Objectiv\Plugins\Checkout\Compatibility\CompatibilityAbstract;

class NMI extends CompatibilityAbstract {
	public function is_available(): bool {
		return defined( 'WC_NMI_VERSION' );
	}

	public function typescript_class_and_params( array $compatibility ): array {
		$compatibility[] = array(
			'class'  => 'NMI',
			'params' => array(),
		);

		return $compatibility;
	}
}

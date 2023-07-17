<?php

namespace Objectiv\Plugins\Checkout\Action;

use Objectiv\Plugins\Checkout\Features\OrderBumps;
use Objectiv\Plugins\Checkout\Managers\SettingsManager;

/**
 * @link checkoutwc.com
 * @since 5.4.0
 * @package Objectiv\Plugins\Checkout\Action
 * @author Clifton Griffin <clif@objectiv.co>
 */
class UpdateSideCart extends CFWAction {
	protected $order_bumps_controller;

	public function __construct( OrderBumps $order_bumps_controller ) {
		parent::__construct( 'update_side_cart' );

		$this->order_bumps_controller = $order_bumps_controller;
	}


	public function action() {
		check_ajax_referer( 'cfw-update-side-cart', 'security' );

		parse_str( wp_unslash( $_POST['cart_data'] ), $cart_data );

		if ( ! empty( $cart_data['cfw-promo-code'] ) ) {
			WC()->cart->add_discount( wc_format_coupon_code( wp_unslash( $cart_data['cfw-promo-code'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}

		do_action( 'cfw_before_update_side_cart_action', $cart_data );

		$result = false;

		if ( SettingsManager::instance()->get_setting( 'enable_order_bumps_on_side_cart' ) === 'yes' ) {
			$result = $this->order_bumps_controller->handle_adding_order_bump_to_cart( $cart_data );
		}

		$this->out(
			array(
				'result'    => $result ? $result : cfw_update_cart( $cart_data['cart'] ?? array() ),
				'cart_hash' => WC()->cart->get_cart_hash(),
			)
		);
	}
}

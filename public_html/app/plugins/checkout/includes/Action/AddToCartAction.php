<?php

namespace Objectiv\Plugins\Checkout\Action;

/**
 * @link checkoutwc.com
 * @since 5.4.0
 * @package Objectiv\Plugins\Checkout\Action
 * @author Clifton Griffin <clif@objectiv.co>
 */
class AddToCartAction extends CFWAction {

	public function __construct() {
		parent::__construct( 'cfw_add_to_cart' );
	}


	public function action() {
		$result     = false;
		$redirect   = false;
		$product_id = apply_filters( 'woocommerce_add_to_cart_product_id', absint( $_POST['add-to-cart'] ?? 0 ) );

		if ( $product_id && empty( wc_get_notices( 'error' ) ) ) {
			do_action( 'woocommerce_ajax_added_to_cart', $product_id );
			$result = true;
		}

		$quantity   = sanitize_text_field( $_REQUEST['quantity'] ?? 1 );
		$product_id = sanitize_text_field( $_REQUEST['add-to-cart'] );

		if ( ! $result ) {
			$redirect = apply_filters( 'woocommerce_cart_redirect_after_error', get_permalink( $product_id ), $product_id );
		}

		cfw_remove_add_to_cart_notice( $product_id, $quantity );

		$this->out(
			array(
				'result'    => $result,
				'cart_hash' => WC()->cart->get_cart_hash(),
				'redirect'  => apply_filters( 'cfw_add_to_cart_redirect', $redirect, $product_id ),
			)
		);
	}
}

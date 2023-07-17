<?php

namespace Objectiv\Plugins\Checkout\Model\Bumps;

class AllProductsBump extends BumpAbstract {
	public function is_displayable(): bool {
		if ( ! $this->can_offer_product_be_added_to_the_cart() ) {
			return false;
		}

		if ( $this->quantity_of_product_in_cart( $this->offer_product ) ) {
			return false;
		}

		return true;
	}

	public function is_cart_bump_valid(): bool {
		/**
		 * Filters whether bump is valid in the cart
		 *
		 * @param string $is_cart_bump_valid Whether the categories bump in the cart is still valid
		 * @since 7.5.0
		 */
		return apply_filters( 'cfw_is_cart_bump_valid', WC()->cart->get_cart_contents_count() > $this->quantity_of_product_in_cart( $this->offer_product ), $this );
	}
}

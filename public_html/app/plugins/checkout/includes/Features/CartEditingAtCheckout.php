<?php

namespace Objectiv\Plugins\Checkout\Features;

use Objectiv\Plugins\Checkout\Admin\Pages\PageAbstract;
use Objectiv\Plugins\Checkout\Interfaces\ItemInterface;
use Objectiv\Plugins\Checkout\Managers\SettingsManager;

/**
 * Cart editing at checkout feature
 *
 * @link checkoutwc.com
 * @since 5.0.0
 */
class CartEditingAtCheckout extends FeaturesAbstract {
	protected function run_if_cfw_is_enabled() {
		add_action( 'cfw_update_checkout_after_customer_save', array( $this, 'handle_update_checkout' ), 10, 1 );
		add_action( 'cfw_cart_item_after_data', array( $this, 'output_cart_edit_item_quantity_control' ), 10, 3 );
		add_action( 'cfw_cart_item_after_data', array( $this, 'maybe_output_cart_item_variation_edit_link' ), 10, 3 );
	}

	public function init() {
		parent::init();

		add_action( 'cfw_do_plugin_activation', array( $this, 'run_on_plugin_activation' ) );
		add_action( 'cfw_cart_summary_before_admin_page_controls', array( $this, 'output_admin_fields' ), 10, 1 );
	}

	/**
	 * Handle update_checkout
	 *
	 * @param string $post_data
	 */
	public function handle_update_checkout( string $raw_post_data ) {
		parse_str( $raw_post_data, $post_data );

		if ( ! isset( $post_data['cart'] ) || 'true' !== $post_data['cfw_update_cart'] ) {
			return;
		}

		cfw_update_cart( $post_data['cart'], false );

		// Check cart has contents.
		if ( WC()->cart->is_empty() && ! is_customize_preview() && cfw_apply_filters( 'woocommerce_checkout_redirect_empty_cart', true ) ) {
			/**
			 * Filters whether to suppress checkout is not available message
			 * when editing cart results in empty cart
			 *
			 * @since 3.14.0
			 *
			 * @param bool $supress_notice Whether to suppress the message
			 */
			if ( false === apply_filters( 'cfw_cart_edit_redirect_suppress_notice', false ) ) {
				wc_add_notice( cfw__( 'Checkout is not available whilst your cart is empty.', 'woocommerce' ), 'notice' );
			}

			// Allow shortcodes to be used in empty cart redirect URL field
			// This is necessary so that WPML (etc) can swap in a locale specific URL
			$cart_editing_redirect_url = do_shortcode( $this->settings_getter->get_setting( 'cart_edit_empty_cart_redirect' ) );

			$redirect = empty( $cart_editing_redirect_url ) ? wc_get_cart_url() : $cart_editing_redirect_url;

			add_filter(
				'cfw_update_checkout_redirect',
				function() use ( $redirect ) {
					return $redirect;
				}
			);
		}
	}

	public function output_cart_edit_item_quantity_control( array $cart_item, string $cart_item_key, ItemInterface $item ) {
		/**
		 * Filters whether to disable cart editing
		 *
		 * @since 7.1.7
		 *
		 * @param int $disable_cart_editing Whether to disable cart editing
		 * @param array $cart_item The cart item
		 * @param string $cart_item_key The cart item key
		 */
		$disable_cart_editing = apply_filters( 'cfw_disable_cart_editing', ! $this->enabled, $cart_item, $cart_item_key );

		if ( $disable_cart_editing ) {
			return;
		}

		echo wp_kses( cfw_get_cart_item_quantity_control( $cart_item, $cart_item_key, $item->get_product() ), cfw_get_allowed_html() );
	}

	public function maybe_output_cart_item_variation_edit_link( array $cart_item, string $cart_item_key, ItemInterface $item ) {
		if ( SettingsManager::instance()->get_setting( 'allow_checkout_cart_item_variation_changes' ) !== 'yes' ) {
			return;
		}

		// If cart item isn't a variation, don't output anything
		if ( empty( $cart_item['variation_id'] ) ) {
			return;
		}

		/**
		 * Filters whether to disable cart variation editing
		 *
		 * @since 8.0.0
		 * @param bool $disable_cart_variation_editing Whether to disable cart editing
		 * @param array $cart_item The cart item
		 * @param string $cart_item_key The cart item key
		 * @param string $context The calling context
		 */
		if ( apply_filters( 'cfw_disable_cart_variation_editing', false, $cart_item, $cart_item_key, 'checkout' ) ) {
			return;
		}
		?>
		<a href="javascript:" class="cfw-cart-edit-item-variation cfw-xtra-small" data-product="<?php echo esc_attr( $cart_item['product_id'] ); ?>" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>">
			<?php echo cfw__( 'Edit', 'woocommerce' ); ?>
		</a>
		<?php
	}

	/**
	 * Output admin fields
	 *
	 * @param PageAbstract $cart_summary_admin_page
	 */
	public function output_admin_fields( PageAbstract $cart_summary_admin_page ) {
		if ( ! $this->available ) {
			$notice = $cart_summary_admin_page->get_upgrade_required_notice( $this->required_plans_list );
		}

		$cart_summary_admin_page->output_checkbox_row(
			'enable_cart_editing',
			cfw__( 'Enable Cart Editing At Checkout', 'checkout-wc' ),
			cfw__( 'Enable or disable Cart Editing. Allows customer to remove or adjust quantity of cart items at checkout.', 'checkout-wc' ),
			array(
				'enabled' => $this->available,
				'notice'  => $notice ?? '',
			)
		);

		$cart_summary_admin_page->output_checkbox_row(
			'allow_checkout_cart_item_variation_changes',
			cfw__( 'Allow Variation Changes', 'checkout-wc' ),
			cfw__( 'Displays an edit link under cart items that allows customers to change which variation is selected in the cart.', 'checkout-wc' ),
			array( 'nested' => true )
		);

		$cart_summary_admin_page->output_text_input_row(
			'cart_edit_empty_cart_redirect',
			cfw__( 'Cart Editing Empty Cart Redirect', 'checkout-wc' ),
			cfw__( 'URL to redirect to when customer empties cart from checkout page.', 'checkout-wc' ) . '<br/>' . cfw__( 'If left blank, customer will be redirected to the cart page.', 'checkout-wc' ),
			array( 'nested' => true )
		);
	}

	public function run_on_plugin_activation() {
		SettingsManager::instance()->add_setting( 'enable_cart_editing', 'yes' );
		SettingsManager::instance()->add_setting( 'allow_checkout_cart_item_variation_changes', 'no' );
		SettingsManager::instance()->add_setting( 'cart_edit_empty_cart_redirect', '' );
	}
}

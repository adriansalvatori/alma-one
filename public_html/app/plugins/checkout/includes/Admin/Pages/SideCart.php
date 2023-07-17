<?php

namespace Objectiv\Plugins\Checkout\Admin\Pages;

use Objectiv\Plugins\Checkout\Managers\PlanManager;

/**
 * @link checkoutwc.com
 * @since 5.0.0
 * @package Objectiv\Plugins\Checkout\Admin\Pages
 * @author Clifton Griffin <clif@checkoutwc.com>
 */
class SideCart extends PageAbstract {
	public function __construct() {
		parent::__construct( cfw__( 'Side Cart', 'checkout-wc' ), 'manage_options', 'side-cart' );
	}
	public function output() {
		$this->output_form_open();
		?>
		<div class="space-y-6">
			<?php
			cfw_admin_page_section(
				'Side Cart',
				'Controls the floating Side Cart.',
				$this->get_settings()
			);
			?>
		</div>
		<?php
		$this->output_form_close();
	}

	protected function get_settings() : string {
		ob_start();

		if ( ! PlanManager::has_premium_plan() ) {
			$notice = $this->get_old_style_upgrade_required_notice( PlanManager::get_english_list_of_required_plans_html() );
		}

		if ( ! empty( $notice ) ) {
			echo $notice;
		}

		$this->output_toggle_checkbox(
			'enable_side_cart',
			cfw__( 'Enable Side Cart', 'checkout-wc' ),
			cfw__( 'Replace your cart page with a beautiful side cart that slides in from the right when items are added to the cart.', 'checkout-wc' ),
			PlanManager::has_premium_plan()
		);

		foreach ( glob(CFW_PATH . '/build/images/cart-icons/*.svg') as $icon_filename ) {
			$icon_options[ basename( $icon_filename ) ] = file_get_contents( $icon_filename ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		}

		$this->output_horizontal_icon_radio_group_row(
			'side_cart_icon',
			'Icon',
			'Choose the Side Cart icon.',
			'cart-outline.svg',
			$icon_options,
			array()
		);

		$this->output_color_picker_input(
			'side_cart_icon_color',
			cfw__( 'Icon Color', 'checkout-wc' ),
			'#222'
		);

		$this->output_number_input_row(
			'side_cart_icon_width',
			cfw__( 'Icon Width', 'checkout-wc' ),
			cfw__( 'The width of the icon in pixels. Default: 34', 'checkout-wc' )
		);

		$this->output_checkbox_row(
			'enable_floating_cart_button',
			cfw__( 'Enable Floating Cart Button', 'checkout-wc' ),
			cfw__( 'Enable floating cart button on the bottom right of pages.', 'checkout-wc' )
		);

		$this->output_number_input_row(
			'floating_cart_button_right_position',
			cfw__( 'Floating Cart Button Right Position', 'checkout-wc' ),
			cfw__( 'The position from the right side of the screen in pixels. Default: 20', 'checkout-wc' ),
			array( 'nested' => true )
		);

		$this->output_number_input_row(
			'floating_cart_button_bottom_position',
			cfw__( 'Floating Cart Button Bottom Position', 'checkout-wc' ),
			cfw__( 'The position from the bottom of the screen in pixels. Default: 20', 'checkout-wc' ),
			array( 'nested' => true )
		);

		$this->output_checkbox_row(
			'hide_floating_cart_button_empty_cart',
			cfw__( 'Hide Button If Empty Cart', 'checkout-wc' ),
			cfw__( 'Hide floating cart button if cart is empty.', 'checkout-wc' ),
			array( 'nested' => true )
		);

		$this->output_checkbox_row(
			'shake_floating_cart_button',
			cfw__( 'Disable Cart Auto Open', 'checkout-wc' ),
			cfw__( 'Instead of opening the side cart, gently shake the floating cart button to indicate a successful add to cart event.', 'checkout-wc' ),
			array( 'nested' => true )
		);

		$this->output_checkbox_row(
			'enable_ajax_add_to_cart',
			cfw__( 'Enable AJAX Add to Cart', 'checkout-wc' ),
			cfw__( 'Use AJAX on archive and single product pages to add items to cart. By default, WooCommerce requires a full form submit with page reload. Enabling this option uses AJAX to add items to the cart.', 'checkout-wc' )
		);

		$this->output_checkbox_row(
			'enable_side_cart_payment_buttons',
			cfw__( 'Enable Express Payment Buttons', 'checkout-wc' ),
			cfw__( 'Enable express payment buttons from gateways that support the WooCommerce Minicart.', 'checkout-wc' )
		);

		$this->output_checkbox_row(
			'enable_order_bumps_on_side_cart',
			cfw__( 'Enable Order Bumps', 'checkout-wc' ),
			cfw__( 'Enable order bumps that are set to display below cart items to appear in side cart.', 'checkout-wc' )
		);

		$this->output_checkbox_row(
			'enable_side_cart_suggested_products',
			cfw__( 'Enable Suggested Products', 'checkout-wc' ),
			cfw__( 'Display cross sells / suggested products at the bottom of the side cart.', 'checkout-wc' )
		);

		$this->output_text_input_row(
			'side_cart_suggested_products_heading',
			cfw__( 'Suggested Products Heading', 'checkout-wc' ),
			cfw__( 'Heading above the suggested products. Leave blank for default. Default: You may also like&hellip;', 'checkout-wc' ),
			array( 'nested' => true )
		);

		$this->output_checkbox_row(
			'enable_side_cart_suggested_products_random_fallback',
			cfw__( 'Enable Random Cross Sells Fallback', 'checkout-wc' ),
			cfw__( 'If none of the cart products have cross sell items defined, display random products instead.', 'checkout-wc' )
		);

		$this->output_checkbox_row(
			'allow_side_cart_item_variation_changes',
			cfw__( 'Allow Variation Changes', 'checkout-wc' ),
			cfw__( 'Displays an edit link under cart items that allows customers to change which variation is selected in the cart.', 'checkout-wc' )
		);

		$this->output_checkbox_row(
			'show_side_cart_item_discount',
			cfw__( 'Enable Sale Prices', 'checkout-wc' ),
			cfw__( 'Enable sale price under on cart item labels in side cart. Example: <s>$10.00</s> $5.00', 'checkout-wc' )
		);

		$this->output_checkbox_row(
			'enable_promo_codes_on_side_cart',
			cfw__( 'Enable Coupons', 'checkout-wc' ),
			cfw__( 'Enable customers to apply coupons from the side cart.', 'checkout-wc' )
		);

		$this->output_checkbox_row(
			'enable_side_cart_continue_shopping_button',
			cfw__( 'Enable Continue Shopping Button', 'checkout-wc' ),
			cfw__( 'Enable Continue Shopping Button at bottom of Side Cart. Disabled by default.', 'checkout-wc' )
		);

		$this->output_checkbox_row(
			'enable_free_shipping_progress_bar',
			cfw__( 'Enable Free Shipping Progress Bar', 'checkout-wc' ),
			cfw__( 'Enable Free Shipping progress bar to show customers how close they are to obtaining free shipping. Uses your shipping settings to determine limits. To override, specify amount below.', 'checkout-wc' )
		);

		$this->output_number_input_row(
			'side_cart_free_shipping_threshold',
			cfw__( 'Free Shipping Threshold', 'checkout-wc' ),
			cfw__( 'Cart subtotal required to qualify for free shipping. To use automatic detection based on shipping configuration, leave blank.', 'checkout-wc' ),
			array( 'nested' => true )
		);

		$this->output_text_input_row(
			'side_cart_amount_remaining_message',
			cfw__( 'Amount Remaining Message', 'checkout-wc' ),
			cfw__( 'The amount remaining to qualify for free shipping message. Leave blank for default. Default: You\'re %s away from free shipping!', 'checkout-wc' ),
			array( 'nested' => true )
		);

		$this->output_text_input_row(
			'side_cart_free_shipping_message',
			cfw__( 'Free Shipping Message', 'checkout-wc' ),
			cfw__( 'The free shipping message. Leave blank for default. Default: Congrats! You get free standard shipping.', 'checkout-wc' ),
			array( 'nested' => true )
		);

		$progress_indicator_color_default_value = cfw_get_active_template()->get_default_setting( 'button_color' );

		$this->output_color_picker_input(
			'side_cart_free_shipping_progress_indicator_color',
			cfw__( 'Free Shipping Progress Indicator Color', 'checkout-wc' ),
			$progress_indicator_color_default_value,
			array( 'nested' => true )
		);

		$progress_bg_color_default_value = '#f5f5f5';

		$this->output_color_picker_input(
			'side_cart_free_shipping_progress_bg_color',
			cfw__( 'Free Shipping Progress Background Color', 'checkout-wc' ),
			$progress_bg_color_default_value,
			array( 'nested' => true )
		);

		return ob_get_clean();
	}
}

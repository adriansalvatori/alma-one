<?php

namespace Objectiv\Plugins\Checkout\Features;

use Objectiv\Plugins\Checkout\FormFieldAugmenter;
use Objectiv\Plugins\Checkout\Interfaces\ItemInterface;
use Objectiv\Plugins\Checkout\Interfaces\SettingsGetterInterface;
use Objectiv\Plugins\Checkout\Managers\SettingsManager;
use Objectiv\Plugins\Checkout\Managers\StyleManager;
use WC_Frontend_Scripts;
use WC_Shipping_Free_Shipping;
use WC_Shipping_Zones;

class SideCart extends FeaturesAbstract {
	protected $item_just_added_to_cart = false;
	protected $order_bumps_controller  = false;

	public function __construct( bool $enabled, bool $available, string $required_plans_list, SettingsGetterInterface $settings_getter, OrderBumps $order_bumps_controller ) {
		$this->order_bumps_controller = $order_bumps_controller;

		parent::__construct( $enabled, $available, $required_plans_list, $settings_getter );
	}

	public function init() {
		parent::init();

		add_action( 'cfw_do_plugin_activation', array( $this, 'run_on_plugin_activation' ) );
	}

	protected function run_if_cfw_is_enabled() {
		/**
		 * Disable side cart if filter is set
		 *
		 * @param bool $disable_side_cart Whether to disable the side cart
		 *
		 * @since 7.2.0
		 */
		if ( apply_filters( 'cfw_disable_side_cart', false ) ) {
			return;
		}

		// Prevent redirecting after add to cart when side cart is on
		add_filter(
			'pre_option_woocommerce_cart_redirect_after_add',
			function () {
				return 'no';
			}
		);

		add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'add_side_cart_to_fragments' ) );
		add_filter( 'woocommerce_add_to_cart_redirect', array( $this, 'maybe_prevent_add_to_cart_redirect' ), 1000 );
		add_action( 'woocommerce_add_to_cart', array( $this, 'detect_item_just_added_to_cart' ) );
		add_action( 'wp', array( $this, 'run_sidecart' ) );
	}

	public function run_sidecart() {
		add_filter( 'cfw_breadcrumbs', array( $this, 'remove_cart_breadcrumb' ) );
		add_filter( 'cfw_show_return_to_cart_link', '__return_false' );
		add_shortcode( 'checkoutwc_cart', array( $this, 'render_shortcode' ) );
		add_action( 'cfw_side_cart_item_after_data', array(
			$this,
			'output_side_cart_edit_item_quantity_control'
		), 10, 3 );
		add_action( 'cfw_side_cart_item_after_data', array(
			$this,
			'maybe_output_cart_item_variation_edit_link'
		), 10, 2 );

		if ( SettingsManager::instance()->get_setting( 'enable_order_bumps_on_side_cart' ) === 'yes' ) {
			add_action( 'cfw_after_side_cart_items_table', array(
				$this->order_bumps_controller,
				'output_cart_summary_bumps'
			) );
			add_action(
				'cfw_after_side_cart_items_table',
				/**
				 * Output side cart bumps
				 *
				 * @throws \Exception
				 */
				function () {
					$this->order_bumps_controller->output_bumps( array( 'below_side_cart_items' ) );
				}
			);
		}

		if ( SettingsManager::instance()->get_setting( 'enable_free_shipping_progress_bar' ) === 'yes' ) {
			add_action( 'cfw_after_side_cart_header', array( $this, 'maybe_output_shipping_progress_bar' ) );
		}

		add_filter( 'cfw_event_data', array( $this, 'add_localized_settings' ) );
		add_action( 'cfw_custom_css_properties', array( $this, 'add_custom_css_property' ) );

		// Turn off empty cart notice
		remove_action( 'woocommerce_cart_is_empty', 'wc_empty_cart_message', 10 );
		add_action( 'woocommerce_cart_is_empty', 'cfw_output_empty_cart_message', 1 );

		// Turn off "Item removed. Undo?" notices
		add_filter( 'woocommerce_cart_item_removed_notice_type', '__return_null' );

		add_filter(
			'pre_option_woocommerce_enable_ajax_add_to_cart',
			function ( $result ) {
				if ( SettingsManager::instance()->get_setting( 'enable_side_cart' ) === 'yes' && SettingsManager::instance()->get_setting( 'enable_ajax_add_to_cart' ) === 'yes' ) {
					$result = 'yes';
				}

				return $result;
			},
			10,
			1
		);

		add_filter(
			'woocommerce_cart_redirect_after_error',
			function ( $url ) {
				if ( SettingsManager::instance()->get_setting( 'enable_side_cart' ) !== 'yes' ) {
					return $url;
				}

				// Add cache busting parameter to url
				return add_query_arg( 'nocache', time(), $url );
			}
		);

		// Output custom styles
		add_action(
			'wp_head',
			function () {
				StyleManager::add_styles( 'cfw_side_cart_css' );
			},
			5
		);

		if ( ! is_cfw_page() ) {
			/**
			 * Compatibility Nightmare Avoidance
			 */
			add_action( 'wp_enqueue_scripts', array(
				$this,
				'make_sure_cart_fragments_script_is_enqueued'
			), 100 * 1000 );
			add_action( 'wp_print_scripts', array( WC_Frontend_Scripts::class, 'localize_printed_scripts' ), 5 );
			add_action( 'wp_print_footer_scripts', array( WC_Frontend_Scripts::class, 'localize_printed_scripts' ), 5 );

			add_action( 'cfw_before_cart_item_subtotal', array( $this, 'add_delete_button' ), 10, 1 );
			add_action( 'wp_footer', array( $this, 'output_side_cart_and_overlay_markup' ), 1 );
		}

		add_action( 'cfw_after_side_cart_proceed_to_checkout_button', array(
			$this,
			'maybe_output_suggested_products'
		) );
	}

	public function unhook_default_mini_cart_buttons() {
		// Remove default cart widget buttons
		remove_action( 'woocommerce_widget_shopping_cart_buttons', 'woocommerce_widget_shopping_cart_button_view_cart', 10 );
		remove_action( 'woocommerce_widget_shopping_cart_buttons', 'woocommerce_widget_shopping_cart_proceed_to_checkout', 20 );
	}

	public function rehook_default_mini_cart_buttons() {
		// Re-add default cart widget buttons
		add_action( 'woocommerce_widget_shopping_cart_buttons', 'woocommerce_widget_shopping_cart_button_view_cart', 10 );
		add_action( 'woocommerce_widget_shopping_cart_buttons', 'woocommerce_widget_shopping_cart_proceed_to_checkout', 20 );
	}

	public function detect_item_just_added_to_cart() {
		$this->item_just_added_to_cart = true;
	}

	public function make_sure_cart_fragments_script_is_enqueued() {
		WC_Frontend_Scripts::load_scripts();
		wp_enqueue_script( 'wc-cart-fragments' );
	}

	public function add_delete_button( ItemInterface $item ) {
		if ( defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			return;
		}

		$_product = $item->get_product();

		echo cfw_apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'woocommerce_cart_item_remove_link',
			sprintf(
				'<a href="%s" class="cfw-remove-item-button" aria-label="%s" data-product_id="%s" data-product_sku="%s">&cross;</a>',
				esc_url( wc_get_cart_remove_url( $item->get_item_key() ) ),
				cfw_esc_html__( 'Remove this item', 'woocommerce' ),
				esc_attr( $_product->get_id() ),
				esc_attr( $_product->get_sku() )
			),
			$item->get_item_key()
		);
	}

	public function output_side_cart_and_overlay_markup() {
		if ( SettingsManager::instance()->get_setting( 'enable_side_cart_payment_buttons' ) === 'yes' ) {
			do_action( 'woocommerce_before_mini_cart' );
		}
		?>
		<div id="cfw-side-cart-overlay"></div>
		<div class="checkoutwc cfw-grid" id="cfw-side-cart" role="dialog" aria-modal="true"
			 aria-label="<?php cfw_e( 'Cart', 'woocommerce' ); ?>">
			<?php
			if ( WC()->cart->is_empty() ) {
				echo $this->get_side_cart_fragment();
			} else {
				echo '<form class="uninitialized checkoutwc" id="cfw-side-cart-form"></form>';
			}
			?>
		</div>

		<?php if ( SettingsManager::instance()->get_setting( 'enable_floating_cart_button' ) === 'yes' ) : ?>
			<?php echo self::get_floating_cart_icon(); ?>
		<?php endif; ?>
		<?php
	}

	public function get_side_cart_fragment() {
		ob_start();
		?>
		<form id="cfw-side-cart-form" class="checkoutwc">
			<div class="cfw-side-cart-contents-header">
				<span class="cfw-side-cart-close-btn" role="button"
					  aria-label="<?php _e( 'Close Cart', 'checkout-wc' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M17 8L21 12M21 12L17 16M21 12L3 12" stroke="#111827" stroke-linecap="round"
							  stroke-linejoin="round"/>
					</svg>
				</span>

				<?php echo self::get_cart_icon(); ?>
				<?php do_action( 'cfw_after_side_cart_header' ); ?>
			</div>

			<div class="cfw-side-cart-contents">
				<?php
				if ( WC()->cart->is_empty() ) {
					do_action( 'woocommerce_cart_is_empty' );
					do_action( 'checkoutwc_empty_side_cart_content' );
				} else {
					echo cfw_get_side_cart_item_summary_table();
					do_action( 'cfw_after_side_cart_items_table' );
				}
				?>
			</div>

			<div class="cfw-side-cart-contents-footer">
				<div class="cfw-side-cart-contents-footer-border-shim"></div>
				<?php do_action( 'cfw_side_cart_footer_start' ); ?>

				<?php if ( ! WC()->cart->is_empty() ) : ?>
					<?php
					if ( wc_coupons_enabled() && SettingsManager::instance()->get_setting( 'enable_promo_codes_on_side_cart' ) === 'yes' ) :
						/**
						 * Filters promo code button label
						 *
						 * @param string $promo_code_button_label Promo code button label
						 *
						 * @since 3.0.0
						 */
						$promo_code_button_label = apply_filters( 'cfw_promo_code_apply_button_label', esc_attr__( 'Apply', 'checkout-wc' ) );
						?>

						<div class="cfw-side-cart-coupon-wrap">
							<div class="row">
								<div class="col-lg-12 no-gutters">
									<a class="cfw-show-coupons-module" href="javascript:">
										<?php
										/**
										 * Filters promo code toggle link text
										 *
										 * @param string $promo_code_toggle_link_text Filters promo code toggle link text
										 *
										 * @since 3.0.0
										 */
										echo apply_filters( 'cfw_promo_code_toggle_link_text', __( 'Have a promo code? Click here.', 'checkout-wc' ) );
										?>
									</a>
								</div>
							</div>
							<div class="cfw-promo-wrap cfw-hidden">
								<div class="row cfw-promo-row cfw-input-wrap-row">
									<?php
									FormFieldAugmenter::instance()->add_hooks();
									$output = woocommerce_form_field(
										'cfw-promo-code',
										array(
											'type'        => 'text',
											'required'    => false,

											/**
											 * Filters promo code label
											 *
											 * @param string $promo_code_label Promo code label
											 *
											 * @since 3.0.0
											 */
											'label'       => apply_filters( 'cfw_promo_code_label', __( 'Promo Code', 'checkout-wc' ) ),

											/**
											 * Filters promo code placeholder
											 *
											 * @param string $promo_code_placeholder Promo code placeholder
											 *
											 * @since 3.0.0
											 */
											'placeholder' => apply_filters( 'cfw_promo_code_placeholder', __( 'Enter Promo Code', 'checkout-wc' ) ),

											'label_class' => 'cfw-input-label',
											'class'       => array( 'no-gutters' ),
											'start'       => false,
											'end'         => false,
											'columns'     => 8,
											'return'      => true,
										)
									);

									$output = str_replace( '(' . cfw_esc_html__( 'optional', 'woocommerce' ) . ')', '', $output );

									echo $output;

									FormFieldAugmenter::instance()->remove_hooks();
									?>
									<div class="col-lg-4">
										<div class="cfw-input-wrap cfw-button-input">
											<input type="button" name="cfw-promo-code-btn" id="cfw-promo-code-btn"
												   class="cfw-secondary-btn"
												   value="<?php echo $promo_code_button_label; ?>"/>
										</div>
									</div>
								</div>
							</div>
						</div>
					<?php
					endif;
					do_action( 'cfw_side_cart_notices' );
					cfw_wc_print_notices();
					?>
					<div class="cfw-side-cart-totals">
						<?php do_action( 'cfw_before_side_cart_totals' ); ?>
						<table class="cfw-module">
							<tr class="cart-subtotal">
								<th><?php cfw_e( 'Subtotal', 'woocommerce' ); ?></th>
								<td data-title="<?php cfw_esc_attr_e( 'Subtotal', 'woocommerce' ); ?>"><?php wc_cart_totals_subtotal_html(); ?></td>
							</tr>

							<?php if ( apply_filters( 'cfw_side_cart_show_total', false ) ) : ?>
								<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
									<tr class="shipping">
										<th><?php esc_html_e( 'Shipping', 'woocommerce' ); ?></th>
										<td data-title="<?php esc_attr_e( 'Shipping', 'woocommerce' ); ?>"><?php echo WC()->cart->get_cart_shipping_total(); ?></td>
									</tr>
								<?php endif; ?>

								<tr class="order-total">
									<th><?php cfw_esc_html_e( 'Total', 'woocommerce' ); ?></th>
									<td data-title="<?php cfw_esc_attr_e( 'Total', 'woocommerce' ); ?>"><?php wc_cart_totals_order_total_html(); ?></td>
								</tr>
							<?php endif; ?>

							<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
								<tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
									<th><?php wc_cart_totals_coupon_label( $coupon ); ?></th>
									<td><?php wc_cart_totals_coupon_html( $coupon ); ?></td>
								</tr>
							<?php endforeach; ?>
						</table>
						<?php do_action( 'cfw_after_side_cart_totals' ); ?>
					</div>

					<div class="wc-proceed-to-checkout">
						<?php
						wc_get_pay_buttons();

						if ( apply_filters( 'cfw_run_woocommerce_cart_actions', false ) ) {
							do_action( 'woocommerce_cart_actions' );
						}
						?>

						<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" role="button"
						   class="cfw-primary-btn cfw-side-cart-checkout-btn">
							<?php cfw_esc_html_e( 'Proceed to checkout', 'woocommerce' ); ?>
						</a>

						<?php
						/**
						 * Filters whether to show continue shopping button in side cart
						 *
						 * @param bool $enable_button Whether to show continue shopping button in side cart
						 *
						 * @since 7.0.5
						 */
						if ( apply_filters( 'cfw_side_cart_enable_continue_shopping_button', SettingsManager::instance()->get_setting( 'enable_side_cart_continue_shopping_button' ) === 'yes' ) ) :
							?>
							<a href="#" class="cfw-side-cart-close-trigger cfw-secondary-btn" role="button">
								<?php cfw_esc_html_e( 'Continue shopping', 'woocommerce' ); ?>
							</a>
						<?php endif; ?>
						<?php
						if ( SettingsManager::instance()->get_setting( 'enable_side_cart_payment_buttons' ) === 'yes' ) {
							$this->unhook_default_mini_cart_buttons();
							do_action( 'woocommerce_widget_shopping_cart_buttons' );
							$this->rehook_default_mini_cart_buttons();
						}

						/**
						 * Fires after checkout proceed to cart (and maybe continue shopping) buttons
						 *
						 * @since 7.0.6
						 */
						do_action( 'cfw_after_side_cart_proceed_to_checkout_button' );
						?>
					</div>
				<?php endif; ?>
			</div>
		</form>
		<?php
		return ob_get_clean();
	}

	/**
	 * @param array|null $data
	 *
	 * @return bool
	 */
	public function does_cart_qualifies_for_free_shipping( array $data = null ): bool {
		$data = $data ?? $this->get_free_shipping_data();

		if ( empty( $data ) ) {
			return false;
		}

		return (bool) $data['has_free_shipping'];
	}

	/**
	 * @param array|null $data
	 *
	 * @return float|null
	 */
	public function get_remaining_amount_to_qualify_for_free_shipping( array $data = null ): ?float {
		$data = $data ?? $this->get_free_shipping_data();

		if ( empty( $data ) ) {
			return null;
		}

		return floatval( $data['amount_remaining'] );
	}

	/**
	 * @param array|null $data
	 *
	 * @return int
	 */
	public function get_fill_percentage( array $data = null ): int {
		$data = $data ?? $this->get_free_shipping_data();

		if ( empty( $data ) ) {
			return 0;
		}

		return intval( $data['fill_percentage'] );
	}

	/**
	 * @return array
	 */
	public function get_free_shipping_data(): array {
		$data = array();

		$threshold = (float) SettingsManager::instance()->get_setting( 'side_cart_free_shipping_threshold' );

		// Set up a dummy product for the filter
		$dummy_product = new \WC_Product();
		$dummy_product->set_price( $threshold );
		$dummy_product->set_regular_price( $threshold );

		$threshold                = cfw_apply_filters( 'woocommerce_product_get_price', $threshold, $dummy_product );
		$has_free_shipping        = false;
		$amount_remaining         = null;
		$fill_percentage          = null;
		$subtotal                 = WC()->cart->get_displayed_subtotal();
		$has_free_shipping_coupon = false;

		/**
		 * Filters whether to exclude discounts from the subtotal when calculating the free shipping bar
		 *
		 * @param bool $exclude_discounts Whether to exclude discounts from the subtotal when calculating the free shipping bar
		 *
		 * @since 7.10.2
		 */
		$exclude_discounts = apply_filters( 'cfw_side_cart_shipping_bar_data_exclude_discounts', false );

		if ( $exclude_discounts ) {
			if ( WC()->cart->display_prices_including_tax() ) {
				$discount = WC()->cart->get_cart_discount_total() + WC()->cart->get_cart_discount_tax_total();
			} else {
				$discount = WC()->cart->get_cart_discount_total();
			}

			$subtotal = $subtotal - $discount;
		}

		foreach ( WC()->cart->get_applied_coupons() as $coupon_code ) {
			$coupon = new \WC_Coupon( $coupon_code );

			if ( $coupon->get_free_shipping() ) {
				$has_free_shipping_coupon = true;
				break;
			}
		}

		if ( $has_free_shipping_coupon ) {
			$data = array(
				'has_free_shipping' => true,
				'amount_remaining'  => 0,
				'fill_percentage'   => 100,
			);

			/**
			 * Filters the free shipping data when a free shipping coupon is applied
			 *
			 * @param array $data
			 *
			 * @since 7.0.5
			 */
			return apply_filters( 'cfw_shipping_bar_data', $data );
		}

		if ( ! empty( $threshold ) && is_numeric( $threshold ) ) {
			$data = array(
				'has_free_shipping' => ( $subtotal >= $threshold ),
				'amount_remaining'  => $subtotal >= $threshold ? 0 : $threshold - $subtotal,
				'fill_percentage'   => min( ceil( ( $subtotal / $threshold ) * 100 ), 100 ),
			);

			/**
			 * Filters the free shipping data when a threshold is set
			 *
			 * @param array $data
			 *
			 * @since 7.0.5
			 */
			return apply_filters( 'cfw_shipping_bar_data', $data );
		}

		WC()->cart->calculate_shipping();

		$packages = WC()->shipping()->get_packages();

		if ( empty( $packages ) ) {
			/**
			 * Filters the free shipping data when no packages are available
			 *
			 * @param array $data
			 *
			 * @since 7.0.5
			 */
			return apply_filters( 'cfw_shipping_bar_data', $data );
		}

		// Only look at first package for this feature
		$available_methods = $packages[0]['rates'];

		foreach ( $available_methods as $available_method ) {
			if ( $available_method instanceof WC_Shipping_Free_Shipping ) {
				$has_free_shipping = true;
				break;
			}
		}

		if ( ! $has_free_shipping ) {
			$shipping_zone    = WC_Shipping_Zones::get_zone_matching_package( $packages[0] );
			$shipping_methods = $shipping_zone->get_shipping_methods( true );

			foreach ( $shipping_methods as $shipping_method ) {
				if ( $shipping_method instanceof WC_Shipping_Free_Shipping && ( 'min_amount' === $shipping_method->requires || 'either' === $shipping_method->requires ) ) {

					if ( 'no' !== $shipping_method->ignore_discounts && $exclude_discounts && ! empty( WC()->cart->get_coupon_discount_totals() ) ) {
						// Maybe add back the discounts if a coupon code rule is overriding
						foreach ( WC()->cart->get_coupon_discount_totals() as $coupon_value ) {
							$subtotal += $coupon_value;
						}
					}

					if ( $subtotal >= $shipping_method->min_amount ) {
						$has_free_shipping = true;
					} else {
						$amount_remaining = $shipping_method->min_amount - $subtotal;
						$fill_percentage  = ceil( ( $subtotal / $shipping_method->min_amount ) * 100 );
					}
					break;
				}
			}
		}

		if ( ! $has_free_shipping && is_null( $amount_remaining ) ) {
			/**
			 * Filters the free shipping data when no free shipping methods are available
			 *
			 * @param array $data
			 *
			 * @since 7.0.5
			 */
			return apply_filters( 'cfw_shipping_bar_data', $data );
		}

		$data = array(
			'has_free_shipping' => $has_free_shipping,
			'amount_remaining'  => $amount_remaining,
			'fill_percentage'   => $has_free_shipping ? 100 : $fill_percentage,
		);

		/**
		 * Filters the free shipping data
		 *
		 * @param array $data
		 *
		 * @since 7.0.5
		 */
		return apply_filters( 'cfw_shipping_bar_data', $data );
	}

	public function maybe_output_shipping_progress_bar() {
		$quantity          = WC()->cart->get_cart_contents_count();
		$data              = $this->get_free_shipping_data();
		$fill_percent      = $this->get_fill_percentage( $data );
		$has_free_shipping = $this->does_cart_qualifies_for_free_shipping( $data );
		$amount_remaining  = $this->get_remaining_amount_to_qualify_for_free_shipping( $data );

		$free_shipping_message    = SettingsManager::instance()->get_setting( 'side_cart_free_shipping_message' );
		$amount_remaining_message = SettingsManager::instance()->get_setting( 'side_cart_amount_remaining_message' );

		if ( empty( $free_shipping_message ) ) {
			$free_shipping_message = __( 'Congrats! You get free standard shipping.', 'checkout-wc' );
		}

		/**
		 * Filter the message displayed when the cart qualifies for free shipping.
		 *
		 * @param string $free_shipping_message
		 *
		 * @since 7.3.0
		 */
		$free_shipping_message = apply_filters( 'cfw_side_cart_free_shipping_progress_bar_free_shipping_message', $free_shipping_message );

		if ( empty( $amount_remaining_message ) ) {
			// translators: %s is the amount remaining for free shipping
			$amount_remaining_message = __( 'You\'re %s away from free shipping!', 'checkout-wc' );
		}

		/**
		 * Filter the message format for the amount remaining for free shipping
		 *
		 * @param string $amount_remaining_message
		 *
		 * @since 7.3.0
		 *
		 */
		$amount_remaining_message = apply_filters( 'cfw_side_cart_free_shipping_progress_bar_amount_remaining_message_format', $amount_remaining_message );

		$amount_remaining = '<strong>' . wc_price( $amount_remaining, array( 'decimals' => 0.0 === fmod( $amount_remaining, 1 ) ? 0 : 2 ) ) . '</strong>';

		$amount_remaining_message = sprintf( $amount_remaining_message, $amount_remaining );

		if ( ! empty( $data ) && 0 < $quantity ) :
			?>
			<div class="cfw-side-cart-free-shipping-progress-wrap">
				<p class="cfw-xtra-small">
					<?php if ( $has_free_shipping ) : ?>
						<?php echo $free_shipping_message; ?>
					<?php else : ?>
						<?php echo $amount_remaining_message; ?>
					<?php endif; ?>
				</p>

				<div class="cfw-side-cart-free-shipping-progress">
					<div class="cfw-side-cart-free-shipping-progress-indicator"
						 style="width: <?php echo $fill_percent; ?>%;"></div>
				</div>
			</div>
		<?php
		endif;
	}

	public function add_custom_css_property( $properties ) {
		$properties['--cfw-side-cart-free-shipping-progress-indicator']  = SettingsManager::instance()->get_setting( 'side_cart_free_shipping_progress_indicator_color' );
		$properties['--cfw-side-cart-free-shipping-progress-background'] = SettingsManager::instance()->get_setting( 'side_cart_free_shipping_progress_bg_color' );
		$properties['--cfw-side-cart-button-bottom-position']            = SettingsManager::instance()->get_setting( 'floating_cart_button_bottom_position' ) . 'px';
		$properties['--cfw-side-cart-button-right-position']             = SettingsManager::instance()->get_setting( 'floating_cart_button_right_position' ) . 'px';
		$properties['--cfw-side-cart-icon-color']                        = SettingsManager::instance()->get_setting( 'side_cart_icon_color' );
		$properties['--cfw-side-cart-icon-width']                        = SettingsManager::instance()->get_setting( 'side_cart_icon_width' ) . 'px';

		return $properties;
	}

	public function add_side_cart_to_fragments( array $fragments ): array {
		$fragments['#cfw-side-cart-form']     = $this->get_side_cart_fragment();
		$fragments['.cfw-side-cart-quantity'] = self::get_quantity();
		$fragments['#cfw-side-cart-floating-button'] = self::get_floating_cart_icon();

		return $fragments;
	}

	public function maybe_prevent_add_to_cart_redirect( $redirect_url ) {
		// Special handling for this aptly named plugin: Direct checkout, Add to cart redirect, Quick purchase button, Buy now button, Quick View button for WooCommerce
		if ( isset( $_POST['pi_quick_checkout'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return $redirect_url;
		}

		// Otherwise, stop it
		return false;
	}

	public function add_localized_settings( array $event_data ): array {
		$event_data['settings']['enable_ajax_add_to_cart'] = SettingsManager::instance()->get_setting( 'enable_ajax_add_to_cart' ) === 'yes';
		$event_data['runtime_params']['openCart']          = $this->item_just_added_to_cart;

		return $event_data;
	}

	public function remove_cart_breadcrumb( $breadcrumbs ) {
		unset( $breadcrumbs['cart'] );

		return $breadcrumbs;
	}

	public function render_shortcode( $attributes ): string {
		$attributes = shortcode_atts(
			array(
				'color'      => SettingsManager::instance()->get_setting( 'side_cart_icon_color' ),
				'width'      => SettingsManager::instance()->get_setting( 'side_cart_icon_width' ) . 'px',
				'text_color' => '#222',
			),
			$attributes,
			'checkoutwc_cart'
		);

		$output  = "<style>.cfw_cart_icon_shortcode { --cfw-side-cart-icon-color: {$attributes['color']}; --cfw-side-cart-icon-width: {$attributes['width']}; --cfw-side-cart-icon-text-color: {$attributes['text_color']}; }</style>";
		$output .= self::get_cart_icon( 'cfw_cart_icon_shortcode cfw-side-cart-open-trigger' );

		return $output;
	}

	public function run_on_plugin_activation() {
		SettingsManager::instance()->add_setting( 'enable_side_cart', 'no' );
		SettingsManager::instance()->add_setting( 'enable_ajax_add_to_cart', 'no' );
		SettingsManager::instance()->add_setting( 'enable_free_shipping_progress_bar', 'no' );
		SettingsManager::instance()->add_setting( 'side_cart_free_shipping_threshold', '' );
		SettingsManager::instance()->add_setting( 'side_cart_amount_remaining_message', '' );
		SettingsManager::instance()->add_setting( 'side_cart_free_shipping_message', '' );
		SettingsManager::instance()->add_setting( 'side_cart_free_shipping_threshold', '' );
		SettingsManager::instance()->add_setting( 'side_cart_free_shipping_progress_indicator_color', cfw_get_active_template()->get_default_setting( 'button_color' ) );
		SettingsManager::instance()->add_setting( 'enable_floating_cart_button', 'yes' );
		SettingsManager::instance()->add_setting( 'floating_cart_button_bottom_position', '20' );
		SettingsManager::instance()->add_setting( 'floating_cart_button_right_position', '20' );
		SettingsManager::instance()->add_setting( 'enable_order_bumps_on_side_cart', 'no' );
		SettingsManager::instance()->add_setting( 'side_cart_icon_color', '#222222' );
		SettingsManager::instance()->add_setting( 'side_cart_icon_width', '34' );
		SettingsManager::instance()->add_setting( 'side_cart_icon', 'cart-outline.svg' );
		SettingsManager::instance()->add_setting( 'show_side_cart_item_discount', 'yes' );
		SettingsManager::instance()->add_setting( 'enable_side_cart_payment_buttons', 'yes' );
	}

	public static function get_floating_cart_icon(): string {
		ob_start();
		?>
		<a id="cfw-side-cart-floating-button" class="cfw-side-cart-floating-button cfw-side-cart-open-trigger"
		   style="<?php echo ! ( ! WC()->cart->is_empty() || SettingsManager::instance()->get_setting( 'hide_floating_cart_button_empty_cart' ) !== 'yes' ) ? 'display: none;' : ''; ?>"
		   aria-expanded="false" aria-controls="cfw_side_cart" tabindex="10" role="button"
		   aria-label="<?php echo esc_attr( cfw__( 'View cart', 'woocommerce' ) ); ?>">
			<?php echo self::get_cart_icon(); ?>
		</a>
		<?php

		return ob_get_clean();
	}

	public static function get_cart_icon( string $additional_class = '' ): string {
		ob_start();
		?>
		<div class="cfw-side-cart-quantity-wrap <?php echo esc_attr( $additional_class ); ?>">
			<?php echo self::get_cart_icon_file_contents(); ?>

			<?php echo self::get_quantity(); ?>
		</div>
		<?php

		return ob_get_clean();
	}

	public static function get_quantity(): string {
		$quantity = WC()->cart->get_cart_contents_count();

		ob_start();
		?>
		<div class="cfw-side-cart-quantity <?php echo 0 === $quantity ? 'cfw-hidden' : ''; ?>">
			<?php echo $quantity; ?>
		</div>
		<?php

		return ob_get_clean();
	}

	public static function get_cart_icon_file_contents(): string {
		$filename = SettingsManager::instance()->get_setting( 'side_cart_icon' );
		$path     = CFW_PATH . '/build/images/cart-icons/' . $filename;

		if ( ! file_exists( $path ) ) {
			return '';
		}

		return file_get_contents( $path );
	}

	public function output_side_cart_edit_item_quantity_control( array $cart_item, string $cart_item_key, ItemInterface $item ) {
		/**
		 * Filters whether to disable side cart item quantity control
		 *
		 * @param int $disable_side_cart_item_quantity_control Whether to disable cart editing
		 * @param array $cart_item The cart item
		 * @param string $cart_item_key The cart item key
		 *
		 * @since 8.0.0
		 *
		 */
		$disable_side_cart_item_quantity_control = apply_filters( 'cfw_disable_side_cart_item_quantity_control', false, $cart_item, $cart_item_key );

		if ( $disable_side_cart_item_quantity_control ) {
			return;
		}

		echo wp_kses( cfw_get_cart_item_quantity_control( $cart_item, $cart_item_key, $item->get_product() ), cfw_get_allowed_html() );
	}

	public function maybe_output_cart_item_variation_edit_link( array $cart_item, string $cart_item_key ) {
		if ( SettingsManager::instance()->get_setting( 'allow_side_cart_item_variation_changes' ) !== 'yes' ) {
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
		if ( apply_filters( 'cfw_disable_cart_variation_editing', false, $cart_item, $cart_item_key, 'side_cart' ) ) {
			return;
		}
		?>
		<a href="javascript:" class="cfw-cart-edit-item-variation cfw-xtra-small"
		   data-product="<?php echo esc_attr( $cart_item['product_id'] ); ?>"
		   data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>">
			<?php echo cfw__( 'Edit', 'woocommerce' ); ?>
		</a>
		<?php
	}

	function maybe_output_suggested_products() {
		if ( SettingsManager::instance()->get_setting( 'enable_side_cart_suggested_products' ) !== 'yes' ) {
			return;
		}

		$random_fallback = SettingsManager::instance()->get_setting( 'enable_side_cart_suggested_products_random_fallback' ) === 'yes';
		$heading         = SettingsManager::instance()->get_setting( 'side_cart_suggested_products_heading' );

		if ( empty( $heading ) ) {
			$heading = cfw__( 'You may also like&hellip;', 'woocommerce' );
		}

		$products = cfw_get_suggested_products( 3, $random_fallback );

		if ( empty( $products ) ) {
			return;
		}
		?>
		<div class="cfw-suggested-products-wrap">
			<h4 class="cfw-suggested-products-heading">
				<?php echo wp_kses_post( $heading ); ?>
			</h4>
			<div class="cfw-suggested-products">
				<?php
				foreach ( $products as $product ) :
					$variation_parent = $product->is_type( 'variable' ) && 0 === $product->get_parent_id();
					?>
					<div class="cfw-suggested-product">
						<div class="row">
							<?php if ( $product->get_image_id() !== '' ) : ?>
								<div class="col-2">
									<?php echo $product->get_image( 'cfw_cart_thumb' ); ?>
								</div>
							<?php endif; ?>
							<div class="cfw-suggested-product-description col">
								<div class="cfw-suggested-product-title">
									<?php echo wp_kses_post( $product->get_title() ); ?>
								</div>
								<div class="cfw-suggested-product-price">
									<?php
									if ( apply_filters( 'cfw_show_cart_item_discount', SettingsManager::instance()->get_setting( 'show_cart_item_discount' ) === 'yes' ) ) {
										echo wp_kses_post( $product->get_price_html() );
									}
									?>
								</div>
								<button type="submit" class="cfw-suggested-product-add-to-cart cfw-secondary-btn"
										name="add-to-cart" data-product="<?php echo esc_attr( $product->get_id() ); ?>"
										data-variable="<?php echo $variation_parent ? 'true' : 'false'; ?>"
										value="<?php echo esc_attr( $product->get_id() ); ?>"
										class="single_add_to_cart_button button alt<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}

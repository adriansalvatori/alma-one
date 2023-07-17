<?php

namespace Objectiv\Plugins\Checkout\API;

use Exception;
use Objectiv\Plugins\Checkout\Factories\BumpFactory;
use Objectiv\Plugins\Checkout\Interfaces\BumpInterface;
use WC_Product;
use WP_REST_Request;

class ModalOrderBumpProductFormAPI {
	protected $route                           = 'modal-order-bump-product-form';
	protected $cfw_ob_offer_cancel_button_text = '';

	public function __construct() {
		add_action( 'rest_api_init', function () {
			register_rest_route( 'checkoutwc/v1', $this->route . '/(?P<bump_id>\d{1,12})', array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_product_form' ),
				'permission_callback' => function () {
					return true;
				}
			) );
		} );
	}

	/**
	 * Get the bumps
	 *
	 * @throws Exception
	 */
	public function get_product_form( WP_REST_Request $data ) {
		$bump    = BumpFactory::get( $data->get_param( 'bump_id' ) );
		$product = $bump->get_offer_product();

		$this->cfw_ob_offer_cancel_button_text = empty( $this->cfw_ob_offer_cancel_button_text ) ? get_post_meta( $bump->get_id(), 'cfw_ob_offer_cancel_button_text', true ) : $this->cfw_ob_offer_cancel_button_text;

		if ( empty( $this->cfw_ob_offer_cancel_button_text ) ) {
			$this->cfw_ob_offer_cancel_button_text = __( 'No thanks', 'checkout-wc' );
		}

		if ( ! $product ) {
			return new \WP_Error( 'product_not_found', __( 'Product not found', 'checkout-wc' ), array( 'status' => 404 ) );
		}

		$product_form_html = '';

		if ( $product->is_type( 'variable' ) && 0 === $product->get_parent_id() ) {
			$product_form_html = $this->get_variable_product_form( $product, $bump );
		} else {
			$product_form_html = $this->get_regular_product_form( $product, $bump );
		}

		return rest_ensure_response(
			array(
				'html' => $product_form_html,
			)
		);
	}

	protected function get_variable_product_form( \WC_Product_Variable $variable_product, BumpInterface $bump ) {
		$selected_variation = array();
		$cart_item          = array();

		if ( isset( $_GET['key'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$cart_item          = WC()->cart->get_cart_item( $_GET['key'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$selected_variation = $cart_item['variation'];
		}

		$selected_qty         = (float) $cart_item['quantity'] ?? 1;
		$available_variations = $variable_product->get_available_variations();
		$variations_json      = wp_json_encode( $available_variations );
		$variations_attr      = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );
		$attributes           = $variable_product->get_variation_attributes();
		$image                = $variable_product->get_image();

		ob_start();
		?>
		<form class="cfw-product-form-modal variable cfw-modal-order-bump-form container"
			  action="<?php echo esc_url( cfw_apply_filters( 'woocommerce_add_to_cart_form_action', $variable_product->get_permalink() ) ); ?>"
			  method="post" enctype='multipart/form-data'
			  data-product_id="<?php echo esc_html( absint( $variable_product->get_id() ) ); ?>"
			  data-product_variations="<?php echo $variations_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
			<input type="hidden" name="cfw_ob_id"
				   value="<?php echo esc_attr( sanitize_key( $bump->get_id() ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>">

			<div class="row">
				<?php if ( ! empty( $image ) ) : ?>
					<div class="col-lg-6 col-sm-12 me-auto">
						<div class="cfw-product-form-modal-image-wrap">
							<?php echo wp_kses_post( $image ); ?>
						</div>
					</div>
				<?php endif; ?>

				<div class="variations col-lg-6 col-sm-12">
					<h4 class="cfw-product-form-modal-title cfw-mb">
						<?php echo wp_kses_post( $variable_product->get_name() ); ?>
					</h4>

					<p>
						<?php echo wp_kses_post( $bump->get_offer_product_price() ); ?>
					</p>

					<p>
						<?php echo wp_kses_post( $bump->get_offer_description() ); ?>
					</p>

					<?php foreach ( $attributes as $attribute_name => $options ) : ?>
						<div class="cfw-mb">
							<label class="cfw-small"
								   for="<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>">
								<?php echo wp_kses_post( wc_attribute_label( $attribute_name ) ); ?>
							</label>
							<br/>
							<?php
							wc_dropdown_variation_attribute_options(
								array(
									'options'   => $options,
									'attribute' => $attribute_name,
									'product'   => $variable_product,
									'selected'  => $selected_variation[ 'attribute_' . sanitize_title( $attribute_name ) ] ?? false,
								)
							);
							?>
						</div>
					<?php endforeach; ?>

					<p>
						<button type="submit" name="add-to-cart"
								value="<?php echo esc_attr( $variable_product->get_id() ); ?>"
								class="cfw-primary-btn single_add_to_cart_button button">
							<?php echo wp_kses_post( $bump->get_offer_language() ); ?>
						</button>
					</p>
					<a href="javascript:" class="cfw-bump-reject">
						<?php echo wp_kses_post( do_shortcode( $this->cfw_ob_offer_cancel_button_text ) ); ?>
					</a>
				</div>
			</div>
			<?php
			global $product;
			$current_product = $product;
			$product         = $variable_product;
			?>
			<div class="single_variation_wrap">
				<?php cfw_do_action( 'woocommerce_single_variation' ); ?>
			</div>
			<?php
			woocommerce_quantity_input(
				array(
					'min_value'   => cfw_apply_filters( 'woocommerce_quantity_input_min', $variable_product->get_min_purchase_quantity(), $variable_product ),
					'max_value'   => cfw_apply_filters( 'woocommerce_quantity_input_max', $variable_product->get_max_purchase_quantity(), $variable_product ),
					'input_value' => $selected_qty ? wc_stock_amount( wp_unslash( $selected_qty ) ) : $variable_product->get_min_purchase_quantity(),
					'classes'     => array( 'cfw-hidden' ),
				),
				$variable_product
			);
			$product = $current_product;
			?>
			<input type="hidden" name="variation_id" class="variation_id" value="0"/>
		</form>
		<?php

		return ob_get_clean();
	}

	protected function get_regular_product_form( WC_Product $product, BumpInterface $bump ) {
		ob_start();
		$image                                 = $product->get_image();
		$this->cfw_ob_offer_cancel_button_text = get_post_meta( $bump->get_id(), 'cfw_ob_offer_cancel_button_text', true );

		if ( empty( $this->cfw_ob_offer_cancel_button_text ) ) {
			$this->cfw_ob_offer_cancel_button_text = __( 'No thanks, just complete my order', 'checkout-wc' );
		}
		?>
		<form class="cfw-product-form-modal cfw-modal-order-bump-form container"
			  action="<?php echo esc_url( cfw_apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>"
			  method="post" enctype='multipart/form-data'>
			<input type="hidden" name="cfw_ob_id"
				   value="<?php echo esc_attr( sanitize_key( $bump->get_id() ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>">

			<div class="row">
				<?php if ( ! empty( $image ) ) : ?>
					<div class="col-lg-6 col-sm-12 me-auto">
						<div class="cfw-product-form-modal-image-wrap">
							<?php echo wp_kses_post( $image ); ?>
						</div>
					</div>
				<?php endif; ?>
				<div class="col cfw-product-form-modal-content">
					<h4 class="cfw-product-form-modal-title cfw-mb">
						<?php echo wp_kses_post( $product->get_name() ); ?>
					</h4>

					<p>
						<?php echo wp_kses_post( $bump->get_offer_product_price() ); ?>
					</p>

					<p>
						<?php echo wp_kses_post( $bump->get_offer_description() ); ?>
					</p>

					<p>
						<button type="submit" name="add-to-cart"
								value="<?php echo esc_attr( $product->get_id() ); ?>"
								class="cfw-primary-btn single_add_to_cart_button button">
							<?php echo wp_kses_post( $bump->get_offer_language() ); ?>
						</button>
					</p>
					<a href="javascript:" class="cfw-bump-reject">
						<?php echo wp_kses_post( do_shortcode( $this->cfw_ob_offer_cancel_button_text ) ); ?>
					</a>
				</div>
			</div>
			<?php
			woocommerce_quantity_input(
				array(
					'min_value'   => cfw_apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
					'max_value'   => cfw_apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
					'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity(),
					// WPCS: CSRF ok, input var ok.
					'classes'     => array( 'cfw-hidden' ),
				)
			);
			?>
		</form>
		<?php
		return ob_get_clean();
	}
}

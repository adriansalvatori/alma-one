<?php

namespace Objectiv\Plugins\Checkout\API;

use WC_Product_Variable;
use WP_REST_Request;

class GetVariationFormAPI {
	public function __construct() {
		add_action( 'rest_api_init', function () {
			register_rest_route( 'checkoutwc/v1', 'get-variation-form/(?P<product_id>\d{1,12})', array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_form' ),
				'permission_callback' => function () {
					return true;
				}
			) );
		} );
	}

	public function get_form( WP_REST_Request $data ) {
		$product = wc_get_product( $data->get_param( 'product_id' ) );

		if ( ! is_a( $product, '\WC_Product_Variable' ) ) {
			return new \WP_Error( 'product_not_found', __( 'Product not found', 'checkout-wc' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response(
			array(
				'html' => $this->get_variable_product_form( $product )
			)
		);
	}

	protected function get_variable_product_form( WC_Product_Variable $variable_product ) {
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
		<form class="cfw-product-form-modal variable container" action="<?php echo esc_url( cfw_apply_filters( 'woocommerce_add_to_cart_form_action', $variable_product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data' data-product_id="<?php echo esc_html( absint( $variable_product->get_id() ) ); ?>" data-product_variations="<?php echo $variations_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
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

					<?php foreach ( $attributes as $attribute_name => $options ) : ?>
						<div class="row cfw-mb">
							<div class="col cfw-product-form-modal-content">
								<label class="cfw-small" for="<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>">
									<?php echo wp_kses_post( wc_attribute_label( $attribute_name ) ); ?>
								</label>
								<br />
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
						</div>
					<?php endforeach; ?>

					<div class="single_variation_wrap">
						<?php woocommerce_single_variation(); ?>
					</div>

					<?php if ( ! isset( $_GET['key'] ) ): ?>
					<p>
						<button type="submit" name="add-to-cart" value="<?php echo esc_attr( $variable_product->get_id() ); ?>" class="cfw-primary-btn single_add_to_cart_button button">
							<?php cfw_e( 'Add to cart', 'woocommerce' ); ?>
						</button>
					</p>
					<?php else: ?>
						<input type="hidden" name="key" value="<?php echo esc_attr( $_GET['key'] ); ?>" />
					<?php endif; ?>
				</div>
			</div>
			<?php
			global $product;
			$current_product = $product;
			$product         = $variable_product;

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
			<input type="hidden" name="variation_id" class="variation_id" value="0" />
			<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $variable_product->get_id() ); ?>" />
		</form>
		<?php

		return ob_get_clean();
	}
}

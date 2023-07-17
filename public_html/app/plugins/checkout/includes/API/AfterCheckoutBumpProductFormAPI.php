<?php

namespace Objectiv\Plugins\Checkout\API;

use Exception;
use Objectiv\Plugins\Checkout\Factories\BumpFactory;
use Objectiv\Plugins\Checkout\Interfaces\BumpInterface;
use WP_REST_Request;

class AfterCheckoutBumpProductFormAPI extends ModalOrderBumpProductFormAPI {
	protected $route = 'order-bump-upsell-product-form';

	/**
	 * Get the bumps
	 *
	 * @throws Exception
	 */
	public function get_product_form( WP_REST_Request $data ) {
		$bump                                  = BumpFactory::get( $data->get_param( 'bump_id' ) );
		$this->cfw_ob_offer_cancel_button_text = get_post_meta( $bump->get_id(), 'cfw_ob_offer_cancel_button_text', true );

		if ( empty( $this->cfw_ob_offer_cancel_button_text ) ) {
			$this->cfw_ob_offer_cancel_button_text = __( 'No thanks, just complete my order', 'checkout-wc' );
		}

		$product_form_html = parent::get_product_form( $data );

		if ( is_wp_error( $product_form_html ) ) {
			return $product_form_html;
		}

		return $this->wrap_product_form( $product_form_html->get_data()['html'] ?? 'Error', $bump );
	}

	protected function wrap_product_form( string $html, BumpInterface $bump ) {
		$cfw_ob_offer_heading    = get_post_meta( $bump->get_id(), 'cfw_ob_offer_heading', true );
		$cfw_ob_offer_subheading = get_post_meta( $bump->get_id(), 'cfw_ob_offer_subheading', true );

		if ( empty( $cfw_ob_offer_heading ) ) {
			$cfw_ob_offer_heading = __( 'Your order is almost complete...', 'checkout-wc' );
		}

		if ( empty( $cfw_ob_offer_subheading ) ) {
			$cfw_ob_offer_subheading = __( 'Add this offer to your order and save!', 'checkout-wc' );
		}

		ob_start();
		?>
		<div class="cfw-bump-modal-wrap">
			<div class="stepper-wrapper">
				<div class="stepper-item completed">
					<div class="step-counter"></div>
					<div class="step-name">
						<?php esc_html_e( 'Order Submitted', 'checkout-wc' ); ?>
					</div>
				</div>
				<div class="stepper-item completed">
					<div class="step-counter"></div>
					<div class="step-name">
						<?php esc_html_e( 'Special Offer', 'checkout-wc' ); ?>
					</div>
				</div>
				<div class="stepper-item">
					<div class="step-counter"></div>
					<div class="step-name">
						<?php esc_html_e( 'Order Received', 'checkout-wc' ); ?>
					</div>
				</div>
			</div>

			<h2>
				<?php echo wp_kses_post( do_shortcode( $cfw_ob_offer_heading ) ); ?>
			</h2>

			<h3>
				<?php echo wp_kses_post( do_shortcode( $cfw_ob_offer_subheading ) ); ?>
			</h3>

			<?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php
		return rest_ensure_response(
			array(
				'html' => ob_get_clean()
			)
		);
	}
}

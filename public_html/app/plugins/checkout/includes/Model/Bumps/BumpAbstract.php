<?php

namespace Objectiv\Plugins\Checkout\Model\Bumps;

use Objectiv\Plugins\Checkout\Interfaces\BumpInterface;
use Objectiv\Plugins\Checkout\Factories\BumpFactory;

abstract class BumpAbstract implements BumpInterface {
	protected $id;
	protected $title;
	protected $display_for;
	protected $products = array();
	protected $excluded_products;
	protected $categories;
	protected $any_product = false;
	protected $location;
	protected $discount_type;
	protected $offer_product;
	protected $offer_discount;
	protected $offer_language;
	protected $offer_description;
	protected $upsell = false;
	protected $offer_quantity;
	protected $can_quantity_be_updated = false;

	public function __construct() {}

	/**
	 * Load the bump
	 *
	 * @param $post
	 * @return bool
	 */
	public function load( $post ): bool {
		$this->id                      = $post->ID;
		$this->title                   = $post->post_title;
		$this->display_for             = get_post_meta( $post->ID, 'cfw_ob_display_for', true );
		$this->products                = get_post_meta( $post->ID, 'cfw_ob_products', true );
		$this->excluded_products       = get_post_meta( $post->ID, 'cfw_ob_exclude_products', true );
		$this->categories              = get_post_meta( $post->ID, 'cfw_ob_categories', true );
		$this->any_product             = get_post_meta( $post->ID, 'cfw_ob_any_product', true ) === 'yes';
		$this->location                = get_post_meta( $post->ID, 'cfw_ob_display_location', true );
		$this->discount_type           = get_post_meta( $post->ID, 'cfw_ob_discount_type', true );
		$this->offer_product           = get_post_meta( $post->ID, 'cfw_ob_offer_product', true );
		$this->offer_discount          = get_post_meta( $post->ID, 'cfw_ob_offer_discount', true );
		$this->offer_language          = get_post_meta( $post->ID, 'cfw_ob_offer_language', true );
		$this->offer_description       = get_post_meta( $post->ID, 'cfw_ob_offer_description', true );
		$this->upsell                  = get_post_meta( $post->ID, 'cfw_ob_upsell', true ) === 'yes';
		$this->offer_quantity          = get_post_meta( $post->ID, 'cfw_ob_offer_quantity', true );
		$this->can_quantity_be_updated = get_post_meta( $post->ID, 'cfw_ob_enable_quantity_updates', true ) === 'yes';

		// Get post type of offer_product
		$this->offer_product = apply_filters( 'wpml_object_id', $this->offer_product, get_post_type( $this->offer_product ), true );

		if ( empty( $this->offer_quantity ) ) {
			$this->offer_quantity = 1;
		}

		return true;
	}

	/**
	 * Get bump title
	 *
	 * @return string
	 */
	public function get_title(): string {
		return $this->title;
	}

	/**
	 * Is displayable
	 *
	 * @return bool
	 */
	abstract public function is_displayable(): bool;

	/**
	 * Is cart bump valid
	 *
	 * @return bool
	 */
	abstract public function is_cart_bump_valid(): bool;

	public function is_published(): bool {
		return get_post_status( $this->id ) === 'publish';
	}

	/**
	 * Is valid upsell
	 *
	 * @return bool
	 */
	public function is_valid_upsell(): bool {
		return $this->upsell && 'specific_products' === $this->display_for && count( $this->products ) === 1;
	}

	public function can_offer_product_be_added_to_the_cart(): bool {
		$product = $this->get_offer_product();

		return $product && $product->is_purchasable() && ( $product->is_in_stock() || $product->backorders_allowed() );
	}

	public function add_bump_meta_to_order_item( $item, $values ) {
		if ( ! $this->is_cart_bump_valid() || ! $this->is_published() ) {
			return;
		}

		$item->update_meta_data( '_cfw_order_bump_id', $values['_cfw_order_bump_id'] );
	}

	public function get_cfw_cart_item_discount( string $price_html ): string {
		if ( ! $this->is_cart_bump_valid() || ! $this->is_published() ) {
			return $price_html;
		}

		return $this->get_offer_product_price();
	}

	/**
	 * Add to cart
	 *
	 * @throws \Exception
	 */
	public function add_to_cart( \WC_Cart $cart ) {
		$product        = $this->get_offer_product();
		$variation_id   = $product->is_type( 'variable' ) ? $product->get_id() : null;
		$product_id     = $product->is_type( 'variable' ) && 0 !== $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		$variation_data = null;
		$metadata       = array(
			'_cfw_order_bump_id' => $this->id,
		);

		if ( $product->is_type( 'variation' ) ) {
			$variation_data = array();

			foreach ( $product->get_variation_attributes() as $taxonomy => $term_names ) {
				$taxonomy                                = str_replace( 'attribute_', '', $taxonomy );
				$attribute_label_name                    = str_replace( 'attribute_', '', wc_attribute_label( $taxonomy ) );
				$variation_data[ $attribute_label_name ] = $term_names;
			}
		}

		$quantity = $this->get_offer_quantity();

		do_action( 'cfw_before_order_bump_add_to_cart', $this );

		if ( has_action( 'cfw_order_bump_add_to_cart_product_type_' . $product->get_type() ) ) {
			do_action( 'cfw_order_bump_add_to_cart_product_type_' . $product->get_type(), $product_id, $quantity, $variation_id, $variation_data, $metadata, $product );

			return true;
		}

		try {
			return $cart->add_to_cart( $product_id, $quantity, $variation_id, $variation_data, $metadata );
		} catch ( \Exception $e ) {
			wc_get_logger()->error( 'CheckoutWC: Failed to add order bump to cart. Error: ' . $e->getMessage(), array( 'source' => 'checkout-wc' ) );
			return false;
		}

	}

	/**
	 * @param int $needle_product_id
	 * @return int
	 */
	public function quantity_of_product_in_cart( int $needle_product_id ): int {
		$needle_product = wc_get_product( $needle_product_id );

		if ( ! $needle_product ) {
			return 0;
		}

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( $this->cart_item_is_product( $cart_item, $needle_product_id ) ) {
				return $cart_item['quantity'];
			}
		}

		return 0;
	}

	/**
	 * @param int $needle_product_id
	 * @return int
	 */
	public function quantity_of_normal_product_in_cart( int $needle_product_id ): int {
		$needle_product = wc_get_product( $needle_product_id );

		if ( ! $needle_product ) {
			return 0;
		}

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( isset( $cart_item['_cfw_order_bump_id'] ) ) {
				continue;
			}

			if ( $this->cart_item_is_product( $cart_item, $needle_product_id ) ) {
				return $cart_item['quantity'];
			}
		}

		return 0;
	}

	protected function cart_item_is_product( array $cart_item, int $product_id ): bool {
		if ( $cart_item['product_id'] === $product_id ) {
			return true;
		}

		if ( empty( $cart_item['variation_id'] ) ) {
			return false;
		}

		if ( $cart_item['variation_id'] === $product_id ) {
			return true;
		}

		return wp_get_post_parent_id( $cart_item['variation_id'] ) === $product_id;
	}

	public function record_purchased() {
		$this->increment_displayed_on_purchases_count();
		$this->increment_purchased_count();
		$this->update_conversion_rate();

		$offer_product = $this->get_offer_product();

		if ( ! $offer_product ) {
			return;
		}

		if ( $this->is_valid_upsell() ) {
			$base_product = wc_get_product( $this->get_products()[0] );

			if ( ! $base_product ) {
				return;
			}

			$new_revenue = $base_product->get_price() - $this->get_price();
		} else {
			$new_revenue = $this->get_price();
		}

		$this->add_captured_revenue( $new_revenue );
	}

	public function display( string $location ): bool {
		$display_bump          = $this->is_displayable() && ! $this->is_excluded() && ( 'all' === $location || $this->get_display_location() === $location );
		$filtered_display_bump = apply_filters( 'cfw_display_bump', $display_bump, $this, $location );

		if ( ! $filtered_display_bump ) {
			return false;
		}

		$link_wrap        = '<div class="cfw-order-bump-image"><a target="_blank" href="%s">%s</a></div>';
		$offer_product    = $this->get_offer_product();
		$thumb            = $offer_product->get_image( 'cfw_cart_thumb' );
		$wrapped_thumb    = $offer_product->is_visible() ? sprintf( $link_wrap, $offer_product->get_permalink(), $thumb ) : $thumb;
		$variation_parent = $this->get_offer_product()->is_type( 'variable' ) && 0 === $this->get_offer_product()->get_parent_id() && 'no' === get_post_meta( $this->get_id(), 'cfw_ob_enable_auto_match', true );
		?>
		<div class="cfw-order-bump cfw-module">
			<input type="hidden" name="cfw_displayed_order_bump[]" value="<?php echo esc_attr( $this->get_id() ); ?>" />

			<div class="cfw-order-bump-header">
				<label>
					<input type="checkbox" class="cfw_order_bump_check" name="<?php echo $variation_parent ? 'placeholder' : 'cfw_order_bump[]'; ?>" value="<?php echo esc_attr( $this->get_id() ); ?>" data-variable="<?php echo $variation_parent ? 'true' : 'false'; ?>" data-product="<?php echo $this->get_offer_product()->get_id(); ?>" />
					<span>
						<?php echo do_shortcode( $this->get_offer_language() ); ?>
					</span>
				</label>
			</div>
			<div class="cfw-order-bump-body">
				<div class="row">
					<div class="col-2">
						<?php echo wp_kses_post( $wrapped_thumb ); ?>
					</div>
					<div class="col-10 cfw-order-bump-content">
						<?php echo do_shortcode( $this->get_offer_description() ); ?>

						<div class="cfw-order-bump-total">
							<?php echo wp_kses_post( $this->get_offer_product_price() ); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
		return true;
	}

	public function is_excluded(): bool {
		// If excluded product is in the cart, dont' show
		foreach ( $this->get_excluded_products() as $excluded_product ) {
			if ( $this->quantity_of_normal_product_in_cart( (int) $excluded_product ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function get_offer_language(): string {
		return $this->offer_language;
	}

	/**
	 * @return string
	 */
	public function get_offer_description(): string {
		return $this->offer_description;
	}

	/**
	 * @return mixed
	 */
	public function get_offer_quantity() {
		return $this->offer_quantity;
	}

	/**
	 * @return false|\WC_Product|null
	 */
	public function get_offer_product() {
		return wc_get_product( $this->offer_product );
	}

	/**
	 * @return string
	 */
	public function get_offer_product_price(): string {
		$product              = $this->get_offer_product();
		$price                = wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) );
		$sale_price           = wc_get_price_to_display( $product, array( 'price' => $this->get_price() ) );
		$sale_price_formatted = wc_price( $sale_price );

		if ( $price > $sale_price ) {
			return wc_format_sale_price( $price, $sale_price );
		}

		return $sale_price_formatted;
	}

	/**
	 * Get the bump price
	 *
	 * @param string $context
	 *
	 * @return float
	 */
	public function get_price( string $context = 'view' ): float {
		$product = $this->get_offer_product();

		$discount_type = $this->discount_type;
		$discount      = $this->offer_discount;

		if ( wc_prices_include_tax() ) {
			$price = wc_get_price_including_tax( $product, array( 'price' => $product->get_price( $context ) ) );
		} else {
			$price = wc_get_price_excluding_tax( $product, array( 'price' => $product->get_price( $context ) ) );
		}

		if ( 'percent' === $discount_type ) {
			$discount_value = $price * ( $discount / 100 );
		} else {
			$discount_value = $discount;
		}

		// Run amount off discounts through product price filter if we're in a view context
		// This is to allow currency plugins can adjust the currency of the discounted amount properly
		if ( 'view' === $context && 'percent' !== $discount_type ) {
			$discount_value = cfw_apply_filters( 'woocommerce_product_get_price', $discount_value, $product );
		}

		/**
		 * Filter the order bump price.
		 *
		 * @since 5.0.0
		 * @param float $price The price of the order bump.
		 * @param string $context The context of the price.
		 * @param BumpInterface $order_bump The order bump object.
		 */
		return apply_filters( 'cfw_order_bump_get_price', $price - $discount_value, $context, $this );
	}

	public function get_display_location(): string {
		return $this->location ?? 'below_cart_items';
	}

	/**
	 * Get Displayed On Purchases Count
	 *
	 * The number of times this bump was displayed and a purchase was subsequently made.
	 *
	 * @return integer
	 */
	private function get_displayed_on_purchases_count(): int {
		return intval( get_post_meta( $this->id, 'times_bump_displayed_on_purchases', true ) );
	}

	/**
	 * Get Purchase Count
	 *
	 * The number of times this bump was added to the cart and purchased.
	 *
	 * @return integer
	 */
	public function get_purchase_count(): int {
		return intval( get_post_meta( $this->id, 'times_bump_purchased', true ) );
	}

	public function record_displayed() {
		$this->increment_displayed_on_purchases_count();
		$this->update_conversion_rate();
	}

	public function increment_displayed_on_purchases_count() {
		update_post_meta( $this->id, 'times_bump_displayed_on_purchases', $this->get_displayed_on_purchases_count() + 1 );
	}

	public function increment_purchased_count() {
		update_post_meta( $this->id, 'times_bump_purchased', $this->get_purchase_count() + 1 );
	}

	public function add_captured_revenue( float $new_revenue ) {
		$captured_revenue = max( (float) get_post_meta( $this->id, 'captured_revenue', true ), 0.0 );

		$new_revenue = apply_filters( 'cfw_order_bump_captured_revenue', $new_revenue, $this );

		update_post_meta(
			$this->id,
			'captured_revenue',
			$captured_revenue + $new_revenue
		);
	}

	public function update_conversion_rate() {
		$purchase_count  = $this->get_purchase_count();
		$displayed_count = $this->get_displayed_on_purchases_count();
		$not_calculable  = min( $purchase_count, $displayed_count ) < 1;

		$value = $not_calculable ? 0 : round( $purchase_count / $displayed_count * 100, 2 );

		update_post_meta( $this->id, 'conversion_rate', $value );
	}

	public function get_conversion_rate(): string {
		$value = get_post_meta( $this->id, 'conversion_rate', true );

		return '' === $value ? '--' : floatval( $value ) . '%';
	}

	public function get_item_removal_behavior(): string {
		$value = get_post_meta( $this->id, 'cfw_ob_item_removal_behavior', true );

		return empty( $value ) ? 'keep' : $value;
	}

	public function get_estimated_revenue(): float {
		$offer_product = $this->get_offer_product();

		if ( ! $offer_product ) {
			return 0.0;
		}

		if ( $this->is_valid_upsell() ) {
			$base_product = wc_get_product( $this->get_products()[0] );

			if ( ! $base_product ) {
				return 0.0;
			}

			return ( $base_product->get_price() - $this->get_price() ) * $this->get_purchase_count();
		}

		return $this->get_purchase_count() * $this->get_price();
	}

	public function get_captured_revenue(): float {
		return floatval( get_post_meta( $this->id, 'captured_revenue', true ) );
	}

	/**
	 * @return array
	 */
	public function get_products(): array {
		return (array) $this->products;
	}

	/**
	 * @return array
	 */
	public function get_excluded_products(): array {
		return (array) $this->excluded_products;
	}

	public static function get_post_type(): string {
		return 'cfw_order_bumps';
	}

	public static function init( $parent_menu_slug ) {
		$post_type = self::get_post_type();

		add_action(
			'init',
			function() use ( $post_type, $parent_menu_slug ) {
				register_post_type(
					$post_type,
					array(
						'labels'             => array(
							'name'               => cfw__( 'Order Bumps', 'checkout-wc' ),
							'singular_name'      => cfw__( 'Order Bump', 'checkout-wc' ),
							'add_new'            => cfw__( 'Add New', 'checkout-wc' ),
							'add_new_item'       => cfw__( 'Add New Order Bump', 'checkout-wc' ),
							'edit_item'          => cfw__( 'Edit Order Bump', 'checkout-wc' ),
							'new_item'           => cfw__( 'New Order Bump', 'checkout-wc' ),
							'view_item'          => cfw__( 'View Order Bump', 'checkout-wc' ),
							'search_items'       => cfw__( 'Find Order Bump', 'checkout-wc' ),
							'not_found'          => cfw__( 'No order bumps were found.', 'checkout-wc' ),
							'not_found_in_trash' => cfw__( 'Not found in trash', 'checkout-wc' ),
							'menu_name'          => cfw__( 'Order Bumps', 'checkout-wc' ),
						),
						'public'             => false,
						'publicly_queryable' => true,
						'show_ui'            => true,
						'show_in_menu'       => false,
						'query_var'          => false,
						'rewrite'            => false,
						'has_archive'        => false,
						'hierarchical'       => false,
						'supports'           => array( 'title', 'page-attributes' ),
						'capabilities'       => array(
							'edit_post'          => 'manage_options',
							'read_post'          => 'manage_options',
							'delete_post'        => 'manage_options',
							'edit_posts'         => 'manage_options',
							'edit_others_posts'  => 'manage_options',
							'delete_posts'       => 'manage_options',
							'publish_posts'      => 'manage_options',
							'read_private_posts' => 'manage_options',
						),
					)
				);
			}
		);
	}

	/**
	 * @return bool
	 */
	public function is_in_cart(): bool {
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( isset( $cart_item['_cfw_order_bump_id'] ) && $cart_item['_cfw_order_bump_id'] === $this->id ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function can_quantity_be_updated(): bool {
		return $this->can_quantity_be_updated;
	}
}

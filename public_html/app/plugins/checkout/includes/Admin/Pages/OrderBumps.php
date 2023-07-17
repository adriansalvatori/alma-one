<?php

namespace Objectiv\Plugins\Checkout\Admin\Pages;

use Objectiv\Plugins\Checkout\Admin\TabNavigation;
use Objectiv\Plugins\Checkout\Admin\Pages\Traits\TabbedAdminPageTrait;
use Objectiv\Plugins\Checkout\Factories\BumpFactory;
use Objectiv\Plugins\Checkout\Managers\PlanManager;
use Objectiv\Plugins\Checkout\Model\Bumps\BumpAbstract;

/**
 * @link checkoutwc.com
 * @since 5.0.0
 * @package Objectiv\Plugins\Checkout\Admin\Pages
 */
class OrderBumps extends PageAbstract {
	use TabbedAdminPageTrait;

	protected $post_type_slug;
	protected $nonce_field  = '_cfw_ob_nonce';
	protected $nonce_action = 'cfw_save_ob_mb';
	protected $formatted_required_plans_list;
	protected $is_available;
	protected $tab_navigation;

	public function __construct( string $post_type_slug, string $formatted_required_plans_list, bool $is_available ) {
		parent::__construct( cfw__( 'Order Bumps', 'checkout-wc' ), 'manage_options', 'order_bumps' );

		$this->post_type_slug                = $post_type_slug;
		$this->formatted_required_plans_list = $formatted_required_plans_list;
		$this->is_available                  = $is_available;
	}

	public function init() {
		parent::init();

		$this->tab_navigation = new TabNavigation( 'Order Bumps', 'subpage' );

		$this->tab_navigation->add_tab( 'Settings', add_query_arg( array( 'subpage' => 'settings' ), $this->get_url() ) );
		$this->tab_navigation->add_tab( 'Manage Bumps', add_query_arg( array(
			'post_type' => $this->post_type_slug,
		), admin_url( 'edit.php' ) ) );

		add_action( 'all_admin_notices', array( $this, 'output_post_type_editor_header' ) );

		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_metaboxes' ) );
		add_action( 'all_admin_notices', array( $this, 'maybe_show_license_upgrade_splash' ) );

		// Add checkbox to publish metabox
		add_action( 'post_submitbox_misc_actions', array( $this, 'add_stat_reset_checkbox' ) );

		/**
		 * Highlights Order Bumps submenu item when
		 * on the New Order Bumps admin page
		 */
		add_filter( 'submenu_file', array( $this, 'maybe_highlight_order_bumps_submenu_item' ) );

		/**
		 * Highlight parent menu
		 */
		add_filter( 'parent_file', array( $this, 'menu_highlight' ) );

		$post_type = $this->post_type_slug;

		add_filter(
			"manage_{$post_type}_posts_columns",
			function( $columns ) {
				$date = array_pop( $columns );

				$columns['order_bump_id']   = 'ID';
				$columns['conversion_rate'] = 'Conversion Rate' . wc_help_tip( 'Conversion Rate tracks how often a bump is added to an actual completed purchase. If 20 orders are placed and a bump was displayed on 10 of those orders and the bump was purchased 5 times, the conversion rate is 50%.' );
				$columns['revenue']         = 'Revenue' . wc_help_tip( 'The additional revenue that an Order Bump has captured. When configured as an upsell, it calculates the relative value between the offer product and the product being replaced. Revenues incurred before version 6.1.4 are estimated.' );
				$columns['location']        = 'Location';
				$columns['type']            = 'Type';
				$columns['date']            = $date;

				return $columns;
			}
		);

		add_action(
			"manage_{$post_type}_posts_custom_column",
			function( $column, $post_id ) {
				if ( 'conversion_rate' === $column ) {
					echo esc_html( BumpFactory::get( $post_id )->get_conversion_rate() );
				}

				if ( 'revenue' === $column ) {
					$captured_revenue = BumpFactory::get( $post_id )->get_captured_revenue();

					echo wp_kses_post( 0.0 === $captured_revenue ? '--' : wc_price( $captured_revenue ) );
				}

				if ( 'location' === $column ) {
					echo esc_html( OrderBumps::convert_value_to_label( BumpFactory::get( $post_id )->get_display_location() ) );
				}

				if ( 'type' === $column ) {
					$display_for = get_post_meta( $post_id, 'cfw_ob_display_for', true );
					echo esc_html( OrderBumps::convert_value_to_label( $display_for ) );
				}

				if ( 'order_bump_id' === $column ) {
					echo absint( $post_id );
				}
			},
			10,
			2
		);
	}

	public static function convert_value_to_label( $value ): string {
		$value = str_replace( '_', ' ', $value );

		return ucwords( $value );
	}

	/**
	 * The admin page wrap
	 *
	 * @since 1.0.0
	 */
	public function output_post_type_editor_header() {
		global $post;

		if ( isset( $_GET['post_type'] ) && $this->post_type_slug !== $_GET['post_type'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		} elseif ( isset( $post ) && $this->post_type_slug !== $post->post_type ) {
			return;
		} elseif ( ! isset( $_GET['post_type'] ) && ! isset( $post ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		?>
		<div class="cfw-tw">
			<div id="cfw_admin_page_header" class="absolute left-0 right-0 top-0 divide-y shadow z-50">
				<?php
				/**
				 * Fires before the admin page header
				 *
				 * @since 7.0.0
				 * @param OrderBumps $this The OrderBumps instance.
				 */
				do_action( 'cfw_before_admin_page_header', $this );
				?>
				<div class="min-h-[64px] bg-white flex items-center pl-8">
					<span>
						<?php echo file_get_contents( CFW_PATH . '/build/images/cfw.svg' ); // phpcs:ignore ?>
					</span>
					<nav class="flex" aria-label="Breadcrumb">
						<ol role="list" class="flex items-center space-x-2">
							<li class="m-0">
								<div class="flex items-center">
									<span class="ml-2 text-sm font-medium text-gray-800">
										<?php cfw_e( 'CheckoutWC', 'checkout-wc' ); ?>
									</span>
								</div>
							</li>
							<li class="m-0">
								<div class="flex items-center">
									<!-- Heroicon name: solid/chevron-right -->
									<svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg"
										 viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
										<path fill-rule="evenodd"
											  d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
											  clip-rule="evenodd"/>
									</svg>
									<span class="ml-2 text-sm font-medium text-gray-500" aria-current="page">
										<?php echo wp_kses_post( $this->title ); ?>
									</span>
								</div>
							</li>
						</ol>
					</nav>
				</div>
				<?php
				/**
				 * Fires after the admin page header
				 *
				 * @since 7.0.0
				 *
				 * @param AbandonedCartRecovery $this The AbandonedCartRecovery instance.
				 */
				do_action( 'cfw_after_admin_page_header', $this );
				?>
			</div>

			<div class="mt-4">
				<?php $this->tab_navigation->display_tabs(); ?>
			</div>
		</div>
		<?php
	}

	public function get_url(): string {
		$page_slug = join( '-', array_filter( array( self::$parent_slug, 'order_bumps' ) ) );
		$url       = add_query_arg( 'page', $page_slug, admin_url( 'admin.php' ) );

		return esc_url( $url );
	}

	/**
	 * Keeps the submenu open when on the order bumps editor
	 *
	 * @return void
	 */
	public function setup_menu() {
		parent::setup_menu();

		global $submenu;

		$stash_menu_item = null;

		if ( empty( $submenu[ self::$parent_slug ] ) ) {
			return;
		}

		foreach ( (array) $submenu[ self::$parent_slug ] as $i => $item ) {
			if ( $this->slug === $item[2] ) {
				$stash_menu_item = $submenu[ self::$parent_slug ][ $i ];
				unset( $submenu[ self::$parent_slug ][ $i ] );
			}
		}

		if ( empty( $stash_menu_item ) ) {
			return;
		}

		$submenu[ self::$parent_slug ][ $this->priority ] = $stash_menu_item;
	}

	public function register_meta_boxes() {
		add_meta_box( 'cfw_order_bump_products_mb', cfw__( 'Display Conditions', 'checkout-wc' ), array( $this, 'render_products_meta_box' ), $this->post_type_slug );
		add_meta_box( 'cfw_order_bump_offer_mb', cfw__( 'Offer', 'checkout-wc' ), array( $this, 'render_offer_meta_box' ), $this->post_type_slug );
		add_meta_box( 'cfw_order_bump_actions_mb', cfw__( 'Actions', 'checkout-wc' ), array( $this, 'render_actions_meta_box' ), $this->post_type_slug );
	}

	/**
	 * @param \WP_Post $post
	 */
	public function render_products_meta_box( \WP_Post $post ) {
		$cfw_ob_display_for_options = array(
			'all_products'        => cfw__( 'All Products', 'checkout-wc' ),
			'specific_products'   => cfw__( 'Specific Products', 'checkout-wc' ),
			'specific_categories' => cfw__( 'Specific Categories', 'checkout-wc' ),
		);

		$cfw_ob_display_for_value = get_post_meta( $post->ID, 'cfw_ob_display_for', true );
		$cfw_ob_any_product_value = get_post_meta( $post->ID, 'cfw_ob_any_product', true );

		wp_nonce_field( $this->nonce_action, $this->nonce_field );
		?>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row" valign="top">
						<label for="cfw_ob_display_for">
							<?php cfw_e( 'Display Offer For', 'checkout-wc' ); ?>
						</label>
					</th>
					<td>
						<select id="cfw_ob_display_for" name="cfw_ob_display_for">
							<?php foreach ( $cfw_ob_display_for_options as $option_value => $option_label ) : ?>
								<option value="<?php echo $option_value; ?>" <?php echo $option_value === $cfw_ob_display_for_value ? 'selected="selected"' : ''; ?>>
									<?php echo $option_label; ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top">
						<label for="cfw_ob_products">
							<?php cfw_e( 'Products', 'checkout-wc' ); ?>
						</label>
					</th>
					<td>
						<select class="wc-product-search" multiple="multiple" style="width: 50%;" id="cfw_ob_products" name="cfw_ob_products[]" data-placeholder="<?php cfw_esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products_and_variations">
							<?php
							$product_ids = get_post_meta( $post->ID, 'cfw_ob_products', true );

							foreach ( $product_ids as $product_id ) {
								$product = wc_get_product( $product_id );
								if ( is_object( $product ) ) {
									echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ) . '</option>';
								}
							}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top">
						<label for="cfw_ob_products">
							<?php cfw_e( 'Categories', 'checkout-wc' ); ?>
						</label>
					</th>
					<td>
						<select class="wc-category-search" multiple="multiple" style="width: 50%;" id="cfw_ob_categories" name="cfw_ob_categories[]" data-placeholder="<?php cfw_esc_attr_e( 'Search for a category&hellip;', 'woocommerce' ); ?>" data-allow_clear="true">
							<?php
							$category_slugs = get_post_meta( $post->ID, 'cfw_ob_categories', true );

							foreach ( $category_slugs as $category_slug ) {
								$category = get_term_by( 'slug', $category_slug, 'product_cat' );

								if ( $category ) {
									echo '<option value="' . esc_attr( $category_slug ) . '"' . selected( true, true, false ) . '>' . esc_html( wp_strip_all_tags( $category->name ) ) . '</option>';
								}
							}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top">
						<label for="cfw_ob_any_product">
							<?php cfw_e( 'Condition', 'checkout-wc' ); ?>
						</label>
					</th>
					<td>
						<input type="hidden" name="cfw_ob_any_product" value="yes" />
						<input type="checkbox" class="cfw-checkbox" name="cfw_ob_any_product" id="cfw_ob_any_product" value="no" <?php echo 'no' === $cfw_ob_any_product_value ? 'checked' : ''; ?> />

						<label class="cfw-checkbox-label" for="cfw_ob_any_product">
							<?php cfw_e( 'Apply if all matching products are in the cart.', 'checkout-wc' ); ?>
						</label>

						<p>
							<span class="description">
								<?php cfw_e( 'If checked, all products above must be in the cart. If unchecked order bump will show if any of the above products are in the cart.', 'checkout-wc' ); ?>
							</span>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top">
						<label for="cfw_ob_display_location">
							<?php cfw_e( 'Display Location', 'checkout-wc' ); ?>
						</label>
					</th>
					<td>
						<p>
							<?php
							$cfw_ob_display_location_value = get_post_meta( $post->ID, 'cfw_ob_display_location', true );
							$default_value                 = 'below_cart_items';

							$display_location_options = array(
								'below_cart_items'            => 'Below Cart Items',
								'below_side_cart_items'       => 'Below Cart Items (Side Cart Only)',
								'below_checkout_cart_items'   => 'Below Cart Items (Checkout Only)',
								'above_terms_and_conditions'  => 'Above Terms and Conditions',
								'above_express_checkout'      => 'Above Express Checkout',
								'bottom_information_tab'      => 'Bottom of Information Step',
								'bottom_shipping_tab'         => 'Bottom of Shipping Step',
								'below_complete_order_button' => 'Below Complete Order Button',
								'complete_order'              => 'After Checkout Submit Modal',
							);
							foreach ( $display_location_options as $option_value => $option_label ) :
								?>
								<label>
									<input type="radio" name="cfw_ob_display_location" value="<?php echo $option_value; ?>" <?php echo $option_value === $cfw_ob_display_location_value || ( empty( $cfw_ob_display_location_value ) && $option_value === $default_value ) ? 'checked' : ''; ?> /> <?php echo $option_label; ?><br />
								</label>
							<?php endforeach; ?>
						</p>

						<p class="description">
							<?php cfw_e( 'Where to display order bumps. Below Cart Items bumps will always display above the terms and conditions on mobile.', 'checkout-wc' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top">
						<label for="cfw_ob_products">
							<?php cfw_e( 'Exclude If These Products Are in The Cart', 'checkout-wc' ); ?>
						</label>
					</th>
					<td>
						<select class="wc-product-search" multiple="multiple" style="width: 50%;" id="cfw_ob_exclude_products" name="cfw_ob_exclude_products[]" data-placeholder="<?php cfw_esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products_and_variations">
							<?php
							$product_ids = get_post_meta( $post->ID, 'cfw_ob_exclude_products', true );

							foreach ( $product_ids as $product_id ) {
								$product = wc_get_product( $product_id );
								if ( is_object( $product ) ) {
									echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ) . '</option>';
								}
							}
							?>
						</select>
						<p class="description">
							<?php cfw_e( 'If any of these products are in the cart, this offer will not be shown.', 'checkout-wc' ); ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * @param \WP_Post $post
	 */
	public function render_offer_meta_box( \WP_Post $post ) {
		$cfw_ob_discount_type_options = array(
			'percent' => 'Percent Off',
			'amount'  => 'Amount Off',
		);

		$cfw_ob_discount_type_default = 'percent';

		$cfw_ob_discount_type_value           = get_post_meta( $post->ID, 'cfw_ob_discount_type', true );
		$cfw_ob_offer_product                 = get_post_meta( $post->ID, 'cfw_ob_offer_product', true );
		$cfw_ob_offer_discount                = get_post_meta( $post->ID, 'cfw_ob_offer_discount', true );
		$cfw_ob_offer_language                = get_post_meta( $post->ID, 'cfw_ob_offer_language', true );
		$cfw_ob_offer_description             = get_post_meta( $post->ID, 'cfw_ob_offer_description', true );
		$cfw_ob_upsell_value                  = get_post_meta( $post->ID, 'cfw_ob_upsell', true );
		$cfw_ob_offer_quantity                = get_post_meta( $post->ID, 'cfw_ob_offer_quantity', true );
		$cfw_ob_enable_auto_match             = get_post_meta( $post->ID, 'cfw_ob_enable_auto_match', true );
		$cfw_ob_item_removal_behavior_value   = get_post_meta( $post->ID, 'cfw_ob_item_removal_behavior', true );
		$cfw_ob_enable_quantity_updates_value = get_post_meta( $post->ID, 'cfw_ob_enable_quantity_updates', true );
		$cfw_ob_offer_heading                 = get_post_meta( $post->ID, 'cfw_ob_offer_heading', true );
		$cfw_ob_offer_subheading              = get_post_meta( $post->ID, 'cfw_ob_offer_subheading', true );
		$cfw_ob_offer_cancel_button_text      = get_post_meta( $post->ID, 'cfw_ob_offer_cancel_button_text', true );

		if ( empty( $cfw_ob_offer_quantity ) ) {
			$cfw_ob_offer_quantity = 1;
		}

		if ( empty( $cfw_ob_offer_language ) ) {
			$cfw_ob_offer_language = 'Yes! Please add this offer to my order';
		}

		if ( empty( $cfw_ob_offer_description ) ) {
			$cfw_ob_offer_description = 'Limited time offer! Get an EXCLUSIVE discount right now! Click the checkbox above to add this product to your order now.';
		}
		?>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row" valign="top">
						<label for="cfw_ob_offer_product">
							<?php cfw_e( 'Product', 'checkout-wc' ); ?>
						</label>
					</th>
					<td>
						<select class="wc-product-search" style="width: 50%;" id="cfw_ob_offer_product" name="cfw_ob_offer_product" data-placeholder="<?php cfw_esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products_and_variations">
							<?php
							$product_ids = array( $cfw_ob_offer_product );

							foreach ( $product_ids as $product_id ) {
								$product = wc_get_product( $product_id );
								if ( is_object( $product ) ) {
									echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ) . '</option>';
								}
							}
							?>
						</select>
					</td>
				</tr>

				<tr>
					<th scope="row" valign="top">
						<label for="cfw_ob_upsell">
							<?php cfw_e( 'Upsell', 'checkout-wc' ); ?>
						</label>
					</th>
					<td>
						<input type="hidden" name="cfw_ob_upsell" value="no" />
						<input type="checkbox" class="cfw-checkbox" name="cfw_ob_upsell" id="cfw_ob_upsell" value="yes" <?php echo 'yes' === $cfw_ob_upsell_value ? 'checked' : ''; ?> />

						<label class="cfw-checkbox-label" for="cfw_ob_upsell">
							<?php cfw_e( 'Replace cart product with offer product when this order bump is taken.', 'checkout-wc' ); ?>
						</label>

						<p style="margin-top: 1em">
							<span class="description">
								<?php cfw_e( 'Requirements: <i>Display Offer For</i> must be set to <i>Specific Products</i>. Only one product should be defined in <i>Products</i> list.', 'checkout-wc' ); ?>
							</span>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row" valign="top">
						<label for="cfw_ob_enable_auto_match">
							<?php cfw_e( 'Auto Match Variation', 'checkout-wc' ); ?>
						</label>
					</th>
					<td>
						<input type="hidden" name="cfw_ob_enable_auto_match" value="no" />
						<input type="checkbox" class="cfw-checkbox" name="cfw_ob_enable_auto_match" id="cfw_ob_enable_auto_match" value="yes" <?php echo 'yes' === $cfw_ob_enable_auto_match ? 'checked' : ''; ?> />

						<label class="cfw-checkbox-label" for="cfw_ob_enable_auto_match">
							<?php cfw_e( 'Add offer product to the cart with the same variable configuration as the specific display condition product above.', 'checkout-wc' ); ?>
						</label>
						<p style="margin-top: 1em">
							<span class="description">
								<?php cfw_e( 'Only applies when Display Condition Product and Offer Product are variable products with matching variation attributes (Size, Color, etc)', 'checkout-wc' ); ?>
							</span>
						</p>
						<p>
							<span class="description">
								<?php cfw_e( 'If either product is not a variable product, auto matching will not be attempted.', 'checkout-wc' ); ?>
							</span>
						</p>
						<p>
							<span class="description">
								<?php cfw_e( 'Only one product should be defined in Products list.', 'checkout-wc' ); ?>
							</span>
						</p>
						<p>
							<span class="description">
								<?php cfw_e( 'Leave unchecked to have customers to select variation options in a modal window. <i>If offer product is not variable, this option is ignored.</i>', 'checkout-wc' ); ?>
							</span>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row" valign="top">
						<label for="cfw_ob_offer_quantity">
							<?php cfw_e( 'Quantity', 'checkout-wc' ); ?>
						</label>
					</th>
					<td>
						<input type="text" value="<?php echo esc_attr( $cfw_ob_offer_quantity ); ?>" name="cfw_ob_offer_quantity" id="cfw_ob_offer_quantity" />

						<p class="description">
							<?php cfw_e( 'The quantity to add to the cart when offer is accepted.', 'checkout-wc' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row" valign="top">
						<label for="cfw_ob_enable_quantity_updates">
							<?php cfw_e( 'Quantity Updates', 'checkout-wc' ); ?>
						</label>
					</th>
					<td>
						<input type="hidden" name="cfw_ob_enable_quantity_updates" value="no" />
						<input type="checkbox" class="cfw-checkbox" name="cfw_ob_enable_quantity_updates" id="cfw_ob_enable_quantity_updates" value="yes" <?php echo 'yes' === $cfw_ob_enable_quantity_updates_value ? 'checked' : ''; ?> />

						<label class="cfw-checkbox-label" for="cfw_ob_enable_quantity_updates">
							<?php cfw_e( 'Allow customer to change the quantity of this order bump in the cart.', 'checkout-wc' ); ?>
						</label>
					</td>
				</tr>

				<tr>
					<th scope="row" valign="top">
						<label for="cfw_ob_discount_type">
							<?php cfw_e( 'Discount Type', 'checkout-wc' ); ?>
						</label>
					</th>
					<td>
						<p>
							<?php foreach ( $cfw_ob_discount_type_options as $option_value => $option_label ) : ?>
								<label>
									<input type="radio" name="cfw_ob_discount_type" value="<?php echo $option_value; ?>" <?php echo $option_value === $cfw_ob_discount_type_value || ( empty( $cfw_ob_discount_type_value ) && $option_value === $cfw_ob_discount_type_default ) ? 'checked' : ''; ?> /> <?php echo $option_label; ?><br />
								</label>
							<?php endforeach; ?>
						</p>

						<p class="description">
							<?php cfw_e( 'Amount Off: Remove fixed amount from the product price.', 'checkout-wc' ); ?>
						</p>

						<p class="description">
							<?php cfw_e( 'Percent Off: Discount product by specified percentage.', 'checkout-wc' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row" valign="top">
						<label for="cfw_ob_offer_discount">
							<?php cfw_e( 'Discount', 'checkout-wc' ); ?>
						</label>
					</th>
					<td>
						<input type="text" value="<?php echo esc_attr( $cfw_ob_offer_discount ); ?>" name="cfw_ob_offer_discount" />
					</td>
				</tr>

				<tr>
					<th scope="row" valign="top">
						<label for="cfw_ob_offer_language">
							<?php cfw_e( 'Offer Language', 'checkout-wc' ); ?>
						</label>
					</th>
					<td>
						<input size="60" type="text" value="<?php echo esc_attr( $cfw_ob_offer_language ); ?>" name="cfw_ob_offer_language" />

						<p class="description">
							<?php cfw_e( 'Example: Yes! Please add this offer to my order', 'checkout-wc' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row" valign="top">
						<label for="cfw_ob_offer_description">
							<?php cfw_e( 'Offer Description', 'checkout-wc' ); ?>
						</label>
					</th>
					<td>
						<textarea cols="60" rows="6" type="text" name="cfw_ob_offer_description"><?php echo esc_attr( $cfw_ob_offer_description ); ?></textarea>

						<p class="description">
							Example: Limited time offer! Get an EXCLUSIVE discount right now! Click the checkbox above to add this product to your order now.
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row" valign="top">
						<label for="cfw_ob_item_removal_behavior">
							<?php cfw_e( 'Item Removal Behavior', 'checkout-wc' ); ?>
						</label>
					</th>
					<td>
						<p>
							<?php
							$default_value = 'keep';

							$display_location_options = array(
								'keep'   => 'Leave In Cart With Normal Price',
								'delete' => 'Remove Order Bump From Cart',
							);
							foreach ( $display_location_options as $option_value => $option_label ) :
								?>
								<label>
									<input type="radio" name="cfw_ob_item_removal_behavior" value="<?php echo $option_value; ?>" <?php echo $option_value === $cfw_ob_item_removal_behavior_value || ( empty( $cfw_ob_item_removal_behavior_value ) && $option_value === $default_value ) ? 'checked' : ''; ?> /> <?php echo $option_label; ?><br />
								</label>
							<?php endforeach; ?>
						</p>

						<p class="description">
							<?php cfw_e( 'What happens when the display for product is removed from the cart. Default: Order Bump remains in the cart but bump specific discounts are removed.', 'checkout-wc' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row" valign="top">
						<label for="cfw_ob_offer_heading">
							<?php cfw_e( 'Modal Heading', 'checkout-wc' ); ?>
						</label>
					</th>
					<td>
						<input size="60" type="text" value="<?php echo esc_attr( $cfw_ob_offer_heading ); ?>" name="cfw_ob_offer_heading" />

						<p class="description">
							<?php cfw_e( 'Default if blank: Your order is almost complete...', 'checkout-wc' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row" valign="top">
						<label for="cfw_ob_offer_subheading">
							<?php cfw_e( 'Modal Subheading', 'checkout-wc' ); ?>
						</label>
					</th>
					<td>
						<input size="60" type="text" value="<?php echo esc_attr( $cfw_ob_offer_subheading ); ?>" name="cfw_ob_offer_subheading" />

						<p class="description">
							<?php cfw_e( 'Default if blank: Add this offer to your order and save!', 'checkout-wc' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row" valign="top">
						<label for="cfw_ob_offer_cancel_button_text">
							<?php cfw_e( 'Offer Rejection Link Text', 'checkout-wc' ); ?>
						</label>
					</th>
					<td>
						<input size="60" type="text" value="<?php echo esc_attr( $cfw_ob_offer_cancel_button_text ); ?>" name="cfw_ob_offer_cancel_button_text" />

						<p class="description">
							<?php cfw_e( 'Displays in modal version of Order Bump, such as when configured as a After Checkout Submit Modal or when the Offer Product is a variable product (as opposed to a specific variation)', 'checkout-wc' ); ?>
						</p>
						<p class="description cfw-post-purchase-upsell-show">
							<?php cfw_e( 'Default if blank: No thanks, just complete my order', 'checkout-wc' ); ?>
						</p>
						<p class="description cfw-post-purchase-upsell-hide">
							<?php cfw_e( 'Default if blank: No thanks', 'checkout-wc' ); ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	public function render_actions_meta_box( \WP_Post $post ) {
		$cfw_ob_apply_free_shipping = get_post_meta( $post->ID, 'cfw_ob_apply_free_shipping', true );

		?>
		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row" valign="top">
					<label for="cfw_ob_products_to_remove">
						<?php cfw_e( 'Remove These Products From The Cart', 'checkout-wc' ); ?>
					</label>
				</th>
				<td>
					<select class="wc-product-search" multiple="multiple" style="width: 50%;" id="cfw_ob_products_to_remove" name="cfw_ob_products_to_remove[]" data-placeholder="<?php cfw_esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products_and_variations">
						<?php
						$product_ids = get_post_meta( $post->ID, 'cfw_ob_products_to_remove', true );

						foreach ( $product_ids as $product_id ) {
							$product = wc_get_product( $product_id );
							if ( is_object( $product ) ) {
								echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ) . '</option>';
							}
						}
						?>
					</select>
					<p class="description">
						<?php cfw_e( 'If any of these products are in the cart, remove them when this bump is added to the cart.', 'checkout-wc' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row" valign="top">
					<label for="cfw_ob_apply_free_shipping">
						<?php cfw_e( 'Apply Free Shipping', 'checkout-wc' ); ?>
					</label>
				</th>
				<td>
					<input type="hidden" name="cfw_ob_apply_free_shipping" value="no" />
					<input type="checkbox" class="cfw-checkbox" name="cfw_ob_apply_free_shipping" id="cfw_ob_apply_free_shipping" value="yes" <?php echo 'yes' === $cfw_ob_apply_free_shipping ? 'checked' : ''; ?> />

					<label class="cfw-checkbox-label" for="cfw_ob_apply_free_shipping">
						<?php cfw_e( 'When this bump is added to the cart, apply free shipping to the cart.', 'checkout-wc' ); ?>
					</label>
				</td>
			</tr>

			</tbody>
		</table>
		<?php
	}

	function add_stat_reset_checkbox( \WP_Post $post = null ) {
		if ( ! $post ) {
			return;
		}

		if ( BumpAbstract::get_post_type() !== $post->post_type ) {
			return;
		}
		?>
		<div class="misc-pub-section">
			<label>
				<input type="checkbox" id="cfw_reset_stats" name="cfw_reset_stats" value="1" /> <?php cfw_e( 'Reset Order Bump Conversion Stats', 'checkout-wc' ); ?>
			</label>
		</div>
		<?php
	}

	/**
	 * @param int $post_id
	 */
	public function save_metaboxes( int $post_id ) {
		$nonce_name = $_POST[ $this->nonce_field ] ?? '';

		if ( ! wp_verify_nonce( $nonce_name, $this->nonce_action ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Display Conditions
		update_post_meta( $post_id, 'cfw_ob_display_for', sanitize_text_field( $_POST['cfw_ob_display_for'] ?? '' ) );
		update_post_meta( $post_id, 'cfw_ob_products', $_POST['cfw_ob_products'] ?? '' );
		update_post_meta( $post_id, 'cfw_ob_exclude_products', $_POST['cfw_ob_exclude_products'] ?? '' );
		update_post_meta( $post_id, 'cfw_ob_categories', $_POST['cfw_ob_categories'] ?? '' );
		update_post_meta( $post_id, 'cfw_ob_any_product', sanitize_text_field( $_POST['cfw_ob_any_product'] ?? '' ) );
		update_post_meta( $post_id, 'cfw_ob_display_location', sanitize_text_field( $_POST['cfw_ob_display_location'] ?? '' ) );
		update_post_meta( $post_id, 'cfw_ob_item_removal_behavior', sanitize_text_field( $_POST['cfw_ob_item_removal_behavior'] ?? '' ) );

		// Offer Fields
		update_post_meta( $post_id, 'cfw_ob_discount_type', sanitize_text_field( $_POST['cfw_ob_discount_type'] ?? '' ) );
		update_post_meta( $post_id, 'cfw_ob_offer_product', $_POST['cfw_ob_offer_product'] ?? '' );
		update_post_meta( $post_id, 'cfw_ob_offer_discount', sanitize_text_field( $_POST['cfw_ob_offer_discount'] ?? '' ) );
		update_post_meta( $post_id, 'cfw_ob_offer_language', sanitize_text_field( $_POST['cfw_ob_offer_language'] ?? '' ) );
		update_post_meta( $post_id, 'cfw_ob_offer_description', sanitize_text_field( $_POST['cfw_ob_offer_description'] ?? '' ) );
		update_post_meta( $post_id, 'cfw_ob_upsell', sanitize_text_field( $_POST['cfw_ob_upsell'] ?? '' ) );
		update_post_meta( $post_id, 'cfw_ob_offer_quantity', sanitize_text_field( $_POST['cfw_ob_offer_quantity'] ?? '' ) );
		update_post_meta( $post_id, 'cfw_ob_enable_auto_match', sanitize_text_field( $_POST['cfw_ob_enable_auto_match'] ?? '' ) );
		update_post_meta( $post_id, 'cfw_ob_enable_quantity_updates', sanitize_text_field( $_POST['cfw_ob_enable_quantity_updates'] ?? '' ) );
		update_post_meta( $post_id, 'cfw_ob_offer_heading', sanitize_text_field( $_POST['cfw_ob_offer_heading'] ?? '' ) );
		update_post_meta( $post_id, 'cfw_ob_offer_subheading', sanitize_text_field( $_POST['cfw_ob_offer_subheading'] ?? '' ) );
		update_post_meta( $post_id, 'cfw_ob_offer_cancel_button_text', sanitize_text_field( $_POST['cfw_ob_offer_cancel_button_text'] ?? '' ) );

		// Actions Fields
		update_post_meta( $post_id, 'cfw_ob_apply_free_shipping', sanitize_text_field( $_POST['cfw_ob_apply_free_shipping'] ?? '' ) );
		update_post_meta( $post_id, 'cfw_ob_products_to_remove', $_POST['cfw_ob_products_to_remove'] ?? '' );

		if ( ! empty( $_POST['cfw_reset_stats'] ) ) {
			delete_post_meta( $post_id, 'times_bump_displayed_on_purchases' );
			delete_post_meta( $post_id, 'times_bump_purchased' );
			delete_post_meta( $post_id, 'captured_revenue' );
			delete_post_meta( $post_id, 'conversion_rate' );
		}
	}

	public function is_current_page(): bool {
		global $post;

		if ( parent::is_current_page() ) {
			return true;
		}

		if ( isset( $_GET['post_type'] ) && $this->post_type_slug === $_GET['post_type'] ) {
			return true;
		}

		if ( $post && $this->post_type_slug === $post->post_type ) {
			return true;
		}

		return false;
	}

	public function maybe_show_license_upgrade_splash() {
		if ( $this->is_current_page() && ! $this->is_available ) {
			echo $this->get_old_style_upgrade_required_notice( $this->formatted_required_plans_list );
		}
	}

	/**
	 * @param mixed $submenu_file
	 * @return mixed
	 */
	public function maybe_highlight_order_bumps_submenu_item( $submenu_file ) {
		global $post;

		$post_type = $this->post_type_slug;

		if ( isset( $_GET['post_type'] ) && $_GET['post_type'] === $post_type ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return $this->get_slug();
		} elseif ( $post && $post->post_type === $post_type ) {
			return $this->get_slug();
		}

		return $submenu_file;
	}

	public function menu_highlight( $parent_file ) {
		global $plugin_page, $post_type;

		if ( $this->post_type_slug === $post_type ) {
			$plugin_page = PageAbstract::$parent_slug; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		return $parent_file;
	}

	public function output() {
		if ( $this->get_current_tab() === false ) {
			$_GET['subpage'] = 'settings';
		}

		if ( ! PlanManager::has_premium_plan() ) {
			$notice = $this->get_old_style_upgrade_required_notice( PlanManager::get_english_list_of_required_plans_html() );
		}

		if ( ! empty( $notice ) ) {
			echo $notice;
		}

		if ( isset( $_GET['post_type'] ) ) {
			return;
		}

		$current_tab_function = $this->get_current_tab() === false ? 'settings_tab' : $this->get_current_tab() . '_tab';
		$callable             = array( $this, $current_tab_function );

		$this->tab_navigation->display_tabs();

		call_user_func( $callable );
	}

	public function settings_tab() {
		$this->output_form_open( 'order_bumps_settings_form');
		?>
		<div class="space-y-6">
			<?php
			cfw_admin_page_section(
				cfw__( 'Order Bumps', 'checkout-wc' ),
				cfw__( 'Control how Order Bumps work.', 'checkout-wc' ),
				$this->get_settings()
			);
			?>
		</div>
		<?php
		$this->output_form_close();
	}

	protected function get_settings() {
		ob_start();

		$this->output_checkbox_row(
			'enable_order_bumps',
			cfw__( 'Enable Order Bumps', 'checkout-wc' ),
			cfw__( 'Allow Order Bumps to display.', 'checkout-wc' )
		);

		$this->output_text_input_row(
			'max_bumps',
			cfw__( 'Maximum Order Bumps', 'checkout-wc' ),
			cfw__( 'The maximum number of bumps that can be displayed per output location. Use -1 for unlimited.', 'checkout-wc' )
		);

		return ob_get_clean();
	}
}

<?php

namespace Objectiv\Plugins\Checkout\Features;

use Objectiv\Plugins\Checkout\Admin\Pages\PageAbstract;
use Objectiv\Plugins\Checkout\Managers\SettingsManager;
use WC_Order;
use WC_Shipping_Rate;
use WP_Roles;

class Pickup extends FeaturesAbstract {
	protected function run_if_cfw_is_enabled() {
		// Show the delivery method selector
		add_action( 'cfw_checkout_before_customer_info_address', array( $this, 'render_delivery_methods' ) );

		// Show the pickup method selector (maybe)
		$action = 'cfw_after_delivery_method';

		if ( SettingsManager::instance()->get_setting( 'enable_pickup_method_step' ) === 'yes' ) {
			$action = 'cfw_checkout_after_shipping_methods';
		}
		add_action( $action, array( $this, 'render_pickup_methods' ) );

		// Remember whether delivery method changed
		add_action( 'cfw_checkout_update_order_review', array( $this, 'maybe_change_delivery_method' ) );

		// Actions that happen when the delivery method changes
		add_action( 'cfw_delivery_method_changed', array( $this, 'on_delivery_method_changed' ) );

		// Limit which shipping methods are available based on whether pickup or delivery is selected
		add_filter( 'woocommerce_package_rates', array( $this, 'filter_available_shipping_methods' ), 10 );

		// Add a local pickup setting that we can access from JS
		add_filter( 'cfw_event_data', array( $this, 'add_localized_settings' ) );

		// Change labels based on whether pickup or delivery is selected
		add_filter( 'cfw_ship_to_label', array( $this, 'filter_ship_to_label' ), 10 );
		add_filter( 'cfw_get_review_pane_shipping_address', array( $this, 'filter_shipping_address' ), 10 );
		add_filter( 'cfw_show_shipping_tab', array( $this, 'filter_show_shipping_tab' ), 10 );
		add_filter( 'cfw_cart_totals_shipping_label', array( $this, 'filter_shipping_totals_label' ), 10 );

		// Save the pickup location to the order
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'handle_order_meta' ) );

		// Add pickup instructions to the thank you page
		add_action( 'cfw_thank_you_content', array( $this, 'pickup_instructions_wrapped' ), 60, 1 );

		// Show pickup location on orders list view and single order view
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_pickup_location_column_header' ), 100 );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'pickup_location_column_content' ), 10, 2 );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'pickup_location_view_order' ), 10, 1 );

		// When pickup is selected, we don't need a shipping address so don't require one on the server side
		add_filter( 'woocommerce_checkout_fields', array( $this, 'unrequire_checkout_fields' ) );
		add_action( 'woocommerce_checkout_posted_data', array( $this, 'handle_missing_shipping_country' ) );
		add_action( 'woocommerce_countries_shipping_countries', array( $this, 'handle_invalid_shipping_country_shim' ) );

		// Conditionally disable shipping and pickup methods based on available methods
		add_filter( 'cfw_local_pickup_disable_shipping_option', array( $this, 'maybe_disable_shipping_option' ) );
		add_filter( 'cfw_local_pickup_disable_pickup_option', array( $this, 'maybe_disable_pickup_option' ) );
	}

	public function on_delivery_method_changed() {
		// Clear WooCommerce shipping package cache so that shipping methods are reassessed
		foreach ( WC()->cart->get_shipping_packages() as $package_key => $package ) {
			WC()->session->set( 'shipping_for_package_' . $package_key, false );
		}
	}

	public function render_delivery_methods() {
		if ( ! WC()->cart->needs_shipping() ) {
			return;
		}

		$pickup_option_label = $this->settings_getter->get_setting( 'pickup_option_label' );

		if ( empty( $pickup_option_label ) ) {
			$pickup_option_label = __( 'Pick up', 'checkout-wc' );
		}

		/**
		 * Filters the local pickup option label
		 *
		 * @since 7.3.1
		 * @param string $pickup_option_label The pickup option label
		 */
		$pickup_option_label = apply_filters( 'cfw_local_pickup_option_label', $pickup_option_label );

		$ship_option_label = $this->settings_getter->get_setting( 'pickup_ship_option_label' );

		if ( empty( $ship_option_label ) ) {
			$ship_option_label = __( 'Ship', 'checkout-wc' );
		}

		/**
		 * Filters the local pickup shipping option label
		 *
		 * @since 7.3.1
		 * @param string $ship_option_label The shipping option label
		 */
		$ship_option_label = apply_filters( 'cfw_local_pickup_shipping_option_label', $ship_option_label );

		/**
		 * Filters whether the shipping option should be disabled
		 *
		 * @since 8.1.6
		 * @param bool $disable_shipping_option Whether the shipping option should be disabled
		 */
		$disable_shipping_option = apply_filters( 'cfw_local_pickup_disable_shipping_option', $this->settings_getter->get_setting( 'enable_pickup_ship_option' ) !== 'yes' );

		/**
		 * Filters whether the pickup option should be disabled
		 *
		 * @since 8.1.6
		 * @param bool $disable_pickup_option Whether the pickup option should be disabled
		 */
		$disable_pickup_option = apply_filters( 'cfw_local_pickup_disable_pickup_option', false );
		?>
		<h3>
			<?php esc_html_e( 'Delivery method', 'checkout-wc' ); ?>
		</h3>
		<div id="cfw-delivery-method" class="cfw-module cfw-accordion">
			<ul class="cfw-radio-reveal-group">
				<?php if ( ! $disable_shipping_option ) : ?>
				<li class="cfw-radio-reveal-li cfw-no-reveal">
					<div class="cfw-radio-reveal-title-wrap">
						<input type="radio" name="cfw_delivery_method" id="cfw_delivery_method_ship_radio" value="ship" class="garlic-auto-save" checked="checked" />

						<label for="cfw_delivery_method_ship_radio" class="cfw-radio-reveal-label">
							<div>
								<span class="cfw-radio-reveal-title">
									<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" /></svg>
									<?php echo esc_html( $ship_option_label ); ?>
								</span>
							</div>
						</label>
					</div>
				</li>
				<?php endif; ?>
				<?php if ( ! $disable_pickup_option ) : ?>
				<li class="cfw-radio-reveal-li">
					<div class="cfw-radio-reveal-title-wrap">
						<input type="radio" name="cfw_delivery_method" id="cfw_delivery_method_pickup_radio" value="pickup" class="garlic-auto-save" <?php echo $disable_shipping_option ? 'checked="checked"' : ''; ?> />

						<label for="cfw_delivery_method_pickup_radio" class="cfw-radio-reveal-label">
							<div>
								<span class="cfw-radio-reveal-title">
									<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>
									<?php echo esc_html( $pickup_option_label ); ?>
								</span>
							</div>
						</label>
					</div>
				</li>
				<?php endif; ?>
			</ul>
		</div>
		<?php

		/**
		 * Fires after the delivery method radio buttons are rendered.
		 *
		 * @since 7.3.0
		 */
		do_action( 'cfw_after_delivery_method' );
	}

	public function render_pickup_methods() {
		$pickup_locations = get_posts(
			array(
				'post_type'        => self::get_post_type(),
				'suppress_filters' => false,
			)
		);

		if ( ! $pickup_locations ) {
			return;
		}

		$checked = reset( $pickup_locations )->ID;
		?>
		<div id="cfw-pickup-location-wrap">
			<h3>
				<?php esc_html_e( 'Pickup locations', 'checkout-wc' ); ?>
			</h3>
			<div id="cfw-pickup-location" class="cfw-module cfw-accordion">
				<ul class="cfw-radio-reveal-group">
					<?php
					foreach ( $pickup_locations as $pickup_location ) :
						$pickup_time = get_post_meta( $pickup_location->ID, 'cfw_pl_estimated_time', true );

						/**
						 * Filters the pickup location estimated time
						 *
						 * NOTE: Use cfw_pickup_times to extend the list of available pickup times
						 *
						 * @since 7.5.0
						 * @param string $pickup_time The estimated time
						 */
						$pickup_time = apply_filters( 'cfw_estimated_pickup_time', self::get_pickup_times()[ $pickup_time ] ?? '', $pickup_location->ID );
						?>
						<li class="cfw-radio-reveal-li cfw-no-reveal">
							<div class="cfw-radio-reveal-title-wrap cfw-align-top">
								<input type="radio" name="cfw_pickup_location" id="cfw_pickup_location_radio_<?php echo esc_attr( sanitize_title_with_dashes( $pickup_location->ID ) ); ?>" value="<?php echo esc_attr( $pickup_location->ID ); ?>" <?php checked( $pickup_location->ID, $checked, true ); ?> />

								<label for="cfw_pickup_location_radio_<?php echo esc_attr( sanitize_title_with_dashes( $pickup_location->ID ) ); ?>" class="cfw-radio-reveal-label cfw-align-top">
									<div style="align-items: flex-start">
								<span class="cfw-radio-reveal-title">
									<?php echo esc_html( $pickup_location->post_title ); ?>

									<?php $pickup_address = get_post_meta( $pickup_location->ID, 'cfw_pl_address', true ); ?>
									<?php if ( $pickup_address ) : ?>
									<div class="cfw-xtra-small mt-2">
										<?php
										// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
										echo wpautop( $pickup_address );
										// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
										?>
									</div>
									<?php endif; ?>
								</span>
										<div class="cfw-xtra-small">
											<?php
											// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
											echo wpautop( $pickup_time );
											// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
											?>
										</div>
									</div>
								</label>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
		<?php
	}

	public function maybe_change_delivery_method( $post_data ) {
		$saved_delivery_method = WC()->session->get( 'cfw_delivery_method' );

		parse_str( $post_data, $parsed_data );

		if ( isset( $parsed_data['cfw_delivery_method'] ) && $saved_delivery_method !== $parsed_data['cfw_delivery_method'] ) {
			WC()->session->set( 'cfw_delivery_method', $parsed_data['cfw_delivery_method'] );

			/**
			 * Fires when delivery method changes
			 *
			 * @since 7.3.2
			 * @param string $delivery_method The current delivery method
			 */
			do_action( 'cfw_delivery_method_changed', $parsed_data['cfw_delivery_method'] );
		}
	}

	public function filter_available_shipping_methods( $methods ): array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		parse_str( wc_clean( wp_unslash( $_POST['post_data'] ?? '' ) ), $post_data );

		$delivery_method = $post_data['cfw_delivery_method'] ?? wc_clean( $_POST['cfw_delivery_method'] ?? 'none' );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( 'none' === $delivery_method ) {
			return $methods;
		}

		$pickup_methods = $this->get_pickup_methods( $methods );

		if ( self::pickup_is_selected() ) {
			$methods = array_intersect_key( $methods, $pickup_methods );
		} else {
			$methods = array_diff_key( $methods, $pickup_methods );
		}

		return $methods;
	}

	public function init() {
		parent::init();

		add_action( 'cfw_do_plugin_activation', array( $this, 'run_on_plugin_activation' ) );
		$this->register_post_type();
		$this->map_capabilities();
	}

	public function run_on_plugin_activation() {
		SettingsManager::instance()->add_setting( 'enable_pickup', 'no' );
	}

	/**
	 * Get pickup methods
	 *
	 * @param array $methods
	 *
	 * @return array
	 */
	public function get_pickup_methods( array $methods = array() ): array {
		$raw_pickup_methods = (array) $this->settings_getter->get_setting( 'pickup_methods' );
		$pickup_methods     = array();

		foreach ( $raw_pickup_methods as $raw_pickup_method ) {
			$pickup_methods[ $raw_pickup_method ] = $raw_pickup_method;
		}

		// Handle other shipping methods.
		if ( isset( $pickup_methods['other'] ) ) {
			$regex       = 'yes' === $this->settings_getter->get_setting( 'enable_pickup_shipping_method_other_regex' );
			$other_label = (string) $this->settings_getter->get_setting( 'pickup_shipping_method_other_label' );

			/**
			 * WC_Shipping_Rate instance
			 *
			 * @var WC_Shipping_Rate $method
			 */
			foreach ( $methods as $method ) {
				if (
					( $regex && preg_match( '/' . $other_label . '/i', $method->get_label() ) )
					|| $method->get_label() === $other_label
				) {
					$pickup_methods[ $method->get_id() ] = $method->get_id();
					break;
				}
			}
		}

		// Cleanup placeholder other method
		unset( $pickup_methods['other'] );

		return $pickup_methods;
	}

	public function add_localized_settings( $event_data ): array {
		$event_data['settings']['local_pickup_enabled'] = $this->enabled;
		$event_data['settings']['hide_pickup_methods']  = SettingsManager::instance()->get_setting( 'hide_pickup_methods' ) === 'yes';

		return $event_data;
	}

	public function register_post_type() {
		$labels = array(
			'name'               => cfw__( 'Pickup Locations', 'checkout-wc' ),
			'singular_name'      => cfw__( 'Pickup Location', 'checkout-wc' ),
			'menu_name'          => cfw_x( 'Pickup Locations', 'Admin menu name', 'checkout-wc' ),
			'add_new'            => cfw__( 'Add Pickup Location', 'checkout-wc' ),
			'add_new_item'       => cfw__( 'Add New Pickup Location', 'checkout-wc' ),
			'edit'               => cfw__( 'Edit', 'checkout-wc' ),
			'edit_item'          => cfw__( 'Edit Pickup Location', 'checkout-wc' ),
			'new_item'           => cfw__( 'New Pickup Location', 'checkout-wc' ),
			'view'               => cfw__( 'View Pickup Locations', 'checkout-wc' ),
			'view_item'          => cfw__( 'View Pickup Location', 'checkout-wc' ),
			'search_items'       => cfw__( 'Search Pickup Locations', 'checkout-wc' ),
			'not_found'          => cfw__( 'No Pickup Location found', 'checkout-wc' ),
			'not_found_in_trash' => cfw__( 'No Pickup Locations found in trash', 'checkout-wc' ),
		);

		$post_type_args = array(
			'labels'              => $labels,
			'description'         => cfw__( 'This is where you can add new Pickup Locations.', 'checkout-wc' ),
			'public'              => false,
			'show_ui'             => true,
			'capability_type'     => self::get_post_type(),
			'map_meta_cap'        => true,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_in_menu'        => false,
			'hierarchical'        => false,
			'rewrite'             => false,
			'query_var'           => false,
			'supports'            => array(
				'title',
			),
			'show_in_nav_menus'   => false,
		);

		register_post_type( self::get_post_type(), $post_type_args );
	}

	public function map_capabilities() {
		global $wp_roles;

		if ( ! $wp_roles instanceof WP_Roles && class_exists( 'WP_Roles' ) ) {
			// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
			$wp_roles = new WP_Roles();
			// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		if ( ! is_object( $wp_roles ) ) {
			return;
		}

		$args                  = new \stdClass();
		$args->map_meta_cap    = true;
		$args->capability_type = self::get_post_type();
		$args->capabilities    = array();

		foreach ( (array) get_post_type_capabilities( $args ) as $mapped ) {
			$wp_roles->add_cap( 'shop_manager', $mapped );
			$wp_roles->add_cap( 'administrator', $mapped );
		}

		$wp_roles->add_cap( 'shop_manager', 'manage_woocommerce_pickup_locations' );
		$wp_roles->add_cap( 'administrator', 'manage_woocommerce_pickup_locations' );
	}

	public static function get_post_type(): string {
		return 'cfw_pickup_location';
	}

	public static function pickup_is_selected(): bool {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		parse_str( wc_clean( wp_unslash( $_POST['post_data'] ?? '' ) ), $post_data );

		// $post_data['cfw_delivery_method'] is for update_checkout
		// $_POST['cfw_delivery_method'] is for complete_order
		$delivery_method = $post_data['cfw_delivery_method'] ?? wc_clean( $_POST['cfw_delivery_method'] ?? 'ship' );

		return 'pickup' === $delivery_method;
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	public function filter_ship_to_label( $label ): string {

		if ( self::pickup_is_selected() ) {
			return cfw__( 'Method', 'woocommerce' );
		}

		return $label;
	}

	public function filter_shipping_address( $address ): string {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		parse_str( wp_unslash( wc_clean( $_POST['post_data'] ?? '' ) ), $post_data );
		$location = $post_data['cfw_pickup_location'] ?? '';

		if ( empty( $location ) ) {
			return $address;
		}

		$location = get_post( $location );

		if ( self::pickup_is_selected() && $location ) {
			return sprintf( '%s  &bullet; <b>%s</b><div class="mt-2">%s</div>', __( 'Pick up in store', 'checkout-wc' ), $location->post_title, get_post_meta( $location->ID, 'cfw_pl_address', true ) );
		}

		return $address;
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	public function filter_show_shipping_tab( $show ): bool {
		if ( ! self::pickup_is_selected() ) {
			return $show;
		}

		return SettingsManager::instance()->get_setting( 'enable_pickup_method_step' ) === 'yes';
	}

	public function filter_shipping_totals_label( $label ): string {
		if ( self::pickup_is_selected() ) {
			return __( 'Pickup', 'checkout-wc' );
		}

		return $label;
	}

	/**
	 * @throws \WC_Data_Exception
	 */
	public function handle_order_meta( int $order_id ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$order = wc_get_order( $order_id );

		if ( ! empty( $_POST['cfw_delivery_method'] ) ) {
			$order->update_meta_data( '_cfw_delivery_method', wc_clean( $_POST['cfw_delivery_method'] ), true );
		}

		if ( ! empty( $_POST['cfw_pickup_location'] ) ) {
			$order->update_meta_data( '_cfw_pickup_location', wc_clean( $_POST['cfw_pickup_location'] ), true );
		}

		/**
		 * Determine whether to copy pickup details to order notes
		 *
		 * @since 7.7.2
		 * @param bool $copy_pickup_details_to_order_notes Whether to copy pickup details to order notes
		 */
		if ( apply_filters( 'cfw_copy_pickup_details_to_order_notes', false ) && isset( $_POST['cfw_pickup_location'] ) ) {
			$location = (int) $_POST['cfw_pickup_location'];
			$address  = get_post_meta( $location, 'cfw_pl_address', true );
			$name     = get_the_title( $location );

			$existing_note = $order->get_customer_note();
			$newline       = "\r\n\r\n";
			$note          = '';

			if ( ! empty( $existing_note ) ) {
				$note = $existing_note . $newline;
			}

			$note .= __( 'Pickup Location', 'checkout-wc' ) . ':' . $newline;
			$note .= $name . $newline;
			$note .= $address;

			$order->set_customer_note( $note );
		}

		$order->save();
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	public function pickup_instructions_wrapped( WC_Order $order ) {
		cfw_thank_you_section_auto_wrap(
			array( $this, 'pickup_instructions' ),
			'cfw-order-updates',
			array( $order )
		);
	}

	public function pickup_instructions( WC_Order $order ) {
		$location = $order->get_meta( '_cfw_pickup_location', true );

		if ( empty( $location ) ) {
			return;
		}

		$raw_address = get_post_meta( $location, 'cfw_pl_address', true );

		/**
		 * Whether to link the local pickup address to Google Maps for directions
		 *
		 * @since 7.3.2
		 * @param bool $link Whether to link the local pickup address to Google Maps
		 */
		if ( apply_filters( 'cfw_local_pickup_use_google_address_link', true ) ) {
			$address = sprintf( '<a href="https://www.google.com/maps/dir/?api=1&destination=%s">%s</a>', rawurlencode( $raw_address ), wpautop( $raw_address ) );
		} else {
			$address = wpautop( $raw_address );
		}

		/**
		 * Filter the local pickup address shown to customers on the thank you page
		 *
		 * @since 7.3.2
		 * @param string $address The local pickup address shown to customers
		 */
		$address = apply_filters( 'cfw_local_pickup_thank_you_address', $address, $raw_address, $order );
		?>
		<h3>
			<?php
			/**
			 * Filter the pickup instructions
			 *
			 * @since 7.3.2
			 * @param string $instructions The pickup instructions
			 * @param WC_Order $order The order
			 */
			echo esc_html( apply_filters( 'cfw_order_updates_heading', __( 'Pickup instructions', 'checkout-wc' ), $order ) );
			?>
		</h3>
		<?php
		/**
		 * Filters pickup instructions text
		 *
		 * @since 7.3.0
		 *
		 * @param string $pickup_instructions_text Thank you page order updates text
		 */
		echo wp_kses_post( wpautop( apply_filters( 'cfw_pickup_instructions_text', get_post_meta( $location, 'cfw_pl_instructions', true ), $order ) ) );
		?>
		<h4>
			<?php cfw_e( 'Address', 'woocommerce' ); ?>
		</h4>
		<?php
		echo wp_kses_post( $address );
	}

	public function add_pickup_location_column_header( array $columns ): array {

		$new_cols = array();

		foreach ( $columns as $key => $value ) {
			$new_cols[ $key ] = $value;

			if ( 'shipping_address' === $key ) {
				$new_cols['pickup_location'] = cfw__( 'Pickup Location', 'checkout-wc' );
			}
		}

		return $new_cols;
	}

	public function pickup_location_column_content( $column, $post_id ) {
		if ( 'pickup_location' !== $column ) {
			return;
		}

		$order = wc_get_order( $post_id );

		if ( ! $order ) {
			return;
		}

		$location = $order->get_meta( '_cfw_pickup_location', true );

		if ( ! $location ) {
			echo '-';
			return;
		}

		$location = get_post( $location );
		$address  = get_post_meta( $location->ID, 'cfw_pl_address', true );

		echo '<b>' . esc_html( $location->post_title ) . '</b><br>' . wp_kses_post( wpautop( $address ) );
	}

	public function pickup_location_view_order( WC_Order $order ) {
		$location = $order->get_meta( '_cfw_pickup_location', true );

		if ( ! $location ) {
			return;
		}

		$location = get_post( $location );
		$address  = get_post_meta( $location->ID, 'cfw_pl_address', true );

		echo '<h3>' . esc_html( cfw__( 'Pickup Location', 'checkout-wc' ) ) . '</h3>';
		echo '<p><i>' . esc_html( $location->post_title ) . '</i><br>' . wp_kses_post( wpautop( $address ) ) . '</p>';
	}

	public function unrequire_checkout_fields( $fields ): array {
		if ( self::pickup_is_selected() ) {
			unset( $fields['shipping']['shipping_first_name'] );
			unset( $fields['shipping']['shipping_last_name'] );
			unset( $fields['shipping']['shipping_company'] );
			unset( $fields['shipping']['shipping_city'] );
			unset( $fields['shipping']['shipping_postcode'] );
			unset( $fields['shipping']['shipping_country'] );
			unset( $fields['shipping']['shipping_state'] );
			unset( $fields['shipping']['shipping_address_1'] );
			unset( $fields['shipping']['shipping_address_2'] );
		}

		return $fields;
	}

	public function handle_missing_shipping_country( $data ): array {
		if ( ! self::pickup_is_selected() ) {
			return $data;
		}

		$data['shipping_country'] = $data['billing_country'];

		return $data;
	}

	public function handle_invalid_shipping_country_shim( $countries ): array {
		if ( self::pickup_is_selected() ) {
			return WC()->countries->get_countries();
		}

		return $countries;
	}

	public static function get_pickup_times() {
		/**
		 * Filters the pickup times
		 *
		 * @since 7.3.0
		 * @param array $pickup_times The pickup times
		 */
		return apply_filters(
			'cfw_pickup_times',
			array(
				'1h'  => __( 'Usually ready in 1 hour.', 'checkout-wc' ),
				'2h'  => __( 'Usually ready in 2 hours.', 'checkout-wc' ),
				'4h'  => __( 'Usually ready in 4 hours.', 'checkout-wc' ),
				'24h' => __( 'Usually ready in 24 hours.', 'checkout-wc' ),
				'24d' => __( 'Usually ready in 2-4 days.', 'checkout-wc' ),
				'5d'  => __( 'Usually ready in 5+ days.', 'checkout-wc' ),
			)
		);
	}

	public function maybe_disable_shipping_option( $disable ) {
		if ( $disable ) {
			return $disable;
		}

		$has_calculated_shipping = WC()->customer->has_calculated_shipping();
		$address_required        = get_option( 'woocommerce_shipping_cost_requires_address' ) === 'yes';
		$missing_address         = $address_required && ! $has_calculated_shipping;

		if ( $missing_address ) {
			return $disable; // don't treat missing address like missing methods
		}

		// Get all shipping methods
		$shipping_methods = array();

		foreach ( WC()->shipping()->get_packages() as $package ) {
			$methods = cfw_apply_filters( 'woocommerce_package_rates', $package['rates'], $package );

			foreach ( $methods as $method ) {
				$shipping_methods[ $method->get_id() ] = $method->get_id();
			}
		}

		// If only shipping methods are pickup methods, disable shipping option
		$pickup_methods = $this->get_pickup_methods( $shipping_methods );

		foreach ( $shipping_methods as $method_id ) {
			if ( isset( $pickup_methods[$method_id] ) ) {
				continue;
			} else {
				return false;
			}
		}

		return $disable;
	}

	public function maybe_disable_pickup_option( $disable ) {
		if ( $disable ) {
			return $disable;
		}

		$has_calculated_shipping = WC()->customer->has_calculated_shipping();
		$address_required        = get_option( 'woocommerce_shipping_cost_requires_address' ) === 'yes';
		$missing_address         = $address_required && ! $has_calculated_shipping;

		if ( $missing_address ) {
			return $disable; // don't treat missing address like missing methods
		}

		// Get all shipping methods
		$shipping_methods = array();

		foreach ( WC()->shipping()->get_packages() as $package ) {
			$methods = cfw_apply_filters( 'woocommerce_package_rates', $package['rates'], $package );

			foreach ( $methods as $method ) {
				$shipping_methods[ $method->get_id() ] = $method->get_id();
			}
		}

		// If only shipping methods are pickup methods, disable shipping option
		$pickup_methods = $this->get_pickup_methods( $shipping_methods );

		// If $shipping_methods doesn't contain any $pickup_methods disable pickup option
		$common_methods = array_intersect_key($shipping_methods, $pickup_methods);

		if ( empty( $common_methods ) ) {
			$disable = true;
		}

		return $disable;
	}
}

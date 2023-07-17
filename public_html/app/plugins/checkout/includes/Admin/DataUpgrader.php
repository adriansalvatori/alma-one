<?php

namespace Objectiv\Plugins\Checkout\Admin;

use Objectiv\Plugins\Checkout\Factories\BumpFactory;
use Objectiv\Plugins\Checkout\Features\AbandonedCartRecovery;
use Objectiv\Plugins\Checkout\Features\Pickup;
use Objectiv\Plugins\Checkout\Managers\SettingsManager;
use Objectiv\Plugins\Checkout\Managers\UpdatesManager;
use Exception;
use Objectiv\Plugins\Checkout\Stats\StatCollection;

class DataUpgrader {
	public function __construct() {
	}

	/**
	 * Init
	 *
	 * @throws Exception
	 */
	public function init() {
		global $wpdb;

		$db_version = get_option( 'cfw_db_version', false );

		// Don't run upgrades for first time activators
		if ( ! $db_version ) {
			$this->update_version();

			return;
		}

		// Prevents data migrations from running on every page load
		// If already at the prescribed version, bail
		if ( CFW_VERSION === $db_version ) {
			return;
		}

		// If db version is greater than plugin version, bail
		if ( version_compare( $db_version, CFW_VERSION, '>' ) ) {
			return;
		}

		do_action( 'cfw_before_plugin_data_upgrades', $db_version );

		// 3.0.0 upgrades
		if ( version_compare( '3.0.0', $db_version, '>' ) ) {
			cfw_get_active_template()->init();

			if ( SettingsManager::instance()->get_setting( 'allow_tracking' ) === 1 ) {
				SettingsManager::instance()->update_setting( 'allow_tracking', md5( trailingslashit( home_url() ) ) );
			}
		}

		// 3.3.0 upgrades
		if ( version_compare( '3.3.0', $db_version, '>' ) ) {
			SettingsManager::instance()->add_setting( 'override_view_order_template', 'yes' );

			// Do this again because we are dumb
			if ( SettingsManager::instance()->get_setting( 'allow_tracking' ) === 1 ) {
				SettingsManager::instance()->update_setting( 'allow_tracking', md5( trailingslashit( home_url() ) ) );
			}
		}

		// 3.6.1 upgrades
		if ( version_compare( '3.6.1', $db_version, '>' ) ) {
			// Set default glass accent color
			SettingsManager::instance()->update_setting( 'accent_color', '#dee6fe', array( 'glass' ) );
		}

		// 3.14.0 upgrades
		if ( version_compare( '3.14.0', $db_version, '>' ) ) {
			// Set default glass accent color
			SettingsManager::instance()->add_setting( 'enable_order_review_step', 'no' );
		}

		// 5.3.0 upgrades
		if ( version_compare( '5.3.0', $db_version, '>' ) ) {
			foreach ( cfw_get_available_templates() as $template ) {
				$breadcrumb_completed_text_color   = '#7f7f7f';
				$breadcrumb_current_text_color     = '#333333';
				$breadcrumb_next_text_color        = '#7f7f7f';
				$breadcrumb_completed_accent_color = '#333333';
				$breadcrumb_current_accent_color   = '#333333';
				$breadcrumb_next_accent_color      = '#333333';

				if ( $template->get_slug() === 'glass' ) {
					$breadcrumb_current_text_color   = SettingsManager::instance()->get_setting( 'button_color', array( 'glass' ) );
					$breadcrumb_current_accent_color = SettingsManager::instance()->get_setting( 'button_color', array( 'glass' ) );
					$breadcrumb_next_text_color      = '#dfdcdb';
					$breadcrumb_next_accent_color    = '#dfdcdb';

				} elseif ( $template->get_slug() === 'futurist' ) {
					$futurist_header_bg_color          = SettingsManager::instance()->get_setting( 'header_background_color', array( $template->get_slug() ) );
					$color                             = '#ffffff' === $futurist_header_bg_color ? '#333333' : '#222222';
					$breadcrumb_completed_text_color   = $color;
					$breadcrumb_current_text_color     = $color;
					$breadcrumb_next_text_color        = $color;
					$breadcrumb_completed_accent_color = $color;
					$breadcrumb_current_accent_color   = $color;
					$breadcrumb_next_accent_color      = $color;
				}

				SettingsManager::instance()->update_setting( 'breadcrumb_completed_text_color', $breadcrumb_completed_text_color, array( $template->get_slug() ) );
				SettingsManager::instance()->update_setting( 'breadcrumb_current_text_color', $breadcrumb_current_text_color, array( $template->get_slug() ) );
				SettingsManager::instance()->update_setting( 'breadcrumb_next_text_color', $breadcrumb_next_text_color, array( $template->get_slug() ) );
				SettingsManager::instance()->update_setting( 'breadcrumb_completed_accent_color', $breadcrumb_completed_accent_color, array( $template->get_slug() ) );
				SettingsManager::instance()->update_setting( 'breadcrumb_current_accent_color', $breadcrumb_current_accent_color, array( $template->get_slug() ) );
				SettingsManager::instance()->update_setting( 'breadcrumb_next_accent_color', $breadcrumb_next_accent_color, array( $template->get_slug() ) );
			}

			// Convert order bump data
			$items = $wpdb->get_results( "SELECT order_item_id, meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key = '_cfw_order_bump_id';" );

			foreach ( $items as $item ) {
				$order_id = $wpdb->get_var( $wpdb->prepare( "SELECT order_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d", $item->order_item_id ) );

				$order = \wc_get_order( (int) $order_id );
				if ( $order ) {
					if ( (int) $order->get_meta( 'cfw_has_bump' ) === 1 ) {
						continue;
					}
					$order->add_meta_data( 'cfw_has_bump', true );
					$order->add_meta_data( 'cfw_bump_' . $item->meta_value, true );
					$order->save();
				}
			}
		}

		// 5.3.1 upgrades
		if ( version_compare( '5.3.1', $db_version, '>' ) ) {
			foreach ( cfw_get_available_templates() as $template ) {
				$template->init();
			}

			SettingsManager::instance()->update_setting( 'summary_background_color', '#f8f8f8', array( 'futurist' ) );
		}

		if ( version_compare( '5.3.2', $db_version, '>' ) ) {
			$futurist_header_bg_color = SettingsManager::instance()->get_setting( 'header_background_color', array( 'futurist' ) );
			$color                    = '#ffffff' === $futurist_header_bg_color ? '#333333' : $futurist_header_bg_color;

			SettingsManager::instance()->update_setting( 'breadcrumb_completed_text_color', $color, array( 'futurist' ) );
			SettingsManager::instance()->update_setting( 'breadcrumb_current_text_color', $color, array( 'futurist' ) );
			SettingsManager::instance()->update_setting( 'breadcrumb_next_text_color', $color, array( 'futurist' ) );
			SettingsManager::instance()->update_setting( 'breadcrumb_completed_accent_color', $color, array( 'futurist' ) );
			SettingsManager::instance()->update_setting( 'breadcrumb_current_accent_color', $color, array( 'futurist' ) );
			SettingsManager::instance()->update_setting( 'breadcrumb_next_accent_color', $color, array( 'futurist' ) );
		}

		if ( version_compare( '6.0.0', $db_version, '>' ) ) {
			SettingsManager::instance()->add_setting( 'enable_international_phone_field', 'no' );
			SettingsManager::instance()->add_setting( 'enable_side_cart', 'no' );
			SettingsManager::instance()->add_setting( 'enable_free_shipping_progress_bar', 'no' );
			SettingsManager::instance()->add_setting( 'side_cart_free_shipping_threshold', '' );
			SettingsManager::instance()->add_setting( 'side_cart_amount_remaining_message', '' );
			SettingsManager::instance()->add_setting( 'side_cart_free_shipping_message', '' );
			SettingsManager::instance()->add_setting( 'side_cart_free_shipping_threshold', '' );
			SettingsManager::instance()->add_setting( 'side_cart_free_shipping_progress_indicator_color', cfw_get_active_template()->get_default_setting( 'button_color' ) );
			SettingsManager::instance()->add_setting( 'enable_floating_cart_button', 'yes' );
			SettingsManager::instance()->add_setting( 'floating_cart_button_bottom_position', '20' );
			SettingsManager::instance()->add_setting( 'floating_cart_button_right_position', '20' );
			SettingsManager::instance()->add_setting( 'enable_ajax_add_to_cart', 'no' );
		}

		if ( version_compare( '6.0.1', $db_version, '>' ) ) {
			SettingsManager::instance()->add_setting( 'side_cart_free_shipping_progress_bg_color', '#f5f5f5' );
		}

		if ( version_compare( '6.0.6', $db_version, '>' ) ) {
			SettingsManager::instance()->add_setting( 'enable_order_bumps_on_side_cart', 'no' );
		}

		if ( version_compare( '6.1.0', $db_version, '>' ) ) {
			SettingsManager::instance()->add_setting( 'side_cart_icon_color', '#222222' );
			SettingsManager::instance()->add_setting( 'side_cart_icon_width', '34' );
			SettingsManager::instance()->add_setting( 'side_cart_icon', 'cart-outline.svg' );
		}

		if ( version_compare( '6.1.4', $db_version, '>' ) ) {
			SettingsManager::instance()->add_setting( 'hide_floating_cart_button_empty_cart', 'no' );
			SettingsManager::instance()->add_setting( 'enable_astra_support', 'no' );

			$bumps = BumpFactory::get_all();

			foreach ( $bumps as $bump ) {
				add_post_meta( $bump->get_id(), 'captured_revenue', $bump->get_estimated_revenue() );
			}
		}

		if ( version_compare( '7.0.0', $db_version, '>' ) ) {
			SettingsManager::instance()->add_setting( 'hide_optional_address_fields_behind_link', 'yes' );
			SettingsManager::instance()->add_setting( 'enable_discreet_address_1_fields', 'no' );
			SettingsManager::instance()->add_setting( 'use_fullname_field', 'no' );

			SettingsManager::instance()->add_setting( 'header_scripts_checkout', '' );
			SettingsManager::instance()->add_setting( 'header_scripts_thank_you', '' );
			SettingsManager::instance()->add_setting( 'header_scripts_order_pay', '' );

			SettingsManager::instance()->add_setting( 'footer_scripts_checkout', '' );
			SettingsManager::instance()->add_setting( 'footer_scripts_thank_you', '' );
			SettingsManager::instance()->add_setting( 'footer_scripts_order_pay', '' );
		}

		if ( version_compare( '7.0.17', $db_version, '>' ) ) {
			SettingsManager::instance()->add_setting( 'trust_badge_position', 'below_cart_summary' );
		}

		if ( version_compare( '7.1.5', $db_version, '>' ) ) {
			SettingsManager::instance()->add_setting( 'enable_side_cart_continue_shopping_button', 'no' );
		}

		if ( version_compare( '7.1.8', $db_version, '>' ) ) {
			foreach ( cfw_get_available_templates() as $template ) {
				SettingsManager::instance()->update_setting( 'summary_link_color', '#0073aa', array( $template->get_slug() ) );
			}

			SettingsManager::instance()->add_setting( 'show_cart_item_discounts', 'no' );
			SettingsManager::instance()->add_setting( 'show_side_cart_item_discount', 'no' );
		}

		if ( version_compare( '7.3.0', $db_version, '>' ) ) {
			SettingsManager::instance()->add_setting(
				'enabled_billing_address_fields',
				array(
					'billing_first_name',
					'billing_last_name',
					'billing_address_1',
					'billing_address_2',
					'billing_company',
					'billing_country',
					'billing_postcode',
					'billing_state',
					'billing_city',
					'billing_phone',
				)
			);
		}

		if ( version_compare( '7.3.1', $db_version, '>' ) ) {
			$pickup_times     = Pickup::get_pickup_times();
			$pickup_locations = get_posts(
				array(
					'post_type' => Pickup::get_post_type(),
				)
			);

			if ( $pickup_locations ) {
				foreach ( $pickup_locations as $pickup_location ) {
					$cfw_pl_estimated_time = get_post_meta( $pickup_location->ID, 'cfw_pl_estimated_time', true );

					$key = array_search( $cfw_pl_estimated_time, $pickup_times, true );

					if ( $key ) {
						update_post_meta( $pickup_location->ID, 'cfw_pl_estimated_time', $key );
					}
				}
			}

			$skip_shipping_step = SettingsManager::instance()->get_setting( 'enable_pickup_skip_shipping_step' );

			if ( 'no' === $skip_shipping_step ) {
				SettingsManager::instance()->add_setting( 'enable_pickup_method_step', 'yes' );
			} else {
				SettingsManager::instance()->add_setting( 'enable_pickup_method_step', 'no' );
			}
		}

		if ( version_compare( '7.6.0', $db_version, '>' ) ) {
			SettingsManager::instance()->add_setting( 'hide_pickup_methods', 'no' );
			SettingsManager::instance()->add_setting( 'enable_pickup_ship_option', 'yes' );
		}

		if ( version_compare( '7.8.0', $db_version, '>' ) ) {
			UpdatesManager::instance()->get_license_data();
		}

		if ( version_compare( '7.9.0', $db_version, '>' ) ) {
			StatCollection::instance()->send_checkin( false, true );
		}

		if ( version_compare( '7.10.2', $db_version, '>' ) ) {
			$side_cart_icon = SettingsManager::instance()->get_setting( 'side_cart_icon' );

			if ( file_exists( $side_cart_icon ) ) {
				SettingsManager::instance()->update_setting( 'side_cart_icon', basename( $side_cart_icon ) );
			}
		}

		$data_migrated = get_option( 'cfw_v80_data_migrated', false );

		if ( version_compare( '8.0.0', $db_version, '>' ) && ! $data_migrated ) {
			// Migrate settings
			$settings_obj = SettingsManager::instance()->get_settings_obj( true );

			if ( ! empty( $settings_obj ) ) {
				foreach ( $settings_obj as $key => $value ) {
					// Have to use update_setting because of edge case where someone upgrades by activating the plugin
					SettingsManager::instance()->update_setting( $key, $value );
				}
			}

			add_option( 'cfw_v80_data_migrated', true );

			// Migrate Inter to Inter-cfw
			$body_font    = SettingsManager::instance()->get_setting( 'body_font' );
			$heading_font = SettingsManager::instance()->get_setting( 'heading_font' );

			if ( 'Inter' === $body_font ) {
				SettingsManager::instance()->update_setting( 'body_font', 'inter-cfw' );
			}

			if ( 'Inter' === $heading_font ) {
				SettingsManager::instance()->update_setting( 'heading_font', 'inter-cfw' );
			}

			// Make font a template setting by migrating existing font setting to the templates
			$body_font    = SettingsManager::instance()->get_setting( 'body_font' );
			$heading_font = SettingsManager::instance()->get_setting( 'heading_font' );

			// Update the current active template
			SettingsManager::instance()->add_setting( 'body_font', $body_font, array( cfw_get_active_template()->get_slug() ) );
			SettingsManager::instance()->add_setting( 'heading_font', $heading_font, array( cfw_get_active_template()->get_slug() ) );

			// Cleanup old global settings
			SettingsManager::instance()->delete_setting( 'body_font' );
			SettingsManager::instance()->delete_setting( 'heading_font' );
		}

		// 8.0 standard setting upgrades
		if ( version_compare( '8.0.0', $db_version, '>' ) ) {
			SettingsManager::instance()->add_setting( 'allow_checkout_cart_item_variation_changes', 'no' );
			SettingsManager::instance()->add_setting( 'allow_side_cart_item_variation_changes', 'no' );
			SettingsManager::instance()->add_setting( 'enable_order_bumps', 'yes' );
			SettingsManager::instance()->add_setting( 'max_bumps', '-1' );
			SettingsManager::instance()->add_setting( 'shake_floating_cart_button', 'no' );
			SettingsManager::instance()->add_setting( 'enable_beta_version_updates', 'no' );
			SettingsManager::instance()->add_setting( 'disable_express_checkout', 'no' );

			/**
			 * Action: cfw_acr_activate
			 *
			 * Fires when the plugin is activated.
			 *
			 * @since 8.0.0
			 */
			do_action( 'cfw_acr_activate' );

			// Only do this redirect until we get to 8.1
			if ( version_compare( '8.1.0', CFW_VERSION, '>' ) ) {
				set_transient( '_cfw_80_upgrade_welcome_redirect', true, 30 );
			}
		}

		// Between 8.0.0 and 8.0.2 data fix
		if ( version_compare( $db_version, '8.0.0', '>=' ) && version_compare( '8.0.3', $db_version, '>' ) ) {
			// Groove was accidentally set to the current body / heading font instead of its default font
			// This repairs the font setting for Groove unless it's already the active template in which
			// case we stand down as we don't know if the customer prefers the System Font Stack option
			if ( 'groove' !==  cfw_get_active_template()->get_slug() ) {
				SettingsManager::instance()->update_setting( 'body_font', 'inter-cfw', array( 'groove' ) );
				SettingsManager::instance()->update_setting( 'heading_font', 'inter-cfw', array( 'groove' ) );
			}
		}

		$this->update_version();

		/**
		 * Fires after plugin data upgrades.
		 *
		 * @since 5.0.0
		 * @param string $db_version The current db version.
		 */
		do_action( 'cfw_after_plugin_data_upgrades', $db_version );
	}

	private function update_version() {
		$db_version = get_option( 'cfw_db_version', '0.0.0' );

		// Only update db version if the current version is greater than the db version
		if ( version_compare( CFW_VERSION, $db_version, '>' ) ) {
			update_option( 'cfw_db_version', CFW_VERSION );
		}
	}
}

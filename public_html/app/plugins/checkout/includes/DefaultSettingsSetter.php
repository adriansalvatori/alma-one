<?php

namespace Objectiv\Plugins\Checkout;

use Objectiv\Plugins\Checkout\Managers\SettingsManager;

/**
 * This class is a holding tank for settings defaults that have yet to find a home
 *
 * @deprecated
 */
class DefaultSettingsSetter {
	public function __construct() {
	}

	public function init() {
		$settings_manager = SettingsManager::instance();

		// Maybe update from lite version
		$lite_settings = get_option( '_cfwlite__settings', false );

		if ( ! empty( $lite_settings ) ) {
			foreach ( $lite_settings as $key => $value ) {
				$settings_manager->add_setting( $key, $value ); // don't overwrite pro settings
			}
		}

		$settings_manager->add_setting( 'enable', 'no' );
		$settings_manager->add_setting( 'login_style', 'enhanced' );
		$settings_manager->add_setting( 'registration_style', 'enhanced' );
		$settings_manager->add_setting( 'label_style', 'floating' );
		$settings_manager->add_setting( 'cart_item_link', 'disabled' );
		$settings_manager->add_setting( 'cart_item_data_display', 'short' );
		$settings_manager->add_setting( 'skip_shipping_step', 'no' );
		$settings_manager->add_setting( 'enable_order_notes', 'no' );
		$settings_manager->add_setting( 'active_template', 'groove' );
		$settings_manager->add_setting( 'allow_checkout_field_editor_address_modification', 'no' );
		$settings_manager->add_setting( 'enable_elementor_pro_support', 'no' );
		$settings_manager->add_setting( 'enable_beaver_themer_support', 'no' );
		$settings_manager->add_setting( 'template_loader', 'redirect' );
		$settings_manager->add_setting( 'override_view_order_template', 'yes' );
		$settings_manager->add_setting( 'show_logos_mobile', 'no' );
		$settings_manager->add_setting( 'show_mobile_coupon_field', 'no' );
		$settings_manager->add_setting( 'enable_order_pay', 'no' );
		$settings_manager->add_setting( 'enable_thank_you_page', 'no' );
		$settings_manager->add_setting( 'thank_you_order_statuses', 'no' );
		$settings_manager->add_setting( 'enable_map_embed', 'no' );
		$settings_manager->add_setting( 'override_view_order_template', 'no' );
		$settings_manager->add_setting( 'google_places_api_key', '' );
		$settings_manager->add_setting( 'user_matching', 'enabled' );
		$settings_manager->add_setting( 'hide_optional_address_fields_behind_link', 'yes' );
		$settings_manager->add_setting( 'enable_pickup_ship_option', 'yes' );
		$settings_manager->add_setting( 'enable_coupon_code_link', 'yes' );
		$settings_manager->add_setting( 'enable_cart_editing', 'yes' );
		$settings_manager->add_setting( 'enable_order_bumps', 'yes' );
		$settings_manager->add_setting( 'max_bumps', '10' );
		$settings_manager->add_setting( 'shake_floating_cart_button', 'no' );
		$settings_manager->add_setting( 'enable_beta_version_updates', 'no' );

		$custom_logo_id = get_theme_mod( 'custom_logo' );

		if ( $custom_logo_id ) {
			$settings_manager->add_setting( 'logo_attachment_id', $custom_logo_id );
		}

		// 7.1.8
		$settings_manager->add_setting( 'show_cart_item_discount', 'yes' );

		// 7.3.0
		$settings_manager->add_setting(
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

		// Init templates
		foreach ( cfw_get_available_templates() as $template ) {
			$template->init();
		}
	}
}

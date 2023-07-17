<?php

namespace Objectiv\Plugins\Checkout\API;

use Objectiv\Plugins\Checkout\Managers\SettingsManager;
use WP_Error;
use WP_REST_Server;

class SettingsAPI {
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_route' ) );
	}

	public function register_route() {
		register_rest_route( 'checkoutwc/v1', 'settings/(?P<setting_key>[\S]+)', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_setting' ),
			'permission_callback' => array( $this, 'can_access_settings_api' ),
		) );

		register_rest_route( 'checkoutwc/v1', 'settings/(?P<setting_key>[\S]+)', array(
			'methods'             => WP_REST_Server::EDITABLE,
			'callback'            => array( $this, 'update_setting' ),
			'permission_callback' => array( $this, 'can_access_settings_api' ),
		) );
	}

	public function get_setting( \WP_REST_Request $request ) {
		$key = $request->get_param( 'setting_key' );

		if ( isset( $_GET['keys'] ) && is_array( $_GET['keys'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
			$keys = $this->recursive_sanitize_text_field( $_GET['keys'] );
		} else {
			$keys = array();
		}

		$setting = SettingsManager::instance()->get_setting( $key, $keys );

		return rest_ensure_response( array(
			'key'   => $key,
			'value' => $setting,
		) );
	}

	public function update_setting( \WP_REST_Request $request ) {
		$manager       = SettingsManager::instance();
		$key           = $request->get_param( 'setting_key' );
		$body          = json_decode( $request->get_body() );
		$response_data = array();

		if ( ! isset( $body->value ) ) {
			$response_data['error'] = 'No value provided';
			$response               = new WP_Error( '400', 'No value provided', $response_data );

			return rest_ensure_response( $response );
		}

		if ( isset( $_GET['keys'] ) && is_array( $_GET['keys'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
			$keys = $this->recursive_sanitize_text_field( $_GET['keys'] );
		} else {
			$keys = array();
		}

		$manager->update_setting( $key, $body->value, $keys );

		$newValue                 = $manager->get_setting( $key, $keys );
		$success                  = $this->areEqual($body->value, $newValue );
		$response_data['success'] = $success;

		if ( $success ) {
			return rest_ensure_response( $response_data );
		}

		$response_data['error'] = "Unable to update setting_key: $key to value: $body->value";
		$response               = new WP_Error( '400', 'Unable to update setting. If this error persists contact your site administrator.', $response_data );

		return rest_ensure_response( $response );
	}

	public function areEqual( $a, $b ): bool {
		if ( gettype( $a ) !== gettype( $b ) ) {
			return false;
		}

		if ( is_scalar( $a ) || is_null( $a ) ) {
			return ( $a === $b );
		}

		if ( is_array( $a ) ) {
			if ( count( $a ) !== count( $b ) ) {
				return false;
			}

			foreach ( $a as $key => $value ) {
				if ( ! array_key_exists( $key, $b ) ) {
					return false;
				}

				if ( ! $this->areEqual( $value, $b[ $key ] ) ) {
					return false;
				}
			}

			return true;
		}

		// For objects, we need to compare their properties
		$a_properties = get_object_vars( $a );
		$b_properties = get_object_vars( $b );

		if ( count( $a_properties ) !== count( $b_properties ) ) {
			return false;
		}

		foreach ( $a_properties as $property => $value ) {
			if ( ! array_key_exists( $property, $b_properties ) ) {
				return false;
			}

			if ( ! $this->areEqual( $value, $b_properties[ $property ] ) ) {
				return false;
			}
		}

		return true;
	}


	public function can_access_settings_api(): bool {
		return current_user_can( 'manage_options' );
	}

	public function recursive_sanitize_text_field( $array ) {
		foreach ( $array as $key => &$value ) {
			if ( is_array( $value ) ) {
				$value = $this->recursive_sanitize_text_field( $value );
			} else {
				$value = sanitize_text_field( $value );
			}
		}

		return $array;
	}
}

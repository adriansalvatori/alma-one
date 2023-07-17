<?php

namespace Objectiv\Plugins\Checkout\Managers;

/**
 * Determine whether the user has the right plan for a feature
 *
 * @link checkoutwc.com
 * @since 5.0.0
 * @package Objectiv\Plugins\Checkout\Managers
 */
class PlanManager {
	const PREMIUM_PLANS = array( 'Plus', 'Pro', 'Agency' );

	/**
	 * Does the user have the required plan?
	 *
	 * @return bool
	 */
	public static function has_premium_plan(): bool {
		$price_id = UpdatesManager::instance()->get_license_price_id();

		return in_array(
			$price_id,
			array(
				2, // Plus (Legacy)
				3, // Agency (Legacy)
				4, // Agency Monthly (Legacy)
				6, // Plus Monthly (Legacy)
				7, // Pro (Legacy)
				8, // Pro Monthly (Legacy)
				9, // Plus (5 Sites) - 2023
				10, // Agency (50 Sites) - 2023
				12, // Pro (10 Sites) - 2023
			),
			true
		);
	}

	/**
	 * Get the required plans
	 *
	 * @return array
	 */
	public static function get_required_plans(): array {
		return self::PREMIUM_PLANS;
	}

	/**
	 * Returns an English formatted list of plans
	 *
	 * Examples:
	 * - X or Y
	 * - X, Y, or Z
	 *
	 * @param array $array_of_strings
	 * @return string
	 */
	public static function get_formatted_english_list( array $array_of_strings ): string {
		if ( count( $array_of_strings ) <= 2 ) {
			return join( ' or ', $array_of_strings );
		}

		return implode( ', ', array_slice( $array_of_strings, 0, -1 ) ) . ', or ' . end( $array_of_strings );
	}

	/**
	 * Get English list of required plans
	 *
	 * @return string
	 */
	public static function get_english_list_of_required_plans_html(): string {
		$plans = self::get_required_plans();

		$plans = array_map(
			function( $plan ) {
				return "<strong>{$plan}</strong>";
			},
			$plans
		);

		return self::get_formatted_english_list( $plans );
	}

	/**
	 * Can access feature?
	 *
	 * @param string $setting_key
	 *
	 * @return bool
	 */
	public static function can_access_feature( string $setting_key ): bool {
		$has_correct_plan = self::has_premium_plan();

		$value = SettingsManager::instance()->get_setting( $setting_key );

		return $has_correct_plan && ( 'yes' === $value || 'enabled' === $value );
	}
}

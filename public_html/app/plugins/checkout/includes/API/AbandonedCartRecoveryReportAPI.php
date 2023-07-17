<?php

namespace Objectiv\Plugins\Checkout\API;

use DateTime;
use Exception;

class AbandonedCartRecoveryReportAPI {
	public function __construct() {
		add_action( 'rest_api_init', function () {
			register_rest_route( 'checkoutwc/v1', 'acr/(?P<startDate>\d{4}-\d{2}-\d{2})/(?P<endDate>\d{4}-\d{2}-\d{2})', array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_acr_report' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				}
			) );
		} );
	}

	/**
	 * Get the acr report
	 *
	 * @throws Exception
	 */
	public function get_acr_report( \WP_REST_Request $data ) {
		global $wpdb;

		$startDate          = new DateTime( $data->get_param( 'startDate' ) );
		$endDate            = new DateTime( $data->get_param( 'endDate' ) );
		$table_name         = $wpdb->prefix . 'cfw_acr_carts';
		$decimal_separator  = wc_get_price_decimal_separator();
		$thousand_separator = wc_get_price_thousand_separator();
		$decimals           = wc_get_price_decimals();
		$price_format       = get_woocommerce_price_format();

		$endDate->modify( '+1 day' );

		$carts = $wpdb->get_results( $query = $wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE created >= %s AND created <= %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$startDate->format( 'Y-m-d H:i:s' ),
			$endDate->format( 'Y-m-d H:i:s' )
		) );

		$counts = array(
			'new'                 => 0,
			'abandoned'           => 0,
			'lost'                => 0,
			'recovered'           => 0,
			'recoverable_revenue' => 0,
			'recovered_revenue'   => 0,
		);

		foreach ( $carts as $cart ) {
			switch ( $cart->status ) {
				case 'new':
					$counts['new'] ++;
					$counts['recoverable_revenue'] += $cart->subtotal;
					break;
				case 'abandoned':
					$counts['abandoned'] ++;
					$counts['recoverable_revenue'] += $cart->subtotal;
					break;
				case 'lost':
					$counts['lost'] ++;
					break;
				case 'recovered':
					$counts['recovered'] ++;
					$counts['recovered_revenue'] += $cart->subtotal;
					break;
			}
		}

		$recoverable_revenue = number_format( (float) $counts['recoverable_revenue'], $decimals, $decimal_separator, $thousand_separator );
		$recovered_revenue   = number_format( (float) $counts['recovered_revenue'], $decimals, $decimal_separator, $thousand_separator );

		return array(
			array(
				'name' => cfw__( 'Recoverable Orders', 'checkout-wc' ),
				'stat' => $counts['new'] + $counts['abandoned'],
			),
			array(
				'name' => cfw__( 'Recovered Orders', 'checkout-wc' ),
				'stat' => $counts['recovered'],
			),
			array(
				'name' => cfw__( 'Lost Orders', 'checkout-wc' ),
				'stat' => $counts['lost'],
			),
			array(
				'name' => cfw__( 'Recoverable Revenue', 'checkout-wc' ),
				'stat' => html_entity_decode( sprintf( $price_format, get_woocommerce_currency_symbol(), $recoverable_revenue ) ),
			),
			array(
				'name' => cfw__( 'Recovered Revenue', 'checkout-wc' ),
				'stat' => html_entity_decode( sprintf( $price_format, get_woocommerce_currency_symbol(), $recovered_revenue ) ),
			),
			array(
				'name' => cfw__( 'Recovery Rate', 'checkout-wc' ),
				'stat' => $counts['recovered'] ? round( $counts['recovered'] / ( $counts['new'] + $counts['abandoned'] + $counts['lost'] + $counts['recovered'] ) * 100 ) . '%' : '0%',
			),
		);
	}
}

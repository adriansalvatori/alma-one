<?php

namespace Objectiv\Plugins\Checkout\Action;

/**
 * Class GetVariationForm
 *
 * @link checkoutwc.com
 * @since 8.0.0
 * @package Objectiv\Plugins\Checkout\Action
 */
class GetVariationForm extends CFWAction {

	/**
	 * LogInAction constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct( 'cfw_get_variation_form' );
	}

	/**
	 * Logs in the user based on the information passed. If information is incorrect it returns an error message
	 *
	 * @since 1.0.0
	 */
	public function action() {

	}
}

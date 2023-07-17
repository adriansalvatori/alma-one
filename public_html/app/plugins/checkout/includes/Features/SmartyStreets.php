<?php

namespace Objectiv\Plugins\Checkout\Features;

use Objectiv\Plugins\Checkout\Action\SmartyStreetsAddressValidationAction;
use Objectiv\Plugins\Checkout\Admin\Pages\PageAbstract;
use Objectiv\Plugins\Checkout\Managers\PlanManager;
use Objectiv\Plugins\Checkout\Managers\SettingsManager;

class SmartyStreets extends FeaturesAbstract {
	public function init() {
		parent::init();

		add_action( 'cfw_do_plugin_activation', array( $this, 'run_on_plugin_activation' ) );
		add_action( 'cfw_after_admin_page_address_options_section', array( $this, 'output_admin_settings' ) );
	}

	protected function run_if_cfw_is_enabled() {
		add_action( 'cfw_checkout_customer_info_tab', array( $this, 'output_modal' ), 60 );
		add_filter( 'cfw_event_data', array( $this, 'add_localized_settings' ) );
	}

	/**
	 * Output admin settings
	 *
	 * @param PageAbstract $checkout_admin_page
	 */
	public function output_admin_settings( PageAbstract $checkout_admin_page ) {
		if ( ! $this->available ) {
			$notice = $checkout_admin_page->get_upgrade_required_notice( $this->required_plans_list );
		}

		$checkout_admin_page->output_checkbox_row(
			'enable_smartystreets_integration',
			cfw__( 'Enable Smarty Address Validation', 'checkout-wc' ),
			cfw__( 'Validates shipping address with Smarty.com and provides alternative, corrected addresses for incorrect or incomplete addresses.', 'checkout-wc' ),
			array(
				'enabled' => PlanManager::has_premium_plan(),
				'notice'  => $notice ?? '',
			)
		);

		$checkout_admin_page->output_text_input_row(
			'smartystreets_auth_id',
			cfw__( 'Smarty Auth ID', 'checkout-wc' ),
			cfw__( 'Smarty Auth ID. Available in your <a target="_blank" href="https://www.smarty.com/account/keys">Smarty Account</a>.', 'checkout-wc' ),
			array( 'nested' => true )
		);

		$checkout_admin_page->output_text_input_row(
			'smartystreets_auth_token',
			cfw__( 'Smarty Auth Token', 'checkout-wc' ),
			cfw__( 'Smarty Auth Token. Available in your <a target="_blank" href="https://www.smarty.com/account/keys">Smarty Account</a>.', 'checkout-wc' ),
			array( 'nested' => true )
		);
	}

	/**
	 * Add localized settings
	 *
	 * @param array $event_data
	 * @return array
	 */
	public function add_localized_settings( array $event_data ): array {
		/**
		 * Whether to enable Smarty integration
		 *
		 * @since 5.2.1
		 * @param bool $enable Whether to enable Smarty integration
		 */
		$event_data['settings']['enable_smartystreets_integration'] = apply_filters( 'cfw_enable_smartystreets_integration', true );

		return $event_data;
	}

	public function output_modal() {
		$translated_button_label = __( 'Use This Address', 'checkout-wc' );
		?>
		<a href="#cfw_smartystreets_confirm_modal" class="cfw-smartystreets-modal-trigger cfw-hidden"></a>
		<div id="cfw_smartystreets_confirm_modal" class="cfw-hidden">
			<h2 id="cfw-smarty-modal-title" class="cfw-smarty-matched">
				<?php esc_html_e( 'Use recommended address instead?', 'checkout-wc' ); ?>
			</h2>

			<h2 id="cfw-smarty-modal-title" class="cfw-smarty-unmatched">
				<?php esc_html_e( 'We are unable to verify your address.', 'checkout-wc' ); ?>
			</h2>

			<h4 id="cfw-smarty-modal-subtitle" class="cfw-small cfw-smarty-matched">
				<?php esc_html_e( 'We\'re unable to verify your address, but found a close match.', 'checkout-wc' ); ?>
			</h4>

			<h4 id="cfw-smarty-modal-subtitle" class="cfw-small cfw-smarty-unmatched">
				<?php esc_html_e( 'Please confirm you would like to use this address or try again.', 'checkout-wc' ); ?>
			</h4>

			<div class="cfw-smartystreets-option-wrap">
				<h4>
					<label>
						<?php esc_html_e( 'You Entered', 'checkout-wc' ); ?>
					</label>
				</h4>

				<p class="cfw-smartystreets-user-address"></p>
			</div>

			<div class="cfw-smartystreets-option-wrap cfw-smarty-matched">
				<h4>
					<label>
						<?php esc_html_e( 'Recommended', 'checkout-wc' ); ?>
					</label>
				</h4>

				<p class="cfw-smartystreets-suggested-address"></p>
			</div>

			<p class="cfw-smartystreets-button-wrap">
				<a href="javascript:" class="cfw-smartystreets-button cfw-primary-btn cfw-smartystreets-suggested-address-button">
					<?php esc_html_e( 'Use Recommended', 'checkout-wc' ); ?>
				</a>
			</p>

			<p class="cfw-smartystreets-button-wrap">
				<a href="javascript:" class="cfw-smartystreets-button cfw-smartystreets-user-address-button">
					<?php esc_html_e( 'Use Your Address', 'checkout-wc' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	public function load_ajax_action() {
		( new SmartyStreetsAddressValidationAction( $this->settings_getter->get_setting( 'smartystreets_auth_id' ), $this->settings_getter->get_setting( 'smartystreets_auth_token' ) ) )->load();
	}

	public function run_on_plugin_activation() {
		SettingsManager::instance()->add_setting( 'enable_smartystreets_integration', 'no' );
	}
}

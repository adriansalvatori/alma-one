<?php

namespace Objectiv\Plugins\Checkout\Admin\Pages;

use Objectiv\Plugins\Checkout\Admin\TabNavigation;
use Objectiv\Plugins\Checkout\Admin\Pages\Traits\TabbedAdminPageTrait;
use Objectiv\Plugins\Checkout\Features\Pickup;
use Objectiv\Plugins\Checkout\Managers\PlanManager;
use Objectiv\Plugins\Checkout\Managers\SettingsManager;

/**
 * @link checkoutwc.com
 * @since 5.0.0
 * @package Objectiv\Plugins\Checkout\Admin\Pages
 * @author Clifton Griffin <clif@checkoutwc.com>
 */
class LocalPickup extends PageAbstract {
	use TabbedAdminPageTrait;

	protected $tab_navigation;

	public function __construct() {
		parent::__construct( cfw__( 'Local Pickup', 'checkout-wc' ), 'manage_options', 'local-pickup' );
	}

	public function init() {
		parent::init();

		$this->tab_navigation = new TabNavigation( 'Local Pickup', 'subpage' );

		$this->tab_navigation->add_tab( 'Settings', add_query_arg( array( 'subpage' => 'settings' ), $this->get_url() ) );
		$this->tab_navigation->add_tab( 'Manage Pickup Locations', add_query_arg( array(
			'post_type' => Pickup::get_post_type(),
		), admin_url( 'edit.php' ) ) );

		add_action( 'all_admin_notices', array( $this, 'output_post_type_editor_header' ) );

		/**
		 * Highlights Local Pickup submenu item when
		 * on the New Pickup Location admin page
		 */
		add_filter( 'submenu_file', array( $this, 'maybe_highlight_submenu_item' ) );

		/**
		 * Highlight parent menu
		 */
		add_filter( 'parent_file', array( $this, 'menu_highlight' ) );
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
		$this->output_form_open();
		?>
		<div class="space-y-6">
			<?php cfw_admin_page_section( 'Local Pickup', 'Control local pickup options.', $this->get_pickup_fields() ); ?>
		</div>
		<?php
		$this->output_form_close();
	}

	/**
	 * @param mixed $submenu_file
	 * @return mixed
	 */
	public function maybe_highlight_submenu_item( $submenu_file ) {
		global $post;

		$post_type = Pickup::get_post_type();

		if ( isset( $_GET['post_type'] ) && $_GET['post_type'] === $post_type ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return $this->get_slug();
		} elseif ( $post && $post->post_type === $post_type ) {
			return $this->get_slug();
		}

		return $submenu_file;
	}

	public function menu_highlight( $parent_file ) {
		global $plugin_page, $post_type;

		if ( Pickup::get_post_type() === $post_type ) {
			$plugin_page = PageAbstract::$parent_slug; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		return $parent_file;
	}

	/**
	 * The admin page wrap
	 *
	 * @since 1.0.0
	 */
	public function output_post_type_editor_header() {
		global $post;

		if ( isset( $_GET['post_type'] ) && Pickup::get_post_type() !== $_GET['post_type'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		} elseif ( isset( $post ) && Pickup::get_post_type() !== $post->post_type ) {
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
				 * @param LocalPickup $this The LocalPickup instance.
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

	function get_pickup_fields() {
		ob_start();

		if ( ! PlanManager::has_premium_plan() ) {
			$notice = $this->get_upgrade_required_notice( PlanManager::get_english_list_of_required_plans_html() );
		}

		$this->output_checkbox_row(
			'enable_pickup',
			cfw__( 'Enable Local Pickup', 'checkout-wc' ),
			cfw__( 'Provide customer with the option to choose their delivery method. Choosing pickup bypasses the shipping address.', 'checkout-wc' ),
			array(
				'enabled' => PlanManager::has_premium_plan(),
				'notice'  => $notice ?? '',
			)
		);

		$this->output_checkbox_row(
			'enable_pickup_ship_option',
			cfw__( 'Enable Shipping Option', 'checkout-wc' ),
			cfw__( 'If you only offer pickup, uncheck this to hide the shipping option.', 'checkout-wc' ),
			array(
				'nested' => true,
			)
		);

		$this->output_text_input_row(
			'pickup_ship_option_label',
			cfw__( 'Shipping Option Label', 'checkout-wc' ),
			cfw__( 'If left blank, this default will be used: ', 'checkout-wc' ) . cfw__( 'Ship', 'checkout-wc' ),
			array(
				'nested' => true,
			)
		);

		$this->output_text_input_row(
			'pickup_option_label',
			cfw__( 'Local Pickup Option Label', 'checkout-wc' ),
			cfw__( 'If left blank, this default will be used: ', 'checkout-wc' ) . cfw__( 'Pick up', 'checkout-wc' ),
			array(
				'nested' => true,
			)
		);

		$this->output_checkbox_group(
			'pickup_methods',
			cfw__( 'Local Pickup Shipping Methods', 'checkout-wc' ),
			cfw__( 'Choose which shipping methods are local pickup options. Only these options will be shown when Pickup is selected. These options will be hidden if Delivery is selected.', 'checkout-wc' ),
			$this->get_shipping_methods(),
			(array) SettingsManager::instance()->get_setting( 'pickup_methods' ),
			array(
				'nested' => true,
			)
		);

		$this->output_text_input_row(
			'pickup_shipping_method_other_label',
			cfw__( 'Other Shipping Method', 'checkout-wc' ),
			cfw__( 'Enter the name of your local pickup shipping method. If you have multiple options, or the name varies, check the box below to use regular expressions.', 'checkout-wc' ),
			array(
				'nested' => true,
			)
		);

		$this->output_checkbox_row(
			'enable_pickup_shipping_method_other_regex',
			cfw__( 'Enable Regex', 'checkout-wc' ),
			cfw__( 'Match local shipping method name with regex.', 'checkout-wc' ),
			array(
				'nested' => true,
			)
		);

		$this->output_checkbox_row(
			'enable_pickup_method_step',
			cfw__( 'Enable Pickup Step', 'checkout-wc' ),
			cfw__( 'When Pickup is selected, show the shipping method step. Can be useful when integrating with plugins that allow customers to choose a pickup time slot, etc.', 'checkout-wc' ),
			array(
				'nested' => true,
			)
		);

		$this->output_checkbox_row(
			'hide_pickup_methods',
			cfw__( 'Hide Pickup Methods', 'checkout-wc' ),
			cfw__( 'On the pickup step, hide the actual pickup methods. If you need the pickup step and only have one pickup method, you should use this option.', 'checkout-wc' ),
			array(
				'nested' => true,
			)
		);
		?>
		<div class="cfw-admin-field-container relative flex">
			<a href="<?php echo admin_url( 'edit.php?post_type=cfw_pickup_location' ); ?>" target="_blank" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
				<?php cfw_e( 'Edit Pickup Locations', 'checkout-wc' ); ?>
			</a>
		</div>
		<?php

		return ob_get_clean();
	}

	public function get_shipping_methods(): array {
		// Get all shipping methods
		$data_store = \WC_Data_Store::load( 'shipping-zone' );
		$raw_zones  = $data_store->get_zones();
		$zones      = array();
		$methods    = array();
		foreach ( $raw_zones as $raw_zone ) {
			$zones[] = new \WC_Shipping_Zone( $raw_zone );
		}
		$zones[] = new \WC_Shipping_Zone( 0 ); // ADD ZONE "0" MANUALLY

		foreach ( $zones as $zone ) {
			$zone_shipping_methods = $zone->get_shipping_methods();
			foreach ( $zone_shipping_methods as $method ) {
				$methods[ $method->get_rate_id() ] = $zone->get_zone_name() . ': ' . $method->get_title();
			}
		}

		$methods['other'] = cfw__( 'Other', 'checkout-wc' );

		return $methods;
	}
}






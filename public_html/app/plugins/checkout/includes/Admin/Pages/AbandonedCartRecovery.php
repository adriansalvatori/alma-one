<?php

namespace Objectiv\Plugins\Checkout\Admin\Pages;

use Objectiv\Plugins\Checkout\Admin\TabNavigation;
use Objectiv\Plugins\Checkout\Admin\Pages\Traits\TabbedAdminPageTrait;
use Objectiv\Plugins\Checkout\Managers\PlanManager;
use Objectiv\Plugins\Checkout\Managers\SettingsManager;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use function WordpressEnqueueChunksPlugin\registerScripts as cfwRegisterChunkedScripts;

/**
 * @link checkoutwc.com
 * @since 5.0.0
 * @package Objectiv\Plugins\Checkout\Admin\Pages
 */
class AbandonedCartRecovery extends PageAbstract {
	use TabbedAdminPageTrait;

	protected $tab_navigation;
	protected $acr_feature;

	public function __construct( \Objectiv\Plugins\Checkout\Features\AbandonedCartRecovery $acr_feature ) {
		parent::__construct( cfw__( 'Abandoned Cart Recovery', 'checkout-wc' ), 'manage_options', 'acr' );

		$this->acr_feature = $acr_feature;
	}

	public function init() {
		parent::init();

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 1001 );

		$this->tab_navigation = new TabNavigation( 'Abandoned Cart Recovery', 'subpage' );

		$this->tab_navigation->add_tab( 'Report', add_query_arg( array( 'subpage' => 'report' ), $this->get_url() ) );
		$this->tab_navigation->add_tab( 'Emails', add_query_arg( array(
			'post_type' => \Objectiv\Plugins\Checkout\Features\AbandonedCartRecovery::get_post_type()
		), admin_url( 'edit.php' ) ) );
		$this->tab_navigation->add_tab( 'Settings', add_query_arg( array( 'subpage' => 'settings' ), $this->get_url() ) );

		add_action( 'all_admin_notices', array( $this, 'output_post_type_editor_header' ) );

		/**
		 * Highlights ACR submenu item when appropriate
		 */
		add_filter( 'submenu_file', array( $this, 'maybe_highlight_acr_submenu_item' ) );

		/**
		 * Highlight parent menu
		 */
		add_filter( 'parent_file', array( $this, 'menu_highlight' ) );

		/**
		 * MBs
		 */
		add_action( 'edit_form_after_title', array( $this, 'add_email_subject_line_and_prehead' ) );
		add_action( 'edit_form_after_editor', array( $this, 'add_other_email_options' ) );
		add_filter( 'enter_title_here', array( $this, 'change_email_title_placeholder' ), 10, 2 );
		add_action( 'save_post', array( $this, 'save_custom_fields' ), 10, 2 );

		// Enable font size & font family selects in the editor
		add_filter( 'mce_buttons_2', function ( $buttons ) {
			// Only for our post type
			if ( \Objectiv\Plugins\Checkout\Features\AbandonedCartRecovery::get_post_type() !== get_post_type() ) {
				return $buttons;
			}

			array_unshift( $buttons, 'fontselect' ); // Add Font Select
			array_unshift( $buttons, 'fontsizeselect' ); // Add Font Size Select

			return $buttons;
		} );

		$post_type = \Objectiv\Plugins\Checkout\Features\AbandonedCartRecovery::get_post_type();

		add_filter(
			"manage_{$post_type}_posts_columns",
			function ( $columns ) {
				$date = array_pop( $columns );

				$columns['cfw_email_subject_col'] = cfw__( 'Subject', 'checkout-wc' );
				$columns['cfw_send_after_col']    = 'Send After' . wc_help_tip( 'Send this long after cart has been abandoned.' );
				$columns['cfw_email_active_col']  = 'Active' . wc_help_tip( 'Active (published) emails, are sent to customers. Inactive (draft / unpublished) emails are not.' );

				return $columns;
			}
		);

		add_action(
			"manage_{$post_type}_posts_custom_column",
			function ( $column, $post_id ) {
				if ( 'cfw_email_subject_col' === $column ) {
					echo esc_html( get_post_meta( $post_id, 'cfw_subject', true ) );
				}

				if ( 'cfw_send_after_col' === $column ) {
					$cfw_email_wait      = get_post_meta( $post_id, 'cfw_wait', true );
					$cfw_email_wait_unit = get_post_meta( $post_id, 'cfw_wait_unit', true );

					echo esc_html( $cfw_email_wait . ' ' . $cfw_email_wait_unit );
				}

				if ( 'cfw_email_active_col' === $column ) {
					$active = get_post_status( $post_id ) === 'publish' ? 'Active' : 'Inactive';

					echo esc_html( $active );
				}
			},
			10,
			2
		);

		add_action( 'admin_enqueue_scripts', array( $this, 'add_acr_localized_variables' ), 1010 );
		add_filter( 'mce_buttons', array( $this, 'add_mce_button' ) );
		add_filter( 'mce_external_plugins', array( $this, 'add_mce_plugin' ), 9 );

		// Send Preview Email
		add_action( 'wp_ajax_cfw_acr_preview_email_send', array( $this, 'send_preview_email' ) );
	}

	public function output() {
		if ( $this->get_current_tab() === false ) {
			$_GET['subpage'] = 'report';
		}

		if ( ! PlanManager::has_premium_plan() ) {
			$notice = $this->get_old_style_upgrade_required_notice( PlanManager::get_english_list_of_required_plans_html() );
		}

		if ( ! empty( $notice ) ) {
			echo $notice;
		}

		$current_tab_function = $this->get_current_tab() === false ? 'report_tab' : $this->get_current_tab() . '_tab';
		$callable             = array( $this, $current_tab_function );

		$this->tab_navigation->display_tabs();

		call_user_func( $callable );
	}

	public function report_tab() {
		?>
		<style>
			#cfw_admin_header_save_button {
				display: none;
			}
		</style>
		<div id="cfw-acr-reports"></div>
		<div id="cfw-acr-carts"></div>
		<?php
	}

	public function settings_tab() {
		$this->output_form_open();
		?>
		<div class="space-y-6">
			<?php
			cfw_admin_page_section(
				cfw__( 'Abandoned Cart Recovery', 'checkout-wc' ),
				cfw__( 'Control how CheckoutWC handles abandoned carts.', 'checkout-wc' ),
				$this->get_settings(),
				$this->get_cron_notice() . $this->get_sendwp_banner()
			);

			cfw_admin_page_section(
				cfw__( 'Email Sending', 'checkout-wc' ),
				cfw__( 'Configure email sending options.', 'checkout-wc' ),
				$this->get_email_sending_settings()
			);

			cfw_admin_page_section(
				cfw__( 'Advanced Options', 'checkout-wc' ),
				cfw__( 'Configure advanced options.', 'checkout-wc' ),
				$this->get_advanced_settings()
			);

			cfw_admin_page_section(
				cfw__( 'Danger Zone', 'checkout-wc' ),
				cfw__( 'Clear your cart data.', 'checkout-wc' ),
				$this->get_danger_zone_settings()
			);
			?>
		</div>
		<?php
		$this->output_form_close();
	}

	protected function get_settings() {
		ob_start();

		$this->output_checkbox_row(
			'enable_acr',
			cfw__( 'Enable Abandoned Cart Tracking', 'checkout-wc' ),
			cfw__( 'Enable Abandoned Cart Recovery feature.', 'checkout-wc' )
		);

		$this->output_text_input_row(
			'acr_abandoned_time',
			cfw__( 'Cart Is Abandoned After X Minutes', 'checkout-wc' ),
			cfw__( 'The number of minutes after which a cart is considered abandoned.', 'checkout-wc' )
		);

		return ob_get_clean();
	}

	protected function get_cron_notice() {
		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
			return '';
		}

		ob_start();
		?>
		<div class="bg-white shadow sm:rounded-lg mb-6">
			<div class="px-4 py-5 sm:p-6">
				<h3 class="text-base font-semibold leading-6 text-gray-900">
					<?php cfw_e( 'Error: WP Cron Configured Incorrectly!', 'checkout-wc' ); ?>
				</h3>
				<div class="mt-2 sm:flex sm:items-start sm:justify-between">
					<div class="max-w-xl text-sm text-gray-500">
						<p class="mb-2">
							<?php cfw_e( 'It looks like WP Cron is enabled which will cause issues with tracking carts and sending emails.', 'checkout-wc' ); ?>
						</p>
						<p class="mb-2">
							<?php cfw_e( 'To properly configure WP Cron for ACR, please read our guide:', 'checkout-wc' ); ?>
							<br/>
							<a class="text-blue-600 underline" target="_blank"
							   href="https://www.checkoutwc.com/documentation/how-to-ensure-your-wordpress-cron-is-working-properly/">Properly
								configure WordPress cron for Abandoned Cart Recovery</a>
						</p>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	protected function get_sendwp_banner() {
		if ( function_exists( 'sendwp_get_server_url' ) ) {
			//return '';
		}
		ob_start();
		?>
		<div class="bg-white shadow sm:rounded-lg mb-6">
			<div class="px-4 py-5 sm:p-6">
				<h3 class="text-base font-semibold leading-6 text-gray-900">
					<?php cfw_e( 'SendWP - Transactional Email', 'checkout-wc' ); ?>
				</h3>
				<div class="mt-2 sm:flex sm:items-start sm:justify-between">
					<div class="max-w-xl text-sm text-gray-500">
						<p class="mb-2">
							<?php cfw_e( 'SendWP makes getting emails delivered as simple as a few clicks. So you can relax know those important emails are being delivered on time.', 'checkout-wc' ); ?>
						</p>
						<p class="mb-2">
							<?php cfw_e( 'Try SendWP now and <strong>get your first month for just $1.</strong>', 'checkout-wc' ); ?>
						</p>
						<p>
							<a href="https://www.checkoutwc.com/documentation/abandoned-cart-recovery/" target="_blank"
							   class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
								Learn More
							</a>
						</p>
						<p class="mt-2">
							<em>
								<?php cfw_e( 'Note: SendWP is optional. You can use any transactional email service you prefer.' ); ?>
							</em>
						</p>
					</div>
					<div class="mt-5 sm:mt-0 sm:ml-6 sm:flex sm:flex-shrink-0 sm:items-center">
						<div class="text-center w-96">
							<div>
								<button type="button" id="cfw_sendwp_install_button"
										class="inline-flex items-center mb-2 px-6 py-3 border border-transparent text-lg shadow font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
									<?php cfw_e( 'Connect to SendWP', 'checkout-wc' ); ?>
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	protected function get_email_sending_settings() {
		ob_start();

		$this->output_text_input_row(
			'acr_from_name',
			cfw__( 'From Name', 'checkout-wc' ),
			cfw__( 'The name you wish Abandoned Cart Recovery emails to be sent from.', 'checkout-wc' )
		);

		$this->output_text_input_row(
			'acr_from_address',
			cfw__( 'From Address', 'checkout-wc' ),
			cfw__( 'The email address you wish Abandoned Cart Recovery emails to be sent from.', 'checkout-wc' )
		);

		$this->output_text_input_row(
			'acr_reply_to_address',
			cfw__( 'Reply-To Address', 'checkout-wc' ),
			cfw__( 'The email address you wish Abandoned Cart Recovery emails replies to be sent to.', 'checkout-wc' )
		);

		return ob_get_clean();
	}

	protected function get_advanced_settings() {
		ob_start();

		$this->output_checkbox_group(
			'acr_recovered_order_statuses',
			cfw__( 'Cart Recovered Order Statuses', 'checkout-wc' ),
			cfw__( 'Choose which Order Statuses indicate a successful order.', 'checkout-wc' ),
			wc_get_order_statuses(),
			(array) SettingsManager::instance()->get_setting( 'acr_recovered_order_statuses' )
		);

		$roles = array();

		foreach ( wp_roles()->roles as $role => $role_data ) {
			$roles[ $role ] = $role_data['name'];
		}

		$this->output_checkbox_group(
			'acr_excluded_roles',
			cfw__( 'Exclude From Abandoned Cart Recovery By Role', 'checkout-wc' ),
			cfw__( 'Check any user role that should be excluded from abandoned cart emails.', 'checkout-wc' ),
			$roles,
			(array) SettingsManager::instance()->get_setting( 'acr_excluded_roles' )
		);

		return ob_get_clean();
	}

	protected function get_danger_zone_settings() {
		ob_start();

		$url = add_query_arg(
			array(
				'page'                => 'cfw-settings-acr',
				'subpage'             => 'settings',
				'clear-all-acr-carts' => 'true',
				'nonce'               => wp_create_nonce( 'clear-all-acr-carts' ),
			),
			admin_url( 'admin.php' )
		);
		echo '<a id="cfw-delete-acr-carts" href="' . esc_url( $url ) . '" class="button button-secondary">' . cfw_esc_html__( 'Delete All Tracked Carts', 'checkout-wc' ) . '</a>';
		echo '<p>Note: This resets all ACR recovery statistics!</p>';

		return ob_get_clean();
	}

	/**
	 * The admin page wrap
	 *
	 * @since 1.0.0
	 */
	public function output_post_type_editor_header() {
		global $post;

		if ( isset( $_GET['post_type'] ) && \Objectiv\Plugins\Checkout\Features\AbandonedCartRecovery::get_post_type() !== $_GET['post_type'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		} elseif ( isset( $post ) && \Objectiv\Plugins\Checkout\Features\AbandonedCartRecovery::get_post_type() !== $post->post_type ) {
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
				 * @param AbandonedCartRecovery $this The AbandonedCartRecovery instance.
				 *
				 * @since 7.0.0
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
				 * @param AbandonedCartRecovery $this The AbandonedCartRecovery instance.
				 *
				 * @since 7.0.0
				 *
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

	public function is_current_page(): bool {
		global $post;

		if ( parent::is_current_page() ) {
			return true;
		}

		if ( isset( $_GET['post_type'] ) && \Objectiv\Plugins\Checkout\Features\AbandonedCartRecovery::get_post_type() === $_GET['post_type'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return true;
		}

		if ( $post && \Objectiv\Plugins\Checkout\Features\AbandonedCartRecovery::get_post_type() === $post->post_type ) {
			return true;
		}

		return false;
	}

	/**
	 * Hide 'Emails' post type from CFW settings submenu
	 *
	 * This keeps the submenu open when editing an email
	 *
	 * @return void
	 */
	public function setup_menu() {
		parent::setup_menu();

		global $submenu;

		$post_type_slug = \Objectiv\Plugins\Checkout\Features\AbandonedCartRecovery::get_post_type();

		if ( empty( $submenu[ self::$parent_slug ] ) ) {
			return;
		}

		foreach ( (array) $submenu[ self::$parent_slug ] as $i => $item ) {
			if ( 'edit.php?post_type=' . $post_type_slug === $item[2] ) {
				unset( $submenu[ self::$parent_slug ][ $i ] );
			}
		}
	}

	/**
	 * Maybe highlight the ACR submenu item if we're on the posts sreen or editing a post
	 *
	 * @param mixed $submenu_file
	 *
	 * @return mixed
	 */
	public function maybe_highlight_acr_submenu_item( $submenu_file ) {
		global $post;

		$post_type = \Objectiv\Plugins\Checkout\Features\AbandonedCartRecovery::get_post_type();

		if ( isset( $_GET['post_type'] ) && $_GET['post_type'] === $post_type ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return $this->get_slug();
		} elseif ( $post && $post->post_type === $post_type ) {
			return $this->get_slug();
		}

		return $submenu_file;
	}

	public function menu_highlight( $parent_file ) {
		global $plugin_page, $post_type;

		if ( \Objectiv\Plugins\Checkout\Features\AbandonedCartRecovery::get_post_type() === $post_type ) {
			$plugin_page = PageAbstract::$parent_slug; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		return $parent_file;
	}

	public function add_email_subject_line_and_prehead( $post ) {
		if ( \Objectiv\Plugins\Checkout\Features\AbandonedCartRecovery::get_post_type() !== $post->post_type ) {
			return;
		}

		$subject = get_post_meta( $post->ID, 'cfw_subject', true );

		?>
		<div>
			<div>
				<label id="cfw_email_subject-label"
					   for="cfw_email_subject"><?php cfw_e( 'Subject', 'checkout-wc' ); ?></label>
				<input type="text" placeholder="<?php cfw_e( 'Enter Email Subject', 'checkout-wc' ); ?>"
					   name="cfw_email_subject" size="30" value="<?php echo esc_attr( $subject ); ?>"
					   id="cfw_email_subject" spellcheck="true" autocomplete="off"
					   value="<?php echo esc_attr( $subject ); ?>">
			</div>
		</div>
		<?php

		$prehead = get_post_meta( $post->ID, 'cfw_preheader', true );

		?>
		<div>
			<div>
				<label id="cfw_email_preheader-label"
					   for="cfw_email_preheader"><?php cfw_e( 'Preview Text', 'checkout-wc' ); ?></label>
				<input type="text"
					   placeholder="<?php cfw_e( 'Shows in the email preview in the inbox before the content.', 'checkout-wc' ); ?>"
					   name="cfw_email_preheader" size="30" value="<?php echo esc_attr( $prehead ); ?>"
					   id="cfw_email_preheader" spellcheck="true" autocomplete="off"
					   value="<?php echo esc_attr( $prehead ); ?>">
			</div>
		</div>
		<?php
	}

	public function add_other_email_options( $post ) {
		if ( \Objectiv\Plugins\Checkout\Features\AbandonedCartRecovery::get_post_type() !== $post->post_type ) {
			return;
		}

		$wait      = get_post_meta( $post->ID, 'cfw_wait', true );
		$wait_unit = get_post_meta( $post->ID, 'cfw_wait_unit', true );

		if ( ! $wait_unit ) {
			$wait_unit = 'minutes';
		}

		if ( ! $wait ) {
			$wait = 5;
		}

		$email_address = wp_get_current_user()->user_email ?? get_option( 'admin_email' );

		$cfw_use_wc_template = get_post_meta( $post->ID, 'cfw_use_wc_template', true );
		?>
		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row" valign="top">
					<label id="cfw_email_wait-label"
						   for="cfw_email_wait"><?php cfw_e( 'Send After', 'checkout-wc' ); ?></label>
				</th>
				<td>
					<div class="cfw-admin-align-center">
						<input type="text" placeholder="<?php cfw_e( 'Send After', 'checkout-wc' ); ?>"
							   name="cfw_email_wait" size="30" value="<?php echo intval( $wait ); ?>"
							   id="cfw_email_wait" autocomplete="off" value="<?php echo esc_attr( $wait ); ?>">

						<select name="cfw_email_wait_unit" id="cfw_email_wait_unit">
							<option
								value="minutes" <?php selected( $wait_unit, 'minutes' ); ?>><?php cfw_e( 'Minutes', 'checkout-wc' ); ?></option>
							<option
								value="hours" <?php selected( $wait_unit, 'hours' ); ?>><?php cfw_e( 'Hours', 'checkout-wc' ); ?></option>
							<option
								value="days" <?php selected( $wait_unit, 'days' ); ?>><?php cfw_e( 'Days', 'checkout-wc' ); ?></option>
						</select>
					</div>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label id="cfw_use_wc_template-label"
						   for="cfw_use_wc_template"><?php cfw_e( 'Template', 'checkout-wc' ); ?></label>
				</th>
				<td>
					<label>
						<input type="hidden" name="cfw_use_wc_template" value="no"/>
						<input type="checkbox" name="cfw_use_wc_template" value="yes"
							   id="cfw_use_wc_template" <?php echo checked( $cfw_use_wc_template ); ?>>
						<?php cfw_e( 'Use WooCommerce Email Template', 'checkout-wc' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label id="cfw_send_preview-label"
						   for="cfw_send_preview"><?php cfw_e( 'Send Preview', 'checkout-wc' ); ?></label>
				</th>
				<td>
					<div class="cfw-admin-align-center">
						<input type="text" placeholder="<?php cfw_e( 'Send Preview', 'checkout-wc' ); ?>"
							   name="cfw_send_preview" size="30" value="<?php echo esc_attr( $email_address ); ?>"
							   id="cfw_send_preview" autocomplete="off">
						<button type="button" class="button button-secondary" name="cfw_send_preview_button"
								value="sendit"
								id="cfw_send_preview_button"><?php cfw_e( 'Send Preview', 'checkout-wc' ); ?></button>
					</div>
				</td>
			</tr>
			</tbody>
		</table>
		<?php
	}

	public function change_email_title_placeholder( $title, $post ) {
		if ( \Objectiv\Plugins\Checkout\Features\AbandonedCartRecovery::get_post_type() === $post->post_type ) {
			$title = cfw__( 'Add Email Title', 'checkout-wc' );
		}

		return $title;
	}

	public function save_custom_fields( $post_id, $post ) {
		if ( \Objectiv\Plugins\Checkout\Features\AbandonedCartRecovery::get_post_type() !== $post->post_type ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['cfw_email_subject'] ) ) {
			update_post_meta( $post->ID, 'cfw_subject', sanitize_text_field( $_POST['cfw_email_subject'] ) );
		}

		if ( isset( $_POST['cfw_email_preheader'] ) ) {
			update_post_meta( $post->ID, 'cfw_preheader', sanitize_text_field( $_POST['cfw_email_preheader'] ) );
		}

		if ( isset( $_POST['cfw_email_wait'] ) ) {
			update_post_meta( $post->ID, 'cfw_wait', intval( $_POST['cfw_email_wait'] ) );
		}

		if ( isset( $_POST['cfw_email_wait_unit'] ) ) {
			update_post_meta( $post->ID, 'cfw_wait_unit', sanitize_text_field( $_POST['cfw_email_wait_unit'] ) );
		}

		if ( isset( $_POST['cfw_use_wc_template'] ) ) {
			update_post_meta( $post->ID, 'cfw_use_wc_template', 'yes' === $_POST['cfw_use_wc_template'] );
		}

		// Get the number of seconds represented by cfw_email_wait and cfw_email_wait_unit
		$wait = intval( $_POST['cfw_email_wait'] );
		$unit = sanitize_text_field( $_POST['cfw_email_wait_unit'] );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$current_epoch = time();
		$plus_interval = strtotime( "+{$wait} {$unit}", $current_epoch );
		$seconds       = $plus_interval - $current_epoch;


		update_post_meta( $post->ID, 'cfw_acr_email_interval', $seconds );
	}

	public function add_acr_localized_variables() {
		$vars = array(
			'site_name'           => cfw__( 'Site Name', 'checkout-wc' ),
			'cart_products_table' => cfw__( 'Abandoned Cart Details Table', 'checkout-wc' ),
			'checkout_url'        => cfw__( 'Checkout URL', 'checkout-wc' ),
			'checkout_button'     => cfw__( 'Checkout Button', 'checkout-wc' ),
			'customer_firstname'  => cfw__( 'Customer First Name', 'checkout-wc' ),
			'customer_lastname'   => cfw__( 'Customer Last Name', 'checkout-wc' ),
			'customer_full_name'  => cfw__( 'Customer Full Name', 'checkout-wc' ),
			'cart_abandoned_date' => cfw__( 'Abandoned Date', 'checkout-wc' ),
			'site_url'            => cfw__( 'Site URL', 'checkout-wc' ),
			'unsubscribe_url'     => cfw__( 'Unsubscribe Link', 'checkout-wc' ),
		);

		wp_localize_script( 'cfw-admin', 'cfw_acr_replacement_codes', $vars );
		wp_localize_script( 'cfw-admin', 'cfw_acr_preview', array(
			'nonce' => wp_create_nonce( 'send_preview' ),
		) );
	}

	public function add_mce_button( $buttons ) {
		if ( get_post_type() !== \Objectiv\Plugins\Checkout\Features\AbandonedCartRecovery::get_post_type() ) {
			return $buttons;
		}

		$buttons[] = 'cfw_acr';

		return $buttons;
	}

	public function add_mce_plugin( $plugins ) {
		if ( ! $this->is_current_page() ) {
			return $plugins;
		}

		$front = trailingslashit( CFW_PATH_ASSETS );

		$plugins['cfw_acr'] = $front . 'js/mce.js';

		return $plugins;
	}

	public function enqueue_scripts() {
		if ( ! $this->is_current_page() ) {
			return;
		}
		cfwRegisterChunkedScripts( array( 'admin-acr-reports' ) );

		wp_enqueue_script( 'cfw-admin-acr-reports' );
	}

	/**
	 * @throws \Exception
	 */
	function send_preview_email() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( cfw__( 'Permission denied.', 'checkout-wc' ) );
		}

		if ( empty( $_REQUEST['email_id'] ) ) {
			wp_send_json_error( cfw__( 'Email ID is required.', 'checkout-wc' ) );
		}

		check_ajax_referer( 'send_preview', 'security' );

		$email_id = intval( $_REQUEST['email_id'] );
		$cart     = new \stdClass();

		if ( ! WC()->cart->get_cart_contents_count() ) {
			$args = array(
				'status'       => 'publish',
				'type'         => 'simple',
				'stock_status' => 'instock',
				'orderby'      => 'rand',
				'limit'        => 1,
			);

			$random_products = wc_get_products( $args );

			if ( ! empty( $random_products ) ) {
				$random_product = reset( $random_products );
				WC()->cart->add_to_cart( $random_product->id );
			}
		}

		$cart->cart         = wp_json_encode( WC()->cart->get_cart() );
		$cart->subtotal     = WC()->cart->get_subtotal();
		$cart->id           = 0;
		$cart->email        = sanitize_email( $_REQUEST['email_address'] ?? wp_get_current_user()->user_email );
		$cart->status       = 'abandoned';
		$cart->wp_user      = wp_get_current_user()->ID;
		$cart->created_unix = time();
		$cart->created      = date( 'Y-m-d H:i:s' ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
		$cart->first_name   = wp_get_current_user()->first_name;
		$cart->last_name    = wp_get_current_user()->last_name;
		$cart->emails_sent  = 0;
		$cart->fields       = '{}';
		$email              = get_post( $email_id );
		$subject            = get_post_meta( $email->ID, 'cfw_subject', true );
		$preheader          = get_post_meta( $email->ID, 'cfw_preheader', true );
		$use_wc_template    = get_post_meta( $email->ID, 'cfw_use_wc_template', true );
		$raw_content        = wpautop( $email->post_content );
		$content            = cfw_get_email_template( $subject, $preheader, $raw_content );
		$cssToInlineStyles  = new CssToInlineStyles();
		$content            = $this->acr_feature->process_replacements( $content, $cart, $email->ID );
		$content            = $cssToInlineStyles->convert( $content, cfw_get_email_stylesheet() );

		// Send email
		$from_name    = SettingsManager::instance()->get_setting( 'acr_from_name' );
		$from_address = SettingsManager::instance()->get_setting( 'acr_from_address' );
		$reply_to     = SettingsManager::instance()->get_setting( 'acr_reply_to_address' );

		$headers = array(
			'From: ' . $from_name . ' <' . $from_address . '>',
			'Reply-To: ' . $reply_to,
			'Content-Type: text/html; charset=UTF-8',
		);

		if ( $use_wc_template ) {
			$mailer = WC()->mailer();
			$body   = cfw_get_email_body( $preheader, $raw_content );
			$body   = $cssToInlineStyles->convert( $body, cfw_get_email_stylesheet() );
			$body   = $this->acr_feature->process_replacements( $body, $cart, $email->ID );
			$body   = cfw_wc_wrap_message( $subject, $body );
			$sent   = $mailer->send( $cart->email, $subject, $body, $headers );
		} else {
			$sent = wp_mail( $cart->email, $subject, $content, $headers );
		}

		if ( $sent ) {
			wp_send_json_success( $sent );

			return;
		}

		wp_send_json_error( cfw__( 'Failed to send preview email.', 'checkout-wc' ) );
	}
}

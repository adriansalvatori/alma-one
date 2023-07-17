<?php

namespace Objectiv\Plugins\Checkout\Admin\Pages;

use Objectiv\Plugins\Checkout\Admin\TabNavigation;
use Objectiv\Plugins\Checkout\Admin\Pages\Traits\TabbedAdminPageTrait;
use Objectiv\Plugins\Checkout\Managers\SettingsManager;
use Objectiv\Plugins\Checkout\Managers\UpdatesManager;
use WP_Admin_Bar;
use function WordpressEnqueueChunksPlugin\registerScripts as cfwRegisterChunkedScripts;

/**
 * Start Here admin page
 *
 * @link checkoutwc.com
 * @since 5.0.0
 * @package Objectiv\Plugins\Checkout\Admin\Pages
 */
class General extends PageAbstract {
	use TabbedAdminPageTrait;

	protected $appearance_page;

	public function __construct( Appearance $appearance_page ) {
		$this->appearance_page = $appearance_page;
		parent::__construct( cfw__( 'Start Here', 'checkout-wc' ), 'manage_options' );
	}

	public function init() {
		parent::init();

		//$this->tabbed_navigation = new TabNavigation( 'Start Here', 'subpage' );

		//$this->tabbed_navigation->add_tab( 'Getting Started', add_query_arg( array( 'subpage' => 'getting_started' ), $this->get_url() ), 'getting_started' );

		add_action( 'admin_bar_menu', array( $this, 'add_parent_node' ), 100 );
		add_action( 'admin_menu', array( $this, 'setup_main_menu_page' ), $this->priority - 5 );
	}

	public function setup_menu() {
		add_submenu_page( self::$parent_slug, $this->title, $this->title, $this->capability, $this->slug, null, $this->priority );
	}

	public function setup_main_menu_page() {
		add_menu_page( 'CheckoutWC', 'CheckoutWC', 'manage_options', self::$parent_slug, array( $this, 'output_with_wrap' ), 'data:image/svg+xml;base64,' . base64_encode( file_get_contents( CFW_PATH . '/build/images/cfw.svg' ) ) );
	}

	public function output() {
		if ( isset( $_GET['upgrade'] ) && '80' === $_GET['upgrade'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->output_upgrade_notice();
			return;
		}

		if ( $this->get_current_tab() === false ) {
			$_GET['subpage'] = 'getting_started';
		}
		?>
		<div class="max-w-3xl pb-8">
			<div>
				<p class="text-5xl font-bold text-gray-900">
					<?php cfw_e( 'Welcome to the new standard for WooCommerce checkouts.', 'checkout-wc' ); ?>
				</p>
				<p class="max-w-xl mt-5 text-2xl text-gray-500">
					<?php cfw_e( 'Higher conversions start here.', 'checkout-wc' ); ?>
				</p>
				<p class="mt-6">
					<a href="https://kb.checkoutwc.com" target="_blank" class="inline-flex items-center px-6 py-3 border border-transparent text-lg shadow font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
						<?php cfw_e( 'Documentation', 'checkout-wc' ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php

		//$this->tabbed_navigation->display_tabs();

		$current_tab_function = $this->get_current_tab() === false ? 'checkout_tab' : $this->get_current_tab() . '_tab';
		$callable             = array( $this, $current_tab_function );

		if ( is_callable( $callable ) ) {
			call_user_func( $callable );
		}
	}

	public function output_upgrade_notice() {
		?>
		<div class="max-w-5xl pb-8">
			<div>
				<p class="text-5xl font-bold text-gray-900">
					<?php cfw_e( 'Welcome to CheckoutWC 8.0', 'checkout-wc' ); ?>
				</p>
				<p class="max-w-xl mt-5 text-2xl text-gray-500">
					<?php cfw_e( 'CheckoutWC 8.0 is a major update with a brand new template, new features, and a new ways to customize your WooCommerce store.', 'checkout-wc' ); ?>
				</p>

				<p class="max-w-xl mt-5 text-2xl text-gray-500">
					<?php cfw_e( 'Just how big is 8.0? We checked in 392 separate commits on the way to releasing 8.0 - by far our most ambitious update since version 2.0! There’s a lot to explore.', 'checkout-wc' ); ?>
				</p>

				<div class="grid grid-cols-2 gap-8 mt-12">
					<div>
						<p class="text-4xl font-bold text-gray-900">
							<?php cfw_e( 'New Default Font', 'checkout-wc' ); ?>
						</p>
						<p class="max-w-xl mt-5 text-2xl text-gray-500">
							<?php cfw_e( 'We updated our default font to Inter. Inter is modern, dynamic, and highly readable - and it\'s open source! Want to try it out? Go to Appearance and change the body and heading fonts.', 'checkout-wc' ); ?>
						</p>
						<p class="mt-5">
							<a href="<?php echo esc_attr( add_query_arg( array('page' => 'cfw-settings-appearance', 'subpage' => 'design' ), admin_url( 'admin.php' ) ) ); ?>" target="_blank" class="inline-flex items-center px-6 py-3 border border-transparent text-lg shadow font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
								<?php cfw_e( 'Customize Fonts', 'checkout-wc' ); ?>
							</a>
						</p>
					</div>
					<div>
						<img src="https://www.checkoutwc.com/wp-content/uploads/2023/05/inter.png" alt="Inter Font" />
					</div>
				</div>

				<div class="grid grid-cols-2 gap-8 mt-12">
					<div>
						<img src="https://www.checkoutwc.com/wp-content/uploads/2023/04/screenshot.png" alt="Groove template" />
					</div>
					<div>
						<p class="text-4xl font-bold text-gray-900">
							<?php cfw_e( 'New Template', 'checkout-wc' ); ?>
						</p>
						<p class="max-w-xl mt-5 text-2xl text-gray-500">
							<?php cfw_e( "Groove is our newest checkout template. Inspired by Stripe Checkout, Groove is our first dynamic template - the side bar / cart summary colors are automatically adjusted based on the background color you set. This means it works equally well with dark and light color schemes.", 'checkout-wc' ); ?>
						</p>
						<p class="mt-5">
							<a href="<?php echo esc_attr( add_query_arg( array('page' => 'cfw-settings-appearance' ), admin_url( 'admin.php' ) ) ); ?>" target="_blank" class="inline-flex items-center px-6 py-3 border border-transparent text-lg shadow font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
								<?php cfw_e( 'Customize Template', 'checkout-wc' ); ?>
							</a>
						</p>
					</div>
				</div>

				<div class="grid grid-cols-2 gap-8 mt-12">
					<div>
						<p class="text-4xl font-bold text-gray-900">
							<?php cfw_e( 'After Checkout Submit Order Bumps', 'checkout-wc' ); ?>
						</p>
						<p class="max-w-xl mt-5 text-2xl text-gray-500">
							<?php cfw_e( 'You can now add Order Bumps that display after your customer clicks Complete Order. Perfect for offering last second upsells!', 'checkout-wc' ); ?>
						</p>
						<p class="mt-5">
							<a href="<?php echo esc_attr( add_query_arg( array('page' => 'cfw-settings-order_bumps' ), admin_url( 'admin.php' ) ) ); ?>" target="_blank" class="inline-flex items-center px-6 py-3 border border-transparent text-lg shadow font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
								<?php cfw_e( 'Configure Order Bumps', 'checkout-wc' ); ?>
							</a>
						</p>
					</div>
					<div>
						<img src="https://www.checkoutwc.com/wp-content/uploads/2023/05/340002881_1231624537716016_1927147220152555574_n.jpg" alt="After Checkout Submit Order Bump" />
					</div>
				</div>

				<div class="grid grid-cols-2 gap-8 mt-12">
					<div>
						<img src="https://www.checkoutwc.com/wp-content/uploads/2023/04/2023-01-18-14.19.24.gif" alt="Variable order bumps" />
					</div>
					<div>
						<p class="text-4xl font-bold text-gray-900">
							<?php cfw_e( 'New Bump Locations and Variable Order Bumps', 'checkout-wc' ); ?>
						</p>
						<p class="max-w-xl mt-5 text-2xl text-gray-500">
							<?php cfw_e( 'We have added lots of new Order Bump output locations, a new option for setting the maximum number of bumps that can appear at one time, as well as new Order Bump configuration options. You can target Order Bumps to appear only in the Side Cart as well as use variable products as your Order Bump offer product - customers can choose the variation they prefer in a modal window.', 'checkout-wc' ); ?>
						</p>
						<p class="mt-5">
							<a href="<?php echo esc_attr( add_query_arg( array('page' => 'cfw-settings-order_bumps' ), admin_url( 'admin.php' ) ) ); ?>" target="_blank" class="inline-flex items-center px-6 py-3 border border-transparent text-lg shadow font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
								<?php cfw_e( 'Configure Order Bumps', 'checkout-wc' ); ?>
							</a>
						</p>
					</div>
				</div>

				<div class="grid grid-cols-2 gap-8 mt-12">
					<div>
						<p class="text-4xl font-bold text-gray-900">
							<?php cfw_e( 'Order Bump Actions', 'checkout-wc' ); ?>
						</p>
						<p class="max-w-xl mt-5 text-2xl text-gray-500">
							<?php cfw_e( 'I know - another Order Bump feature! You can now define actions that occur when an Order Bump offer is accepted. Such as removing products from the cart, or applying free shipping to your order.', 'checkout-wc' ); ?>
						</p>
						<p class="mt-5">
							<a href="<?php echo esc_attr( add_query_arg( array('page' => 'cfw-settings-order_bumps' ), admin_url( 'admin.php' ) ) ); ?>" target="_blank" class="inline-flex items-center px-6 py-3 border border-transparent text-lg shadow font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
								<?php cfw_e( 'Configure Order Bumps', 'checkout-wc' ); ?>
							</a>
						</p>
					</div>
					<div>
						<img src="https://www.checkoutwc.com/wp-content/uploads/2023/04/Xnapper-2023-04-11-08.57.00-2048x650.png" alt="Order Bump actions" />
					</div>
				</div>

				<div class="grid grid-cols-2 gap-8 mt-12">
					<div>
						<img src="https://www.checkoutwc.com/wp-content/uploads/2023/05/Xnapper-2023-05-05-10.40.36.png" alt="Abandoned Cart Recovery reports" />
					</div>
					<div>
						<p class="text-4xl font-bold text-gray-900">
							<?php cfw_e( 'Abandoned Cart Recovery', 'checkout-wc' ); ?>
						</p>
						<p class="max-w-xl mt-5 text-2xl text-gray-500">
							<?php cfw_e( 'The biggest addition to 8.0 is the Abandoned Cart Recovery feature. Designed to help WooCommerce store owners recover lost sales, this feature sends customizable email reminders to customers who have abandoned their cart, offering them incentives like discounts or free shipping to encourage them to complete their purchase. Define your email sequence, view customer carts, and monitor your recovery rate through our reporting tools.', 'checkout-wc' ); ?>
						</p>
						<p class="mt-5">
							<a href="<?php echo esc_attr( add_query_arg( array('page' => 'cfw-settings-acr' ), admin_url( 'admin.php' ) ) ); ?>" target="_blank" class="inline-flex items-center px-6 py-3 border border-transparent text-lg shadow font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
								<?php cfw_e( 'Configure Abandoned Cart Recovery', 'checkout-wc' ); ?>
							</a>
						</p>
					</div>
				</div>

				<div class="grid grid-cols-2 gap-8 mt-12">
					<div>
						<p class="text-4xl font-bold text-gray-900">
							<?php cfw_e( 'Suggested Side Cart Products', 'checkout-wc' ); ?>
						</p>
						<p class="max-w-xl mt-5 text-2xl text-gray-500">
							<?php cfw_e( 'Suggested Products Carousel can be customized with cross-sells, upsells, or just random products. Boost your Average Order Value and help your customers find the right products for their purchase.', 'checkout-wc' ); ?>
						</p>
						<p class="mt-5">
							<a href="<?php echo esc_attr( add_query_arg( array('page' => 'cfw-settings-side-cart' ), admin_url( 'admin.php' ) ) ); ?>" target="_blank" class="inline-flex items-center px-6 py-3 border border-transparent text-lg shadow font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
								<?php cfw_e( 'Configure Side Cart', 'checkout-wc' ); ?>
							</a>
						</p>
					</div>
					<div>
						<img src="https://www.checkoutwc.com/wp-content/uploads/2023/04/330854616_1671429076661059_6856928533136526146_n.jpg" alt="Suggested Side Cart Products" />
					</div>
				</div>

				<div class="grid grid-cols-2 gap-8 mt-12">
					<div>
						<img src="https://www.checkoutwc.com/wp-content/uploads/2023/05/Xnapper-2023-05-05-10.43.34.png" alt="Store policies" />
					</div>
					<div>
						<p class="text-4xl font-bold text-gray-900">
							<?php cfw_e( 'Store Policies', 'checkout-wc' ); ?>
						</p>
						<p class="max-w-xl mt-5 text-2xl text-gray-500">
							<?php cfw_e( 'We have brought over a favorite feature from Shopify. Store Policies allows you to define a list of links to your policy pages that open in a modal window at checkout.', 'checkout-wc' ); ?> <a class="text-blue-400" href="https://www.youtube.com/watch?v=22d45gqbtEQ&embeds_euri=https%3A%2F%2Fwww.checkoutwc.com%2F&source_ve_path=OTY3MTQ&feature=emb_imp_woyt" target="_blank"><?php cfw_e( 'Here is a video of the feature in action.' ); ?></a>
						</p>
						<p class="mt-5">
							<a href="<?php echo esc_attr( add_query_arg( array('page' => 'cfw-settings-acr' ), admin_url( 'admin.php' ) ) ); ?>" target="_blank" class="inline-flex items-center px-6 py-3 border border-transparent text-lg shadow font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
								<?php cfw_e( 'Configure Abandoned Cart Recovery', 'checkout-wc' ); ?>
							</a>
						</p>
					</div>
				</div>

				<div class="grid grid-cols-2 gap-8 mt-12">
					<div>
						<p class="text-4xl font-bold text-gray-900">
							<?php cfw_e( 'Email Domain Autocomplete', 'checkout-wc' ); ?>
						</p>
						<p class="max-w-xl mt-5 text-2xl text-gray-500">
							<?php cfw_e( 'One of our favorite new features is Email Domain Autocomplete. As soon as a user starts typing their email address, we’ll start showing them common email service providers, such as gmail, yahoo, hotmail, etc.', 'checkout-wc' ); ?>
						</p>
					</div>
					<div>
						<video controls="" src="https://www.checkoutwc.com/wp-content/uploads/2023/04/ScreenFlow.mp4"></video>
					</div>
				</div>

				<div class="grid grid-cols-2 gap-8 mt-12">
					<div>
						<img src="https://www.checkoutwc.com/wp-content/uploads/2023/04/2023-01-18-14.18.37.gif" alt="Variation editing" />
					</div>
					<div>
						<p class="text-4xl font-bold text-gray-900">
							<?php cfw_e( 'Cart Variation Editing', 'checkout-wc' ); ?>
						</p>
						<p class="max-w-xl mt-5 text-2xl text-gray-500">
							<?php cfw_e( 'Customers can now edit variable products in the cart. Editable items have a new Edit link that opens up the product options in a modal window for them to adjust their selections. This will help keep customers at checkout and save them time - meaning more conversions. Can be enabled in the Side Cart, Checkout, or both.' ); ?>
						</p>
						<p class="mt-5">
							<a href="<?php echo esc_attr( add_query_arg( array('page' => 'cfw-settings-order_bumps' ), admin_url( 'admin.php' ) ) ); ?>" target="_blank" class="inline-flex items-center px-6 py-3 border border-transparent text-lg shadow font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
								<?php cfw_e( 'Configure Order Bumps', 'checkout-wc' ); ?>
							</a>
						</p>
					</div>
				</div>

				<div class="grid grid-cols-2 gap-8 mt-12">
					<div>
						<p class="text-4xl font-bold text-gray-900">
							<?php cfw_e( 'Under The Hood', 'checkout-wc' ); ?>
						</p>
						<p class="max-w-xl mt-5 text-2xl text-gray-500">
							<?php cfw_e( 'In addition to new features, we made lots of improvements to performance. Assets are now chunked more efficiently and we removed legacy scripts to reduce bandwidth. We also did a performance review of our frontend scripts and removed blocking JS calls such as setTimeout and switched animations to CSS transitions from JavaScript. CheckoutWC 8.0 is the fastest CheckoutWC yet.', 'checkout-wc' ); ?>
						</p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	public function getting_started_tab() {
		$this->output_form_open();
		?>
		<div class="space-y-8 mt-4">
			<?php
			cfw_admin_page_section(
				cfw__( '1. Activate Your License', 'checkout-wc' ),
				cfw__( 'Enter your license key. An active license is required for all functionality.', 'checkout-wc' ),
				$this->get_licensing_settings()
			);

			cfw_admin_page_section(
				cfw__( '2. Pick a Template', 'checkout-wc' ),
				cfw__( 'Pick from four different designs.', 'checkout-wc' ),
				$this->get_pick_template_content()
			);

			cfw_admin_page_section(
				cfw__( '3. Customize Logo and Colors', 'checkout-wc' ),
				cfw__( 'Review your logo and set your brand colors.', 'checkout-wc' ),
				$this->get_design_content()
			);

			cfw_admin_page_section(
				cfw__( '4. Review Your Checkout Page', 'checkout-wc' ),
				cfw__( 'Test your checkout page and make sure everything is working correctly.', 'checkout-wc' ),
				$this->get_preview_content()
			);

			cfw_admin_page_section(
				cfw__( '5. Go Live', 'checkout-wc' ),
				cfw__( 'Enable templates for all visitors.', 'checkout-wc' ),
				$this->get_activation_settings()
			);
			?>
		</div>
		<?php
		$this->output_form_close();

		if ( isset( $_GET['cfw_debug_settings'] ) ) {
			$all_settings = SettingsManager::instance()->get_settings_obj();

			echo '<div class="max-w-lg">';
			foreach ( $all_settings as $key => $value ) {
				echo '<h3 class="text-base font-bold mb-4">' . $key . '</h3>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<pre class="shadow-sm bg-white p-6 focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md mb-6">' . $value . '</pre>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '</div>';
		}
	}

	/**
	 * @param array $plugin_info
	 */
	public function recommended_plugin_card( array $plugin_info ) {
		?>
		<div class="plugin-card plugin-card-<?php echo esc_attr( $plugin_info['slug'] ); ?>">
			<div class="plugin-card-top">
				<div class="name column-name">
					<h3>
						<a target="_blank" href="<?php echo esc_attr( $plugin_info['url'] ); ?>">
							<?php echo esc_html( $plugin_info['name'] ); ?> <img src="<?php echo esc_attr( $plugin_info['image'] ); ?>" class="plugin-icon" alt="">
						</a>
					</h3>
				</div>
				<div class="action-links">
					<ul class="plugin-action-buttons">
						<li>
							<a class="button" target="_blank"  href="<?php echo esc_attr( $plugin_info['url'] ); ?>" role="button"><?php cfw_e( 'More Info' ); ?></a></li>
						</li>
					</ul>
				</div>
				<div class="desc column-description">
					<p><?php echo esc_html( $plugin_info['description'] ); ?></p>
					<p class="authors"> <cite><?php echo sprintf( cfw_esc_html__( 'By %s' ), esc_html( $plugin_info['author'] ) ); ?></cite></p>
				</div>
			</div>
		</div>
		<?php
	}

	public function get_activation_settings() {
		ob_start();

		$this->output_toggle_checkbox(
			'enable',
			cfw__( 'Activate CheckoutWC Templates', 'checkout-wc' ),
			cfw__( 'Requires a valid and active license key. CheckoutWC Templates are always activated for admin users, even without a valid license.', 'checkout-wc' )
		);

		return ob_get_clean();
	}

	public function get_licensing_settings() {
		ob_start();

		UpdatesManager::instance()->admin_page_fields();
		UpdatesManager::instance()->admin_page_activation_status_button();

		return ob_get_clean();
	}

	public function get_pick_template_content() {
		ob_start();
		?>
		<div class="flex flex-row items-center">
			<a href="<?php echo esc_attr( add_query_arg( array( 'subpage' => 'templates' ), $this->appearance_page->get_url() ) ); ?>" target="_blank" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
				<?php cfw_e( 'Choose a Template', 'checkout-wc' ); ?>
			</a>
			<svg xmlns="http://www.w3.org/2000/svg" class="ml-2 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-label="<?php cfw_e( 'Opens in new tab' ); ?>">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
			</svg>
		</div>
		<?php
		return ob_get_clean();
	}

	public function get_design_content() {
		ob_start();
		?>
		<div class="flex flex-row items-center">
			<a href="<?php echo esc_attr( $this->appearance_page->get_url() ); ?>" target="_blank" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
				<?php cfw_e( 'Customize Logo and Colors', 'checkout-wc' ); ?>
			</a>
			<svg xmlns="http://www.w3.org/2000/svg" class="ml-2 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-label="<?php cfw_e( 'Opens in new tab' ); ?>">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
			</svg>
		</div>
		<?php
		return ob_get_clean();
	}

	public function get_preview_content() {
		$url = wc_get_checkout_url();

		$products = wc_get_products(
			array(
				'limit'        => 1,
				'status'       => 'publish',
				'type'         => array( 'simple' ),
				'stock_status' => 'instock',
			)
		);

		if ( empty( $products ) ) {
			$products = wc_get_products(
				array(
					'parent_exclude' => 0,
					'limit'          => 1,
					'status'         => 'publish',
					'type'           => array( 'variable' ),
					'stock_status'   => 'instock',
				)
			);
		}

		// Get any simple or variable woocommerce product
		if ( ! empty( $products ) ) {
			$product = $products[0];

			$url = add_query_arg( array( 'add-to-cart' => $product->get_id() ), $url );
		}

		ob_start();
		?>
		<div class="flex flex-row items-center">
			<a href="<?php echo esc_attr( $url ); ?>" target="_blank" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
				<?php cfw_e( 'Preview Your Checkout Page', 'checkout-wc' ); ?>
			</a>
			<svg xmlns="http://www.w3.org/2000/svg" class="ml-2 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-label="<?php cfw_e( 'Opens in new tab' ); ?>">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
			</svg>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Add parent node
	 *
	 * @param WP_Admin_Bar $admin_bar
	 */
	public function add_parent_node( WP_Admin_Bar $admin_bar ) {
		if ( ! $this->can_show_admin_bar_button() ) {
			return;
		}

		if ( cfw_is_checkout() ) {
			// Remove irrelevant buttons
			$admin_bar->remove_node( 'new-content' );
			$admin_bar->remove_node( 'updates' );
			$admin_bar->remove_node( 'edit' );
			$admin_bar->remove_node( 'comments' );
		}

		$url = $this->get_url();

		$admin_bar->add_node(
			array(
				'id'     => self::$parent_slug,
				'title'  => '<span class="ab-icon dashicons dashicons-cart"></span>' . cfw__( 'CheckoutWC', 'checkout-wc' ),
				'href'   => $url,
				'parent' => false,
			)
		);
	}

	/**
	 * Add admin bar menu node
	 *
	 * @param WP_Admin_Bar $admin_bar
	 */
	public function add_admin_bar_menu_node( WP_Admin_Bar $admin_bar ) {
		if ( ! apply_filters( 'cfw_do_admin_bar', true ) ) {
			return;
		}

		$admin_bar->add_node(
			array(
				'id'     => $this->slug . '-general',
				'title'  => $this->title,
				'href'   => $this->get_url(),
				'parent' => self::$parent_slug,
			)
		);
	}
}

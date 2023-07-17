<?php
/**
 * Plugin Name: Bulky - Bulk Edit Products for WooCommerce
 * Plugin URI: https://villatheme.com/extensions/bulky-woocommerce-bulk-edit-products/
 * Description: Bulky - Bulk Edit Products for WooCommerce helps easily work with products in bulk. The plugin offers sufficient simple and advanced tools to help filter various available attributes of simple and variable products such as  ID, Title, Content, Excerpt, Slugs, SKU, Post date, range of regular price and sale price, Sale date, range of stock quantity, Product type, Categories.... Users can quickly search for wanted products fields and work with the product fields in bulk. The plugin promises to help users to save time and optimize manipulation when working with products in bulk.
 * Version: 1.1.4
 * Author: VillaTheme
 * Author URI: https://villatheme.com
 * Text Domain: bulky-bulk-edit-products-for-woo
 * Domain Path: /languages
 * Copyright 2021-2022 VillaTheme.com. All rights reserved.
 * Requires at least: 5.0
 * Tested up to: 6.2
 * WC requires at least: 5.0
 * WC tested up to: 7.7
 * Requires PHP: 7.0
 **/

use WCBEditor\Admin\Admin;
use WCBEditor\Admin\Ajax;
use WCBEditor\Admin\Editor;
use WCBEditor\Admin\History;
use WCBEditor\Includes\Data;
use WCBEditor\Includes\Enqueue;
use WCBEditor\Includes\Support;

defined( 'ABSPATH' ) || exit;

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

if ( ! is_plugin_active( 'bulky-woocommerce-bulk-edit-products/bulky-woocommerce-bulk-edit-products.php' ) ) {
	if ( is_file( plugin_dir_path( __FILE__ ) . 'autoload.php' ) ) {
		require_once plugin_dir_path( __FILE__ ) . 'autoload.php';
	}

	class  WooCommerce_Products_Bulk_Editor_F {
		public $plugin_name = 'Bulky - Bulk Edit Products for WooCommerce';

		public $version = '1.1.4';

		public $conditional = '';

		protected static $instance = null;

		public function __construct() {
			$this->define();

			add_action( 'plugins_loaded', [ $this, 'init' ] );
			register_activation_hook( __FILE__, [ $this, 'active' ] );
		}

		public static function instance() {
			return self::$instance == null ? self::$instance = new self() : self::$instance;
		}

		public function define() {
			define( 'WCBE_CONST_F', [
				'version'      => $this->version,
				'slug'         => 'bulky-bulk-edit-products-for-woo',
				'assets_slug'  => 'bulky-bulk-edit-products-for-woo-',
				'file'         => __FILE__,
				'basename'     => plugin_basename( __FILE__ ),
				'plugin_dir'   => plugin_dir_path( __FILE__ ),
				'includes_dir' => plugin_dir_path( __FILE__ ) . 'includes' . DIRECTORY_SEPARATOR,
				'admin_dir'    => plugin_dir_path( __FILE__ ) . 'admin' . DIRECTORY_SEPARATOR,
				'dist_dir'     => plugin_dir_path( __FILE__ ) . 'assets' . DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR,
				'dist_url'     => plugins_url( 'assets/dist/', __FILE__ ),
				'libs_url'     => plugins_url( 'assets/libs/', __FILE__ ),
				'img_url'      => plugins_url( 'assets/img/', __FILE__ ),
				'capability'   => 'manage_woocommerce',
				'pro_url'      => 'https://1.envato.market/vn4ZEA',
			] );
		}

		public function init() {
			if ( ! class_exists( 'VillaTheme_Require_Environment' ) ) {
				include_once WCBE_CONST_F['plugin_dir'] . 'support/support.php';
			}

			$environment = new \VillaTheme_Require_Environment( [
					'plugin_name'     => $this->plugin_name,
					'php_version'     => '7.0',
					'wp_version'      => '5.0',
					'wc_version'      => '5.0',
					'require_plugins' => [
						[
							'slug' => 'woocommerce',
							'name' => 'WooCommerce',
						],
					]
				]
			);

			if ( $environment->has_error() ) {
				return;
			}

			$this->load_class();
			add_action( 'init', [ $this, 'load_text_domain' ] );
			add_action( 'before_woocommerce_init', [ $this, 'custom_order_tables_declare_compatibility' ] );
			add_filter( 'plugin_action_links_' . WCBE_CONST_F['basename'], [ $this, 'setting_link' ] );
		}

		public function setting_link( $links ) {
			$editor_link = [ sprintf( "<a href='%1s' >%2s</a>", esc_url( admin_url( 'admin.php?page=vi_wbe_bulk_editor' ) ), esc_html__( 'Editor', 'bulky-bulk-edit-products-for-woo' ) ) ];

			return array_merge( $editor_link, $links );
		}

		public function load_class() {

			if ( ! function_exists( 'WCBEdit_Data' ) ) {
				function WCBEdit_Data() {
					return Data::instance();
				}
			}

			History::instance();

			if ( is_admin() ) {
				Enqueue::instance();
				Admin::instance();
				Editor::instance();
				Support::instance();
				Ajax::instance();
			}
		}

		public function load_text_domain() {
			$locale = determine_locale();
			$locale = apply_filters( 'plugin_locale', $locale, 'bulky-bulk-edit-products-for-woo' );

			unload_textdomain( 'bulky-bulk-edit-products-for-woo' );
			load_textdomain( 'bulky-bulk-edit-products-for-woo', WP_LANG_DIR . '/bulky-bulk-edit-products-for-woo/bulky-bulk-edit-products-for-woo-' . $locale . '.mo' );
			load_plugin_textdomain( 'bulky-bulk-edit-products-for-woo', false, plugin_basename( dirname( WCBE_CONST_F['file'] ) ) . '/languages' );
		}

		public function active( $network_wide ) {
			global $wpdb;
			$history = History::instance();
			if ( function_exists( 'is_multisite' ) && is_multisite() && $network_wide ) {
				$current_blog = $wpdb->blogid;
				$blogs        = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );
				foreach ( $blogs as $blog ) {
					switch_to_blog( $blog );
					$history->create_database_table();
				}
				switch_to_blog( $current_blog );
			} else {
				$history->create_database_table();
			}
		}

		public function custom_order_tables_declare_compatibility() {
			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			}
		}

	}

	WooCommerce_Products_Bulk_Editor_F::instance();
}


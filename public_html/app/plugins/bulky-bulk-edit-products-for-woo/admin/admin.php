<?php

namespace WCBEditor\Admin;

defined( 'ABSPATH' ) || exit;

class Admin {

	protected static $instance = null;

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
	}

	public static function instance() {
		return self::$instance == null ? self::$instance = new self() : self::$instance;
	}

	public function admin_menu() {
		add_menu_page(
			esc_html__( 'WooCommerce Bulk Editor', 'bulky-bulk-edit-products-for-woo' ),
			esc_html__( 'Bulky', 'bulky-bulk-edit-products-for-woo' ),
			WCBE_CONST_F['capability'],
			'vi_wbe_bulk_editor',
			'',
			'dashicons-media-spreadsheet',
			40
		);

		add_submenu_page( 'vi_wbe_bulk_editor',
			esc_html__( 'Products Bulk Editor', 'bulky-bulk-edit-products-for-woo' ),
			esc_html__( 'Products Bulk Editor', 'bulky-bulk-edit-products-for-woo' ),
			WCBE_CONST_F['capability'],
			'vi_wbe_bulk_editor',
			[ Editor::instance(), 'editor' ]
		);
	}
}
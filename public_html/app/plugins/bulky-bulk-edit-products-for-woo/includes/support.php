<?php

namespace WCBEditor\Includes;

defined( 'ABSPATH' ) || exit;

class Support {

	protected static $instance = null;

	public function __construct() {
		$this->support();
		add_action( 'vi_wbe_admin_field_auto_update_key', [ $this, 'auto_update_key' ] );
	}

	public static function instance() {
		return self::$instance == null ? self::$instance = new self() : self::$instance;
	}

	public function support() {
		if ( ! class_exists( 'VillaTheme_Support' ) ) {
			include_once WCBE_CONST_F['plugin_dir'] . 'support/support.php';
		}

		new \VillaTheme_Support(
			array(
				'support'    => 'https://wordpress.org/support/plugin/bulky-bulk-edit-products-for-woo',
				'docs'       => 'http://docs.villatheme.com/?item=bulky-bulk-edit-products-for-woo',
				'review'     => 'https://wordpress.org/support/plugin/bulky-bulk-edit-products-for-woo/reviews/?rate=5#rate-response',
				'css'        => WCBE_CONST_F['dist_url'],
				'image'      => WCBE_CONST_F['img_url'],
				'slug'       => WCBE_CONST_F['slug'],
				'menu_slug'  => 'vi_wbe_bulk_editor',
				'version'    => WCBE_CONST_F['version'],
				'pro_url'    => WCBE_CONST_F['pro_url'],
				'survey_url' => 'https://script.google.com/macros/s/AKfycbwofdw-o9mzaa4JNKu6d3SwvFsI1Rigpr5p90JwzfVJYSmVFy6hips6eB5SnHJnz4Et/exec'
			)
		);
	}

	public function auto_update_key() {
		?>
        <table class="form-table">
            <tr>
                <th>
					<?php esc_html_e( 'Auto update key', 'bulky-bulk-edit-products-for-woo' ); ?>
                </th>
                <td>
					<?php self::get_pro_version(); ?>
                </td>
            </tr>
        </table>
		<?php
	}

	public static function get_pro_version() {
		printf( '<a class="vi-ui button tiny" href="%s" target="_blank">%s</a>',
			esc_url( WCBE_CONST_F['pro_url'] ), esc_html__( 'Pro version', 'bulky-bulk-edit-products-for-woo' ) );
	}

}
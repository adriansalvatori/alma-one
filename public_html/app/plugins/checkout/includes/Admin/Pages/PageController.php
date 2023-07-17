<?php

namespace Objectiv\Plugins\Checkout\Admin\Pages;

use Objectiv\Plugins\Checkout\Managers\SettingsManager;
use function WordpressEnqueueChunksPlugin\registerScripts as cfwRegisterChunkedScripts;
use function WordpressEnqueueChunksPlugin\get as cfwChunkedScriptsConfigGet;

class PageController {
	protected $pages = array();

	public function __construct( PageAbstract ...$pages ) {
		$this->pages = $pages;
	}

	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 1000 );

		$this->maybe_add_body_class();

		foreach ( $this->pages as $page ) {
			$page->init();
		}
	}

	public function is_cfw_admin_page(): bool {
		foreach ( $this->pages as $page ) {
			if ( $page->is_current_page() ) {
				return true;
			}
		}

		return false;
	}

	public function enqueue_scripts() {
		if ( ! $this->is_cfw_admin_page() ) {
			return;
		}

		$front    = trailingslashit( CFW_PATH_ASSETS );
		$manifest = cfwChunkedScriptsConfigGet( 'manifest' );

		// PHP 8.1+ Fix
		foreach ( $manifest['chunks'] as $chunk_name => $chunk ) {
			add_filter(
				"wpecp/register/{$chunk_name}",
				function ( $args ) use ( $chunk_name ) {
					if ( ! in_array( $chunk_name, array( 'admin', 'admin-acr-reports', 'admin-settings' ), true ) ) {
						return $args;
					}

					array_push( $args['deps'], 'wp-color-picker', 'wc-enhanced-select', 'jquery-blockui', 'wp-api' );

					return $args;
				}
			);
		}

		cfwRegisterChunkedScripts( array( 'admin' ) );

		wp_enqueue_code_editor( array( 'type' => 'text/html' ) );
		wp_enqueue_script( 'cfw-admin' );

		if ( isset( $manifest['chunks']['admin-styles']['file'] ) ) {
			wp_enqueue_style( 'objectiv-cfw-admin-styles', "{$front}/{$manifest['chunks']['admin-styles']['file']}", array(), $manifest['chunks']['admin-styles']['hash'] );
		}

		wp_enqueue_style( 'woocommerce_admin_styles' );

		$settings_array = array(
			'logo_attachment_id' => SettingsManager::instance()->get_setting( 'logo_attachment_id' ),
			'i18n_nav_warning'   => cfw__( 'The changes you made will be lost if you navigate away from this page.', 'woocommerce' ),
			'ajax_url'           => admin_url( 'admin-ajax.php' ),
			'nonce'              => wp_create_nonce( 'objectiv-cfw-admin-save' ),
		);
		wp_localize_script( 'cfw-admin', 'objectiv_cfw_admin', $settings_array );
	}

	protected function maybe_add_body_class() {
		if ( ! $this->is_cfw_admin_page() ) {
			return;
		}

		add_filter(
			'admin_body_class',
			function( $classes ) {
				return $classes . ' cfw-admin-page';
			},
			10000
		);
	}
}

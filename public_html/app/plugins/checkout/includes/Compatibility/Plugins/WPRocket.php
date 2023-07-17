<?php

namespace Objectiv\Plugins\Checkout\Compatibility\Plugins;

use Objectiv\Plugins\Checkout\Managers\SettingsManager;

class WPRocket {
	public function init() {
		add_action( SettingsManager::instance()->prefix . '_settings_saved', array( $this, 'maybe_delete_cache_empty_cart' ), 10, 1 );
		add_action( 'cfw_before_plugin_data_upgrades', array( $this, 'delete_cache_empty_cart' ) );
	}

	public function maybe_delete_cache_empty_cart( array $new_settings ) {
		if ( ! defined( 'WP_ROCKET_VERSION' ) ) {
			return;
		}

		if ( ! isset( $new_settings['enable_side_cart'] ) ) {
			return;
		}

		$this->delete_cache_empty_cart();
	}

	/** Copied from wp-rocket/inc/ThirdParty/Plugins/Ecommerce/WooCommerceSubscriber.php */
	public function delete_cache_empty_cart() {
		if ( ! defined( 'WP_ROCKET_VERSION' ) ) {
			return;
		}

		$langs = get_rocket_i18n_code();

		if ( $langs ) {
			foreach ( $langs as $lang ) {
				delete_transient( 'rocket_get_refreshed_fragments_cache_' . $lang );
			}
		}

		delete_transient( 'rocket_get_refreshed_fragments_cache' );
	}
}

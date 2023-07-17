<?php

namespace Objectiv\Plugins\Checkout\Admin\Pages\Traits;

trait TabbedAdminPageTrait {
	protected $tabbed_navigation;

	public function get_current_tab() {
		return empty( $_GET['subpage'] ) ? false : sanitize_text_field( $_GET['subpage'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
}
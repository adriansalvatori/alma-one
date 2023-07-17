<?php

namespace Objectiv\Plugins\Checkout\Admin\Pages;

use Objectiv\Plugins\Checkout\Features\Pickup;

/**
 * @link checkoutwc.com
 * @since 7.3.0
 * @package Objectiv\Plugins\Checkout\Admin\Pages
 * @author Clifton Griffin <clif@checkoutwc.com>
 */
class PickupLocations extends PageAbstract {
	protected $post_type_slug;
	protected $nonce_field  = '_cfw_pl_nonce';
	protected $nonce_action = 'cfw_save_pl_mb';
	protected $formatted_required_plans_list;
	protected $is_available;

	public function __construct( string $post_type_slug, bool $is_available ) {
		parent::__construct( cfw__( 'Pickup Locations', 'checkout-wc' ), 'manage_options', null );

		$this->post_type_slug = $post_type_slug;
		$this->slug           = 'edit.php?post_type=' . $this->post_type_slug;
		$this->is_available   = $is_available;
	}

	public function init() {
		parent::init();

		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_metaboxes' ) );
		add_action( 'all_admin_notices', array( $this, 'output_with_wrap' ) );
		add_action( 'all_admin_notices', array( $this, 'maybe_show_license_upgrade_splash' ) );

		/**
		 * Highlights Pickup Locations submenu item when
		 * on the New Pickup Locations admin page
		 */
		add_filter( 'submenu_file', array( $this, 'maybe_highlight_pickup_locations_submenu_item' ) );
	}

	public function get_url(): string {
		$url = admin_url( $this->slug );

		return esc_url( $url );
	}

	public function setup_menu() {
		global $submenu;

		$stash_menu_item = null;

		if ( empty( $submenu[ self::$parent_slug ] ) ) {
			return;
		}

		foreach ( (array) $submenu[ self::$parent_slug ] as $i => $item ) {
			if ( $this->slug === $item[2] ) {
				$stash_menu_item = $submenu[ self::$parent_slug ][ $i ];
				unset( $submenu[ self::$parent_slug ][ $i ] );
			}
		}

		if ( empty( $stash_menu_item ) ) {
			return;
		}

		$submenu[ self::$parent_slug ][ $this->priority ] = $stash_menu_item;
	}

	public function register_meta_boxes() {
		add_meta_box( 'cfw_order_bump_products_mb', cfw__( 'Pickup Location Details', 'checkout-wc' ), array( $this, 'render_meta_box' ), $this->post_type_slug );
	}

	/**
	 * @param \WP_Post $post
	 */
	public function render_meta_box( \WP_Post $post ) {
		$cfw_pl_address        = get_post_meta( $post->ID, 'cfw_pl_address', true );
		$cfw_pl_instructions   = get_post_meta( $post->ID, 'cfw_pl_instructions', true );
		$cfw_pl_estimated_time = get_post_meta( $post->ID, 'cfw_pl_estimated_time', true );

		wp_nonce_field( $this->nonce_action, $this->nonce_field );
		?>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row" valign="top">
						<label for="cfw_pl_address">
							<?php cfw_e( 'Address', 'checkout-wc' ); ?>
						</label>
					</th>
					<td>
						<?php
						wp_editor(
							$cfw_pl_address,
							sanitize_title_with_dashes( 'cfw_pl_address' ),
							array(
								'textarea_rows' => 4,
								'quicktags'     => false,
								'media_buttons' => false,
								'textarea_name' => 'cfw_pl_address',
								'tinymce'       => false,
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top">
						<label for="cfw_pl_estimated_time">
							<?php cfw_e( 'Estimated Pickup Time', 'checkout-wc' ); ?>
						</label>
					</th>
					<td>
						<select style="width: 50%;" id="cfw_pl_estimated_time" name="cfw_pl_estimated_time">
							<?php foreach ( Pickup::get_pickup_times() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $cfw_pl_estimated_time, true ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top">
						<label for="cfw_pl_instructions">
							<?php cfw_e( 'Pickup Instructions', 'checkout-wc' ); ?>
						</label>
					</th>
					<td>
						<?php
						wp_editor(
							$cfw_pl_instructions,
							sanitize_title_with_dashes( 'cfw_pl_instructions' ),
							array(
								'textarea_rows' => 4,
								'quicktags'     => false,
								'media_buttons' => false,
								'textarea_name' => 'cfw_pl_instructions',
								'tinymce'       => false,
							)
						);
						?>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * @param int $post_id
	 */
	public function save_metaboxes( int $post_id ) {
		$nonce_name = $_POST[ $this->nonce_field ] ?? '';

		if ( ! wp_verify_nonce( $nonce_name, $this->nonce_action ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, 'cfw_pl_address', $_POST['cfw_pl_address'] );
		update_post_meta( $post_id, 'cfw_pl_instructions', $_POST['cfw_pl_instructions'] );
		update_post_meta( $post_id, 'cfw_pl_estimated_time', $_POST['cfw_pl_estimated_time'] );
	}

	public function is_current_page(): bool {
		global $post;

		if ( isset( $_GET['post_type'] ) && $this->post_type_slug === $_GET['post_type'] ) {
			return true;
		}

		if ( $post && $this->post_type_slug === $post->post_type ) {
			return true;
		}

		return false;
	}

	/**
	 * The admin page wrap
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function output_with_wrap() {
		if ( ! $this->is_current_page() ) {
			return;
		}
		?>
		<div class="cfw-tw">
			<div id="cfw_admin_page_header" class="absolute left-0 right-0 top-0 divide-y shadow z-50">
				<?php do_action( 'cfw_before_admin_page_header', $this ); ?>
				<div class="min-h-[64px] bg-white flex items-center pl-8">
					<span>
						<?php echo file_get_contents( CFW_PATH . '/build/images/cfw.svg' ); ?>
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
									<svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
										<path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
									</svg>
									<span class="ml-2 text-sm font-medium text-gray-500" aria-current="page">
										<?php echo $this->title; ?>
									</span>
								</div>
							</li>
						</ol>
					</nav>
				</div>
				<?php do_action( 'cfw_after_admin_page_header', $this ); ?>
			</div>
		</div>
		<?php
	}

	public function maybe_show_license_upgrade_splash() {
		if ( $this->is_current_page() && ! $this->is_available ) {
			echo $this->get_old_style_upgrade_required_notice( $this->formatted_required_plans_list );
		}
	}

	/**
	 * @param mixed $submenu_file
	 * @return mixed
	 */
	public function maybe_highlight_pickup_locations_submenu_item( $submenu_file ) {
		$post_type = $this->post_type_slug;

		if ( stripos( $_SERVER['REQUEST_URI'], "post-new.php?post_type=$post_type" ) !== false ) {
			return $this->get_slug();
		}

		return $submenu_file;
	}

	public function output() {}
}

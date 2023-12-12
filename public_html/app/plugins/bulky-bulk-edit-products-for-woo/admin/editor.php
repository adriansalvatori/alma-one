<?php

namespace WCBEditor\Admin;

use WCBEditor\Includes\Support;

defined( 'ABSPATH' ) || exit;

class Editor {

	protected static $instance = null;
	protected $filter_saved;
	protected $allow_html_tags;

	public function __construct() {
		add_action( 'admin_notices', [ $this, 'remove_notice' ], 1 );
		add_filter( 'admin_body_class', [ $this, 'full_screen_option' ] );
	}

	public static function instance() {
		return self::$instance == null ? self::$instance = new self() : self::$instance;
	}

	public function remove_notice() {
		if ( isset( $_GET['page'] ) && $_GET['page'] === 'vi_wbe_bulk_editor' ) {
			remove_all_actions( 'admin_notices' );
		}
	}

	public function full_screen_option( $body_class ) {
		$full_screen = get_option( 'vi_wbe_full_screen_option' ) ? ' vi-wbe-full-screen ' : '';

		return $body_class . $full_screen;
	}

	public function editor() {
		$user_id = get_current_user_id();
		delete_transient( "vi_wbe_filter_data_{$user_id}" );

		$columns            = WCBEdit_Data()->get_culumns_type_title();
		$this->filter_saved = get_transient( "vi_wbe_filter_data_{$user_id}" );

		$roles = [];
		foreach ( wp_roles()->roles as $role_name => $role_obj ) {
			if ( ! empty( $role_obj['capabilities']['edit_posts'] ) ) {
				$roles[] = $role_name;
			}
		}
		$users = get_users( [ 'role__in' => $roles, 'fields' => [ 'ID', 'display_name' ] ] );

		$users_options = [ '' => esc_html__( 'Author', 'bulky-bulk-edit-products-for-woo' ) ];
		if ( ! empty( $users ) && is_array( $users ) ) {
			foreach ( $users as $user ) {
				$users_options[ $user->ID ] = $user->display_name;
			}
		}

		$full_screen_icon  = get_option( 'vi_wbe_full_screen_option' ) ? 'window close outline' : 'external alternate';
		$full_screen_title = get_option( 'vi_wbe_full_screen_option' ) ? esc_html__( 'Exit full screen', 'bulky-woocommerce-bulk-edit-products' ) : esc_html__( 'Full screen', 'bulky-woocommerce-bulk-edit-products' );

		add_filter( 'bulky_filter_behaviors_list', [ $this, 'add_sku_behavior' ], 10, 2 );

		?>

        <div id="vi-wbe-container">
            <div id="vi-wbe-wrapper">

                <div id="vi-wbe-menu-bar">
                    <div class="vi-ui menu">

                        <a class="item vi-wbe-open-sidebar" data-menu_tab="filter" title="<?php esc_html_e( 'Filter', 'bulky-bulk-edit-products-for-woo' ); ?>">
                            <i class="filter icon"> </i>
                        </a>

                        <a class="item vi-wbe-open-sidebar" data-menu_tab="settings" title="<?php esc_html_e( 'Settings', 'bulky-bulk-edit-products-for-woo' ); ?>">
                            <i class="cog icon"> </i> <!--sliders horizontal-->
                        </a>

                        <a class="item vi-wbe-open-sidebar" data-menu_tab="meta_field" title="<?php esc_html_e( 'Meta fields', 'bulky-bulk-edit-products-for-woo' ); ?>">
                            <i class="server icon"> </i>
                        </a>
                        <a class="item vi-wbe-open-sidebar" data-menu_tab="history" title="<?php esc_html_e( 'History', 'bulky-bulk-edit-products-for-woo' ); ?>">
                            <i class="history icon"> </i>
                        </a>

                        <a class="item vi-wbe-new-products" title="<?php esc_html_e( 'Add new', 'bulky-bulk-edit-products-for-woo' ); ?>">
                            <i class="plus icon"> </i>
                        </a>
                        <a class="item vi-wbe-save-button" title="<?php esc_html_e( 'Save', 'bulky-bulk-edit-products-for-woo' ); ?>">
                            <i class="save icon"> </i>
                        </a>

                        <a class="item vi-wbe-get-product" title="<?php esc_html_e( 'Reload this page', 'bulky-bulk-edit-products-for-woo' ); ?>">
                            <i class="sync alternate icon"> </i>
                        </a>

                        <a class="item vi-wbe-full-screen-btn" title="<?php echo esc_html( $full_screen_title ) ?>">
                            <i class="<?php echo esc_attr( $full_screen_icon ) ?> icon"> </i>
                        </a>

                        <div class="vi-wbe-menu-bar-center">

                        </div>

                        <div class="vi-wbe-pagination">
                        </div>
                    </div>
                </div>


                <div id="vi-wbe-sidebar" class="vi-ui form small">
                    <div class="vi-wbe-sidebar-wrapper">
                        <span class="vi-wbe-close-sidebar"><i class="dashicons dashicons-no-alt"></i></span>
                        <div class="vi-wbe-sidebar-inner">

                            <div class="vi-ui top attached tabular menu">
                                <a class="active item" data-tab="filter"><?php esc_html_e( 'Filter', 'bulky-bulk-edit-products-for-woo' ); ?></a>
                                <a class="item" data-tab="settings"><?php esc_html_e( 'Settings', 'bulky-bulk-edit-products-for-woo' ); ?></a>
                                <a class="item" data-tab="meta_field"><?php esc_html_e( 'Meta fields', 'bulky-bulk-edit-products-for-woo' ); ?></a>
                                <a class="item" data-tab="history"><?php esc_html_e( 'History', 'bulky-bulk-edit-products-for-woo' ); ?></a>
                            </div>

                            <div class="vi-ui bottom attached active tab segment" data-tab="filter">
                                <form class="" id="vi-wbe-products-filter">
									<?php
									$this->filter_input_element( [
										'type'  => 'text',
										'id'    => 'id',
										'label' => esc_html__( 'ID', 'bulky-bulk-edit-products-for-woo' )
									] );

									$this->filter_input_element( [
										'type'     => 'text',
										'id'       => 'post_title',
										'label'    => esc_html__( 'Title', 'bulky-bulk-edit-products-for-woo' ),
										'behavior' => true
									] );

									$this->filter_input_element( [
										'type'     => 'text',
										'id'       => 'post_content',
										'label'    => esc_html__( 'Content', 'bulky-bulk-edit-products-for-woo' ),
										'behavior' => true
									] );

									$this->filter_input_element( [
										'type'     => 'text',
										'id'       => 'post_excerpt',
										'label'    => esc_html__( 'Excerpt', 'bulky-bulk-edit-products-for-woo' ),
										'behavior' => true
									] );

									$this->filter_input_element( [
										'type'     => 'text',
										'id'       => 'post_name',
										'label'    => esc_html__( 'Slug', 'bulky-bulk-edit-products-for-woo' ),
										'behavior' => true
									] );

									$this->filter_input_element( [
										'type'     => 'text',
										'id'       => 'sku',
										'label'    => esc_html__( 'SKU', 'bulky-bulk-edit-products-for-woo' ),
										'behavior' => true
									] );

									?>

                                    <div class="two fields">
										<?php
										$this->filter_input_element( [
											'type'  => 'date',
											'id'    => 'post_date_from',
											'label' => esc_html__( 'Post date from', 'bulky-bulk-edit-products-for-woo' )
										] );
										$this->filter_input_element( [
											'type'  => 'date',
											'id'    => 'post_date_to',
											'label' => esc_html__( 'Post date to', 'bulky-bulk-edit-products-for-woo' )
										] );
										?>
                                    </div>

                                    <div class="two fields">
										<?php
										$this->filter_input_element( [
											'type'  => 'number',
											'id'    => 'regular_price_from',
											'label' => esc_html__( 'Regular price from', 'bulky-bulk-edit-products-for-woo' )
										] );
										$this->filter_input_element( [
											'type'  => 'number',
											'id'    => 'regular_price_to',
											'label' => esc_html__( 'Regular price to', 'bulky-bulk-edit-products-for-woo' )
										] );
										?>
                                    </div>

                                    <div class="two fields">
										<?php
										$this->filter_input_element( [
											'type'  => 'number',
											'id'    => 'sale_price_from',
											'label' => esc_html__( 'Sale price from', 'bulky-bulk-edit-products-for-woo' )
										] );
										$this->filter_input_element( [
											'type'  => 'number',
											'id'    => 'sale_price_to',
											'label' => esc_html__( 'Sale price to', 'bulky-bulk-edit-products-for-woo' )
										] );
										?>
                                    </div>

                                    <div class="two fields">
										<?php
										$this->filter_input_element( [
											'type'  => 'date',
											'id'    => 'sale_date_from',
											'label' => esc_html__( 'Sale date from', 'bulky-bulk-edit-products-for-woo' )
										] );
										$this->filter_input_element( [
											'type'  => 'date',
											'id'    => 'sale_date_to',
											'label' => esc_html__( 'Sale date to', 'bulky-bulk-edit-products-for-woo' )
										] );
										?>
                                    </div>

                                    <div class="two fields">
										<?php
										$this->filter_input_element( [
											'type'  => 'number',
											'id'    => 'stock_quantity_from',
											'label' => esc_html__( 'Stock quantity from', 'bulky-bulk-edit-products-for-woo' )
										] );
										$this->filter_input_element( [
											'type'  => 'number',
											'id'    => 'stock_quantity_to',
											'label' => esc_html__( 'Stock quantity to', 'bulky-bulk-edit-products-for-woo' )
										] );
										?>
                                    </div>

                                    <div class="two fields">
										<?php
										$this->filter_input_element( [
											'type'  => 'number',
											'id'    => 'width_from',
											'label' => esc_html__( 'Width from', 'bulky-bulk-edit-products-for-woo' )
										] );
										$this->filter_input_element( [
											'type'  => 'number',
											'id'    => 'width_to',
											'label' => esc_html__( 'Width to', 'bulky-bulk-edit-products-for-woo' )
										] );
										?>
                                    </div>

                                    <div class="two fields">
										<?php
										$this->filter_input_element( [
											'type'  => 'number',
											'id'    => 'height_from',
											'label' => esc_html__( 'Height from', 'bulky-bulk-edit-products-for-woo' )
										] );
										$this->filter_input_element( [
											'type'  => 'number',
											'id'    => 'height_to',
											'label' => esc_html__( 'Height to', 'bulky-bulk-edit-products-for-woo' )
										] );
										?>
                                    </div>

                                    <div class="two fields">
										<?php
										$this->filter_input_element( [
											'type'  => 'number',
											'id'    => 'length_from',
											'label' => esc_html__( 'Lenght from', 'bulky-bulk-edit-products-for-woo' )
										] );
										$this->filter_input_element( [
											'type'  => 'number',
											'id'    => 'length_to',
											'label' => esc_html__( 'Lenght to', 'bulky-bulk-edit-products-for-woo' )
										] );
										?>
                                    </div>

                                    <div class="two fields">
										<?php
										$this->filter_input_element( [
											'type'  => 'number',
											'id'    => 'weight_from',
											'label' => esc_html__( 'Weight from', 'bulky-bulk-edit-products-for-woo' )
										] );
										$this->filter_input_element( [
											'type'  => 'number',
											'id'    => 'weight_to',
											'label' => esc_html__( 'Weight to', 'bulky-bulk-edit-products-for-woo' )
										] );
										?>
                                    </div>

                                    <div class="two fields">
										<?php
										$this->filter_input_element( [
											'type'    => 'select',
											'id'      => 'type',
											'options' => [ '' => esc_html__( 'Product type', 'bulky-bulk-edit-products-for-woo' ) ] + wc_get_product_types()
										] );
										$this->filter_input_element( [
											'type'    => 'select',
											'id'      => 'status',
											'options' => [
												''        => esc_html__( 'Product status', 'bulky-bulk-edit-products-for-woo' ),
												'draft'   => esc_html__( 'Draft', 'bulky-bulk-edit-products-for-woo' ),
												'pending' => esc_html__( 'Pending', 'bulky-bulk-edit-products-for-woo' ),
												'private' => esc_html__( 'Private', 'bulky-bulk-edit-products-for-woo' ),
												'publish' => esc_html__( 'Publish', 'bulky-bulk-edit-products-for-woo' ),
											]
										] );
										?>
                                    </div>

                                    <div class="two fields">
										<?php
										$this->filter_input_element( [
											'type'    => 'select',
											'id'      => 'stock_status',
											'options' => [ '' => esc_html__( 'Stock status', 'bulky-bulk-edit-products-for-woo' ) ] + wc_get_product_stock_status_options()
										] );
										$this->filter_input_element( [
											'type'    => 'select',
											'id'      => 'featured',
											'options' => [
												''    => esc_html__( 'Featured', 'bulky-bulk-edit-products-for-woo' ),
												'yes' => esc_html__( 'Yes', 'bulky-bulk-edit-products-for-woo' ),
												'no'  => esc_html__( 'No', 'bulky-bulk-edit-products-for-woo' ),
											]
										] );
										?>
                                    </div>

                                    <div class="two fields">
										<?php
										$this->filter_input_element( [
											'type'    => 'select',
											'id'      => 'downloadable',
											'options' => [
												''    => esc_html__( 'Downloadable', 'bulky-bulk-edit-products-for-woo' ),
												'yes' => esc_html__( 'Yes', 'bulky-bulk-edit-products-for-woo' ),
												'no'  => esc_html__( 'No', 'bulky-bulk-edit-products-for-woo' ),
											]
										] );
										$this->filter_input_element( [
											'type'    => 'select',
											'id'      => 'sold_individually',
											'options' => [
												''    => esc_html__( 'Sold individually', 'bulky-bulk-edit-products-for-woo' ),
												'yes' => esc_html__( 'Yes', 'bulky-bulk-edit-products-for-woo' ),
												'no'  => esc_html__( 'No', 'bulky-bulk-edit-products-for-woo' ),
											]
										] );
										?>
                                    </div>

                                    <div class="two fields">
										<?php
										$this->filter_input_element( [
											'type'    => 'select',
											'id'      => 'backorders',
											'options' => [ '' => esc_html__( 'Backorders', 'bulky-bulk-edit-products-for-woo' ) ] + wc_get_product_backorder_options()
										] );

										$this->filter_input_element( [
											'type'    => 'select',
											'id'      => 'author',
											'options' => $users_options
										] );
										?>
                                    </div>

									<?php
									$this->filter_input_element( [
										'type'    => 'select',
										'id'      => 'visibility',
										'options' => [ '' => esc_html__( 'Catalog visibility', 'bulky-bulk-edit-products-for-woo' ) ] + wc_get_product_visibility_options()
									] );

									$this->filter_input_element( [
										'type'        => 'multi-select',
										'id'          => 'product_cat',
										'options'     => [ '' => esc_html__( 'Categories', 'bulky-bulk-edit-products-for-woo' ) ] + WCBEdit_Data()->get_categories(),
										'name_prefix' => 'taxonomies',
										'operator'    => true,
									] );

									$this->filter_input_element( [
										'type'        => 'multi-select',
										'id'          => 'product_tag',
										'options'     => [ '' => esc_html__( 'Tags', 'bulky-bulk-edit-products-for-woo' ) ] + WCBEdit_Data()->get_product_tags(),
										'name_prefix' => 'taxonomies',
										'operator'    => true,
									] );

									$attribute_taxonomies = wc_get_attribute_taxonomies();
									foreach ( $attribute_taxonomies as $tax ) {
										$taxonomy = wc_attribute_taxonomy_name( $tax->attribute_name );
										$options  = [];
										if ( taxonomy_exists( $taxonomy ) ) {
											$terms = get_terms( $taxonomy, 'hide_empty=0' );
											foreach ( $terms as $term ) {
												$options[ $term->slug ] = $term->name;
											}
										}

										$this->filter_input_element( [
											'type'        => 'multi-select',
											'id'          => $taxonomy,
											'options'     => [ '' => $tax->attribute_label ] + $options,
											'name_prefix' => 'taxonomies',
											'operator'    => true,
										] );
									}

									?>

                                </form>

                                <div class="vi-wbe-sidebar-footer">
                                        <span class="vi-ui button small vi-wbe-apply-filter">
                                            <?php esc_html_e( 'Filter', 'bulky-bulk-edit-products-for-woo' ); ?>
                                        </span>
                                    <span class="vi-ui button small vi-wbe-clear-filter">
                                            <?php esc_html_e( 'Clear', 'bulky-bulk-edit-products-for-woo' ); ?>
                                        </span>
                                </div>

                            </div>

                            <div class="vi-ui bottom attached tab segment" data-tab="settings">
                                <form class="vi-wbe-settings-tab ">

									<?php
									$this->setting_input_element( [
										'type'         => 'multi-select',
										'id'           => 'edit_fields',
										'select_class' => 'vi-wbe-select-columns-to-edit vi-wbe-select2 search',
										'label'        => esc_html__( 'Fields to edit', 'bulky-bulk-edit-products-for-woo' ),
										'options'      => [ '' => esc_html__( 'All fields', 'bulky-bulk-edit-products-for-woo' ) ] + $columns,
										'clear_button' => true
									] );

									$this->setting_input_element( [
										'type'         => 'multi-select',
										'id'           => 'exclude_edit_fields',
										'select_class' => 'vi-wbe-exclude-fields-to-edit vi-wbe-select2 search',
										'label'        => esc_html__( 'Exclude fields to edit', 'bulky-bulk-edit-products-for-woo' ),
										'options'      => [ '' => esc_html__( 'No field', 'bulky-bulk-edit-products-for-woo' ) ] + $columns,
										'clear_button' => true
									] );

									$this->setting_input_element( [
										'type'  => 'get_pro_version',
										'id'    => 'products_per_page',
										'min'   => 1,
										'max'   => 50,
										'label' => esc_html__( 'Products per page (default: 10)', 'bulky-bulk-edit-products-for-woo' )
									] );

									$this->setting_input_element( [
										'type'    => 'select',
										'id'      => 'load_variations',
										'label'   => esc_html__( 'Load variations', 'bulky-bulk-edit-products-for-woo' ),
										'options' => [
											'yes' => esc_html__( 'Yes', 'bulky-bulk-edit-products-for-woo' ),
											'no'  => esc_html__( 'No', 'bulky-bulk-edit-products-for-woo' ),
										]
									] );

									$this->setting_input_element( [
										'type'    => 'select',
										'id'      => 'order_by',
										'label'   => esc_html__( 'Order by', 'bulky-bulk-edit-products-for-woo' ),
										'options' => [
											'ID'    => 'ID',
											'title' => esc_html__( 'Title', 'bulky-bulk-edit-products-for-woo' ),
											'price' => esc_html__( 'Price', 'bulky-bulk-edit-products-for-woo' ),
											'sku'   => esc_html__( 'SKU', 'bulky-bulk-edit-products-for-woo' ),
										]
									] );

									$this->setting_input_element( [
										'type'    => 'select',
										'id'      => 'order',
										'label'   => esc_html__( 'Order', 'bulky-bulk-edit-products-for-woo' ),
										'options' => [ 'DESC' => 'DESC', 'ASC' => 'ASC', ]
									] );

									$this->setting_input_element( [
										'type'  => 'get_pro_version',
										'id'    => 'auto_remove_revision',
										'min'   => 0,
										'max'   => 1000,
										'label' => esc_html__( 'Time to delete revision', 'bulky-bulk-edit-products-for-woo' ),
										'unit'  => esc_html__( 'day(s)', 'bulky-bulk-edit-products-for-woo' ),
									] );

									$this->setting_input_element( [
										'type'  => 'get_pro_version',
										'id'    => 'save_filter',
										'label' => esc_html__( 'Save filter when reload page', 'bulky-bulk-edit-products-for-woo' ),
									] );

									$this->setting_input_element( [
										'type'  => 'checkbox',
										'id'    => 'variation_filter',
										'label' => esc_html__( 'Filter include variation', 'bulky-woocommerce-bulk-edit-products' ),
									] );
									?>
                                </form>

								<?php do_action( 'villatheme_support_' . WCBE_CONST_F['slug'] ); ?>

                                <div class="vi-wbe-sidebar-footer">
                                    <span class="vi-ui button small vi-wbe-save-settings">
                                        <?php esc_html_e( 'Save', 'bulky-bulk-edit-products-for-woo' ); ?>
                                    </span>
                                </div>

                            </div>

                            <div class="vi-ui bottom attached tab segment" data-tab="meta_field">
								<?php Support::get_pro_version(); ?>
                            </div>

                            <div class="vi-ui bottom attached tab segment" data-tab="history">
								<?php Support::get_pro_version(); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="vi-wbe-editor" class="vi-ui segment">
                    <div class="wvps-scroll">

                        <div id="vi-wbe-spreadsheet">

                        </div>
                    </div>
                </div>

            </div>

            <!------------------- Modal ---------------------->
            <div class="vi-ui modal">
                <i class="close icon"></i>

                <div class="scrolling content vi-wbe-editing" style="box-sizing: border-box;height: 5000px">
                    <div>
                        <textarea id="vi-wbe-text-editor"></textarea>
                    </div>
                </div>

                <div class="actions vi-wbe-editing">
                    <!--                    <div class="vi-ui button tiny vi-wbe-text-editor-save vi-wbe-next">-->
                    <!--						--><?php //esc_html_e( 'Next', 'bulky-bulk-edit-products-for-woo' ); ?>
                    <!--                    </div>-->
                    <div class="vi-ui button tiny vi-wbe-text-editor-save">
						<?php esc_html_e( 'Save', 'bulky-bulk-edit-products-for-woo' ); ?>
                    </div>
                    <div class="vi-ui button tiny vi-wbe-text-editor-save vi-wbe-close">
						<?php esc_html_e( 'Save & Close', 'bulky-bulk-edit-products-for-woo' ); ?>
                    </div>
                </div>
            </div>

            <div class=" vi-ui segment form vi-wbe-context-popup"></div>

        </div>
		<?php
	}

	public function filter_input_element( $args = [] ) {
		$args = wp_parse_args( $args, [
			'type'         => '',
			'id'           => '',
			'label'        => '',
			'behavior'     => '',
			'operator'     => '',
			'name_prefix'  => '',
			'class'        => '',
			'placeholder'  => '',
			'label_class'  => 'vi-wbe-filter-label',
			'input_class'  => 'vi-wbe-filter-input',
			'select_class' => 'vi-wbe-filter-select',
			'more_content' => '',
			'unit'         => ''
		] );

		if ( in_array( $args['type'], [ 'text', 'number', 'date' ] ) ) {
			$args['class'] .= 'vi-wbe-filter-input-scope';
		}

		if ( $args['behavior'] ) {
			$args['more_content'] = $this->behavior_ui( $args['id'] );
			$args['action_class'] = 'action';
		}

		if ( $args['operator'] ) {
			$args['more_content'] = $this->operator_ui( $args['id'] );
			$args['action_class'] = 'action';
		}

		if ( $args['unit'] ) {
			$args['more_content'] = sprintf( "<div class='vi-ui basic label'>%s</div>", esc_html( $args['unit'] ) );
		}

		if ( $args['name_prefix'] ) {
			$value = $this->filter_saved[ $args['name_prefix'] ][ $args['id'] ] ?? ( $this->filter_saved[ $args['id'] ] ?? '' );
		} else {
			$value = $this->filter_saved[ $args['id'] ] ?? '';
		}

		$this->core_elements( $args, $value );
	}

	public function setting_input_element( $args ) {
		$args = wp_parse_args( $args, [
			'type'         => '',
			'id'           => '',
			'default'      => '',
			'label'        => '',
			'behavior'     => '',
			'operator'     => '',
			'name_prefix'  => '',
			'class'        => '',
			'label_class'  => '',
			'input_class'  => '',
			'select_class' => '',
			'more_content' => '',
			'unit'         => '',
			'clear_button' => ''
		] );

		if ( $args['unit'] ) {
			$args['more_content'] = sprintf( "<div class='vi-ui basic label'>%s</div>", esc_html( $args['unit'] ) );
			$args['action_class'] = 'right labeled';
		}

		$data  = WCBEdit_Data()->get_settings();
		$value = $data[ $args['id'] ] ?? '';
		$this->core_elements( $args, $value );
	}

	public function core_elements( $args, $value ) {
		if ( ! $this->allow_html_tags ) {
			$this->allow_html_tags = wp_parse_args(
				[
					'input'  => [ 'class' => true, 'name' => true ],
					'select' => [ 'class' => true, 'name' => true, 'multiple' => true ],
					'option' => [ 'value' => true, 'selected' => true ],
					'div'    => [ 'value' => true, 'class' => true ]
				],
				wp_kses_allowed_html() );
		}

		?>
        <div class="field <?php echo esc_attr( $args['class'] ) ?>">
			<?php
			switch ( $args['type'] ) {
				case 'text':
				case 'number':
				case 'date':
					$min = isset( $args['min'] ) ? " min={$args['min']}" : '';
					$max = isset( $args['max'] ) ? " max={$args['max']}" : '';
					?>
                    <label class="<?php echo esc_attr( $args['label_class'] ) ?>">
						<?php echo esc_attr( $args['label'] ) ?>
                    </label>
                    <div class="vi-ui input small <?php echo esc_attr( $args['action_class'] ?? '' ); ?>">
                        <input type="<?php echo esc_attr( $args['type'] ) ?>" placeholder="<?php echo esc_attr( $args['placeholder'] ) ?>"
                               name="<?php echo esc_attr( $args['id'] ) ?>"
                               value="<?php echo esc_attr( $value ) ?>"
                               class="<?php echo esc_attr( $args['input_class'] ) ?>" <?php echo esc_attr( $min . $max ) ?>>
						<?php echo wp_kses( $args['more_content'], $this->allow_html_tags ); ?>
                    </div>
					<?php
					break;

				case 'select':
				case 'multi-select':
					$multiple = $args['type'] == 'multi-select' ? 'multiple' : '';
					$name = $args['name_prefix'] ? $args['name_prefix'] . "[{$args['id']}]" : $args['id'];
					$name = $multiple ? $name . '[]' : $name;
					?>
                    <label class="<?php echo esc_attr( $args['label_class'] ) ?>">
						<?php echo esc_attr( $args['label'] ) ?>
                    </label>
                    <div class="vi-ui input small <?php echo esc_attr( $args['action_class'] ?? '' ); ?>">
                        <select id="vi-wbe-<?php echo esc_attr( $args['id'] ?? '' ) ?>"
                                name="<?php echo esc_attr( $name ) ?>"
                                class="vi-wbe vi-ui fluid dropdown <?php echo esc_attr( $args['select_class'] ) ?>"
                                data-placeholder="" <?php echo esc_attr( $multiple ) ?> >
							<?php
							if ( ! empty( $args['options'] ) && is_array( $args['options'] ) ) {
								foreach ( $args['options'] as $key => $label ) {
									if ( $multiple && is_array( $value ) ) {
										$selected = in_array( $key, $value ) ? 'selected' : '';
									} else {
										$selected = $key == $value ? 'selected' : '';
									}
									printf( "<option value='%s' %s>%s</option>", esc_attr( $key ), esc_attr( $selected ), esc_html( $label ) );
								}
							}
							?>
                        </select>
						<?php
						if ( ! empty( $args['clear_button'] ) ) {
							?>
                            <span class="vi-wbe-multi-select-clear"><i class="dashicons dashicons-no-alt"> </i></span>
							<?php
						}
						?>
						<?php echo wp_kses( $args['more_content'], $this->allow_html_tags ); ?>
                    </div>
					<?php
					break;

				case 'checkbox':
					?>
                    <label class="<?php echo esc_attr( $args['label_class'] ) ?>">
						<?php echo esc_attr( $args['label'] ) ?>
                    </label>
                    <div class="vi-ui toggle checkbox small <?php echo esc_attr( $args['action_class'] ?? '' ); ?>">
                        <input type="checkbox"
                               name="<?php echo esc_attr( $args['id'] ) ?>"
                               value="1" <?php checked( $value, 1 ) ?>
                               class="<?php echo esc_attr( $args['input_class'] ) ?>">
                        <label> </label>
						<?php echo wp_kses( $args['more_content'], $this->allow_html_tags ); ?>
                    </div>
					<?php
					break;

				case 'get_pro_version':
					?>
                    <label class="<?php echo esc_attr( $args['label_class'] ) ?>">
						<?php echo esc_attr( $args['label'] ) ?>
                    </label>
                    <div class="vi-ui toggle checkbox small <?php echo esc_attr( $args['action_class'] ?? '' ); ?>">
						<?php Support::get_pro_version(); ?>
                    </div>
					<?php
					break;
			}
			?>
        </div>
		<?php

	}

	public function behavior_ui( $id ) {
		$behaviors = [
			'like'  => esc_html__( 'Like', 'bulky-bulk-edit-products-for-woo' ),
			'exact' => esc_html__( 'Exact', 'bulky-bulk-edit-products-for-woo' ),
			'not'   => esc_html__( 'Not', 'bulky-bulk-edit-products-for-woo' ),
			'begin' => esc_html__( 'Begin', 'bulky-bulk-edit-products-for-woo' ),
			'end'   => esc_html__( 'End', 'bulky-bulk-edit-products-for-woo' ),
			'empty' => esc_html__( 'Empty', 'bulky-bulk-edit-products-for-woo' ),
		];

		$behaviors = apply_filters( 'bulky_filter_behaviors_list', $behaviors, $id );

		$saved_behavior = $this->filter_saved['behavior'][ $id ] ?? '';
		ob_start();
		?>
        <select class="vi-ui compact selection dropdown" name="behavior[<?php echo esc_attr( $id ) ?>]">
			<?php
			foreach ( $behaviors as $behavior => $show ) {
				printf( '<option value="%s" %s>%s</option>', esc_attr( $behavior ), selected( $behavior, $saved_behavior, false ), esc_html( $show ) );
			}
			?>
        </select>
		<?php
		return ob_get_clean();
	}

	public function operator_ui( $id ) {
		$operators      = [
			'or'     => esc_html__( 'Or', 'bulky-bulk-edit-products-for-woo' ),
			'and'    => esc_html__( 'And', 'bulky-bulk-edit-products-for-woo' ),
			'not_in' => esc_html__( 'Not in', 'bulky-bulk-edit-products-for-woo' ),
		];
		$saved_operator = $this->filter_saved['operator'][ $id ] ?? '';
		ob_start();
		?>
        <select class="vi-ui compact selection dropdown" name="operator[<?php echo esc_attr( $id ) ?>]">
			<?php
			foreach ( $operators as $operator => $show ) {
				printf( '<option value="%s" %s>%s</option>',
					esc_attr( $operator ), selected( $operator, $saved_operator, false ), esc_html( $show ) );
			}
			?>
        </select>
		<?php
		return ob_get_clean();
	}


	public function add_sku_behavior( $behaviors, $id ) {
		if ( $id == 'sku' ) {
			$behaviors = [ 'in' => 'In' ] + $behaviors;
		}

		return $behaviors;
	}
}
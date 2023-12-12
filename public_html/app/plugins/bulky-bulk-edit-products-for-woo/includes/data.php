<?php

namespace WCBEditor\Includes;

defined( 'ABSPATH' ) || exit;

class Data {

	protected static $instance = null;

	protected $params;
	protected $filter;
	protected $settings;

	public function __construct() {
	}

	public static function instance() {
		return self::$instance == null ? self::$instance = new self() : self::$instance;
	}

	public function get_params() {
		$params_saved   = get_option( 'vi_wbe_settings' );
		$default_params = [

		];

		return $this->params = wp_parse_args( $params_saved, $default_params );
	}

	public function get_param( $key ) {
		if ( ! $this->params ) {
			$this->get_params();
		}

		return $this->params[ $key ] ?? '';
	}

	public function fixed_columns() {
		return [ 'id', 'post_title', 'product_type', 'parent_id' ]; //'action',
	}

	public function downloadable() {
		return [ 'download_file', 'download_limit', 'download_expiry' ];
	}

	public function define_columns_type() {
		$cache_key  = 'vi_web_cache_define_columns';
		$cache_data = wp_cache_get( $cache_key );

		if ( ! empty( $cache_data ) ) {
			return $cache_data;
		}


		$shipping_class = [];
		$terms          = get_terms( [ 'taxonomy' => 'product_shipping_class', 'hide_empty' => false, ] );
		if ( ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				$shipping_class[] = [ 'id' => intval( $term->term_id ), 'name' => $term->name ];
			}
		}

		$post_status = [
			[ 'id' => 'publish', 'name' => esc_html__( 'Published', 'bulky-bulk-edit-products-for-woo' ) ],
			[ 'id' => 'future', 'name' => esc_html__( 'Scheduled', 'bulky-bulk-edit-products-for-woo' ) ],
			[ 'id' => 'pending', 'name' => esc_html__( 'Pending', 'bulky-bulk-edit-products-for-woo' ) ],
			[ 'id' => 'draft', 'name' => esc_html__( 'Draft', 'bulky-bulk-edit-products-for-woo' ) ],
			[ 'id' => 'private', 'name' => esc_html__( 'Private', 'bulky-bulk-edit-products-for-woo' ) ],
		];


		$catalog_visibility = $this->parse_to_dropdown_source( wc_get_product_visibility_options() );
		$product_types      = $this->parse_to_dropdown_source( wc_get_product_types() );
		$tax_class_options  = $this->parse_to_dropdown_source( wc_get_product_tax_class_options() );
		$stock_status       = $this->parse_to_dropdown_source( wc_get_product_stock_status_options() );
		$allow_backorder    = $this->parse_to_dropdown_source( wc_get_product_backorder_options() );

		$sub_tax_class_options    = $tax_class_options;
		$sub_tax_class_options[0] = [ 'id' => 'parent', 'name' => esc_html__( 'Same as parent', 'bulky-bulk-edit-products-for-woo' ) ];

		$users = [];
		if ( $users === [] ) {
			$roles = [];
			foreach ( wp_roles()->roles as $role_name => $role_obj ) {
				if ( ! empty( $role_obj['capabilities']['edit_posts'] ) ) {
					$roles[] = $role_name;
				}
			}
			$uu = get_users( [ 'role__in' => $roles ] );
			if ( ! empty( $uu ) ) {
				foreach ( $uu as $u ) {
					$users[] = [ 'id' => $u->data->ID, 'name' => $u->data->display_name ];
				}
			}
		}

		$decimal_separator = wc_get_price_decimal_separator();
		$currency          = get_woocommerce_currency_symbol();
		$curency_format    = "###{$decimal_separator}#";

		$columns = [
			'id'           => [ 'type' => 'number', 'width' => 70, 'title' => 'ID', 'readOnly' => true ],
			'parent_id'    => [ 'type' => 'number', 'width' => 60, 'title' => 'Parent', 'readOnly' => true, ],
			'post_title'   => [ 'type' => 'text', 'width' => 200, 'title' => esc_html__( 'Title', 'bulky-bulk-edit-products-for-woo' ), 'align' => 'left' ],
			'product_type' => [ 'type' => 'dropdown', 'width' => 100, 'title' => esc_html__( 'Product type', 'bulky-bulk-edit-products-for-woo' ), 'source' => $product_types ],
			'image'        => [ 'type' => 'custom', 'width' => 70, 'title' => esc_html__( 'Image', 'bulky-bulk-edit-products-for-woo' ), 'editor' => 'image' ],
			'sku'          => [ 'type' => 'text', 'width' => 70, 'title' => esc_html__( 'SKU', 'bulky-bulk-edit-products-for-woo' ), 'align' => 'left' ],
			'post_name'    => [ 'type' => 'text', 'width' => 70, 'title' => esc_html__( 'Slug', 'bulky-bulk-edit-products-for-woo' ), 'align' => 'left' ],

			'post_date' => [
				'type'    => 'calendar',
				'width'   => 120,
				'title'   => esc_html__( 'Publish date', 'bulky-woocommerce-bulk-edit-products' ),
				'options' => [ 'format' => 'YYYY-MM-DD HH24:MI', 'time' => 1 ]
			],

			'post_content' => [ 'type' => 'custom', 'width' => 100, 'title' => esc_html__( 'Description', 'bulky-bulk-edit-products-for-woo' ), 'align' => 'left', 'editor' => 'textEditor' ],
			'post_excerpt' => [ 'type' => 'custom', 'width' => 100, 'title' => esc_html__( 'Short Desc', 'bulky-bulk-edit-products-for-woo' ), 'align' => 'left', 'editor' => 'textEditor' ],
			'gallery'      => [ 'type' => 'custom', 'width' => 60, 'title' => esc_html__( 'Gallery', 'bulky-bulk-edit-products-for-woo' ), 'editor' => 'gallery' ],

			'attributes' => [
				'type'   => 'custom',
				'width'  => 80,
				'title'  => esc_html__( 'Attributes', 'bulky-bulk-edit-products-for-woo' ),
				'editor' => 'product_attributes'
			],

			'default_attributes' => [
				'type'   => 'custom',
				'width'  => 80,
				'title'  => esc_html__( 'Default attributes', 'bulky-bulk-edit-products-for-woo' ),
				'editor' => 'default_attributes'
			],

			'grouped_products' => [
				'type'   => 'custom',
				'width'  => 100,
				'title'  => esc_html__( 'Grouped', 'bulky-bulk-edit-products-for-woo' ),
				'editor' => 'link_products'
			],

			'product_url' => [
				'type'  => 'text',
				'width' => 100,
				'title' => esc_html__( 'Product URL', 'bulky-bulk-edit-products-for-woo' ),
			],

			'button_text' => [
				'type'  => 'text',
				'width' => 100,
				'title' => esc_html__( 'Button text', 'bulky-bulk-edit-products-for-woo' ),
			],

			'status'   => [
				'type'      => 'dropdown',
				'width'     => 80,
				'title'     => esc_html__( 'Status', 'bulky-bulk-edit-products-for-woo' ),
				'source'    => $post_status,
				'subSource' => [
					[ 'id' => 'publish', 'name' => esc_html__( 'Enable', 'bulky-bulk-edit-products-for-woo' ) ],
					[ 'id' => 'private', 'name' => esc_html__( 'Disable', 'bulky-bulk-edit-products-for-woo' ) ],
				],
				'filter'    => 'sourceForVariation'
			],
			'password' => [ 'type' => 'text', 'width' => 100, 'title' => esc_html__( 'Password', 'bulky-bulk-edit-products-for-woo' ) ],
			'featured' => [ 'type' => 'checkbox', 'width' => 60, 'title' => esc_html__( 'Featured', 'bulky-bulk-edit-products-for-woo' ) ],

			'regular_price' => [
				'type'       => 'number',
				'width'      => 110,
				'title'      => esc_html__( 'Regular price', 'bulky-bulk-edit-products-for-woo' ) . sprintf( ' (%s)', esc_html( $currency ) ),
				'mask'       => $curency_format, // $currency .
				'allowEmpty' => true,
			],

			'sale_price' => [
				'type'       => 'number',
				'width'      => 90,
				'title'      => esc_html__( 'Sale price', 'bulky-bulk-edit-products-for-woo' ) . sprintf( ' (%s)', esc_html( $currency ) ),
				'mask'       => $curency_format, // $currency .
				'allowEmpty' => true,
			],

			'sale_date_from' => [
				'type'    => 'calendar',
				'width'   => 100,
				'title'   => esc_html__( 'Sale date from', 'bulky-bulk-edit-products-for-woo' ),
				'options' => [ 'format' => 'YYYY-MM-DD' ]
			],

			'sale_date_to' => [
				'type'    => 'calendar',
				'width'   => 100,
				'title'   => esc_html__( 'Sale date to', 'bulky-bulk-edit-products-for-woo' ),
				'options' => [ 'format' => 'YYYY-MM-DD' ]
			],

			'manage_stock'      => [ 'type' => 'checkbox', 'width' => 70, 'title' => esc_html__( 'Manage stock', 'bulky-bulk-edit-products-for-woo' ) ],
			'stock'             => [ 'type' => 'number', 'width' => 70, 'title' => esc_html__( 'Stock', 'bulky-bulk-edit-products-for-woo' ) ],
			'stock_status'      => [ 'type' => 'dropdown', 'width' => 100, 'title' => esc_html__( 'Stock status', 'bulky-bulk-edit-products-for-woo' ), 'source' => $stock_status ],
			'allow_backorder'   => [ 'type' => 'dropdown', 'width' => 80, 'title' => esc_html__( 'Allow backorder', 'bulky-bulk-edit-products-for-woo' ), 'source' => $allow_backorder ],
			'sold_individually' => [ 'type' => 'checkbox', 'width' => 75, 'title' => esc_html__( 'Sold individually', 'bulky-bulk-edit-products-for-woo' ), ],
			'virtual'           => [ 'type' => 'checkbox', 'width' => 55, 'title' => esc_html__( 'Virtual', 'bulky-bulk-edit-products-for-woo' ), ],

			'product_cat' => [
				'type'       => 'dropdown',
				'width'      => 140,
				'title'      => esc_html__( 'Categories', 'bulky-bulk-edit-products-for-woo' ),
				'source'     => $this->get_categories( true ),
				'multiple'   => true,
				'allowEmpty' => true,
			],

			'tags' => [
				'type'         => 'custom',
				'width'        => 100,
				'title'        => esc_html__( 'Tags', 'bulky-bulk-edit-products-for-woo' ),
				'editor'       => 'tags',
				'multiple'     => true,
				'remoteSearch' => true,
				'url'          => admin_url( 'admin-ajax.php?action=vi_wbe_search_tags&nonce=' . wp_create_nonce( 'vi_web_ajax_nonce' ) ),
			],

			'weight' => [ 'type' => 'number', 'allowEmpty' => true, 'width' => 70, 'title' => esc_html__( 'Weight', 'bulky-bulk-edit-products-for-woo' ) ],
			'length' => [ 'type' => 'number', 'allowEmpty' => true, 'width' => 70, 'title' => esc_html__( 'Length', 'bulky-bulk-edit-products-for-woo' ) ],
			'width'  => [ 'type' => 'number', 'allowEmpty' => true, 'width' => 70, 'title' => esc_html__( 'Width', 'bulky-bulk-edit-products-for-woo' ) ],
			'height' => [ 'type' => 'number', 'allowEmpty' => true, 'width' => 70, 'title' => esc_html__( 'Height', 'bulky-bulk-edit-products-for-woo' ) ],

			'upsells'     => [ 'type' => 'custom', 'width' => 100, 'title' => esc_html__( 'Upsells', 'bulky-bulk-edit-products-for-woo' ), 'editor' => 'link_products' ],
			'cross_sells' => [ 'type' => 'custom', 'width' => 100, 'title' => esc_html__( 'Cross-sells', 'bulky-bulk-edit-products-for-woo' ), 'editor' => 'link_products' ],

			'downloadable'    => [ 'type' => 'checkbox', 'width' => 90, 'title' => esc_html__( 'Downloadable', 'bulky-bulk-edit-products-for-woo' ), ],
			'download_file'   => [
				'type'     => 'text',
				'width'    => 90,
				'title'    => esc_html__( 'Download file', 'bulky-bulk-edit-products-for-woo' ),
				'editor'   => 'download',
				'wordWrap' => false
			],
			'download_limit'  => [ 'type' => 'number', 'allowEmpty' => true, 'width' => 90, 'title' => esc_html__( 'Download limit', 'bulky-bulk-edit-products-for-woo' ), 'mask' => "###", ],
			'download_expiry' => [ 'type' => 'number', 'allowEmpty' => true, 'width' => 90, 'title' => esc_html__( 'Download expiry', 'bulky-bulk-edit-products-for-woo' ), 'mask' => "###", ]
		];

		$tax_columns = [];
		if ( wc_tax_enabled() ) {
			$tax_columns = [
				'tax_status' => [
					'type'       => 'dropdown',
					'width'      => 90,
					'title'      => esc_html__( 'Tax status', 'bulky-bulk-edit-products-for-woo' ),
					'source'     => [
						[ 'id' => 'taxable', 'name' => esc_html__( 'Taxable', 'bulky-bulk-edit-products-for-woo' ) ],
						[ 'id' => 'shipping', 'name' => esc_html__( 'Shipping only', 'bulky-bulk-edit-products-for-woo' ) ],
						[ 'id' => 'none', 'name' => esc_html__( 'None', 'bulky-bulk-edit-products-for-woo' ) ],
					],
					'allowEmpty' => false,
				],

				'tax_class' => [
					'type'      => 'dropdown',
					'width'     => 90,
					'title'     => esc_html__( 'Tax class', 'bulky-bulk-edit-products-for-woo' ),
					'source'    => $tax_class_options,
					'subSource' => $sub_tax_class_options,
					'filter'    => 'sourceForVariation'
				],
			];
		}

		$columns_2 = [
			'purchase_note' => [ 'type' => 'text', 'width' => 90, 'title' => esc_html__( 'Purchase note', 'bulky-bulk-edit-products-for-woo' ), 'editor' => 'textEditor' ],
			'menu_order'    => [ 'type' => 'number', 'width' => 70, 'title' => esc_html__( 'Menu order', 'bulky-bulk-edit-products-for-woo' ), ],
			'allow_reviews' => [ 'type' => 'checkbox', 'width' => 70, 'title' => esc_html__( 'Enable reviews', 'bulky-bulk-edit-products-for-woo' ), 'default' => true ],

//			'author' => [ 'type' => 'dropdown', 'width' => 100, 'title' => esc_html__( 'Author', 'bulky-bulk-edit-products-for-woo' ), 'source' => $users, ],

			'catalog_visibility' => [
				'type'   => 'dropdown',
				'width'  => 100,
				'title'  => esc_html__( 'Catalog visibility', 'bulky-bulk-edit-products-for-woo' ),
				'source' => $catalog_visibility,
			],

			'shipping_class' => [
				'type'      => 'dropdown',
				'width'     => 100,
				'title'     => esc_html__( 'Shipping class', 'bulky-bulk-edit-products-for-woo' ),
				'source'    => array_merge( [ [ 'id' => '0', 'name' => esc_html__( 'No shipping class', 'woo-bulk-editor' ) ] ], $shipping_class ),
				'subSource' => array_merge( [ [ 'id' => '0', 'name' => esc_html__( 'Same as parent', 'woo-bulk-editor' ) ] ], $shipping_class ),
				'filter'    => 'sourceForVariation'
			],
		];

		$meta_fields = get_option( 'vi_wbe_product_meta_fields' );

		$meta_field_columns = [];
		if ( ! empty( $meta_fields ) && is_array( $meta_fields ) ) {
			foreach ( $meta_fields as $meta_key => $meta_field ) {
				if ( empty( $meta_field['active'] ) ) {
					continue;
				}

				$type   = 'text';
				$editor = '';

				switch ( $meta_field['input_type'] ) {
					case 'textinput':
						$type = 'text';
						break;
					case 'numberinput':
						$type = 'number';
						break;
					case 'checkbox':
						$type = 'checkbox';
						break;
					case 'array':
					case 'json':
						$type   = 'custom';
						$editor = 'array';
						break;
					case 'calendar':
						$type = 'calendar';
						break;
					case 'texteditor':
						$type   = 'custom';
						$editor = 'textEditor';
						break;
				}

				$meta_field_columns[ $meta_key ] = [
					'title'  => ! empty( $meta_field['column_name'] ) ? $meta_field['column_name'] : $meta_key,
					'width'  => 100,
					'type'   => $type,
					'editor' => $editor,
				];

			}
		}

		$columns = array_merge( $columns, $tax_columns, $columns_2, $meta_field_columns );
		wp_cache_add( $cache_key, $columns );

		return $columns;
	}

	public function parse_to_dropdown_source( $options ) {
		$r = [];
		if ( ! empty( $options ) && is_array( $options ) ) {
			foreach ( $options as $id => $name ) {
				$r[] = [ 'id' => $id, 'name' => $name ];
			}
		}

		return $r;
	}

	public function get_culumns_type_title() {
		$columns = wp_list_pluck( $this->define_columns_type(), 'title' );
		unset( $columns['action'] );
		unset( $columns['id'] );
		unset( $columns['parent_id'] );
		unset( $columns['post_title'] );
		unset( $columns['product_type'] );
		unset( $columns['download_file'] );
		unset( $columns['download_limit'] );
		unset( $columns['download_expiry'] );
		unset( $columns['default_attributes'] );

		return $columns;
	}

	public function get_settings() {
		if ( ! $this->settings ) {
			$this->settings = wp_parse_args(
				get_option( 'vi_wbe_settings', [] ),
				[
					'edit_fields'          => [],
					'products_per_page'    => 10,
					'load_variations'      => 'yes',
					'order_by'             => 'ID',
					'order'                => 'DESC',
					'auto_save_revision'   => 60,
					'auto_remove_revision' => 30,
				]
			);
		}

		return $this->settings;
	}

	public function get_setting( $key ) {
		$all_settings = $this->get_settings();

		return $all_settings[ $key ] ?? '';
	}

	public function get_fields_for_parse_product() {
		$defined_columns     = array_keys( $this->define_columns_type() );
		$edit_fields         = $this->get_setting( 'edit_fields' );
		$exclude_edit_fields = $this->get_setting( 'exclude_edit_fields' );

		$r = $defined_columns;

		if ( ! empty( $edit_fields ) && is_array( $edit_fields ) ) {
			$edit_fields = array_merge( $this->fixed_columns(), $edit_fields );
			if ( in_array( 'downloadable', $edit_fields ) ) {
				$edit_fields = array_merge( $edit_fields, $this->downloadable() );
			}

			foreach ( $r as $i => $key ) { //Keep piority
				if ( $key !== false && ! in_array( $key, $edit_fields ) ) {
					unset( $r[ $i ] );
				}
			}
		}

		if ( ! empty( $exclude_edit_fields ) && is_array( $exclude_edit_fields ) ) {
			foreach ( $exclude_edit_fields as $field ) {
				$key = array_search( $field, $r );

				if ( $key !== false && isset( $r[ $key ] ) ) {
					unset( $r[ $key ] );
				}

				if ( $field === 'downloadable' ) {
					foreach ( $this->downloadable() as $value ) {
						$key2 = array_search( $value, $r );
						if ( $key2 !== false && isset( $r[ $key2 ] ) ) {
							unset( $r[ $key2 ] );
						}
					}
				}
			}
		}

		return array_values( $r );
	}

	public function get_columns_type() {
		$columns          = $this->define_columns_type();
		$accepted_columns = [];
		$patterns         = $this->get_fields_for_parse_product();

		if ( ! empty( $patterns ) ) {
			foreach ( $columns as $key => $column ) {
				if ( in_array( $key, $patterns ) ) {
					$accepted_columns[ $key ] = $column;
				}
			}
		} else {
			$accepted_columns = $columns;
		}

		return $accepted_columns;
	}

	public function get_product_tags( $id_name = false ) {
		$tags = get_tags( [ 'taxonomy' => 'product_tag' ] );
		if ( ! empty( $tags ) ) {
			foreach ( $tags as $tag ) {
				if ( $id_name ) {
					$r[] = [ 'id' => $tag->term_id, 'name' => $tag->name ];
				} else {
					$r[ $tag->term_id ] = $tag->name;
				}
			}
		}

		return $r ?? [];
	}

	public function get_categories( $select2 = false ) {
		$categories = get_categories( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
		$categories = json_decode( wp_json_encode( $categories ), true );

		return $select2 ? $this->build_select2_categories_tree( $categories, 0 ) : $this->build_dropsown_categories_tree( $categories, 0 );
	}

	private function build_dropsown_categories_tree( $all_cats, $parent_cat, $level = 1 ) {
		$res = [];
		foreach ( $all_cats as $cat ) {
			if ( $cat['parent'] == $parent_cat ) {
				$prefix                 = str_repeat( '- ', $level - 1 );
				$res[ $cat['term_id'] ] = $prefix . $cat['name'];
				$child_cats             = $this->build_dropsown_categories_tree( $all_cats, $cat['term_id'], $level + 1 );
				if ( $child_cats ) {
					$res += $child_cats;
				}
			}
		}

		return $res;
	}

	private function build_select2_categories_tree( $all_cats, $parent_cat, $level = 1 ) {
		$res = [];
		foreach ( $all_cats as $cat ) {
			$new_cat = [];
			if ( $cat['parent'] == $parent_cat ) {
				$prefix          = str_repeat( '- ', $level - 1 );
				$new_cat['id']   = $cat['term_id'];
				$new_cat['name'] = $prefix . $cat['name'];
				$res[]           = $new_cat;
				$child_cats      = $this->build_select2_categories_tree( $all_cats, $cat['term_id'], $level + 1 );
				if ( $child_cats ) {
					$res = array_merge( $res, $child_cats );
				}
			}
		}

		return $res;
	}

	public function isHTML( $string ) {
		return $string != wp_strip_all_tags( $string );
	}

	public function sanitize( $var ) {
		if ( is_array( $var ) ) {
			return array_map( [ $this, 'sanitize' ], $var );
		} elseif ( $this->isHTML( $var ) ) {
			return wp_kses_post( $var );
		} else {
			return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
		}
	}
}
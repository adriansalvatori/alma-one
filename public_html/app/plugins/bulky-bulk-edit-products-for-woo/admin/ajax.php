<?php

namespace WCBEditor\Admin;

use Automattic\Jetpack\Constants;

defined( 'ABSPATH' ) || exit;

class Ajax {

	protected static $instance = null;
	protected $fields;
	protected $variation_ids = [];

	public function __construct() {
		add_action( 'wp_ajax_vi_wbe_ajax', [ $this, 'ajax_action' ] );
		add_action( 'product_variation_linked', [ $this, 'get_linked_variations' ] );
	}

	public static function instance() {
		return self::$instance == null ? self::$instance = new self() : self::$instance;
	}

	public function define_actions() {
		return [
			'set_full_screen_option',
			'load_products',
			'save_settings',
			'add_filter_data',
			'save_products',
			'search_tags',
			'search_products',
			'add_variation',
			'link_all_variations',
			'get_meta_fields',
			'save_meta_fields',
			'auto_save_revision',
			'recover_history',
			'add_new_product',
			'view_history_point',
			'revert_history_single_product',
			'revert_history_all_products',
			'revert_history_product_attribute',
			'load_history_page',
			'add_new_attribute',
			'duplicate_product',
		];
	}

	public function ajax_action() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		if ( empty( $_POST['sub_action'] ) || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$actions = $this->define_actions();

		$ajax_action = sanitize_text_field( wp_unslash( $_POST['sub_action'] ) );

		if ( ! ( in_array( $ajax_action, $actions ) && method_exists( $this, $ajax_action ) ) ) {
			wp_send_json_error( esc_html__( 'Method is not exist', 'bulky-bulk-edit-products-for-woo' ) );
		}

		$this->$ajax_action();

		wp_die();
	}

	public function set_full_screen_option() {
		if ( empty( $_POST['status'] ) ) {
			return;
		}
		$status = sanitize_text_field( wp_unslash( $_POST['status'] ) );
		$status = $status === 'true' ? true : false;
		update_option( 'vi_wbe_full_screen_option', $status );
	}

	public function save_settings() {
		if ( isset( $_POST['fields'] ) ) {
			wp_parse_str( $_POST['fields'], $new_options );
			$new_options = wc_clean( $new_options );
			$old_options = get_option( 'vi_wbe_settings' );

			$old_edit_fields         = $old_options['edit_fields'] ?? [];
			$new_edit_fields         = $new_options['edit_fields'] ?? [];
			$old_exclude_edit_fields = $old_options['exclude_edit_fields'] ?? [];
			$new_exclude_edit_fields = $new_options['exclude_edit_fields'] ?? [];

			$edit_fields_compare         = ! empty( array_merge( array_diff( $old_edit_fields, $new_edit_fields ), array_diff( $new_edit_fields, $old_edit_fields ) ) );
			$exclude_edit_fields_compare = ! empty( array_merge( array_diff( $old_exclude_edit_fields, $new_exclude_edit_fields ), array_diff( $new_exclude_edit_fields, $old_exclude_edit_fields ) ) );

			update_option( 'vi_wbe_settings', $new_options );

			wp_send_json_success( [
				'settings'     => $new_options,
				'fieldsChange' => $edit_fields_compare || $exclude_edit_fields_compare
			] );
		}

	}

	public function add_new_product() {
		if ( empty( $_POST['product_name'] ) ) {
			return;
		}
		$product_name = sanitize_text_field( wp_unslash( $_POST['product_name'] ) );
		$product      = new \WC_Product();
		$product->set_name( $product_name );
		$pid            = $product->save();
		$product        = wc_get_product( $pid );
		$handle_product = Handle_Product::instance();
		$products_data  = $handle_product->get_product_data_for_edit( $product );
		wp_send_json_success( $products_data );
	}

	public function load_products() {
		$handle_product = Handle_Product::instance();
		$filter         = Filters::instance();
		$settings       = WCBEdit_Data()->get_settings();
		$page           = ! empty( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
		$orderby        = $settings['order_by'];

		$args = [
			'posts_per_page' => 10,
			'paged'          => $page,
			'paginate'       => true,
			'order'          => $settings['order'],
			'orderby'        => 'ID',
			'status'         => array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit' ),
		];

		switch ( $orderby ) {
			case 'price':
				$args['orderby'] = [ 'meta_value_num' => $settings['order'] ];
				add_filter( 'woocommerce_product_data_store_cpt_get_products_query', [ $this, 'orderby_price' ] );
				break;

			case 'sku':
				$args['orderby']  = 'meta_value';
				$args['meta_key'] = '_sku';
				break;

			default:
				$args['orderby'] = $orderby;

				break;
		}

		remove_all_filters( 'woocommerce_product_object_query_args' );
		remove_all_filters( 'woocommerce_product_object_query' );

		$args   = $filter->set_args( $args );
		$result = wc_get_products( $args );

		remove_filter( 'woocommerce_product_data_store_cpt_get_products_query', [ $this, 'orderby_price' ] );

		$count         = $result->total;
		$max_num_pages = $result->max_num_pages;
		$products      = $result->products;

		$products_data = $pids = $img_storage = [];
		if ( $products ) {
			foreach ( $products as $product ) {
				$pid    = $product->get_id();
				$pids[] = $pid;
				$img_id = $product->get_image_id();
				$src    = wp_get_attachment_image_url( $img_id );

				if ( $src ) {
					$img_storage[ $img_id ] = $src;
				}

				$img_ids = $product->get_gallery_image_ids( 'edit' );

				if ( ! empty( $img_ids ) && is_array( $img_ids ) ) {
					foreach ( $img_ids as $img_id ) {
						$src = wp_get_attachment_image_url( $img_id );
						if ( $src ) {
							$img_storage[ $img_id ] = $src;
						}
					}
				}

				$products_data[] = $handle_product->get_product_data_for_edit( $product );

				if ( $product->get_type() == 'variable' && $settings['load_variations'] == 'yes' ) {

					if ( ! empty( $settings['save_filter'] ) && ! empty( $settings['variation_filter'] ) ) {
						add_filter( 'woocommerce_product_data_store_cpt_get_products_query', [ $this, 'filter_variation' ] );
					}

					$variations = wc_get_products(
						array(
							'status'  => array( 'private', 'publish' ),
							'type'    => 'variation',
							'parent'  => $pid,
							'limit'   => - 1,
							'orderby' => array(
								'menu_order' => 'ASC',
								'ID'         => 'DESC',
							),
							'return'  => 'objects',
						)
					);

					remove_filter( 'woocommerce_product_data_store_cpt_get_products_query', [ $this, 'filter_variation' ] );

					if ( ! empty( $variations ) ) {
						foreach ( $variations as $variation ) {
							if ( is_object( $variation ) ) {
								$img_id = $variation->get_image_id();
								$src    = wp_get_attachment_image_url( $img_id );
								if ( $src ) {
									$img_storage[ $img_id ] = $src;
								}
								$products_data[] = $handle_product->get_product_data_for_edit( $variation );
							}
						}
					}
				}
			}
		}

		$respone_data = [
			'products'      => $products_data,
			'count'         => $count,
			'max_num_pages' => $max_num_pages,
			'img_storage'   => $img_storage,
		];

		if ( ! empty( $_POST['re_create'] ) ) {
			$columns                       = WCBEdit_Data()->get_columns_type();
			$id_mapping                    = array_keys( $columns );
			$respone_data['idMapping']     = $id_mapping;
			$respone_data['idMappingFlip'] = array_flip( $id_mapping );
			$respone_data['columns']       = wp_json_encode( array_values( $columns ) );
		}

		wp_send_json_success( $respone_data );
	}

	public function add_filter_data() {
		if ( empty( $_POST['filter_data'] ) ) {
			wp_send_json_error();
		}
		wp_parse_str( $_POST['filter_data'], $filter_data );
		$filter_data = wc_clean( $filter_data );
		$user_id     = get_current_user_id();
		set_transient( "vi_wbe_filter_data_{$user_id}", $filter_data, DAY_IN_SECONDS );

		$this->load_products();
	}

	public function save_products() {
		$products    = isset( $_POST['products'] ) ? json_decode( stripslashes( $_POST['products'] ), true ) : '';
		$trash_ids   = ! empty( $_POST['trash'] ) ? wc_clean( wp_unslash( $_POST['trash'] ) ) : '';
		$untrash_ids = ! empty( $_POST['untrash'] ) ? wc_clean( wp_unslash( $_POST['untrash'] ) ) : '';

		$response = [];

		if ( $untrash_ids ) {
			array_map( 'wp_untrash_post', $untrash_ids );
		}

		$this->fields   = WCBEdit_Data()->get_fields_for_parse_product();
		$handle_product = Handle_Product::instance();

		if ( ! empty( $products ) && is_array( $products ) ) {
			foreach ( $products as $product_data ) {
				if ( empty( $product_data[0] ) ) {
					continue;
				}
				$pid = $product_data[0] ?? '';

				$product = wc_get_product( $pid );

				if ( ! is_object( $product ) ) {
					continue;
				}

				$new_product_type = $sku = '';

				foreach ( $product_data as $key => $value ) {
					$type = $this->fields[ $key ] ?? '';

					if ( ! $type || $key === 0 ) {
						continue;
					}

					if ( $type === 'sku' ) {
						$sku = $value;
					}

					$handle_product->parse_product_data_to_save( $product, $type, $value );
					if ( $type === 'product_type' && $value !== $product->get_type() ) {
						$new_product_type = $value;
					}
				}

				if ( in_array( 'sku', $this->fields ) ) {
					try {
						$current_sku = $product->get_sku();
						if ( $current_sku !== $sku ) {
							$product->set_sku( $sku );
						}
					} catch ( \Exception $e ) {
						$response['skuErrors'][] = $pid;
					}
				}

				$pid = $product->save();

				if ( $new_product_type ) { //Change product type
					if ( in_array( $new_product_type, array_keys( wc_get_product_types() ) ) ) {
						wp_set_object_terms( $pid, $new_product_type, 'product_type' );
					}
				}
				$this->call_hooks_after_product_update( $product );
			}
		}

		if ( $trash_ids ) {
			foreach ( $trash_ids as $pid ) {
				$product = wc_get_product( $pid );
				if ( $product->is_type( 'variation' ) ) {
					wp_delete_post( $pid );
				} else {
					wp_trash_post( $pid );
				}
			}
		}

		wp_send_json_success( $response );
	}

	private function call_hooks_after_product_update( &$product ) {
		$product_id = $product->get_id();
		$pp         = get_post( $product_id );
		do_action( 'save_post', $product_id, $pp, true );
		do_action( "save_post_product", $product_id, $pp, true );
		do_action( 'edit_post', $product_id, $pp );

		if ( $product->get_type() === 'variation' ) {
			do_action( 'woocommerce_update_product_variation', $product_id, $product );
		}
	}

	public function search_tags() {
		$search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$tags   = get_tags( [
			'taxonomy'   => 'product_tag',
			'search'     => $search,
			'hide_empty' => false,
		] );

		$r = [];
		if ( ! empty( $tags ) ) {
			foreach ( $tags as $tag ) {
				$r[] = [ 'id' => $tag->term_id, 'text' => $tag->name ];
			}
		}

		wp_send_json( $r );
	}

	public function search_products() {
		$products   = array();
		$term       = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$data_store = \WC_Data_Store::load( 'product' );
		$ids        = $data_store->search_products( $term, '', true, false, 30 );

		foreach ( $ids as $id ) {
			$product_object = wc_get_product( $id );

			if ( ! wc_products_array_filter_readable( $product_object ) ) {
				continue;
			}

			$formatted_name = $product_object->get_formatted_name();
			$managing_stock = $product_object->managing_stock();

			if ( $managing_stock && ! empty( $_GET['display_stock'] ) ) {
				$stock_amount = $product_object->get_stock_quantity();
				/* Translators: %d stock amount */
				$formatted_name .= ' &ndash; ' . sprintf( __( 'Stock: %d', 'woocommerce' ), wc_format_stock_quantity_for_display( $stock_amount, $product_object ) );
			}

			$products[ $product_object->get_id() ] = rawurldecode( wp_strip_all_tags( $formatted_name ) );
		}

		wp_send_json( $products );
	}

	public function add_variation() {
		if ( ! empty( $_POST['pid'] ) ) {
			$product_id       = sanitize_text_field( intval( $_POST['pid'] ) );
			$product_object   = wc_get_product_object( 'variable', $product_id ); // Forces type to variable in case product is unsaved.
			$variation_object = wc_get_product_object( 'variation' );
			$variation_object->set_parent_id( $product_id );
			$variation_object->set_attributes( array_fill_keys( array_map( 'sanitize_title', array_keys( $product_object->get_variation_attributes() ) ), '' ) );
			$variation_id   = $variation_object->save();
			$product        = wc_get_product( $variation_id );
			$handle_product = Handle_Product::instance();
			$products_data  = $handle_product->get_product_data_for_edit( $product );
			wp_send_json_success( $products_data );
		}
	}

	public function link_all_variations() {
		wc_maybe_define_constant( 'WC_MAX_LINKED_VARIATIONS', 50 );
		wc_set_time_limit( 0 );

		$post_id = isset( $_POST['pid'] ) ? intval( $_POST['pid'] ) : 0;

		if ( ! $post_id ) {
			wp_die();
		}

		$product    = wc_get_product( $post_id );
		$data_store = $product->get_data_store();

		if ( ! is_callable( array( $data_store, 'create_all_product_variations' ) ) ) {
			wp_die();
		}

		$data_store->create_all_product_variations( $product, Constants::get_constant( 'WC_MAX_LINKED_VARIATIONS' ) );
		$data_store->sort_all_product_variations( $product->get_id() );

		$products_data = [];
		if ( ! empty( $this->variation_ids ) ) {
			$handle_product = Handle_Product::instance();
			foreach ( $this->variation_ids as $vid ) {
				$product         = wc_get_product( $vid );
				$products_data[] = $handle_product->get_product_data_for_edit( $product );
			}
		}
		wp_send_json_success( $products_data );
	}

	public function get_linked_variations( $variation_id ) {
		$this->variation_ids[] = $variation_id;
	}

	public function auto_save_revision() {
		wp_send_json_success( [ 'pages' => 1, 'updatePage' => '' ] );
	}

	public function view_history_point() {
		if ( ! empty( $_POST['id'] ) ) {
			$history_id = absint( $_POST['id'] );
			$r          = History::instance()->compare_history_point_and_current( $history_id );
			wp_send_json_success( $r );
		}
	}

	public function recover_history() {
		$recover_id = ! empty( $_POST['id'] ) ? absint( $_POST['id'] ) : '';
		if ( $recover_id ) {
			$history = History::instance()->get_history_by_id( $recover_id );
			if ( $history ) {
				wp_send_json_success( $history );
			}
		}
	}

	public function revert_history_single_product() {
		$pid        = ! empty( $_POST['pid'] ) ? absint( $_POST['pid'] ) : '';
		$history_id = ! empty( $_POST['history_id'] ) ? absint( $_POST['history_id'] ) : '';

		if ( $pid && $history_id ) {
			$product = wc_get_product( $pid );

			if ( ! is_object( $product ) ) {
				wp_send_json_error( [ 'message' => esc_html__( 'Product is not exist', 'bulky-bulk-edit-products-for-woo' ) ] );
			}

			History::instance()->revert_single_product( $product, $history_id );
		}

		wp_send_json_success();
	}

	public function revert_history_all_products() {
		$history_id = ! empty( $_POST['history_id'] ) ? absint( $_POST['history_id'] ) : '';
		if ( ! $history_id ) {
			wp_send_json_error( [ 'message' => esc_html__( 'No history id', 'bulky-bulk-edit-products-for-woo' ) ] );
		}
		History::instance()->revert_history_all_products( $history_id );
	}

	public function revert_history_product_attribute() {
		$pid        = ! empty( $_POST['pid'] ) ? absint( $_POST['pid'] ) : '';
		$history_id = ! empty( $_POST['history_id'] ) ? absint( $_POST['history_id'] ) : '';
		$attribute  = ! empty( $_POST['attribute'] ) ? sanitize_text_field( wp_unslash( $_POST['attribute'] ) ) : '';

		if ( $pid && $history_id && $attribute ) {
			$product = wc_get_product( $pid );

			if ( ! is_object( $product ) ) {
				wp_send_json_error( [ 'message' => esc_html__( 'Product is not exist', 'bulky-bulk-edit-products-for-woo' ) ] );
			}

			History::instance()->revert_history_product_attribute( $product, $history_id, $attribute );

		}

		wp_send_json_success();
	}

	public function load_history_page() {
		$page = ! empty( $_POST['page'] ) ? absint( $_POST['page'] ) : '';
		if ( $page ) {
			History::instance()->get_history_page( $page );
		}
	}

	public function add_new_attribute() {
		if ( current_user_can( 'manage_product_terms' ) && isset( $_POST['taxonomy'], $_POST['term'] ) ) {
			$taxonomy = sanitize_text_field( wp_unslash( $_POST['taxonomy'] ) );
			$term     = wc_clean( wp_unslash( $_POST['term'] ) );

			if ( taxonomy_exists( $taxonomy ) ) {

				$result = wp_insert_term( $term, $taxonomy );

				if ( is_wp_error( $result ) ) {
					wp_send_json_error( [ 'message' => $result->get_error_message(), ] );
				} else {
					$term = get_term_by( 'id', $result['term_id'], $taxonomy );
					wp_send_json_success( [
							'term_id' => $term->term_id,
							'name'    => $term->name,
							'slug'    => $term->slug,
						]
					);
				}
			}
		}
	}


	private function duplicate_product() {
		$pid     = absint( $_POST['product_id'] ?? '' );
		$product = wc_get_product( $pid );

		if ( ! $product ) {
			wp_send_json_error();
		}

		$duplicate = $this->product_duplicate( $product );
		wp_send_json_success( $duplicate );
	}

	private function product_duplicate( $product ) {
		$products_data  = [];
		$handle_product = Handle_Product::instance();
		$settings       = WCBEdit_Data()->get_settings();
		$load_variation = $settings['load_variations'] == 'yes';

		$meta_to_exclude = array_filter(
			apply_filters( 'woocommerce_duplicate_product_exclude_meta', array(), array_map( function ( $datum ) {
				return $datum->key;
			}, $product->get_meta_data() ) )
		);

		$duplicate = clone $product;
		$duplicate->set_id( 0 );
		/* translators: %s contains the name of the original product. */
		$duplicate->set_name( sprintf( esc_html__( '%s (Copy)', 'woocommerce' ), $duplicate->get_name() ) );
		$duplicate->set_total_sales( 0 );
		if ( '' !== $product->get_sku( 'edit' ) ) {
			$duplicate->set_sku( wc_product_generate_unique_sku( 0, $product->get_sku( 'edit' ) ) );
		}
		$duplicate->set_status( 'draft' );
		$duplicate->set_date_created( null );
		$duplicate->set_slug( '' );
		$duplicate->set_rating_counts( 0 );
		$duplicate->set_average_rating( 0 );
		$duplicate->set_review_count( 0 );

		foreach ( $meta_to_exclude as $meta_key ) {
			$duplicate->delete_meta_data( $meta_key );
		}

		do_action( 'woocommerce_product_duplicate_before_save', $duplicate, $product );

		// Save parent product.
		$duplicate->save();

		// Duplicate children of a variable product.
		if ( ! apply_filters( 'woocommerce_duplicate_product_exclude_children', false, $product ) && $product->is_type( 'variable' ) ) {
			foreach ( $product->get_children() as $child_id ) {
				$child           = wc_get_product( $child_id );
				$child_duplicate = clone $child;
				$child_duplicate->set_parent_id( $duplicate->get_id() );
				$child_duplicate->set_id( 0 );
				$child_duplicate->set_date_created( null );

				// If we wait and let the insertion generate the slug, we will see extreme performance degradation
				// in the case where a product is used as a template. Every time the template is duplicated, each
				// variation will query every consecutive slug until it finds an empty one. To avoid this, we can
				// optimize the generation ourselves, avoiding the issue altogether.
				$this->generate_unique_slug( $child_duplicate );

				if ( '' !== $child->get_sku( 'edit' ) ) {
					$child_duplicate->set_sku( wc_product_generate_unique_sku( 0, $child->get_sku( 'edit' ) ) );
				}

				foreach ( $meta_to_exclude as $meta_key ) {
					$child_duplicate->delete_meta_data( $meta_key );
				}

				do_action( 'woocommerce_product_duplicate_before_save', $child_duplicate, $child );

				$child_duplicate->save();

				if ( $load_variation ) {
					$products_data[] = $handle_product->get_product_data_for_edit( $child_duplicate );
				}
			}

			// Get new object to reflect new children.
			$duplicate = wc_get_product( $duplicate->get_id() );
		}

		array_unshift( $products_data, $handle_product->get_product_data_for_edit( $duplicate ) );

		return $products_data;
	}

	private function generate_unique_slug( $product ) {
		global $wpdb;

		$root_slug = preg_replace( '/-[0-9]+$/', '', $product->get_slug() );

		$results = $wpdb->get_results(
			$wpdb->prepare( "SELECT post_name FROM $wpdb->posts WHERE post_name LIKE %s AND post_type IN ( 'product', 'product_variation' )", $root_slug . '%' )
		);

		// The slug is already unique!
		if ( empty( $results ) ) {
			return;
		}

		// Find the maximum suffix so we can ensure uniqueness.
		$max_suffix = 1;
		foreach ( $results as $result ) {
			// Pull a numerical suffix off the slug after the last hyphen.
			$suffix = intval( substr( $result->post_name, strrpos( $result->post_name, '-' ) + 1 ) );
			if ( $suffix > $max_suffix ) {
				$max_suffix = $suffix;
			}
		}

		$product->set_slug( $root_slug . '-' . ( $max_suffix + 1 ) );
	}

	public function orderby_price( $query ) {
		$query['meta_query'] = [
			'relation' => 'OR',
			[
				'key'     => '_price',
				'compare' => 'EXISTS',
			],
			[
				'key'     => '_price',
				'compare' => 'NOT EXISTS',
			]
		];

		return $query;
	}

	public function filter_variation( $query ) {
		$user_id     = get_current_user_id();
		$load_filter = get_transient( "vi_wbe_filter_data_{$user_id}" );

		if ( ! empty( $load_filter['taxonomies'] ) ) {
			$f_taxonomies = $load_filter['taxonomies'];

			foreach ( $f_taxonomies as $taxonomy => $terms ) {
				$terms = array_filter( $terms );
				if ( 'pa_' !== substr( $taxonomy, 0, 3 ) || empty( $terms ) ) {
					continue;
				}

				foreach ( $terms as $term ) {
					$query['meta_query'][] = [
						'key'     => 'attribute_' . $taxonomy,
						'value'   => $term,
						'compare' => '=',
					];
				}
			}
		}

		if ( isset( $load_filter['stock_status'] ) && $load_filter['stock_status'] !== '' ) {
			$query['meta_query'][] = [
				'key'     => '_stock_status',
				'value'   => $load_filter['stock_status'],
				'compare' => '=',
			];
		}

		$this->parse_variation_filter_meta( $query, $load_filter, 'weight_from', '_weight', '>=' );
		$this->parse_variation_filter_meta( $query, $load_filter, 'weight_to', '_weight', '<=' );

		$this->parse_variation_filter_meta( $query, $load_filter, 'length_from', '_length', '>=' );
		$this->parse_variation_filter_meta( $query, $load_filter, 'length_to', '_length', '<=' );

		$this->parse_variation_filter_meta( $query, $load_filter, 'width_from', '_width', '>=' );
		$this->parse_variation_filter_meta( $query, $load_filter, 'width_to', '_width', '<=' );

		$this->parse_variation_filter_meta( $query, $load_filter, 'height_from', '_height', '>=' );
		$this->parse_variation_filter_meta( $query, $load_filter, 'height_to', '_height', '<=' );

		$this->parse_variation_filter_meta( $query, $load_filter, 'stock_quantity_from', '_stock', '>=' );
		$this->parse_variation_filter_meta( $query, $load_filter, 'stock_quantity_to', '_stock', '<=' );

		$this->parse_variation_filter_meta( $query, $load_filter, 'regular_price_from', '_regular_price', '>=' );
		$this->parse_variation_filter_meta( $query, $load_filter, 'regular_price_to', '_regular_price', '<=' );

		$this->parse_variation_filter_meta( $query, $load_filter, 'sale_price_from', '_sale_price', '>=' );
		$this->parse_variation_filter_meta( $query, $load_filter, 'sale_price_to', '_sale_price', '<=' );

//		$this->parse_variation_filter_meta_time( $query, $load_filter, 'sale_date_from', '_sale_price_dates_from', '>=' );
//		$this->parse_variation_filter_meta_time( $query, $load_filter, 'sale_date_to', '_sale_price_dates_to', '<=' );

		if ( ! empty( $query['meta_query'] ) ) {
			$query['meta_query']['relation'] = 'AND';
		}

		return $query;
	}

	private function parse_variation_filter_meta_time( &$query, $load_filter, $value, $key, $compare = '=' ) {

		if ( isset( $load_filter[ $value ] ) && $load_filter[ $value ] !== '' ) {
			$time = 0;
			if ( $key === '_sale_price_dates_from' ) {
				$time = strtotime( $load_filter[ $value ] );
			}

			if ( $key === '_sale_price_dates_to' ) {
				$time = strtotime( "tomorrow {$load_filter[ $value ]}" ) - 1;
			}

			$query['meta_query'][] = [
				'key'     => $key,
				'value'   => $time,
				'compare' => $compare,
			];
		}
	}

	public function parse_variation_filter_meta( &$query, $load_filter, $value, $key, $compare = '=' ) {
		if ( isset( $load_filter[ $value ] ) && $load_filter[ $value ] !== '' ) {
			$query['meta_query'][] = [
				'key'     => $key,
				'value'   => floatval( $load_filter[ $value ] ),
				'compare' => $compare,
				'type'    => 'NUMERIC',
			];
		}
	}
}



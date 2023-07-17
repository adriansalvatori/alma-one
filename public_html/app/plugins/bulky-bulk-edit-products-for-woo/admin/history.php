<?php

namespace WCBEditor\Admin;

defined( 'ABSPATH' ) || exit;

class History {

	protected static $instance = null;
	protected $wpdb;
	protected $table;
	protected $limit = 5;

	public function __construct() {
		global $wpdb;
		$this->wpdb  = $wpdb;
		$this->table = $wpdb->prefix . 'vi_wbe_history';

		if ( ! wp_next_scheduled( 'vi_wbe_remove_revision' ) ) {
			wp_schedule_event( time(), 'daily', 'vi_wbe_remove_revision' );
		}

		add_action( 'vi_wbe_remove_revision', array( $this, 'remove_revision' ) );
	}

	public static function instance() {
		return self::$instance == null ? self::$instance = new self() : self::$instance;
	}

	public function create_database_table() {
		$collate = $this->wpdb->has_cap( 'collation' ) ? $this->wpdb->get_charset_collate() : '';
		$query   = "CREATE TABLE IF NOT EXISTS {$this->table} 
					(`id` int(11) NOT NULL AUTO_INCREMENT, `date` int(16) NOT NULL, `user_id` int(11) NOT NULL,`history` longtext, PRIMARY KEY  (`id`)) {$collate}";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $query );
	}

	public function remove_revision() {
		$time  = current_time( 'U' ) - floatval( WCBEdit_Data()->get_setting( 'auto_remove_revision' ) ) * DAY_IN_SECONDS;
		$query = "delete from {$this->table} where date < %d";
		$this->wpdb->query( $this->wpdb->prepare( $query, $time ) );
	}

	public function set( $data ) {
		if ( empty( $data ) ) {
			return;
		}
		$user_id = get_current_user_id();
		$date    = current_time( 'U' );
		$query   = "insert into {$this->table} (user_id, date, history) values (%d,%d,%s)";
		$this->wpdb->query( $this->wpdb->prepare( $query, $user_id, $date, maybe_serialize( $data ) ) );
	}

	public function get() {
		$query  = "select id,date,user_id from {$this->table} order by id desc limit {$this->limit}";
		$result = $this->wpdb->get_results( $query, ARRAY_A );

		return $result;
	}

	public function count_history_pages() {
		$query  = "select count(id) from {$this->table}";
		$result = $this->wpdb->get_var( $query );
		$result = ceil( $result / $this->limit );

		return $result;
	}

	public function get_history_by_id( $id ) {
		$query           = "select history,date from {$this->table} where id=%d";
		$result          = $this->wpdb->get_row( $this->wpdb->prepare( $query, $id ) );
		$result->history = maybe_unserialize( $result->history );

		return $result;
	}

	public function compare_history_point_and_current( $id ) {
		$full_history = $this->get_history_by_id( $id );
		$products     = $full_history->history;
		$columns      = WCBEdit_Data()->define_columns_type();

		if ( ! empty( $products ) && is_array( $products ) ) {
			$r = [];
			foreach ( $products as $pid => $history ) {
				$product = wc_get_product( $pid );
				if ( ! is_object( $product ) ) {
					continue;
				}

				$fields  = array_keys( $history );
				$current = Handle_Product::instance()->get_product_data( $product, $fields );
				$current = array_combine( $fields, $current );

				$fields_parsed = [];
				foreach ( $fields as $key ) {
					$fields_parsed[ $key ] = $columns[ $key ]['title'] ?? '';
				}

				$r[ $pid ] = [
					'name'    => $product->get_name(),
					'fields'  => $fields_parsed,
					'history' => $history,
					'current' => $current,
				];
			}
		}

		return [ 'compare' => $r ?? '', 'date' => date_i18n( wc_date_format() . ' ' . wc_time_format(), $full_history->date ) ];
	}

	public function revert_single_product( \WC_Product $product, $history_id ) {
		$history         = $this->get_history_by_id( $history_id )->history;
		$pid             = $product->get_id();
		$product_history = $history[ $pid ] ?? '';

		if ( ! empty( $product_history ) && is_array( $product_history ) ) {
			$handle = Handle_Product::instance();
			foreach ( $product_history as $type => $value ) {
				$handle->parse_product_data_to_save( $product, $type, $value );
			}

			$product->save();
		}
	}

	public function revert_history_all_products( $history_id ) {
		$history = $this->get_history_by_id( $history_id )->history;

		if ( ! empty( $history ) && is_array( $history ) ) {
			$handle = Handle_Product::instance();

			foreach ( $history as $pid => $data ) {
				$product = wc_get_product( $pid );

				if ( ! is_object( $product ) ) {
					continue;
				}

				if ( ! empty( $data ) && is_array( $data ) ) {
					foreach ( $data as $type => $value ) {
						$handle->parse_product_data_to_save( $product, $type, $value );
					}
				}

				$product->save();
			}
		}
	}

	public function revert_history_product_attribute( \WC_Product $product, $history_id, $attribute ) {
		$history = $this->get_history_by_id( $history_id )->history;
		$pid     = $product->get_id();
		if ( isset( $history[ $pid ][ $attribute ] ) ) {
			$handle = Handle_Product::instance();
			$handle->parse_product_data_to_save( $product, $attribute, $history[ $pid ][ $attribute ] );
			$product->save();
		}
	}

	public function get_history_page( $page = 1 ) {
		$offset    = ( $page - 1 ) * $this->limit;
		$query     = "select id,date,user_id from {$this->table} order by id desc limit {$offset}, {$this->limit}";
		$histories = $this->wpdb->get_results( $query, ARRAY_A );

		if ( ! empty( $histories ) ) {
			foreach ( $histories as $history ) {
				$user = get_user_by( 'ID', $history['user_id'] );
				printf( '<tr>
								    <td>%s</td>
								    <td>%s</td>
								    <td class="">
								        <div class="vi-wbe-action-col">
								            <button type="button" class="vi-ui button basic mini vi-wbe-view-history-point" data-id="%s">
								                <i class="icon eye"> </i>
								            </button>
								            <button type="button" class="vi-ui button basic mini vi-wbe-recover" data-id="%s">
								                <i class="icon undo"> </i>
								            </button>
								        </div>
								    </td>
								</tr>',
					esc_html( date_i18n( wc_date_format() . ' ' . wc_time_format(), $history['date'] ) ),
					esc_html( $user->__get( 'display_name' ) ), esc_attr( $history['id'] ), esc_attr( $history['id'] ) );
			}
		}
	}


}

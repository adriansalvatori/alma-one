<?php

namespace Objectiv\Plugins\Checkout\Features;

use Exception;
use Objectiv\Plugins\Checkout\Admin\Pages\PageAbstract;
use Objectiv\Plugins\Checkout\Managers\SettingsManager;
use stdClass;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use WP_Roles;

class AbandonedCartRecovery extends FeaturesAbstract {
	protected $table_name;

	protected function run_if_cfw_is_enabled() {
		add_action( 'cfw_checkout_update_order_review', array( $this, 'maybe_track_abandoned_cart' ) );
		add_action( 'cfw_acr_check_carts', array( $this, 'mark_abandoned_carts_and_schedule_emails' ) );
		add_action( 'cfw_acr_mark_lost', array( $this, 'mark_lost_cart' ) );
		add_action( 'cfw_acr_send_email', array( $this, 'send_email' ), 10, 2 );

		if ( ! \as_has_scheduled_action( 'cfw_acr_check_carts', array(), 'checkoutwc' ) ) {
			\as_schedule_recurring_action( time(), 60 * 5, 'cfw_acr_check_carts', array(), 'checkoutwc' );
		}

		add_action( 'cfw_do_plugin_deactivation', function () {
			\as_unschedule_all_actions( 'cfw_acr_check_carts' );
			\as_unschedule_all_actions( 'cfw_acr_send_email' );
			\as_unschedule_all_actions( 'cfw_acr_mark_lost' );
		} );

		if ( isset( $_GET['cfw_acr_unsubscribe'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->process_unsubscribe();
		}

		// Watch for recovered carts
		add_action( 'woocommerce_new_order', array( $this, 'maybe_recover_cart' ) );
		add_action( 'woocommerce_thankyou', array( $this, 'maybe_recover_cart' ) );
		add_action( 'woocommerce_order_status_changed', array( $this, 'handle_order_status_change' ), 999, 3 );
		add_action( 'wp', array( $this, 'handle_email_link_click' ) );

		// Coupon restriction
		add_action( 'woocommerce_coupon_options_usage_restriction', array( $this, 'add_coupon_restriction' ), 10, 2 );
		add_action( 'woocommerce_coupon_options_save', array( $this, 'save_coupon_restriction' ), 10, 2 );
		add_action( 'woocommerce_coupon_get_email_restrictions', array( $this, 'get_coupon_restriction' ), 10, 2 );
	}

	protected function process_unsubscribe() {
		global $wpdb;

		$cart_id = (int) $_GET['cfw_acr_unsubscribe'] ?? 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $cart_id ) {
			return;
		}

		$wpdb->update(
			$this->table_name,
			array(
				'status' => 'unsubscribed',
			),
			array(
				'id' => $cart_id,
			)
		);

		// Unscheduled emails for this specific cart
		$this->unschedule_all_emails_for_cart( $cart_id );

		// Unscheduled mark as lost
		$this->unschedule_mark_as_lost( $cart_id );

		/**
		 * Filter the message shown when a user unsubscribes from cart reminder emails.
		 *
		 * @since 9.0.0
		 * @var string $unsubscribe_notice The message to show.
		 */
		$unsubscribe_notice = apply_filters(
			'cfw_unsubscribe_successful_message',
			__( 'You have been unsubscribed from our cart reminder emails.', 'checkout-wc' )
		);

		wp_die( esc_html( $unsubscribe_notice ) );
	}

	public function init() {
		parent::init();

		global $wpdb;

		$this->table_name = $wpdb->prefix . 'cfw_acr_carts';

		add_action( 'cfw_do_plugin_activation', array( $this, 'run_on_plugin_activation' ) );
		add_action( 'cfw_acr_activate', array( $this, 'run_on_plugin_activation' ) );

		$this->register_post_type();
		$this->map_capabilities();
	}

	public static function get_post_type(): string {
		return 'cfw_acr_emails';
	}

	public function register_post_type() {
		$labels = array(
			'name'               => cfw__( 'Emails', 'checkout-wc' ),
			'singular_name'      => cfw__( 'Email', 'checkout-wc' ),
			'menu_name'          => cfw_x( 'Emails', 'Admin menu name', 'checkout-wc' ),
			'add_new'            => cfw__( 'Add Email', 'checkout-wc' ),
			'add_new_item'       => cfw__( 'Add New Email', 'checkout-wc' ),
			'edit'               => cfw__( 'Edit', 'checkout-wc' ),
			'edit_item'          => cfw__( 'Edit Email', 'checkout-wc' ),
			'new_item'           => cfw__( 'New Email', 'checkout-wc' ),
			'view'               => cfw__( 'View Emails', 'checkout-wc' ),
			'view_item'          => cfw__( 'View Email', 'checkout-wc' ),
			'search_items'       => cfw__( 'Search Emails', 'checkout-wc' ),
			'not_found'          => cfw__( 'No Email found', 'checkout-wc' ),
			'not_found_in_trash' => cfw__( 'No Emails found in trash', 'checkout-wc' ),
		);

		$post_type_args = array(
			'labels'              => $labels,
			'description'         => cfw__( 'This is where you can add new Abandoned Cart Recovery Emails.', 'checkout-wc' ),
			'public'              => false,
			'show_ui'             => true,
			'capability_type'     => self::get_post_type(),
			'map_meta_cap'        => true,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_in_menu'        => PageAbstract::get_parent_slug(),
			'hierarchical'        => false,
			'rewrite'             => false,
			'query_var'           => false,
			'supports'            => array(
				'title',
				'editor',
			),
			'show_in_nav_menus'   => false,
		);

		register_post_type( self::get_post_type(), $post_type_args );
	}

	public function map_capabilities() {
		global $wp_roles;

		if ( ! $wp_roles instanceof WP_Roles && class_exists( 'WP_Roles' ) ) {
			// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
			$wp_roles = new WP_Roles();
			// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		if ( ! is_object( $wp_roles ) ) {
			return;
		}

		$args                  = new stdClass();
		$args->map_meta_cap    = true;
		$args->capability_type = self::get_post_type();
		$args->capabilities    = array();

		foreach ( (array) get_post_type_capabilities( $args ) as $mapped ) {
			$wp_roles->add_cap( 'shop_manager', $mapped );
			$wp_roles->add_cap( 'administrator', $mapped );
		}

		$wp_roles->add_cap( 'shop_manager', 'manage_cfw_acr_emails' );
		$wp_roles->add_cap( 'administrator', 'manage_cfw_acr_emails' );
	}

	public function run_on_plugin_activation() {
		SettingsManager::instance()->add_setting( 'enable_acr', 'no' );
		SettingsManager::instance()->add_setting( 'acr_abandoned_time', 15 );
		SettingsManager::instance()->add_setting( 'acr_excluded_roles', array() );
		SettingsManager::instance()->add_setting( 'acr_recovered_order_statuses', array(
			'wc-processing',
			'wc-completed'
		) );
		SettingsManager::instance()->add_setting( 'acr_from_name', get_bloginfo( 'name' ) );
		SettingsManager::instance()->add_setting( 'acr_from_address', get_option( 'admin_email' ) );
		SettingsManager::instance()->add_setting( 'acr_reply_to_address', get_option( 'admin_email' ) );

		$sql = "CREATE TABLE {$this->table_name} (
		  `id` bigint(20) NOT NULL AUTO_INCREMENT,
		  `email` varchar(254) NOT NULL,
		  `first_name` varchar(254) NOT NULL,
		  `last_name` varchar(254) NOT NULL,
		  `cart` longtext NOT NULL,
		  `fields` longtext NOT NULL,
		  `subtotal` decimal(26,8) NOT NULL,
		  `status` varchar(254) NOT NULL,
		  `wp_user` bigint(20) NOT NULL,
		  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  `emails_sent` int(11) NOT NULL DEFAULT 0,
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public function track_cart( string $email, array $cart_contents, float $subtotal, string $first_name, string $last_name, array $fields ) {
		global $wpdb;

		if ( count( $this->get_emails() ) === 0 ) {
			return;
		}

		$cart_id = WC()->session->get( 'cfw_acr_cart_id', false );

		// If the cart already exists, update it
		// Unless it's already been marked as abandoned / lost / recovered
		if ( $cart_id ) {
			$wpdb->update(
				$this->table_name,
				array(
					'email'      => $email,
					'cart'       => wp_json_encode( $cart_contents ),
					'subtotal'   => $subtotal,
					'first_name' => $first_name,
					'last_name'  => $last_name,
					'fields'     => wp_json_encode( $fields ),
					'wp_user'    => is_user_logged_in() ? get_current_user_id() : 0,
				),
				array(
					'id'     => $cart_id,
					'status' => 'new',
				)
			);

			return;
		}

		// Check for existing cart for user by email
		$carts_matching_customer = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE email = %s AND ( status = 'new' OR status = 'abandoned' )",
				$email
			)
		);

		if ( count( $carts_matching_customer ) > 0 ) {
			return;
		}

		// Otherwise add the cart
		$wpdb->insert(
			$this->table_name,
			array(
				'email'      => $email,
				'cart'       => wp_json_encode( $cart_contents ),
				'subtotal'   => $subtotal,
				'first_name' => $first_name,
				'last_name'  => $last_name,
				'status'     => 'new',
				'fields'     => wp_json_encode( $fields ),
				'wp_user'    => is_user_logged_in() ? get_current_user_id() : 0,
			)
		);

		// Get the cart ID
		$cart_id = $wpdb->insert_id;

		// Set the cart ID to the WC session
		WC()->session->set( 'cfw_acr_cart_id', $cart_id );
	}

	public function maybe_track_abandoned_cart( $post_data ) {
		if ( ! is_array( $post_data ) ) {
			parse_str( $post_data, $post_data );
		}

		$allowed_fields = array(
			'billing_email',
			'country',
			'state',
			'postcode',
			'city',
			'address',
			'address_2',
			's_country',
			's_state',
			's_postcode',
			's_city',
			's_address',
			's_address_2',
			'shipping_phone',
			'billing_phone',
			'billing_first_name',
			'billing_last_name',
		);

		// Filter out any fields that are not allowed
		$fields = array_filter( $_POST, function ( $key ) use ( $allowed_fields ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return in_array( $key, $allowed_fields, true );
		}, ARRAY_FILTER_USE_KEY );

		// If user role is excluded, don't track
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();

			if ( in_array( $user->roles[0], (array) SettingsManager::instance()->get_setting( 'acr_excluded_roles' ), true ) ) {
				return;
			}
		}

		if ( empty( $post_data['billing_email'] ) ) {
			return;
		}

		$this->track_cart(
			$post_data['billing_email'],
			WC()->cart->get_cart(),
			WC()->cart->get_subtotal(),
			$post_data['billing_first_name'] ?? $post_data['shipping_first_name'] ?? '',
			$post_data['billing_last_name'] ?? $post_data['shipping_last_name'] ?? '',
			$fields
		);
	}

	public function mark_abandoned_carts_and_schedule_emails() {
		global $wpdb;

		// Check for carts that are older than the abandoned time
		$abandoned_time = SettingsManager::instance()->get_setting( 'acr_abandoned_time' );

		if ( ! $abandoned_time || ! ( intval( $abandoned_time ) > 0 ) ) {
			return;
		}

		// Build query for carts that are older than abandoned time and status new
		$newly_abandoned_carts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, email FROM `{$this->table_name}` WHERE created < DATE_SUB(NOW(), INTERVAL %d MINUTE) AND status = 'new';", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$abandoned_time
			)
		);

		// Schedule first email
		$first_email = current( $this->get_emails() );

		// Update carts to abandoned status and schedule emails
		foreach ( $newly_abandoned_carts as $cart ) {
			if ( empty( $first_email ) ) {
				$wpdb->update(
					$this->table_name,
					array(
						'status' => 'ineligible',
					),
					array(
						'id' => $cart->id,
					)
				);

				continue;
			}

			$wpdb->update(
				$this->table_name,
				array(
					'status' => 'abandoned',
				),
				array(
					'id' => $cart->id,
				)
			);

			$starting_time = time() + $first_email->interval;

			\as_schedule_single_action(
				$starting_time,
				'cfw_acr_send_email',
				array(
					'cart'     => $cart->id,
					'template' => $first_email->ID,
				),
				'checkoutwc_' . $cart->id
			);
		}
	}

	public function mark_lost_cart( $cart_id ) {
		global $wpdb;

		$wpdb->update(
			$this->table_name,
			array(
				'status' => 'lost',
			),
			array(
				'id' => $cart_id,
			)
		);
	}

	/**
	 * Send the email
	 *
	 * @param int $cart_id
	 * @param int $email_id
	 *
	 * @throws Exception
	 */
	public function send_email( $cart_id, $email_id ) {
		global $wpdb;

		if ( ! $cart_id || ! $email_id ) {
			return;
		}

		$cart = $this->get_tracked_cart( $cart_id );

		if ( empty( $cart ) ) {
			return;
		}

		$email             = get_post( $email_id );
		$subject           = get_post_meta( $email->ID, 'cfw_subject', true );
		$preheader         = get_post_meta( $email->ID, 'cfw_preheader', true );
		$use_wc_template   = get_post_meta( $email->ID, 'cfw_use_wc_template', true );
		$raw_content       = wpautop( $email->post_content );
		$content           = cfw_get_email_template( $subject, $preheader, $raw_content );
		$cssToInlineStyles = new CssToInlineStyles();
		$content           = $this->process_replacements( $content, $cart, $email->ID );
		$content           = $cssToInlineStyles->convert( $content, cfw_get_email_stylesheet() );

		// Send email
		$from_name    = SettingsManager::instance()->get_setting( 'acr_from_name' );
		$from_address = SettingsManager::instance()->get_setting( 'acr_from_address' );
		$reply_to     = SettingsManager::instance()->get_setting( 'acr_reply_to_address' );

		$headers = array(
			'From: ' . $from_name . ' <' . $from_address . '>',
			'Reply-To: ' . $reply_to,
			'Content-Type: text/html; charset=UTF-8',
		);

		if ( $use_wc_template ) {
			$mailer = WC()->mailer();
			$body   = cfw_get_email_body( $preheader, $raw_content );
			$body   = $cssToInlineStyles->convert( $body, cfw_get_email_stylesheet() );
			$body   = $this->process_replacements( $body, $cart, $email->ID );
			$body   = cfw_wc_wrap_message( $subject, $body );
			$sent   = $mailer->send( $cart->email, $subject, $body, $headers );
		} else {
			$sent = wp_mail( $cart->email, $subject, $content, $headers );
		}

		// Update emails_sent
		$wpdb->update(
			$this->table_name,
			array(
				'emails_sent' => $cart->emails_sent + 1,
			),
			array(
				'id' => $cart->id,
			)
		);

		if ( ! $sent ) {
			wc_get_logger()->error( 'Failed to send abandoned cart email to ' . $cart->email, array( 'source' => 'checkout-wc' ) );
		}

		// Schedule next email
		$next_email = $this->get_next_email( $email_id );

		if ( empty( $next_email ) ) {
			// Schedule cart to be marked as lost
			\as_schedule_single_action(
				time() + DAY_IN_SECONDS * 30,
				'cfw_acr_mark_lost',
				array(
					'cart' => $cart->id,
				),
				'checkoutwc_lost_' . $cart->id
			);

			return;
		}

		$next_email = current( $next_email );

		\as_schedule_single_action(
			$cart->created_unix + $next_email->interval,
			'cfw_acr_send_email',
			array(
				'cart'     => $cart->id,
				'template' => $next_email->ID,
			),
			'checkoutwc_' . $cart->id
		);
	}

	public function get_emails(): array {
		$raw_emails = get_posts( array(
			'post_type'      => self::get_post_type(),
			'post_status'    => 'publish',
			'posts_per_page' => - 1,
		) );

		$emails = array();

		foreach ( $raw_emails as $email ) {
			$email_object           = new stdClass();
			$email_object->ID       = $email->ID;
			$email_object->interval = get_post_meta( $email->ID, 'cfw_acr_email_interval', true );
			$emails[ $email->ID ]   = $email_object;
		}

		// Sort by interval, ascending
		uasort( $emails, function ( $a, $b ) {
			return $a->interval - $b->interval;
		} );

		return $emails;
	}

	public function get_next_email( $email_id ): array {
		$emails = $this->get_emails();

		// Slice off emails before the current email
		$future_emails = array_slice( $emails, array_search( $email_id, array_keys( $emails ), true ) + 1 );

		if ( empty( $future_emails ) ) {
			return array();
		}

		return array( $future_emails[0]->ID => $future_emails[0] );
	}

	/**
	 * Process replacements in email content
	 *
	 * @param string $content
	 * @param stdClass $cart
	 * @param int $email_id
	 *
	 * @throws Exception
	 */
	public function process_replacements( string $content, stdClass $cart, int $email_id ): string {
		// Cleanup double URLs
		$content = str_replace( 'http://{{checkout_url}}', '{{checkout_url}}', $content );
		$content = str_replace( 'https://{{checkout_url}}', '{{checkout_url}}', $content );
		$content = str_replace( 'http://{{unsubscribe_url}}', '{{unsubscribe_url}}', $content );
		$content = str_replace( 'https://{{unsubscribe_url}}', '{{unsubscribe_url}}', $content );

		$replacements = array(
			'site_name'           => get_bloginfo( 'name' ),
			'cart_products_table' => $this->get_cart_table( $cart->cart ),
			'checkout_url'        => $this->getCheckoutURL( $cart, $email_id ),
			'customer_firstname'  => $cart->first_name ? $cart->first_name : 'there',
			'customer_lastname'   => $cart->last_name ? $cart->last_name : '',
			'customer_full_name'  => trim( $cart->first_name . ' ' . $cart->last_name ),
			'cart_abandoned_date' => ( new \DateTime( $cart->created, wp_timezone() ) )->format( 'F j, Y' ),
			'site_url'            => esc_url( add_query_arg(
				array(
					'utm_source'   => 'CheckoutWC ACR',
					'utm_medium'   => 'email',
					'utm_campaign' => 'abandoned_cart',
					'utm_term'     => $email_id,
				),
				get_bloginfo( 'url' )
			) ),
			'unsubscribe_url'     => esc_url( add_query_arg(
				array(
					'cfw_acr_unsubscribe' => $cart->id,
				),
				get_bloginfo( 'url' )
			) ),
		);

		$content = preg_replace_callback( '/{{checkout_button( label=\"([^\"]*)\")?}}/', array(
			$this,
			'replace_complete_order_button'
		), $content );

		foreach ( $replacements as $key => $value ) {
			$content = str_replace( '{{' . $key . '}}', $value, $content );
		}

		return $content;
	}

	public function getCheckoutURL( stdClass $cart, $email_id ): string {
		return esc_url( add_query_arg(
			array(
				'cfw_acr_cart_id' => $cart->id ?? 0,
				'utm_source'      => 'CheckoutWC ACR',
				'utm_medium'      => 'email',
				'utm_campaign'    => 'abandoned_cart',
				'utm_term'        => $email_id,
			),
			wc_get_checkout_url()
		) );
	}

	public function replace_complete_order_button( array $matches ): string {
		// Extract the label value from the matches, if it exists
		if ( ! empty( $matches[2] ) ) {
			$label = wp_kses_post( $matches[2] );
		} else {
			$label = __( 'Complete Order', 'checkout-wc' );
		}

		$button_bg_color   = SettingsManager::instance()->get_setting( 'button_color', array( cfw_get_active_template()->get_slug() ) );
		$button_text_color = SettingsManager::instance()->get_setting( 'button_text_color', array( cfw_get_active_template()->get_slug() ) );

		// Create the button HTML using the label
		return "<a href=\"{{checkout_url}}\" style=\"cursor: pointer; display: inline-block; text-decoration: none; background: {$button_bg_color}; color: {$button_text_color}; border-radius: 5px; border: 1px solid var(--cfw-buttons-primary-background-color); font-size: 16px; box-sizing: border-box; font-weight: 400; -webkit-transition: all .3s ease-in-out; -moz-transition: all .3s ease-in-out; transition: all .3s ease-in-out; padding: 19px 15px;\">$label</a>";
	}

	public function get_cart_table( $cart_contents ) {
		$cart_contents = json_decode( $cart_contents, true );

		if ( empty( $cart_contents ) ) {
			return;
		}

		ob_start();

		$styles = array(
			'table' => array(
				'style'     => 'color: #636363; border: 1px solid #e5e5e5;',
				'attribute' => 'align=left',
			),
		);

		/**
		 * Filter the cart table styles
		 *
		 * @param array $styles
		 *
		 * @since 8.0.0
		 */
		$style_filter = apply_filters( 'cfw_cart_table_styles', $styles );
		$style        = $style_filter['table']['style'] ?? '';

		?>
		<table id="cfw_acr_cart_products_table" <?php echo esc_attr( $style_filter['table']['attribute'] ); ?>
			   cellpadding="10" cellspacing="0"
			   style="float: none; border: 1px solid #e5e5e5;">
			<tr align="center">
				<th style="<?php echo esc_attr( $style ); ?>">
					<?php cfw_esc_html_e( 'Item', 'wooocommerce' ); ?>
				</th>
				<th style="<?php echo esc_attr( $style ); ?>">
					<?php cfw_esc_html_e( 'Name', 'woocommerce' ); ?>

				</th>
				<th style="<?php echo esc_attr( $style ); ?>">
					<?php cfw_esc_html_e( 'Quantity', 'woocommerce' ); ?>
				</th>
				<th style="<?php echo esc_attr( $style ); ?>">
					<?php cfw_esc_html_e( 'Price', 'woocommerce' ); ?>

				</th>
				<th style="<?php echo esc_attr( $style ); ?>">
					<?php cfw_esc_html_e( 'Subtotal', 'woocommerce' ); ?>
				</th>
			</tr>

			<?php foreach ( $cart_contents as $item ) : ?>
				<?php
				if ( ! isset( $item['product_id'] ) || ! isset( $item['quantity'] ) || ! isset( $item['line_total'] ) ) {
					continue;
				}

				$id      = $item['variation_id'] > 0 ? $item['variation_id'] : $item['product_id'];
				$product = wc_get_product( $id );

				if ( ! $product ) {
					continue;
				}

				$image        = $product->get_image( 'cfw_cart_thumb' );
				$product_name = $product->get_formatted_name();
				?>
				<tr style="<?php echo esc_attr( $style ); ?>" align="center">
					<?php if ( ! empty( $image ) ) : ?>
						<td style="<?php echo esc_attr( $style ); ?>">
							<?php echo wp_kses_post( $image ); ?>
						</td>
					<?php endif; ?>

					<td style="<?php echo esc_attr( $style ); ?>">
						<?php echo wp_kses_post( $product_name ); ?>
					</td>
					<td style="<?php echo esc_attr( $style ); ?>">
						<?php echo wp_kses_post( $item['quantity'] ); ?>
					</td>
					<td style="<?php echo esc_attr( $style ); ?>">
						<?php echo wp_kses_post( wc_price( $item['line_total'] ) ); ?>
					</td>
					<td style="<?php echo esc_attr( $style ); ?>">
						<?php echo wp_kses_post( wc_price( $item['line_total'] ) ); ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
		<?php

		return ob_get_clean();
	}

	public function maybe_recover_cart( $order_id ) {
		global $wpdb;

		$recovered_order_statuses = SettingsManager::instance()->get_setting( 'acr_recovered_order_statuses' );

		$order        = wc_get_order( $order_id );
		$order_status = $order->get_status();

		if ( ! in_array( "wc-{$order_status}", $recovered_order_statuses, true ) ) {
			// Order status is not considered recovered yet, bail
			return;
		}

		// "Why would this be necessary?" we ask ourselves
		if ( ! WC()->session ) {
			return;
		}

		$cart_id = WC()->session->get( 'cfw_acr_cart_id' ) ?? $this->get_tracked_cart_id_by_email( $order->get_billing_email() );

		if ( ! $cart_id ) {
			return;
		}

		$cart = $this->get_tracked_cart( $cart_id );

		if ( 'recovered' === $cart->status ) {
			// This cart was already recovered, bail
			return;
		}

		// If we didn't send emails, or the status of the cart isn't abandoned/lost or unsubscribed, remove it
		// Unsubscribed is here because the user still got an email and subsequently purchased
		if ( ! $cart->emails_sent || 'new' === $cart->status ) {
			// No emails sent, remove cart - it's not abandoned
			$wpdb->delete( $this->table_name, array( 'id' => $cart_id ) );

			return;
		}

		// Ok, we must have an order that was abandoned and recovered
		$wpdb->update(
			$this->table_name,
			array(
				'status' => 'recovered',
			),
			array(
				'id' => $cart_id,
			)
		);

		// Unscheduled emails for this specific cart
		$this->unschedule_all_emails_for_cart( $cart_id );

		// Unschedule mark as lost
		$this->unschedule_mark_as_lost( $cart_id );

		// Add a note to the order
		$order->add_order_note( __( 'This order was abandoned and subsequently recovered.', 'checkout-wc' ) );

		// Remember this was recovered
		$order->add_meta_data( 'cfw_acr_recovered', true, true );

		// Save the order
		$order->save();

		// Remove the cart from the session
		WC()->session->__unset( 'cfw_acr_cart_id' );
	}

	public function handle_order_status_change( $order_id, $previous_order_status, $order_status ) {
		global $wpdb;

		$recovered_order_statuses = SettingsManager::instance()->get_setting( 'acr_recovered_order_statuses' );

		if ( ! in_array( "wc-{$order_status}", $recovered_order_statuses, true ) ) {
			// Order status is not considered recovered yet, bail
			return;
		}

		$order = wc_get_order( $order_id );

		$cart_id = $this->get_tracked_cart_id_by_email( $order->get_billing_email() );

		if ( ! $cart_id ) {
			return;
		}

		$cart = $this->get_tracked_cart( $cart_id );

		if ( 'recovered' === $cart->status ) {
			// This cart was already recovered, bail
			return;
		}

		// If we didn't send emails, or the status of the cart isn't abandoned/lost or unsubscribed, remove it
		// Unsubscribed is here because the user still got an email and subsequently purchased
		if ( ! $cart->emails_sent || 'new' === $cart->status ) {
			// No emails sent, remove cart - it's not abandoned
			$wpdb->delete( $this->table_name, array( 'id' => $cart_id ) );

			return;
		}

		// Ok, we must have an order that was abandoned and recovered
		$wpdb->update(
			$this->table_name,
			array(
				'status' => 'recovered',
			),
			array(
				'id' => $cart_id,
			)
		);

		// Unscheduled emails for this specific cart
		$this->unschedule_all_emails_for_cart( $cart_id );

		// Unschedule mark as lost
		$this->unschedule_mark_as_lost( $cart_id );

		// Add a note to the order
		$order->add_order_note( __( 'This order was abandoned and subsequently recovered.', 'checkout-wc' ) );

		// Remember this was recovered
		$order->add_meta_data( 'cfw_acr_recovered', true, true );

		// Save the order
		$order->save();
	}

	/**
	 * @throws Exception
	 */
	public function handle_email_link_click() {
		if ( ! isset( $_GET['cfw_acr_cart_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$cart_id = sanitize_text_field( $_GET['cfw_acr_cart_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$cart    = $this->get_tracked_cart( $cart_id );

		if ( ! in_array( $cart->status, array( 'abandoned', 'lost', 'unsubscribed' ), true ) ) {
			return;
		}

		WC()->session->set( 'cfw_acr_cart_id', $cart_id );

		$cart_contents = json_decode( $cart->cart, true );

		if ( ! $cart_contents ) {
			return;
		}

		// Empty cart
		WC()->cart->empty_cart();

		// Get rid of notices about emptying the cart
		wc_clear_notices();

		foreach ( $cart_contents as $cart_item ) {
			// Skip bundled products
			if ( isset( $cart_item['bundled_by'] ) ) {
				continue;
			}

			$variation_data = array();

			if ( isset( $cart_item['variation'] ) ) {
				foreach ( $cart_item['variation'] as $key => $value ) {
					$variation_data[ $key ] = $value;
				}
			}

			WC()->cart->add_to_cart( $cart_item['product_id'], $cart_item['quantity'], $cart_item['variation_id'], $variation_data, $cart_item );
		}

		// Set fields to $_POST
		$fields = json_decode( $cart->fields, true );

		$_POST = array_merge( $_POST, $fields ?? array() ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		WC()->customer->set_props(
			array(
				'billing_first_name' => isset( $_POST['billing_first_name'] ) ? trim( sanitize_email( wp_unslash( $_POST['billing_first_name'] ) ) ) : null,
				'billing_last_name'  => isset( $_POST['billing_last_name'] ) ? trim( sanitize_email( wp_unslash( $_POST['billing_last_name'] ) ) ) : null,
				'billing_phone'      => isset( $_POST['billing_phone'] ) ? trim( sanitize_email( wp_unslash( $_POST['billing_phone'] ) ) ) : null,
				'billing_email'      => isset( $_POST['billing_email'] ) ? trim( sanitize_email( wp_unslash( $_POST['billing_email'] ) ) ) : null,
				'billing_country'    => isset( $_POST['country'] ) ? wc_clean( wp_unslash( $_POST['country'] ) ) : null,
				'billing_state'      => isset( $_POST['state'] ) ? wc_clean( wp_unslash( $_POST['state'] ) ) : null,
				'billing_postcode'   => isset( $_POST['postcode'] ) ? trim( wc_clean( wp_unslash( $_POST['postcode'] ) ) ) : null,
				'billing_city'       => isset( $_POST['city'] ) ? wc_clean( wp_unslash( $_POST['city'] ) ) : null,
				'billing_address_1'  => isset( $_POST['address'] ) ? wc_clean( wp_unslash( $_POST['address'] ) ) : null,
				'billing_address_2'  => isset( $_POST['address_2'] ) ? wc_clean( wp_unslash( $_POST['address_2'] ) ) : null,
			)
		);

		if ( wc_ship_to_billing_address_only() || ! WC()->cart->needs_shipping() ) {
			WC()->customer->set_props(
				array(
					'shipping_first_name' => isset( $_POST['billing_first_name'] ) ? trim( sanitize_email( wp_unslash( $_POST['billing_first_name'] ) ) ) : null,
					'shipping_last_name'  => isset( $_POST['billing_last_name'] ) ? trim( sanitize_email( wp_unslash( $_POST['billing_last_name'] ) ) ) : null,
					'shipping_phone'      => isset( $_POST['billing_phone'] ) ? wc_clean( wp_unslash( $_POST['billing_phone'] ) ) : null,
					'shipping_country'    => isset( $_POST['country'] ) ? wc_clean( wp_unslash( $_POST['country'] ) ) : null,
					'shipping_state'      => isset( $_POST['state'] ) ? wc_clean( wp_unslash( $_POST['state'] ) ) : null,
					'shipping_postcode'   => isset( $_POST['postcode'] ) ? trim( wc_clean( wp_unslash( $_POST['postcode'] ) ) ) : null,
					'shipping_city'       => isset( $_POST['city'] ) ? wc_clean( wp_unslash( $_POST['city'] ) ) : null,
					'shipping_address_1'  => isset( $_POST['address'] ) ? wc_clean( wp_unslash( $_POST['address'] ) ) : null,
					'shipping_address_2'  => isset( $_POST['address_2'] ) ? wc_clean( wp_unslash( $_POST['address_2'] ) ) : null,
				)
			);
		} else {
			WC()->customer->set_props(
				array(
					'shipping_first_name' => $cart->first_name,
					'shipping_last_name'  => $cart->last_name,
					'shipping_phone'      => isset( $_POST['shipping_phone'] ) ? wc_clean( wp_unslash( $_POST['shipping_phone'] ) ) : null,
					'shipping_country'    => isset( $_POST['s_country'] ) ? wc_clean( wp_unslash( $_POST['s_country'] ) ) : null,
					'shipping_state'      => isset( $_POST['s_state'] ) ? wc_clean( wp_unslash( $_POST['s_state'] ) ) : null,
					'shipping_postcode'   => isset( $_POST['s_postcode'] ) ? trim( wc_clean( wp_unslash( $_POST['s_postcode'] ) ) ) : null,
					'shipping_city'       => isset( $_POST['s_city'] ) ? wc_clean( wp_unslash( $_POST['s_city'] ) ) : null,
					'shipping_address_1'  => isset( $_POST['s_address'] ) ? wc_clean( wp_unslash( $_POST['s_address'] ) ) : null,
					'shipping_address_2'  => isset( $_POST['s_address_2'] ) ? wc_clean( wp_unslash( $_POST['s_address_2'] ) ) : null,
				)
			);
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		wp_safe_redirect( wc_get_checkout_url() );
	}

	public function add_coupon_restriction( $coupon_id, $coupon ) {
		// Individual use.
		woocommerce_wp_checkbox(
			array(
				'id'          => 'cfw_acr_only',
				'label'       => cfw__( 'CheckoutWC ACR only', 'checkout-wc' ),
				'description' => cfw__( 'Check this box if the coupon can only be used by customers with an abandoned cart.', 'checkout-wc' ),
				'value'       => wc_bool_to_string( $coupon->get_meta( 'cfw_acr_only' ) ),
			)
		);
	}

	public function save_coupon_restriction( $coupon_id, $coupon ) {
		$coupon->update_meta_data( 'cfw_acr_only', isset( $_POST['cfw_acr_only'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$coupon->save();
	}

	public function get_coupon_restriction( $emails, $coupon ): array {
		$emails = $emails ?? array();

		if ( $coupon->get_meta( 'cfw_acr_only' ) ) {
			$emails += $this->get_active_tracked_cart_emails();
		}

		return $emails;
	}

	public function get_active_tracked_cart_emails(): array {
		global $wpdb;

		return $wpdb->get_col( "SELECT email FROM $this->table_name WHERE status <> 'recovered' AND status <> 'new' AND status <> 'lost'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public function get_tracked_cart_id_by_email( $email ): ?string {
		global $wpdb;

		if ( empty( $email ) ) {
			return null;
		}

		return $wpdb->get_var(
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->prepare( "SELECT id FROM $this->table_name WHERE email = %s ORDER BY created DESC LIMIT 1", $email )
		);
	}

	public function get_tracked_cart( $cart_id ) {
		global $wpdb;

		return $wpdb->get_row(
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->prepare( "SELECT *, UNIX_TIMESTAMP(created) AS created_unix FROM $this->table_name WHERE id = %d", $cart_id )
		);
	}

	public function unschedule_all_emails_for_cart( $cart_id ) {
		\as_unschedule_all_actions( null, null, 'checkoutwc_' . $cart_id );
	}

	public function unschedule_mark_as_lost( $cart_id ) {
		\as_unschedule_all_actions( null, null, 'checkoutwc_lost_' . $cart_id );
	}
}

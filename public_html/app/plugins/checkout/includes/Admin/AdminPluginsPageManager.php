<?php

namespace Objectiv\Plugins\Checkout\Admin;

use Objectiv\Plugins\Checkout\Managers\UpdatesManager;
use function WordpressEnqueueChunksPlugin\get as cfwChunkedScriptsConfigGet;
use function WordpressEnqueueChunksPlugin\registerScripts as cfwRegisterChunkedScripts;

class AdminPluginsPageManager {
	protected $cfw_admin_url;
	private $dev_remote_url = 'https://cfw-stat-collector.test/api/v1/deactivation_survey';
	private $remote_url     = 'https://stats.checkoutwc.com/api/v1/deactivation_survey';

	public function __construct( string $cfw_admin_url ) {
		$this->cfw_admin_url = $cfw_admin_url;
	}

	public function init() {
		add_action( 'after_plugin_row_' . CFW_PATH_BASE, array( $this, 'add_key_nag' ), 10, 2 );
		add_filter( 'plugin_action_links_' . plugin_basename( CFW_MAIN_FILE ), array(
			$this,
			'add_action_link'
		), 10, 1 );
		add_action( 'admin_footer', array( $this, 'deactivation_survey_html' ) );
		add_filter( 'cfw_deactivation_form_fields', array( $this, 'add_deactivation_form_fields' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 1000 );
	}

	public function add_action_link( $links ) {
		$settings_link = array(
			'<a href="' . $this->cfw_admin_url . '">' . cfw__( 'Settings', 'checkout-wc' ) . '</a>',
		);

		return array_merge( $settings_link, $links );
	}

	public function add_key_nag() {
		$key_status = UpdatesManager::instance()->get_field_value( 'key_status' );

		if ( empty( $key_status ) ) {
			return;
		}

		if ( 'valid' === $key_status ) {
			return;
		}

		$current = get_site_transient( 'update_plugins' );
		if ( isset( $current->response[ plugin_basename( __FILE__ ) ] ) ) {
			return;
		}

		if ( is_network_admin() || ! is_multisite() ) {
			$wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );
			echo '<tr class="plugin-update-tr"><td colspan="' . $wp_list_table->get_column_count() . '" class="plugin-update colspanchange"><div class="update-message">';
			echo "<span style='color:red'>You're missing out on important updates because your license key is missing, invalid, or expired.</span>";
			echo '</div></td></tr>';
		}
	}

	public function deactivation_survey_html() {
		global $pagenow;

		require_once CFW_PATH_BASE . 'sources/php/deactivation-survey.php';
	}

	/**
	 * Returns form fields html.
	 *
	 * @since       1.4.0
	 * @param       array  $attr               The attributes of this field.
	 * @param       string $base_class         The basic class for the label.
	 */
	public function render_field_html( $attr = array(), $base_class = 'on-boarding' ) {

		$id       = ! empty( $attr['id'] ) ? 'cfw_' . $attr['id'] : '';
		$name     = ! empty( $attr['name'] ) ? $attr['name'] : '';
		$label    = ! empty( $attr['label'] ) ? $attr['label'] : '';
		$type     = ! empty( $attr['type'] ) ? $attr['type'] : '';
		$class    = ! empty( $attr['extra-class'] ) ? $attr['extra-class'] : '';
		$value    = ! empty( $attr['value'] ) ? $attr['value'] : '';
		$options  = ! empty( $attr['options'] ) ? $attr['options'] : array();
		$multiple = ! empty( $attr['multiple'] ) && 'yes' === $attr['multiple'] ? 'yes' : 'no';
		$required = ! empty( $attr['required'] ) ? 'required="required"' : '';

		$html = '';

		if ( 'hidden' !== $type ) : ?>
			<div class ="mt-6 space-y-6">
		<?php
		endif;

		switch ( $type ) {

			case 'radio':
				// If field requires multiple answers.
				if ( ! empty( $options ) && is_array( $options ) ) :
					?>

					<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_attr( $label ); ?></label>

					<?php
					$is_multiple = ! empty( $multiple ) && 'yes' !== $multiple ? 'name = "' . $name . '"' : '';

					foreach ( $options as $option_value => $option_label ) :
						?>
						<div class="flex items-center gap-x-3">
							<input type="<?php echo esc_attr( $type ); ?>" class="cfw-deactivation-survey-<?php echo esc_attr( $type ); ?>-field <?php echo esc_attr( $class ); ?> h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-600" value="<?php echo esc_attr( $option_value ); ?>" id="<?php echo esc_attr( 'cfw_' . $option_value ); ?>" <?php echo esc_html( $required ); ?> <?php echo $is_multiple; ?>>
							<label for="<?php echo esc_attr( 'cfw_' . $option_value ); ?>"><?php echo wp_kses_post( $option_label ); ?></label>
						</div>
					<?php endforeach; ?>

				<?php
				endif;

				break;

			case 'checkbox':
				// If field requires multiple answers.
				if ( ! empty( $options ) && is_array( $options ) ) :
					?>

					<label class="on-boarding-label" for="<?php echo esc_attr( $id ); ?>'"><?php echo esc_attr( $label ); ?></label>

					<?php foreach ( $options as $option_id => $option_label ) : ?>
					<div class="wps-<?php echo esc_html( $base_class ); ?>-checkbox-wrapper">
						<input type="<?php echo esc_html( $type ); ?>" class="on-boarding-<?php echo esc_html( $type ); ?>-field <?php echo esc_html( $class ); ?>" value="<?php echo esc_html( $value ); ?>" id="<?php echo esc_html( $option_id ); ?>">
						<label class="on-boarding-field-label" for="<?php echo esc_html( $option_id ); ?>"><?php echo esc_html( $option_label ); ?></label>
					</div>

				<?php endforeach; ?>
				<?php
				endif;

				break;

			case 'select':
			case 'select2':
				// If field requires multiple answers.
				if ( ! empty( $options ) && is_array( $options ) ) {

					$is_multiple = 'yes' === $multiple ? 'multiple' : '';
					$select2     = ( 'yes' === $multiple && 'select' === $type ) || 'select2' === $type ? 'on-boarding-select2 ' : '';
					?>

					<label class="on-boarding-label"  for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
					<select class="on-boarding-select-field <?php echo esc_html( $select2 ); ?> <?php echo esc_html( $class ); ?>" id="<?php echo esc_html( $id ); ?>" name="<?php echo esc_html( $name ); ?>[]" <?php echo esc_html( $required ); ?> <?php echo esc_html( $is_multiple ); ?>>

						<?php if ( 'select' === $type ) : ?>
							<option class="on-boarding-options" value=""><?php cfw_esc_html_e( 'Select Any One Option...', 'upsell-order-bump-offer-for-woocommerce' ); ?></option>
						<?php endif; ?>

						<?php foreach ( $options as $option_value => $option_label ) : ?>

							<option class="on-boarding-options" value="<?php echo esc_attr( $option_value ); ?>"><?php echo esc_html( $option_label ); ?></option>

						<?php endforeach; ?>
					</select>

					<?php
				}

				break;

			case 'label':
				/**
				 * Only a text in label.
				 */
				?>
				<label class="" for="<?php echo( esc_attr( $id ) ); ?>"><?php echo( esc_html( $label ) ); ?></label>
				<?php
				break;

			case 'textarea':
				/**
				 * Text Area Field.
				 */
				?>
				<textarea rows="3" cols="50" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 <?php echo esc_attr( $class ); ?>" placeholder="<?php echo( esc_attr( $label ) ); ?>" id="<?php echo( esc_attr( $id ) ); ?>" name="<?php echo( esc_attr( $name ) ); ?>"><?php echo( esc_attr( $value ) ); ?></textarea>

				<?php
				break;

			default:
				/**
				 * Text/ Password/ Email.
				 */
				?>
				<label for="<?php echo( esc_attr( $id ) ); ?>"><?php echo( esc_html( $label ) ); ?></label>
				<input type="<?php echo( esc_attr( $type ) ); ?>" class="on-boarding-<?php echo( esc_attr( $type ) ); ?>-field <?php echo( esc_attr( $class ) ); ?>" value="<?php echo( esc_attr( $value ) ); ?>"  name="<?php echo( esc_attr( $name ) ); ?>" id="<?php echo( esc_attr( $id ) ); ?>" <?php echo( esc_html( $required ) ); ?>>

			<?php
		}

		if ( 'hidden' !== $type ) :
			?>
			</div>
		<?php
		endif;
	}

	public function add_deactivation_form_fields(): array {

		$current_user = wp_get_current_user();
		if ( ! empty( $current_user ) ) {
			$current_user_email = $current_user->user_email ? $current_user->user_email : '';
		}

		$store_name = get_bloginfo( 'name ' );
		$store_url  = get_home_url();

		/**
		 * Do not repeat id index.
		 */

		return array(

			/**
			 * Input field with label.
			 * Radio field with label ( select only one ).
			 * Radio field with label ( select multiple one ).
			 * Checkbox radio with label ( select only one ).
			 * Checkbox field with label ( select multiple one ).
			 * Only Label ( select multiple one ).
			 * Select field with label ( select only one ).
			 * Select2 field with label ( select multiple one ).
			 * Email field with label. ( auto filled with admin email )
			 */

			array(
				'id'          => 'deactivation_reason',
				'label'       => '',
				'type'        => 'radio',
				'name'        => 'reason',
				'value'       => '',
				'multiple'    => 'no',
				'required'    => 'yes',
				'extra-class' => '',
				'options'     => array(
					'temporary_deactivation_for_debug' => '<strong>It is a temporary deactivation.</strong> I am just debugging an issue.',
					'site-layout_broke'                => 'The plugin <strong>broke my layout</strong> or some functionality.',
					'complicated_configuration'        => 'The plugin is <strong>too complicated to configure.</strong>',
					'other'                            => 'Other',
				),
			),

			array(
				'id'          => 'reason_other',
				'label'       => 'Let us know why you are deactivating CheckoutWC so we can improve the plugin',
				'type'        => 'textarea',
				'name'        => 'reason_other',
				'value'       => '',
				'required'    => 'yes',
				'extra-class' => 'hidden',
			),

			array(
				'id'          => 'admin_email',
				'label'       => '',
				'type'        => 'hidden',
				'name'        => 'admin_email',
				'value'       => $current_user_email ?? '',
				'required'    => '',
				'extra-class' => '',
			),

			array(
				'id'          => 'store_name',
				'label'       => '',
				'type'        => 'hidden',
				'name'        => 'store_name',
				'value'       => $store_name,
				'required'    => '',
				'extra-class' => '',
			),

			array(
				'id'          => 'url',
				'label'       => '',
				'type'        => 'hidden',
				'name'        => 'url',
				'value'       => $store_url,
				'required'    => '',
				'extra-class' => '',
			),

			array(
				'id'          => 'price_id',
				'label'       => '',
				'type'        => 'hidden',
				'name'        => 'price_id',
				'value'       => UpdatesManager::instance()->get_license_price_id(),
				'required'    => '',
				'extra-class' => '',
			),

			array(
				'id'          => 'license_status',
				'label'       => '',
				'type'        => 'hidden',
				'name'        => 'license_status',
				'value'       => UpdatesManager::instance()->get_license_status(),
				'required'    => '',
				'extra-class' => '',
			),

			array(
				'id'          => 'version',
				'label'       => '',
				'type'        => 'hidden',
				'name'        => 'version',
				'value'       => CFW_VERSION,
				'required'    => '',
				'extra-class' => '',
			),
		);
	}

	public function enqueue_scripts(): void {
		global $pagenow;

		if ( empty( $pagenow ) || 'plugins.php' !== $pagenow ) {
			return;
		}

		$front    = trailingslashit( CFW_PATH_ASSETS );
		$manifest = cfwChunkedScriptsConfigGet( 'manifest' );

		// PHP 8.1+ Fix
		foreach ( $manifest['chunks'] as $chunk_name => $chunk ) {
			add_filter(
				"wpecp/register/{$chunk_name}",
				function ( $args ) use ( $chunk_name ) {
					if ( 'admin-plugins' !== $chunk_name ) {
						return $args;
					}

					$args['deps'][] = 'wp-api';

					return $args;
				}
			);
		}

		cfwRegisterChunkedScripts( array( 'admin-plugins' ) );
		wp_enqueue_script( 'cfw-admin-plugins' );

		if ( isset( $manifest['chunks']['admin-plugins-styles']['file'] ) ) {
			wp_enqueue_style( 'objectiv-cfw-admin-plugins-styles', "{$front}/{$manifest['chunks']['admin-plugins-styles']['file']}", array(), $manifest['chunks']['admin-plugins-styles']['hash'] );
		}

		wp_localize_script( 'cfw-admin-plugins', 'cfwAdminPluginsScreenData', array(
			'remote_url' => CFW_DEV_MODE ? $this->dev_remote_url : $this->remote_url,
		) );
	}
}

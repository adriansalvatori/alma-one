<?php
/**
 * Provide a admin-facing view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

use Vibes\System\Environment;

wp_enqueue_style( VIBES_ASSETS_ID );
wp_enqueue_script( VIBES_ASSETS_ID );

$warning = '';
if ( Environment::is_plugin_in_dev_mode() ) {
	$icon     = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'alert-triangle', 'none', '#FF8C00' ) . '" />&nbsp;';
	$warning .= '<p>' . $icon . sprintf( esc_html__( 'This version of %s is not production-ready. It is a development preview. Use it at your own risk!', 'vibes' ), VIBES_PRODUCT_NAME ) . '</p>';
}
if ( Environment::is_plugin_in_rc_mode() ) {
	$icon     = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'alert-triangle', 'none', '#FF8C00' ) . '" />&nbsp;';
	$warning .= '<p>' . $icon . sprintf( esc_html__( 'This version of %s is a release candidate. Although ready for production, this version is not officially supported in production environments.', 'vibes' ), VIBES_PRODUCT_NAME ) . '</p>';
}
$intro      = sprintf( esc_html__( '%1$s is a free and open source plugin for WordPress. It integrates other free and open source works (as-is or modified) like: %2$s.', 'vibes' ), '<em>' . VIBES_PRODUCT_NAME . '</em>', do_shortcode( '[vibes-libraries]' ) );
$trademarks = esc_html__( 'All brands, icons and graphic illustrations are registered trademarks of their respective owners.', 'vibes' );
$icon       = '<img class="vibes-about-logo" style="opacity:0;" src="' . Vibes\Plugin\Core::get_base64_logo() . '" />';

?>
<h2><?php echo esc_html( VIBES_PRODUCT_NAME . ' ' . VIBES_VERSION ); ?> / <a href="https://perfops.one">PerfOps One</a></h2>
<?php echo wp_kses( $icon, PERFOO_ALLOWED_HTML_FOR_DASHBOARD, PERFOO_ALLOWED_PROTOCOLS_FOR_DASHBOARD ); ?>
<?php echo wp_kses( $warning, PERFOO_ALLOWED_HTML_FOR_DASHBOARD, PERFOO_ALLOWED_PROTOCOLS_FOR_DASHBOARD ); ?>
<p><?php echo wp_kses( $intro, PERFOO_ALLOWED_HTML_FOR_DASHBOARD, PERFOO_ALLOWED_PROTOCOLS_FOR_DASHBOARD ); ?></p>
<h4><?php esc_html_e( 'Disclaimer', 'vibes' ); ?></h4>
<p><em><?php echo esc_html( $trademarks ); ?></em></p>
<hr/>
<h2><?php esc_html_e( 'Changelog', 'vibes' ); ?></h2>
<?php echo do_shortcode( '[vibes-changelog]' ); ?>
<div style="min-height: 100px; position: fixed; bottom: 4vh; right: 4vw; z-index: 10000">
    <div style="background-color: #FFF; padding: 20px; border-radius: 4px; box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.2)">
        <img style="width:60px; margin-right: 20px;" src="<?php echo \PerfOpsOne\Resources::get_sponsor_base64_logo(); ?>"/><div style="float: right; text-align: center;padding-top:10px">The PerfOps One plugins suite is sponsored by <br/><a href="https://hosterra.eu">Hosterra - Ethical & Sustainable Internet Hosting</a></div>
    </div>
</div>

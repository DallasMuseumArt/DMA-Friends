<?php
/**
 * Handles the display and functionality of the theme settings page.
 *
 * @package Nifty
 * @subpackage Admin
 */

/* Hook the settings page function to 'admin_menu'. */
add_action( 'admin_menu', 'nifty_settings_page_init' );

/**
 * Initializes all the theme settings page functions.
 *
 * @since 12.09
 */
function nifty_settings_page_init() {

	/* Get theme information. */
	$theme = wp_get_theme( 'nifty' );

	/* Register theme settings. */
	register_setting( 'nifty_theme_settings', 'nifty_theme_settings', 'nifty_save_theme_settings' );

	/* Create the theme settings page. */
	add_theme_page( sprintf( __( '%1$s Theme Settings', 'nifty' ), $theme->Name ), sprintf( __( '%1$s Settings', 'nifty' ), $theme->Name ), 'edit_theme_options', 'theme-settings', 'nifty_settings_page' );

	/* Register the default theme settings meta boxes. */
	add_action( 'load-appearance_page_theme-settings', 'nifty_load_settings_page_meta_boxes' );

	/* Create a hook for adding meta boxes. */
	add_action( 'load-appearance_page_theme-settings', 'nifty_load_settings_page_add_meta_boxes' );

	/* Load the JavaScript and stylehsheets needed for the theme settings. */
	add_action( 'load-appearance_page_theme-settings', 'nifty_settings_page_enqueue_style' );
	add_action( 'load-appearance_page_theme-settings', 'nifty_settings_page_enqueue_script' );
	add_action( 'admin_head-appearance_page_theme-settings', 'nifty_settings_page_load_scripts' );
}

/**
 * Validation/Sanitization callback function for theme settings.
 * This just returns the data passed to it.
 *
 * Developers should filter "sanitize_option_nifty_theme_settings" instead.
 *
 * @since 12.09
 */
function nifty_save_theme_settings( $settings ) {

	/* Escape the entered feed URL. */
	if ( isset( $settings['feed_url'] ) )
		$settings['feed_url'] = esc_url( $settings['feed_url'] );

	/* Make sure users without the 'unfiltered_html' capability can't add HTML to the footer insert. */
	if ( isset( $settings['footer_insert'] ) && !current_user_can( 'unfiltered_html' ) )
		$settings['footer_insert'] = stripslashes( wp_filter_post_kses( addslashes( $settings['footer_insert'] ) ) );

	return $settings;
}

/**
 * Creates the default meta boxes for the theme settings page. Parent/child theme and plugin developers
 * should use add_meta_box() to create additional meta boxes.
 *
 * @since 12.09
 * @global string $nifty The global theme object.
 */
function nifty_load_settings_page_meta_boxes() {
	global $nifty;

	/* Load the General, Footer and About meta boxes */
	require_once( trailingslashit( $nifty->nifty_admin ) . 'meta-box-theme-general.php' );
	require_once( trailingslashit( $nifty->nifty_admin ) . 'meta-box-theme-footer.php' );
	require_once( trailingslashit( $nifty->nifty_admin ) . 'meta-box-theme-about.php' );

	/* Load the Layouts meta box */
	require_if_theme_supports( 'nifty-core-theme-layouts', trailingslashit( $nifty->nifty_admin ) . 'meta-box-theme-layouts.php' );
}

/**
 * Provides a hook for adding meta boxes as seen on the post screen in the WordPress admin. This addition 
 * is needed because normal plugin/theme pages don't have this hook by default.
 *
 * @since 12.09
 */
function nifty_load_settings_page_add_meta_boxes() {
	do_action( 'add_meta_boxes', 'appearance_page_theme-settings' );
}

/**
 * Displays the theme settings page and calls do_meta_boxes() to allow additional settings
 * meta boxes to be added to the page.
 *
 * @since 12.09
 */
function nifty_settings_page() {

	/* Get the theme information. */
	$theme = wp_get_theme( 'nifty' ); ?>

	<div class="wrap">

		<?php screen_icon(); ?>

		<h2><?php printf( __( '%1$s Theme Settings', 'nifty' ), $theme->Name ); ?></h2>

		<?php settings_errors(); ?>

		<div id="poststuff">

			<form method="post" action="options.php">

				<?php settings_fields( 'nifty_theme_settings' ); ?>
				<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
				<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>

				<div class="metabox-holder">
					<div class="post-box-container column-1 normal"><?php do_meta_boxes( 'appearance_page_theme-settings', 'normal', null ); ?></div>
					<div class="post-box-container column-2 side"><?php do_meta_boxes( 'appearance_page_theme-settings', 'side', null ); ?></div>
					<div class="post-box-container column-3 advanced"><?php do_meta_boxes( 'appearance_page_theme-settings', 'advanced', null ); ?></div>
				</div>

				<p class="submit">
					<input id="submit" class="button button-primary" type="submit" name="Submit" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
				</p><!-- .submit -->

			</form>

		</div><!-- #poststuff -->

	</div><!-- .wrap --><?php
}

/**
 * Loads the admin.css stylesheet for admin-related features.
 *
 * @since 12.09
 */
function nifty_settings_page_enqueue_style() {
	global $nifty;

	wp_enqueue_style( 'nifty-admin', trailingslashit( $nifty->nifty_uri ) . 'css/admin.css', false, 1.0, 'screen' );
}

/**
 * Loads the JavaScript files required for managing the meta boxes on the theme settings
 * page, which allows users to arrange the boxes to their liking.
 *
 * @since 12.09
 */
function nifty_settings_page_enqueue_script() {
	wp_enqueue_script( 'postbox' );
}

/**
 * Loads the JavaScript required for toggling the meta boxes on the theme settings page.
 *
 * @since 12.09
 */
function nifty_settings_page_load_scripts() { ?>
	<script type="text/javascript">
		//<![CDATA[
		jQuery(document).ready( function($) {
			$('.if-js-closed').removeClass('if-js-closed').addClass('closed');
			postboxes.add_postbox_toggles( 'appearance_page_theme-settings' );
		});
		//]]>
	</script><?php
}

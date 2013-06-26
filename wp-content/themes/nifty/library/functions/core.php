<?php
/**
 * The core functions file for the Nifty Theme Framework. Functions defined here are generally
 * used across the entire framework to make various tasks faster.
 *
 * @package Nifty
 * @subpackage Functions
 */

/**
 * Function for setting the content width of a theme.
 *
 * @since 12.09
 * @param int $width Numeric value of the width to set.
 */
function nifty_set_content_width( $width = '' ) {
	global $content_width;

	$content_width = absint( $width );
}

/**
 * Loads the Nifty Theme Settings once and allows the input of the specific field the user would 
 * like to show. Nifty theme settings are only loaded once on each page load.
 *
 * @since 12.09
 * @param string $option The specific theme setting the user wants.
 * @return string|int|array $settings[$option] Specific setting asked for.
 */
function nifty_get_setting( $option = '' ) {
	global $nifty;

	if ( empty( $option ) )
		return false;

	if ( !isset( $nifty->settings ) )
		$nifty->settings = get_option( 'nifty_theme_settings', nifty_get_default_theme_settings() );

	if ( !is_array( $nifty->settings ) || empty( $nifty->settings[$option] ) )
		return false;

	if ( !is_array( $nifty->settings[$option] ) )
		return wp_kses_stripslashes( $nifty->settings[$option] );

	return $nifty->settings[$option];
}

/**
 * Sets up a default array of theme settings for use with the theme.
 *
 * Theme developers should filter the "nifty_default_theme_settings"
 * hook to define any default theme settings.
 *
 * @since 12.09
 * @return array $settings The default theme settings
 */
function nifty_get_default_theme_settings() {
	$default = array();

	/* If there is a child theme active, add the [child-link] shortcode to the $footer_insert */
	if ( is_child_theme() )
		$default['footer_insert'] = '<p class="copyright">' . __( '&#169; [the-year] [site-link] - [site-description].', 'nifty' ) . '</p>' . "\n\n" . '<p class="powered">' . __( 'Proudly Powered by [wordpress], [theme-link], and [child-link].', 'nifty' ) . '</p>';

	/* If no child theme is active, leave out the [child-link] shortcode */
	else
		$default['footer_insert'] = '<p class="copyright">' . __( '&#169; [the-year] [site-link] - [site-description].', 'nifty' ) . '</p>' . "\n\n" . '<p class="powered">' . __( 'Proudly Powered by [wordpress] and [theme-link].', 'nifty' ) . '</p>';

	/* Adds '2c-l' as default Theme Layout */
	if ( current_theme_supports( 'nifty-core-theme-layouts' ) ) {
		$default['theme_layout'] = '2c-l';
	}

	return apply_filters( 'nifty_default_theme_settings', $default );
}

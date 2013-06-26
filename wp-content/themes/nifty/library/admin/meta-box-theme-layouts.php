<?php
/**
 * Creates a meta box for the layouts settings page.
 *
 * @package Nifty
 * @subpackage Admin
 */

/* Create the footer meta box on the 'add_meta_boxes' hook. */
add_action( 'add_meta_boxes', 'nifty_meta_box_theme_add_layouts' );

/**
 * Adds the footer meta box to the theme settings page in the admin.
 *
 * @since 12.09
 */
function nifty_meta_box_theme_add_layouts() {
	add_meta_box( 'nifty-settings-layouts', __( 'Layouts settings', 'nifty' ), 'nifty_meta_box_theme_display_layouts', 'appearance_page_theme-settings', 'normal', 'default' );
}

/**
 * Creates a meta box that allows users to customize their footer.
 *
 * @since 12.09
 */
function nifty_meta_box_theme_display_layouts( $object, $box ) {
	global $wp_query, $_wp_theme_features, $nifty;

	/* Get the available post layouts. */
	if ( !isset( $_wp_theme_features['nifty-core-theme-layouts'] ) )
		return;

	$theme_layouts = $_wp_theme_features['nifty-core-theme-layouts'];
	$theme_layout = nifty_get_setting( 'theme_layout' ); ?>

	<table class="form-table">
		<tr>
			<th><label for="nifty_theme_settings-theme_layout"><?php _e( 'Main Layout', 'nifty' ); ?></label></th>
			<td> 
			<?php foreach( $theme_layouts[0] as $layout ) : ?>
				<label><input type="radio" name="nifty_theme_settings[theme_layout]" id="nifty_theme_settings-theme_layout" value="<?php echo esc_attr( $layout ); ?>" <?php checked( $theme_layout, $layout ); ?> /><img src="<?php echo trailingslashit( $nifty->nifty_uri ) . 'images/' . $layout ?>.png" /></label>
			<?php endforeach; ?>
			</td>
		</tr>
	</table><!-- .form-table --><?php
}

<?php
/**
 * Creates a meta box for the theme settings page, which holds a textarea for custom footer
 * text within the theme.
 *
 * @package Nifty
 * @subpackage Admin
 */

/* Create the footer meta box on the 'add_meta_boxes' hook. */
add_action( 'add_meta_boxes', 'nifty_meta_box_theme_add_general' );

/**
 * Adds the footer meta box to the theme settings page in the admin.
 *
 * @since 12.09
 */
function nifty_meta_box_theme_add_general() {
	add_meta_box( 'nifty-settings-general', __( 'General settings', 'nifty' ), 'nifty_meta_box_theme_display_general', 'appearance_page_theme-settings', 'normal', 'high' );
}

/**
 * Creates a meta box that allows users to customize their footer.
 *
 * @since 12.09
 */
function nifty_meta_box_theme_display_general( $object, $box ) { ?>

	<table class="form-table">
		<tr>
			<th><label for="nifty_theme_settings-feed_url"><?php _e( 'Feeds', 'nifty' ); ?></label></th>
			<td>
				<input id="nifty_theme_settings-feed_url" name="nifty_theme_settings[feed_url]" type="text" value="<?php echo nifty_get_setting( 'feed_url' ); ?>" /><br />
				<?php _e( 'If you have an alternate feed address, you can enter it here to have the theme redirect your feed links.', 'nifty' ); ?><br /><br />
				<input id="nifty_theme_settings-feeds_redirect" name="nifty_theme_settings[feeds_redirect]" type="checkbox" <?php if ( nifty_get_setting( 'feeds_redirect' ) ) echo 'checked="checked"'; ?> value="true" />
				<label for="nifty_theme_settings-feeds_redirect"><?php _e( 'Direct category, tag, search, and author feeds to your alternate feed address.', 'nifty' ); ?></label>
			</td>
		</tr>
		<tr>
			<th><label for="nifty_theme_settings-windows_live_writer"><?php _e( 'Windows Live Writer', 'nifty' ); ?></label></th>
			<td>
				<input id="nifty_theme_settings-windows_live_writer" name="nifty_theme_settings[windows_live_writer]" type="checkbox" <?php if ( nifty_get_setting( 'windows_live_writer' ) ) echo 'checked="checked"'; ?> value="true" />
				<label for="nifty_theme_settings-windows_live_writer"><?php _e( 'Enable support for Windows Live Writer.', 'nifty' ); ?></label>
			</td>
		</tr>
	</table><!-- .form-table --><?php
}



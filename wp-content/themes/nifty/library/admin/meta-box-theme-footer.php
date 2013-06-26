<?php
/**
 * Creates a meta box for the theme settings page, which holds a textarea for custom footer
 * text within the theme.
 *
 * @package Nifty
 * @subpackage Admin
 */

/* Create the footer meta box on the 'add_meta_boxes' hook. */
add_action( 'add_meta_boxes', 'nifty_meta_box_theme_add_footer' );

/**
 * Adds the footer meta box to the theme settings page in the admin.
 *
 * @since 12.09
 */
function nifty_meta_box_theme_add_footer() {
	add_meta_box( 'nifty-settings-footer', __( 'Footer settings', 'nifty' ), 'nifty_meta_box_theme_display_footer', 'appearance_page_theme-settings', 'normal', 'low' );
}

/**
 * Creates a meta box that allows users to customize their footer.
 *
 * @since 12.09
 */
function nifty_meta_box_theme_display_footer( $object, $box ) { ?>

	<table class="form-table">
		<tr>
			<td>
				<?php wp_editor( esc_textarea( nifty_get_setting( 'footer_insert' ) ), 'nifty_theme_settings-footer_insert', array( 'media_buttons' => false, 'tinymce' => false, 'textarea_name' => 'nifty_theme_settings[footer_insert]' ) ); ?>
				<?php printf( __( 'Shortcodes: %s', 'nifty' ), '<code>[the-year]</code>, <code>[site-link]</code>, <code>[site-description]</code>, <code>[wordpress]</code>, <code>[theme-link]</code>, <code>[child-link]</code>, <code>[loginout-link]</code>, <code>[query-counter]</code>' ); ?>
			</td>
		</tr>
	</table><!-- .form-table --> <?php
}


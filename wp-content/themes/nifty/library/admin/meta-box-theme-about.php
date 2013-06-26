<?php
/**
 * Creates a meta box for the theme settings page, which displays information about the theme. If a child 
 * theme is in use, an additional meta box will be added with its information.
 *
 * @package Nifty
 * @subpackage Admin
 */

/* Create the about theme meta box on the 'add_meta_boxes' hook. */
add_action( 'add_meta_boxes', 'nifty_meta_box_theme_add_about' );

/**
 * Adds the core about theme meta box to the theme settings page.
 *
 * @since 12.09
 */
function nifty_meta_box_theme_add_about() {

	/* Get theme information. */
	$theme = wp_get_theme( 'nifty' );

	/* If the user is using a child theme, add an About box for it. */
	if ( is_child_theme() ) {
		$child = wp_get_theme();
		add_meta_box( 'nifty-about-child-meta-box', sprintf( __( 'About %1$s', 'nifty' ), $child->Name ), 'nifty_meta_box_theme_display_about', 'appearance_page_theme-settings', 'side', 'high' );
	}

	/* Adds the About box for the parent theme. */
	add_meta_box( 'nifty-about-parent-meta-box', sprintf( __( 'About %1$s', 'nifty' ), $theme->Name ), 'nifty_meta_box_theme_display_about', 'appearance_page_theme-settings', 'side', 'high' );
}

/**
 * Creates an information meta box with no settings about the theme. The meta box will display
 * information about both the parent theme and child theme. If a child theme is active, this function
 * will be called a second time.
 *
 * @since 12.09
 */
function nifty_meta_box_theme_display_about( $object, $box ) {

	$theme = array();

	/* Grab theme information for the parent theme. */
	if ( 'nifty-about-parent-meta-box' == $box['id'] )
		$theme = wp_get_theme( 'nifty' );

	/* Grab theme information for the child theme. */
	else if ( 'nifty-about-child-meta-box' == $box['id'] )
		$theme = wp_get_theme(); ?>

	<table class="form-table">
		<tr>
			<th><?php _e( 'Theme', 'nifty' ); ?></th>
			<td><a href="<?php echo $theme->display( 'ThemeURI', true, false ); ?>" title="<?php echo $theme->Name; ?>"><?php echo $theme->Name; ?> <?php echo $theme->Version; ?></a></td>
		</tr>
		<tr>
			<th><?php _e( 'Author', 'nifty' ); ?></th>
			<td><?php echo $theme->Author; ?></td>
		</tr>
		<tr>
			<th><?php _e( 'Description', 'nifty' ); ?></th>
			<td><?php echo $theme->Description; ?></td>
		</tr>
	</table><!-- .form-table --><?php
}

?>

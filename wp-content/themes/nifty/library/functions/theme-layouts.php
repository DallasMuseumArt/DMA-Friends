<?php
/**
 * Theme Layouts was created to allow theme developers to easily style themes with
 * post-specific layout structures.
 * 
 * @package Nifty
 * @subpackage Functions
 */

/* Filters the body_class hook to add a custom class. */
add_filter( 'body_class', 'nifty_theme_layouts_body_class' );

/* Set up the custom post layouts. */
add_action( 'admin_menu', 'nifty_post_layouts_admin_setup' );

/* Filter the sidebar widgets. */
add_filter( 'sidebars_widgets', 'nifty_disable_sidebars' );
add_action( 'template_redirect', 'nifty_one_column' );

/* Embed width/height defaults. */
add_filter( 'embed_defaults', 'nifty_embed_defaults' );

/**
 * Adds the theme layout class to the WordPress body class in the form of "layout-$layout".
 *
 * @since 12.09
 * @param array $classes
 * @return array $classes
 */
function nifty_theme_layouts_body_class( $classes ) {
	$classes[] = nifty_get_theme_layout();
	return $classes;
}

/**
 * Gets the layout for the current post based off the '_nifty_post_layout' custom field key.
 *
 * @since 12.09
 * @return string The layout for the given page.
 */
function nifty_get_theme_layout() {
	global $wp_query, $_wp_theme_features;

	/* Get the available post layouts. */
	if ( !isset( $_wp_theme_features['nifty-core-theme-layouts'] ) )
		return;

	$theme_layouts = $_wp_theme_features['nifty-core-theme-layouts'];

	/* Set the layout to an empty string. */
	$layout = '';

	/* If viewing a singular post, check if a layout has been specified. */
	if ( is_singular() ) {
		$post_id = (int) $wp_query->get_queried_object_id();
		$layout = nifty_get_post_layout( $post_id );
	}

	/* If the theme set a default layout, use it if the layout should be set to default. */
	if ( 'default' == $layout || empty( $layout ) || !in_array( $layout, $theme_layouts[0] ) )
		$layout = nifty_get_setting( 'theme_layout' );

	/* Return the layout. */
	return apply_filters( 'nifty_theme_layout', "layout-{$layout}" );
}

/**
 * Get the post layout based on the given post ID.
 *
 * @since 12.09
 */
function nifty_get_post_layout( $post_id ) {
	$post_layout = get_post_meta( $post_id, '_nifty_post_layout', true );
	return ( !empty( $post_layout ) ? $post_layout : 'default' );
}

/**
 * Get a specific layout's text string.
 *
 * @since 12.09
 * @param string $layout
 * @return string
 */
function nifty_theme_layouts_get_string( $layout ) {

	/* Get an array of post layout strings. */
	$strings = array(
		'default' => __( 'Default',               'nifty' ),
		'1c'      => __( 'One Column',            'nifty' ),
		'2c-l'    => __( 'Two Columns, Left',     'nifty' ),
		'2c-r'    => __( 'Two Columns, Right',    'nifty' ),
		'3c-l'    => __( 'Three Columns, Left',   'nifty' ),
		'3c-r'    => __( 'Three Columns, Right',  'nifty' ),
		'3c-c'    => __( 'Three Columns, Center', 'nifty' )
	);

	/* Return the layout's string if it exists. Else, return the layout slug. */
	return ( ( isset( $strings[$layout] ) ) ? $strings[$layout] : $layout );
}

/**
 * Post layouts admin setup.
 *
 * @since 12.09
 */
function nifty_post_layouts_admin_setup() {

	/* Gets available public post types. */
	$post_types = get_post_types( array( 'public' => true ), 'objects' );

	/* For each available post type, create a meta box on its edit page. */
	foreach ( $post_types as $type ) {
		if ( post_type_supports( $type->name, 'post-layouts' ) )
			add_meta_box( 'post-layouts-meta-box', __( 'Layouts', 'nifty' ), 'nifty_post_layouts_meta_box', $type->name, 'side', 'default' );
	}

	/* Saves the post format on the post editing page. */
	add_action( 'save_post', 'nifty_post_layouts_save_post', 10, 2 );
}

/**
 * Displays a meta box of radio selectors on the post editing screen, which allows theme users to select
 * the layout they wish to use for the specific post.
 *
 * @since 12.09
 */
function nifty_post_layouts_meta_box( $post, $box ) {
	global $_wp_theme_features;

	/* Get the available post layouts. */
	if ( !isset( $_wp_theme_features['nifty-core-theme-layouts'] ) )
		return;

	$theme_layouts = $_wp_theme_features['nifty-core-theme-layouts'];
	$post_layouts = $theme_layouts[0];

	/* Get the current post's layout. */
	$post_layout = nifty_get_post_layout( $post->ID ); ?>

	<div class="post-layouts">

		<input type="hidden" name="post-layouts-meta-box-nonce" value="<?php echo wp_create_nonce( basename( __FILE__ ) ); ?>" />

		<p><?php _e( 'Select a specific layout structure for the post.', 'nifty' ); ?></p>

		<div class="post-layout-wrap">
			<ul>
				<li><input type="radio" name="post-layout" id="post-layout-default" value="default" <?php checked( $post_layout, 'default' );?> /> <label for="post-layout-default"><?php echo esc_html( nifty_theme_layouts_get_string( 'default' ) ); ?></label></li>

				<?php foreach ( $post_layouts as $layout ) : ?>
				<li><input type="radio" name="post-layout" id="post-layout-<?php echo esc_attr( $layout ); ?>" value="<?php echo esc_attr( $layout ); ?>" <?php checked( $post_layout, $layout ); ?> /> <label for="post-layout-<?php echo esc_attr( $layout ); ?>"><?php echo esc_html( nifty_theme_layouts_get_string( $layout ) ); ?></label></li>
				<?php endforeach; ?>
			</ul>
		</div>

	</div><!-- .post-layouts --><?php
}

/**
 * Saves the post layout metadata if on the post editing screen in the admin.
 *
 * @since 12.09
 */
function nifty_post_layouts_save_post( $post_id, $post ) {

	/* Verify the nonce for the post layouts meta box. */
	if ( !post_type_supports( $post->post_type, 'post-layouts' ) || !isset( $_POST['post-layouts-meta-box-nonce'] ) || !wp_verify_nonce( $_POST['post-layouts-meta-box-nonce'], basename( __FILE__ ) ) )
		return $post_id;

	/* Get the previous post layout. */
	$old_layout = nifty_get_post_layout( $post_id );

	/* Get the submitted post layout. */
	$new_layout = esc_attr( $_POST['post-layout'] );

	/* If the old layout doesn't match the new layout, update the post layout meta. */
	if ( $old_layout !== $new_layout )
		update_post_meta( $post_id, '_nifty_post_layout', $new_layout );
}

/**
 * Removes Primary, Secundary and Subsidiary widget areas on the
 * Full Width page template.
 *
 * @since 12.09
 * @uses sidebars_widgets Filter to remove widget areas
 */
function nifty_disable_sidebars( $sidebars_widgets ) {
	global $wp_query;

	if ( is_singular() ) {
		$template = get_post_meta( $wp_query->post->ID, "_wp_{$wp_query->post->post_type}_template", true );

		if ( "{$wp_query->post->post_type}-no-aside.php" == $template )
			$sidebars_widgets = array( false );

		else if ( "{$wp_query->post->post_type}-no-primary-secondary.php" == $template ) {
			$sidebars_widgets['primary'] = false;
			$sidebars_widgets['secondary'] = false;
		}

		if ( 'layout-1c' == nifty_get_theme_layout() ) {
			$sidebars_widgets['primary'] = false;
			$sidebars_widgets['secondary'] = false;
		}
	}

	return $sidebars_widgets;
}

/**
 * Overwrites the default widths for embeds. This function overwrites what the $content_width
 * variable handles with context-based widths.
 *
 * @since 12.09
 */
function nifty_embed_defaults( $args ) {

	if ( 'nifty' === get_stylesheet() ) {

		$layout = nifty_get_theme_layout();

		if ( 'layout-3c-l' == $layout || 'layout-3c-r' == $layout || 'layout-3c-c' == $layout )
			$args['width'] = 500;
		elseif ( 'layout-1c' == $layout )
			$args['width'] = 928;
		else
			$args['width'] = 600;
	}

	return $args;
}

/**
 * Function for deciding which pages should have a one-column layout.
 *
 * @since 12.09
 */
function nifty_one_column() {

	if ( !is_active_sidebar( 'primary' ) && !is_active_sidebar( 'secondary' ) )
		add_filter( 'nifty_theme_layout', 'nifty_layout_one_column' );

	if ( is_attachment() )
		add_filter( 'nifty_theme_layout', 'nifty_layout_one_column' );
}

/**
 * Filters 'nifty_theme_layout' by returning 'layout-1c'.
 *
 * @since 12.09
 */
function nifty_layout_one_column( $layout ) {
	return 'layout-1c';
}


<?php
/**
 * BadgeStack Template Loading
 *
 * @package BadgeStack
 */

/**
 * Our template part loader function. 
 *
 * Based on bbp_get_template_part
 * 
 * First checks the theme, if not found, load from the templates included with the plugin.
 *	
 */
function badgestack_get_template_part( $slug, $name = null ) {

	// Execute code for this part
	do_action( 'get_template_part_' . $slug, $slug, $name );

	// Setup possible parts
	$templates = array();
	if ( isset( $name ) )
		$templates[] = $slug . '-' . $name . '.php';
	$templates[] = $slug . '.php';
	// Return the part that is found
	return badgestack_locate_template( $templates, true, false );
}

/**
 * Find the BadgeStack template to load.
 *
 * Based on bbp_locate_template
 *	
 */
function badgestack_locate_template( $template_names, $load = false, $require_once = true ) {

	// No file found yet
	$located = false;

	// Try to find a template file
	foreach ( (array) $template_names as $template_name ) {
		// Continue if template is empty
		if ( empty( $template_name ) )
			continue;

		// Trim off any slashes from the template name
		$template_name = ltrim( $template_name, '/' );

		// Check child theme first
		if ( file_exists( trailingslashit( STYLESHEETPATH ) . $template_name ) ) {
			$located = trailingslashit( STYLESHEETPATH ) . $template_name;
			break;

		// Check parent theme next
		} elseif ( file_exists( trailingslashit( TEMPLATEPATH ) . $template_name ) ) {
			$located = trailingslashit( TEMPLATEPATH ) . $template_name;
			break;

		// Check theme compatibility last
		} elseif ( file_exists( trailingslashit( badgestack_get_theme_compat_dir() ) . $template_name ) ) {
			$located = trailingslashit( badgestack_get_theme_compat_dir() ) . $template_name;
			break;
		}
	}
	
	if ( ( true == $load ) && !empty( $located ) )
		load_template( $located, $require_once );

	return $located;
}

/**
 * Returns the path to the BadgeStack theme compat template folder.
 *
 * 
 *	
 */
function badgestack_get_theme_compat_dir() {
	return badgestack_get_directory_path() . '/templates/';
}

add_action( 'init', 'badgestack_add_rewrite_rules' );


/**
 * Add rewrite rules for custom BadgeStack pages (dashboard).
 *
 * 
 *	
 */
function badgestack_add_rewrite_rules() {
	$options = get_option( 'badgestack_settings' );
	add_rewrite_rule( '^' . $options['dashboard_slug'] . '/?' , 'index.php?pagename=badgestack-dashboard', 'top' );
	add_rewrite_rule( '^' . $options['user_profile_slug'] . '/([^/]+)/?' , 'index.php?pagename=badgestack-user-profile&badgestack-username=$matches[1]', 'top' );
}

add_action( 'init', 'badgestack_add_rewrite_tags' );

function badgestack_add_rewrite_tags() {
	add_rewrite_tag( '%badgestack-username%', '([^/]+)' );
}

add_action( 'template_include', 'badgestack_template_include_theme_compat' );

/**
 * Reset main query vars and filter 'the_content' to output a BadgeStack
 * template part as needed.
 *
 * Based on bbp_template_include_theme_compat()
 *	
 */
function badgestack_template_include_theme_compat( $file ) {
	global $badgestack, $wp_current_filter;

	// Badge Dashboard
	if ( get_query_var( 'pagename' ) == 'badgestack-dashboard' ) {

		return locate_template( 'page-dashboard.php' );

		badgestack_theme_compat_reset_post( array(
			'ID'             => 0,
			'post_title'     => 'Badge Dashboard',
			'post_author'    => 0,
			'post_date'      => 0,
			'post_content'   => '',
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'is_single'      => true,
			'comment_status' => 'closed', ) 
		);
		$file = locate_template( 'page.php' );
		$badgestack->theme_compat_page = 'dashboard';
	}

	// User Profile
	if ( get_query_var( 'pagename' ) == 'badgestack-user-profile' ) {
		badgestack_theme_compat_reset_post( array(
			'ID'             => 0,
			'post_title'     => 'User Profile for ' . get_query_var( 'badgestack-username' ),
			'post_author'    => 0,
			'post_date'      => 0,
			'post_content'   => '',
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'is_single'      => true,
			'comment_status' => 'closed', ) 
		);
		$file = locate_template( 'page.php' );
		$badgestack->theme_compat_page = 'user-profile';
	}

	// Achievement Archive
	if ( get_query_var( 'post_type' ) 
		 && in_array( get_query_var( 'post_type' ), badgestack_get_achievement_types_slugs() ) 
		 && is_archive() ) {

		return locate_template( 'archive-' . get_query_var( 'post_type' ) . '.php' );

		badgestack_theme_compat_reset_post( array(
			'ID'             => 0,
			'post_title'     => get_query_var( 'post_type' ),
			'post_author'    => 0,
			'post_date'      => 0,
			'post_content'   => '',
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'is_single'      => false,
			'is_archive'     => true,
			'comment_status' => 'closed', ) 
		);
		badgestack_turn_on_theme_compat_mode();
		$file = locate_template( 'page.php' );
	}
	
	// Achievement Single 
	if ( get_query_var( 'post_type' ) 
		 && in_array( get_query_var( 'post_type' ), badgestack_get_achievement_types_slugs()  ) 
		 && is_archive() ) {
		badgestack_theme_compat_reset_post( array(
			'ID'             => 0,
			'post_title'     => badgestack_get_achievement_title(),
			'post_author'    => 0,
			'post_date'      => 0,
			'post_content'   => '',
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'is_single'      => false,
			'is_archive'     => true,
			'comment_status' => 'closed', ) 
		);

		badgestack_turn_on_theme_compat_mode();
		$file = locate_template( 'page.php' );
	}

	return $file;
}

/**
 * Resets the post data after the content has displayed
 *
 * based on bbp_theme_compat_reset_post()
 * 
 * 
 */
function badgestack_theme_compat_reset_post( $args = array() ) {
	global $badgestack, $wp_query, $post;
	$defaults = array(
		'ID'              => - 9999,
		'post_title'      => '',
		'post_author'     => 0,
		'post_date'       => 0,
		'post_content'    => '',
		'post_type'       => 'page',
		'post_status'     => 'publish',
		'post_name'       => '',
		'comment_status'  => 'closed',
		'ping_status'     => 'closed',
		'is_404'          => false,
		'is_page'         => true,
		'is_single'       => false,
		'is_singular'     => true,
		'is_archive'      => false,
		'is_tax'          => false,
		'comment_count'   => 0,
	);
	$dummy = wp_parse_args( $args, $defaults );

	// Clear out the post related globals
	unset( $wp_query->posts );
	unset( $wp_query->post  );
	unset( $post            );
	global $post;
	// Setup the dummy post object
	$wp_query->post                 = new stdClass; 
	$wp_query->post->ID             = $dummy['ID'];
	$wp_query->post->post_title     = $dummy['post_title'];
	$wp_query->post->post_author    = $dummy['post_author'];
	$wp_query->post->post_date      = $dummy['post_date'];
	$wp_query->post->post_content   = $dummy['post_content'];
	$wp_query->post->post_type      = $dummy['post_type'];
	$wp_query->post->post_status    = $dummy['post_status'];
	$wp_query->post->post_name      = $dummy['post_name'];
	$wp_query->post->comment_status = $dummy['comment_status'];
	$wp_query->post->ping_status    = $dummy['ping_status'];
	$wp_query->post->comment_count  = $dummy['comment_count'];

	// Set the $post global
	$post = $wp_query->post;

	// Setup the dummy post loop
	$wp_query->posts[0] = $wp_query->post;

	// Prevent comments form from appearing
	$wp_query->post_count = 1;
	$wp_query->is_404     = $dummy['is_404'];
	$wp_query->is_page    = $dummy['is_page'];
	$wp_query->is_single  = $dummy['is_single'];
	$wp_query->is_archive = $dummy['is_archive'];
	$wp_query->is_tax     = $dummy['is_tax'];

	badgestack_turn_on_theme_compat_mode();

	remove_all_filters( 'the_content' );
	add_filter( 'the_content', 'badgestack_replace_the_content' );
}

/**
 * Turns on theme compatibility mode.
 *
 * 
 * 
 */
function badgestack_turn_on_theme_compat_mode() {
	global $badgestack;
	$badgestack->theme_compat_mode = 'on';
}

/**
 * Check if theme compabitibility mode is on.
 *
 * 
 * 
 */
function badgestack_is_theme_compat_mode_on() {
	global $badgestack;
	if ( isset( $badgestack->theme_compat_mode ) && $badgestack->theme_compat_mode == 'on' )
		return true;
	else
		return false;
}

function badgestack_get_theme_compat_page_name() {
	global $badgestack;
	if ( isset( $badgestack->theme_compat_page ) )
		return $badgestack->theme_compat_page; 
	else
		return false;
}
/**
 * Replace the content if we're in theme compat mode.
 *
 * 
 * 
 */
function badgestack_replace_the_content( $content ) {
	global $badgestack;
	
	if ( ! badgestack_is_theme_compat_mode_on() )
		return $content;

	ob_start();

	if ( badgestack_get_theme_compat_page_name() == 'dashboard' )
		badgestack_get_template_part( 'page', 'dashboard' );
	else if ( badgestack_get_theme_compat_page_name() == 'user-profile' )
		badgestack_get_template_part( 'page', 'user-profile' );
	else if ( is_single() )
		badgestack_get_template_part( 'content-single', get_query_var( 'post_type' ) );
	else if ( is_archive() ) {
		badgestack_get_template_part( 'content-archive', get_query_var( 'post_type' ) );
	}
		
	$content = ob_get_contents();
	ob_end_clean();
	// Empty globals that aren't being used in this loop anymore
	$GLOBALS['withcomments'] = false;
	// $GLOBALS['post']         = false;
	
	return $content;
}

/**
 * Replace the content if we're in theme compat mode.
 *
 * 
 * hi
 */
function badgestack_get_related_achievements_template_parts() {
	global $post;
	$related_achievements = badgestack_get_required_achievements_for_achievement( $post->ID );
	foreach ( $related_achievements as $achievement ) {
		$post = $achievement;
		badgestack_get_achievement_template_part();
	}
}

/**
 * Replace the content if we're in theme compat mode.
 *
 * 
 * 
 */
function badgestack_get_achievement_template_part() {
	global $post;
	badgestack_get_template_part( 'content', $post->post_type );
	echo '<div style="margin-left:20px">';
	badgestack_get_related_achievements_template_parts();
	echo '</div>';
}

function badgestack_get_achievement_title() {
	global $wp_query;
	$achievmeent_id = badgestack_get_achievement_id();
	return get_the_title( $achievmeent_id );
}

function badgestack_get_achievement_id() {
	global $wp_query;
	$achievement_id = $wp_query->post->ID;
	return $achievement_id;
	
}

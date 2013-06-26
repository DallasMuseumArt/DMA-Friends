<?php
/**
 * Functions for making various theme elements context-aware. Controls things such as the smart 
 * and logical body, post, and comment CSS classes.
 *
 * @package Nifty
 * @subpackage Functions
 */

/**
 * Nifty's main contextual function.
 *
 * @since 12.09
 * @return array $nifty->context Several contexts based on the current page.
 */
function nifty_get_context() {
	global $nifty;

	/* If $nifty->context has been set, don't run through the conditionals again. Just return the variable. */
	if ( isset( $nifty->context ) )
		return $nifty->context;

	global $wp_query;

	/* Set some variables for use within the function. */
	$nifty->context = array();
	$object = $wp_query->get_queried_object();
	$object_id = $wp_query->get_queried_object_id();

	/* Front page of the site. */
	if ( is_front_page() )
		$nifty->context[] = 'home';

	/* Blog page. */
	if ( is_home() ) {
		$nifty->context[] = 'blog';
	}

	/* Singular views. */
	elseif ( is_singular() ) {
		$nifty->context[] = 'singular';
		$nifty->context[] = "singular-{$object->post_type}";
		$nifty->context[] = "singular-{$object->post_type}-{$object_id}";
	}

	/* Archive views. */
	elseif ( is_archive() ) {
		$nifty->context[] = 'archive';

		/* Taxonomy archives. */
		if ( is_tax() || is_category() || is_tag() ) {
			$nifty->context[] = 'taxonomy';
			$nifty->context[] = "taxonomy-{$object->taxonomy}";

			$slug = ( ( 'post_format' == $object->taxonomy ) ? str_replace( 'post-format-', '', $object->slug ) : $object->slug );
			$nifty->context[] = "taxonomy-{$object->taxonomy}-" . sanitize_html_class( $slug, $object->term_id );
		}

		/* Post type archives. */
		elseif ( function_exists( 'is_post_type_archive' ) && is_post_type_archive() ) {
			$post_type = get_post_type_object( get_query_var( 'post_type' ) );
			$nifty->context[] = "archive-{$post_type->name}";
		}

		/* User/author archives. */
		elseif ( is_author() ) {
			$nifty->context[] = 'user';
			$nifty->context[] = 'user-' . sanitize_html_class( get_the_author_meta( 'user_nicename', $object_id ), $object_id );
		}

		/* Time/Date archives. */
		else {
			if ( is_date() ) {
				$nifty->context[] = 'date';
				if ( is_year() )
					$nifty->context[] = 'year';
				if ( is_month() )
					$nifty->context[] = 'month';
				if ( get_query_var( 'w' ) )
					$nifty->context[] = 'week';
				if ( is_day() )
					$nifty->context[] = 'day';
			}
			if ( is_time() ) {
				$nifty->context[] = 'time';
				if ( get_query_var( 'hour' ) )
					$nifty->context[] = 'hour';
				if ( get_query_var( 'minute' ) )
					$nifty->context[] = 'minute';
			}
		}
	}

	/* Search results. */
	elseif ( is_search() ) {
		$nifty->context[] = 'search';
	}

	/* Error 404 pages. */
	elseif ( is_404() ) {
		$nifty->context[] = 'error-404';
	}

	return $nifty->context;
}

/**
 * Provides classes for the <body> element depending on page context.
 *
 * @since 12.09
 * @param string|array $class Additional classes for more control
 */
function nifty_body_class( $class = '' ) {
	global $wp_query;

	/* WordPress and Locale */
	$classes = array( 'wordpress', get_locale() );

	/* Check if the current theme is a parent or child theme */
	$classes[] = ( is_child_theme() ? 'child-theme' : 'parent-theme' );

	/* Multisite check adds the 'multisite' class and the blog ID */
	if ( is_multisite() ) {
		$classes[] = 'multisite';
		$classes[] = 'blog-' . get_current_blog_id();
	}

	/* Date classes. */
	$time = time() + ( get_option( 'gmt_offset' ) * 3600 );
	$classes[] = strtolower( gmdate( '\yY \mm \dd \hH l', $time ) );

	/* WP admin bar. */
	if ( is_admin_bar_showing() )
		$classes[] = 'admin-bar';

	/* Use the '.custom-background' class to integrate with the WP background feature. */
	if ( get_background_image() || get_background_color() )
		$classes[] = 'custom-background';

	/* Add the '.custom-header' class if the user is using a custom header. */
	if ( get_header_image() )
		$classes[] = 'custom-header';

	/* Is the current user logged in. */
	$classes[] = ( is_user_logged_in() ) ? 'logged-in' : 'logged-out';

	/* Merge base contextual classes with $classes. */
	$classes = array_merge( $classes, nifty_get_context() );

	/* Singular post (post_type) classes. */
	if ( is_singular() ) {

		/* Get the queried post object. */
		$post = $wp_query->get_queried_object();
		$post_id = (int) $wp_query->get_queried_object_id();

		/* Checks for custom template. */
		$template = str_replace( array ( "{$post->post_type}-template-", "{$post->post_type}-" ), '', basename( get_post_meta( $post_id, "_wp_{$post->post_type}_template", true ), '.php' ) );

		if ( !empty( $template ) ) {
			$classes[] = "{$post->post_type}-template";
			$classes[] = "{$post->post_type}-template-{$template}";
		}

		/* Attachment mime types. */
		if ( is_attachment() ) {
			foreach ( explode( '/', get_post_mime_type() ) as $type )
				$classes[] = "attachment-{$type}";
		}
	}

	/* Paged views. */
	if ( ( ( $page = $wp_query->get( 'paged' ) ) || ( $page = $wp_query->get( 'page' ) ) ) && $page > 1 )
		$classes[] = 'paged paged-' . intval( $page );

	/* Input class. */
	if ( !empty( $class ) ) {
		if ( !is_array( $class ) )
			$class = preg_split( '#\s+#', $class );
		$classes = array_merge( $classes, $class );
	}

	/* Apply the filters for WP's 'body_class'. */
	$classes = apply_filters( 'body_class', $classes, $class );

	/* Join all the classes into one string. */
	$class = join( ' ', $classes );

	/* Print the body class. */
	echo apply_filters( 'nifty_body_class', $class );
}

/**
 * Function for handling what the browser/search engine title should be.
 *
 * @since 12.09
 * @global $wp_query
 */
function nifty_document_title() {
	global $wp_query;

	$doctitle = '';
	$separator = '&raquo;';

	if ( is_front_page() && is_home() )
		$doctitle = get_bloginfo( 'name' ) . ' ' .$separator . ' ' . get_bloginfo( 'description' );

	elseif ( is_home() || is_singular() ) {
		$id = $wp_query->get_queried_object_id();

		$doctitle = get_post_meta( $id, '_nifty_title', true );
		
		if ( !$doctitle && is_front_page() )
			$doctitle = get_bloginfo( 'name' ) . ' ' . $separator . ' ' . get_bloginfo( 'description' );

		elseif ( !$doctitle )
			$doctitle = get_post_field( 'post_title', $id );
	}

	elseif ( is_archive() ) {

		if ( is_category() || is_tag() || is_tax() ) {
			$term = $wp_query->get_queried_object();
			$doctitle = $term->name;
		}

		elseif ( function_exists( 'is_post_type_archive' ) && is_post_type_archive() ) {
			$post_type = get_post_type_object( get_query_var( 'post_type' ) );
			$doctitle = $post_type->labels->name;
		}

		elseif ( is_author() )
			$doctitle = get_the_author_meta( 'display_name', get_query_var( 'author' ) );

		elseif ( is_date () ) {

			if ( is_day() )
				$doctitle = sprintf( __( 'Archive for %1$s', 'nifty' ), get_the_time( __( 'F jS, Y', 'nifty' ) ) );

			elseif ( is_month() )
				$doctitle = sprintf( __( 'Archive for %1$s', 'nifty' ), single_month_title( ' ', false) );

			elseif ( is_year() )
				$doctitle = sprintf( __( 'Archive for %1$s', 'nifty' ), get_the_time( __( 'Y', 'nifty' ) ) );
		}
	}

	elseif ( is_search() )
		$doctitle = sprintf( __( 'Search results for &quot;%1$s&quot', 'nifty' ), esc_attr( get_search_query() ) );

	elseif( is_404() )
		$doctitle = __( 'Page Not Found', 'nifty' );

	/* If is a paged page. */
	if ( ( ( $page = $wp_query->get( 'paged' ) ) || ( $page = $wp_query->get( 'page' ) ) ) && $page > 1 )
		$doctitle = sprintf( __( '%1$s Page %2$s', 'nifty' ), $doctitle . $separator, $page );

	/* Apply the wp_title filters so we're compatible with plugins. */
	$doctitle = apply_filters( 'wp_title', $doctitle, $separator, '' );

	/* Print the title. */
	echo apply_filters( 'nifty_document_title', esc_attr( $doctitle ) );
}

/**
 * Sets a class for each comment. Sets alt, odd/even, and author/user classes. Adds author, user, 
 * and reader classes. Needs more work because WP, by default, assigns even/odd backwards 
 * (Odd should come first, even second).
 *
 * @since 12.09
 */
function nifty_comment_class( $class = '' ) {
	global $post, $comment;

	/* Gets default WP comment classes. */
	$classes = get_comment_class( $class );

	/* User classes to match user role and user. */
	if ( $comment->user_id > 0 ) {

		/* Create new user object. */
		$user = new WP_User( $comment->user_id );

		/* Set a class with the user's role. */
		if ( is_array( $user->roles ) ) {
			foreach ( $user->roles as $role )
				$classes[] = "role-{$role}";
		}

		/* Set a class with the user's name. */
		$classes[] = 'user-' . sanitize_html_class( $user->user_nicename, $user->ID );
	}

	/* If not a registered user */
	else
		$classes[] = 'reader';

	/* Comment by the entry/post author. */
	if ( $post = get_post( $post->ID ) ) {
		if ( $comment->user_id === $post->post_author )
			$classes[] = 'entry-author';
	}

	/* Join all the classes into one string and echo them. */
	$class = join( ' ', $classes );

	echo apply_filters( 'nifty_comment_class', $class );
}

<?php
/**
 * SEO and header functions. Many of the functions handle basic <meta> elements
 * for the <head> area of the site.
 * 
 * @package Nifty
 * @subpackage Functions
 */

/* Initializes all the core seo functions. */
add_action( 'init', 'nifty_core_seo_init' );

/**
 * Function to load all the core seo functions.
 *
 * @since 12.09
 */
function nifty_core_seo_init() {

	/* 301 Redirect */
	add_action( 'template_redirect', 'nifty_singular_redirect', 1 );

	/* Mate-Tags. */
	add_action( 'wp_head', 'nifty_meta_description', 1 );
	add_action( 'wp_head', 'nifty_meta_keywords', 1 );
	add_action( 'wp_head', 'nifty_meta_robots', 1 );
	add_action( 'wp_head', 'nifty_meta_canonical', 1 );
}

/**
 * Generates the meta description.
 * 
 * @since 12.09
 */
function nifty_meta_description() {
	global $wp_query;

	$description = '';

	if ( is_home() )
		$description = get_bloginfo( 'description' );

	elseif ( is_singular() ) {

		/* Get the meta value for the 'Description' meta key. */
		$description = get_post_meta( $wp_query->post->ID, '_nifty_description', true );

		/* If no description was found and viewing the site's front page, use the site's description. */
		if ( empty( $description ) && is_front_page() )
			$description = get_bloginfo( 'description' );

		/* For all other singular views, get the post excerpt. */
		elseif ( empty( $description ) )
			$description = get_post_field( 'post_excerpt', $wp_query->post->ID );
	}

	elseif ( is_archive() ) {

		if ( is_category() || is_tag() || is_tax() )
			$description = term_description( '', get_query_var( 'taxonomy' ) );

		elseif ( is_author() )
			$description = get_the_author_meta( 'description', get_query_var( 'author' ) );

		elseif ( function_exists( 'is_post_type_archive' ) && is_post_type_archive() ) {
			$post_type = get_post_type_object( get_query_var( 'post_type' ) );
			$description = $post_type->description;
		}
	}

	/* Format the meta description. */
	if ( !empty( $description ) )
		$description = '<meta name="description" content="' . str_replace( array( "\r", "\n", "\t" ), ' ', esc_attr( $description ) ) . '">' . "\n";

	echo apply_filters( 'nifty_meta_description', $description );
}

/**
 * Generates meta keywords/tags for the site.
 *
 * @since 12.09
 */
function nifty_meta_keywords() {
	global $wp_query;

	$keywords = '';

	if ( is_singular() && !is_preview() ) {
		$keywords = get_post_meta( $wp_query->post->ID, '_nifty_keywords', true );

		if ( empty( $keywords ) ) {
			$taxonomies = get_object_taxonomies( $wp_query->post->post_type );

			if ( is_array( $taxonomies ) ) {
				foreach ( $taxonomies as $tax ) {
					if ( $terms = get_the_term_list( $wp_query->post->ID, $tax, '', ', ', '' ) )
						$keywords[] = $terms;
				}
			}

			if ( !empty( $keywords ) && is_array( $keywords ) )
				$keywords = join( ', ', $keywords );
		}
	}

	if ( !empty( $keywords ) )
		$keywords = '<meta name="keywords" content="' . esc_attr( strip_tags( $keywords ) ) . '">' . "\n";

	echo apply_filters( 'nifty_meta_keywords', $keywords );
}

/**
 * Sets the default meta robots setting. If private, don't send meta info to the header.
 *
 * @since 12.09
 */
function nifty_meta_robots() {
	if ( !get_option( 'blog_public' ) )
		return;

	global $wp_query;

	$robots = '';

	if ( is_singular() )
		$robots = 'index, follow';

	elseif ( is_search() )
		$robots = 'noindex, follow';

	elseif( is_404() )
		$robots = 'noindex, nofollow, noarchive';

	if ( !empty( $robots ) )
		$robots = '<meta name="robots" content="' . $robots . '">' . "\n";

	echo apply_filters( 'nifty_meta_robots', $robots );
}

/**
 * Output rel=canonical for singular queries
 *
 * @since 12.09
 */
function nifty_meta_canonical() {
	if ( is_404() || is_search() )
		return false;

	global $wp_query;

	$id = $wp_query->get_queried_object_id();

	$canonical = '';

	if ( is_front_page() )
		$canonical = home_url( '/' );

	elseif ( is_singular() ) {
		remove_action( 'wp_head', 'rel_canonical' );
		$canonical = get_permalink( $id );
	}

	elseif ( is_archive() ) {

		if ( is_category() || is_tag() || is_tax() ) {
			$term = $wp_query->get_queried_object();
			$canonical = get_term_link( $term, $term->taxonomy );
		}

		elseif ( is_author() )
			$canonical = get_author_posts_url( $id );

		elseif ( is_date() ) {

			if ( is_day() )
				$canonical = get_day_link( get_query_var( 'year' ), get_query_var( 'monthnum' ), get_query_var( 'day' ) );

			elseif ( is_month() )
				$canonical = get_month_link( get_query_var( 'year' ), get_query_var( 'monthnum' ) );
	
			elseif ( is_year() )
				$canonical = get_year_link( get_query_var( 'year' ) );
		}
	}

	if ( !empty( $canonical ) )
		$canonical = '<link rel="canonical" href="' . $canonical . '">' . "\n";

	echo apply_filters( 'nifty_meta_canonical', $canonical );
}

/**
 * Do Redirect 301.
 * 
 * @since 12.09
 */
function nifty_singular_redirect() {
	global $wp_query;

	if ( is_singular() && $redirect = get_post_meta( $wp_query->post->ID, '_nifty_redirect', true ) ) {
		wp_redirect( $redirect, 301 );
		exit();
	}
}


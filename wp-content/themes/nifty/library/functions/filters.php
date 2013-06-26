<?php
/**
 * Filterable content available throughout the theme. Most action hooks will use the apply_atomic() function,
 * wich creates contextual filter hooks.
 *
 * @package Nifty
 * @subpackage Functions
 */

/**
 * Generates the relevant template info. Adds template meta with theme version.
 *
 * @since 12.09
 */
function nifty_meta_generator() {
	$data = wp_get_theme( 'nifty' );
	$template = '<meta name="generator" content="' . esc_attr( "{$data->Name} {$data->Version}" ) . '">' . "\n";
	echo apply_filters( 'nifty_meta_generator', $template );
}

/**
 * Dynamic element to wrap the site title in.
 *
 * @since 12.09
 */
function nifty_site_title() {

	/* Get the site title. */
	if ( $title = get_bloginfo( 'name' ) )
		$title = '<div id="branding"><h1 id="site-title"><a href="' . home_url() . '" title="' . esc_attr( $title ) . '" rel="home"><span>' . $title . '</span></a></h1>';

	/* Display the site title and apply filters for developers to overwrite. */
	echo apply_filters( 'nifty_site_title', $title );
}

/**
 * Dynamic element to wrap the site description in.
 *
 * @since 12.09
 */
function nifty_site_description() {

	/* Get the site description. */
	if ( $desc = get_bloginfo( 'description' ) )
		$desc = '<h2 id="site-description"><span>' . $desc . '</span></h2></div> <!-- #branding -->' . "\n";

	/* Display the site description and apply filters for developers to overwrite. */
	echo apply_filters( 'nifty_site_description', $desc );
}

function nifty_site_custom_header() {

	if ( !current_theme_supports( 'custom-header' ) )
		return '';

	$image = '';

	$attr = array(
		'src' => get_header_image(),
		'height' => get_custom_header()->height,
		'width' => get_custom_header()->width,
		'header-text' => false,
	);

	extract( $attr );

	$image = "<div id='custom-header'><img src='{$src}' height='{$height}' width='{$width}' alt='' /></div>";

	echo apply_filters( 'nifty_site_custom_header', $image );
}

/**
 * Displays the default entry title. Wraps the title in the appropriate header tag.
 * 
 * @since 12.09
 */
function nifty_entry_title() {
	global $wp_query;

	echo do_shortcode( apply_filters( 'nifty_entry_title', nifty_entry_title_shortcode(), $wp_query->post ) );
}

/**
 * Default entry metadata for posts. Shows the author, date, and edit link.
 * 
 * @since 12.09
 */
function nifty_entry_meta() {
	global $wp_query;

	$metadata = '';

	if ( 'post' == $wp_query->post->post_type )
		$metadata = '<p class="entry-meta">' . __( '<span class="entry-meta-prep entry-meta-prep-author">By</span> [entry-author] <span class="entry-meta-prep entry-meta-prep-published">on</span> [entry-published] [entry-edit-link before="| "]', 'nifty' ) . '</p>';

	echo do_shortcode( apply_filters( 'nifty_entry_meta', $metadata, $wp_query->post ) );
}

/**
 * Displays the default entry utility data. Shows the category, tag, and comments link.
 * 
 * @since 12.09
 */
function nifty_entry_utility() {
	global $wp_query;

	$utilitydata = '';

	if ( 'post' == $wp_query->post->post_type )
		$utilitydata = '<p class="entry-utility">[entry-terms taxonomy="category" before="' . __( 'Posted in', 'nifty' ) . ' "] [entry-terms taxonomy="post_tag" before="| ' . __( 'Tagged', 'nifty' ) . ' "] [entry-permalink before="| "] [entry-comments-link before="| "]</p>';

	else if ( is_page() && current_user_can( 'edit_pages' ) )
		$utilitydata = '<p class="entry-utility">[entry-edit-link]</p>';

	echo do_shortcode( apply_filters( 'nifty_entry_utility', $utilitydata, $wp_query->post ) );
}

/**
 * Display the <link> element for Favicon.
 *
 * @since 12.09
 */
function nifty_favicon() {
	global $nifty;

	$favicon = '';

	if ( file_exists( $nifty->child_theme_dir . 'images/favicon.ico' ) )
		$favicon = "<link rel='shortcut icon' type='image/x-icon' href='{$nifty->child_theme_uri}images/favicon.ico' />" . "\n";

	echo apply_filters( 'nifty_favicon', $favicon );
}

/**
 * Display the <link> element for Apple Touch Icon.
 *
 * @since 12.09
 */
function nifty_apple_touch_icon() {
	global $nifty;

	$apple_touch_icon = '';

	if ( file_exists( $nifty->child_theme_dir . 'images/apple-touch-icon.png' ) )
		$apple_touch_icon = "<link rel='apple-touch-icon' href='{$nifty->child_theme_uri}images/apple-touch-icon.png' />" . "\n";

	echo apply_filters( 'nifty_apple_touch_icon', $apple_touch_icon );
}

/**
 * Displays the pinkback URL.
 * 
 * @since 12.09
 */
function nifty_pingback() {
	$pingback = '<link rel="pingback" href="' . get_bloginfo( 'pingback_url' ) . '" />' . "\n";
	echo apply_filters( 'nifty_head_pingback', $pingback );
}

/**
 * Trim separator + space from beginning and end in case a plugin adds it like bbPress.
 *
 * @since 12.09
 */
function nifty_wp_title( $doctitle, $separator, $position ) {
	return trim( $doctitle, "{$separator} " );
}

/**
 * Filters main feed links for the site.
 * 
 * @since 12.09
 * @param string $output
 * @param string $feed
 * @return string $output
 */
function nifty_feed_link( $output, $feed ) {
	if ( $url = nifty_get_setting( 'feed_url' ) ) {
		$outputarray = array( 'rss' => $url, 'rss2' => $url, 'atom' => $url, 'rdf' => $url, 'comments_rss2' => $url );
		$outputarray[$feed] = $url;
		$output = $outputarray[$feed];
	}

	return $output;
}

/**
 * Filters the category, author, and tag feed links. This changes all of these feed 
 * links to the user's alternate feed URL.
 *
 * @since 12.09
 * @param string $link
 * @return string $link
 */
function nifty_other_feed_link( $link ) {
	if ( nifty_get_setting( 'feeds_redirect' ) && $url = nifty_get_setting( 'feed_url' ) )
		$link = esc_url( $url );

	return $link;
}

/**
 * Displays the footer insert from the theme settings page.
 *
 * @since 12.09
 */
function nifty_footer_insert() {
	if ( $footer_insert = stripslashes( nifty_get_setting( 'footer_insert' ) ) )
		echo do_shortcode( apply_filters( 'nifty_footer_insert', $footer_insert ) );
}


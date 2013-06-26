<?php
/**
 * The framework has its own template hierarchy that can be used instead of the default WordPress 
 * template hierarchy. It was built to extend the default by making it smarter and more flexible.
 *
 * @package Nifty
 * @subpackage Templates
 */

/* Template filters. */
add_filter( 'single_template', 'nifty_singular_template' );

/**
 * Overrides the default single template. Post templates can be
 * loaded using a custom post template, by slug, or by ID.
 *
 * @since 12.09
 * @param string $template The default WordPress post template.
 * @return string $template The theme post template after all templates have been checked for.
 */
function nifty_singular_template() {
	global $wp_query;

	$templates = array();

	/* Check for a custom post template by custom field key '_wp_post_template'. */
	$custom = get_post_meta( $wp_query->post->ID, "_wp_{$wp_query->post->post_type}_template", true );
	if ( $custom )
		$templates[] = $custom;

	$templates[] = "single-{$wp_query->post->post_type}-{$wp_query->post->ID}.php";
	$templates[] = "single-{$wp_query->post->post_type}.php";
	$templates[] = "single.php";

	return locate_template( $templates );
}

/**
 * Function for getting an array of available custom templates with a specific header.
 *
 * @since 12.09
 * @param string $post_type The name of the post type to get templates for.
 * @return array $post_templates The array of templates.
 */
function nifty_get_post_templates( $post_type = 'post' ) {

	/* Set up an empty post templates array. */
	$post_templates = array();

	/* Get the post type object. */
	$post_type_object = get_post_type_object( $post_type );

	/* Get the theme (parent theme if using a child theme) object. */
	$theme = wp_get_theme( get_template(), get_theme_root( get_template_directory() ) );

	/* Get the theme PHP files one level deep. */
	$files = (array) $theme->get_files( 'php', 1 );

	/* If a child theme is active, get its files and merge with the parent theme files. */
	if ( is_child_theme() ) {
		$child = wp_get_theme( get_stylesheet(), get_theme_root( get_stylesheet_directory() ) );
		$child_files = (array) $child->get_files( 'php', 1 );
		$files = array_merge( $files, $child_files );
	}

	/* Loop through each of the PHP files and check if they are post templates. */
	foreach ( $files as $file => $path ) {

		$headers = get_file_data(
			$path,
			array( 
				"{$post_type_object->labels->singular_name} Template" => "{$post_type_object->labels->singular_name} Template",
			)
		);

		if ( empty( $headers["{$post_type_object->labels->singular_name} Template"] ) )
			continue;

		$post_templates[ $file ] = $headers["{$post_type_object->labels->singular_name} Template"];
	}

	/* Return post templates. */
	return array_flip( $post_templates );
}


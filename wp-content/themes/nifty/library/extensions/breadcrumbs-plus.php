<?php
/**
 * Breadcumbs Plus
 * 
 * Breadcrumbs Plus provide links back to each previous page the user navigated through to get to the current page or-in hierarchical
 * site structures-the parent pages of the current one.
 * 
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU 
 * General Public License version 2, as published by the Free Software Foundation.  You may NOT assume 
 * that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without 
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * You should have received a copy of the GNU General Public License along with this program; if not, write 
 * to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 * 
 * @package BreadcrumbsPlus
 * @version 0.5
 * @author Luis Alberto Ochoa Esparza <soy@luisalberto.org>
 * @copyright Copyright (C) 2010-2012, Luis Alberto Ochoa Esparza
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

/* Turn off bbPress breadcrumbs. */
add_filter( 'bbp_no_breadcrumb', '__return_true', 11 );

/**
 * Shows a breadcrumb for all types of pages.
 *
 * This function is formatting the final output of the  breadcrumbs plus.
 * The breadcrumbs_plus_get_items() function returns the items.
 *
 * @since 0.1
 * @param array $args Mixed arguments for the menu.
 * @return string Output of the breadcrumb menu.
 */
function breadcrumbs_plus( $args = array() ) {
	global $wp_query;

	/* Create an empty variable for the breadcrumb. */
	$breadcrumb = '';

	/* Set up the default arguments for the breadcrumb. */
	$defaults = array(
		'separator' => '&raquo;',
		'before' => '<span class="breadcrumb-title">' . __( 'You are here:', 'breadcrumbs-plus' ) . '</span>',
		'after' => false,
		'front_page' => false,
		'show_home' => __( 'Home', 'breadcrumbs-plus' ),
		'echo' => true
	);

	/* Allow singular post views to have a taxonomy's terms. */
	if ( is_singular() ) {
		$post = $wp_query->get_queried_object();
		$defaults["singular_{$post->post_type}_taxonomy"] = false;
	}

	/* Apply filters to the arguments. */
	$args = apply_filters( 'breadcrumbs_plus_args', $args );

	/* Parse the arguments and extract them for easy variable naming. */
	$args = wp_parse_args( $args, $defaults );

	/* Get the items. */
	$items = breadcrumbs_plus_get_items( $args );

	/* Connect the breadcrumbs plus if there are items. */
	if ( !empty( $items ) && is_array( $items ) ) {

		/* Open the breadcrumbs plus containers. */
		$breadcrumb = '<div class="breadcrumb breadcrumbs"><div class="breadcrumbs-plus">';

		/* If $before was set, wrap it in a container. */
		$breadcrumb .= ( !empty( $args['before'] ) ? '<span class="before">' . $args['before'] . '</span> ' : '' );

		/* Wrap the $items['end'] value in a container. */
		if ( !empty( $items['end'] ) )
			$items['end'] = '<span class="end">' . $items['end'] . '</span>';

		/* Format the separator. */
		$separator = ( !empty( $args['separator'] ) ? '<span class="separator">' . $args['separator'] . '</span>' : '<span class="separator">/</span>' );

		/* Join the individual items into a single string. */
		$breadcrumb .= join( " {$separator} ", $items );

		/* If $after was set, wrap it in a container. */
		$breadcrumb .= ( !empty( $args['after'] ) ? ' <span class="after">' . $args['after'] . '</span>' : '' );

		/* Close the breadcrumbs plus containers. */
		$breadcrumb .= '</div></div>';
	}

	/* Allow developers to filter the breadcrumbs plus HTML. */
	$breadcrumb = apply_filters( 'breadcrumbs_plus', $breadcrumb, $args );

	/* Output the breadcrumb. */
	if ( $args['echo'] )
		echo $breadcrumb;
	else
		return $breadcrumb;
}

/**
 * It checks the current page being viewed and decided based on the information
 * provided by WordPress what items should be added to the breadcrumbs plus.
 *
 * @since 0.4.0
 * @todo Build in caching based on the queried object ID.
 * @param array $args Mixed arguments for the menu.
 * @return array List of items to be shown.
 */
function breadcrumbs_plus_get_items( $args = array() ) {
	global $wp_query, $wp_rewrite;

	/* Set up an empty items array and empty path. */
	$items = array();
	$path = '';

	/* If $show_home is set and we're not on the front page of the site, link to the home page. */
	if ( !is_front_page() && $args['show_home'] )
		$items[] = '<a href="' . home_url() . '" title="' . esc_attr( get_bloginfo( 'name' ) ) . '" rel="home" class="begin">' . $args['show_home'] . '</a>';

	/* Allow plugins/themes to override the default items. */
	$override = apply_filters( 'breadcrumbs_plus_pre_items', false, $args );

	if ( false != $override ) {
		$items = $override;
	}

	/* If bbPress is installed and we're on a bbPress page. */
	elseif ( function_exists( 'is_bbpress' ) && is_bbpress() ) {
		$items = array_merge( $items, breadcrumbs_plus_get_bbpress_items() );
	}

	/* If viewing the front page of the site. */
	elseif ( is_front_page() ) {
		if ( $args['show_home'] && $args['front_page'] )
			$items['end'] = "{$args['show_home']}";
	}

	/* If viewing the "home"/posts page. */
	elseif ( is_home() ) {
		$home_page = get_page( $wp_query->get_queried_object_id() );
		$items = array_merge( $items, breadcrumbs_plus_get_parents( $home_page->post_parent, '' ) );
		$items['end'] = get_the_title( $home_page->ID );
	}

	/* If viewing a singular post (page, attachment, etc.). */
	elseif ( is_singular() ) {

		/* Get singular post variables needed. */
		$post = $wp_query->get_queried_object();
		$post_id = absint( $wp_query->get_queried_object_id() );
		$post_type = $post->post_type;
		$parent = absint( $post->post_parent );

		/* Get the post type object. */
		$post_type_object = get_post_type_object( $post_type );

		/* If viewing a singular 'post'. */
		if ( 'post' == $post_type ) {

			/* If $front has been set, add it to the $path. */
			$path .= trailingslashit( $wp_rewrite->front );

			/* If there's a path, check for parents. */
			if ( !empty( $path ) )
				$items = array_merge( $items, breadcrumbs_plus_get_parents( '', $path ) );

			/* Map the permalink structure tags to actual links. */
			$items = array_merge( $items, breadcrumbs_plus_map_rewrite_tags( $post_id, get_option( 'permalink_structure' ), $args ) );
		}

		/* If viewing a singular 'attachment'. */
		elseif ( 'attachment' == $post_type ) {

			/* If $front has been set, add it to the $path. */
			$path .= trailingslashit( $wp_rewrite->front );

			/* If there's a path, check for parents. */
			if ( !empty( $path ) )
				$items = array_merge( $items, breadcrumbs_plus_get_parents( '', $path ) );

			/* Map the post (parent) permalink structure tags to actual links. */
			$items = array_merge( $items, breadcrumbs_plus_map_rewrite_tags( $post->post_parent, get_option( 'permalink_structure' ), $args ) );
		}

		/* If a custom post type, check if there are any pages in its hierarchy based on the slug. */
		elseif ( 'page' !== $post_type ) {

			/* If $front has been set, add it to the $path. */
			if ( $post_type_object->rewrite['with_front'] && $wp_rewrite->front )
				$path .= trailingslashit( $wp_rewrite->front );

			/* If there's a slug, add it to the $path. */
			if ( !empty( $post_type_object->rewrite['slug'] ) )
				$path .= $post_type_object->rewrite['slug'];

			/* If there's a path, check for parents. */
			if ( !empty( $path ) )
				$items = array_merge( $items, breadcrumbs_plus_get_parents( '', $path ) );

			/* If there's an archive page, add it. */
			if ( function_exists( 'get_post_type_archive_link' ) && !empty( $post_type_object->has_archive ) )
				$items[] = '<a href="' . get_post_type_archive_link( $post_type ) . '" title="' . esc_attr( $post_type_object->labels->name ) . '">' . $post_type_object->labels->name . '</a>';
		}

		/* If the post type path returns nothing and there is a parent, get its parents. */
		if ( ( empty( $path ) && 0 !== $parent ) || ( 'attachment' == $post_type ) )
			$items = array_merge( $items, breadcrumbs_plus_get_parents( $parent, '' ) );

		/* Or, if the post type is hierarchical and there's a parent, get its parents. */
		elseif ( 0 !== $parent && is_post_type_hierarchical( $post_type ) )
			$items = array_merge( $items, breadcrumbs_plus_get_parents( $parent, '' ) );

		/* Display terms for specific post type taxonomy if requested. */
		if ( !empty( $args["singular_{$post_type}_taxonomy"] ) && is_taxonomy_hierarchical( $args["singular_{$post_type}_taxonomy"] ) ) {
			$terms = wp_get_object_terms( $post_id, $args["singular_{$post_type}_taxonomy"] );
			$items = array_merge( $items, breadcrumbs_plus_get_term_parents( $terms[0], $args["singular_{$post_type}_taxonomy"] ) );
		}

		elseif ( !empty( $args["singular_{$post_type}_taxonomy"] ) ) {
			$items[] = get_the_term_list( $post_id, $args["singular_{$post_type}_taxonomy"], '', ', ', '' );
		}

		/* End with the post title. */
		$post_title = get_the_title();

		if ( !empty( $post_title ) )
			$items['end'] = $post_title;
	}

	/* If we're viewing any type of archive. */
	elseif ( is_archive() ) {

		/* If viewing a taxonomy term archive. */
		if ( is_category() || is_tag() || is_tax() ) {

			/* Get some taxonomy and term variables. */
			$term = $wp_query->get_queried_object();
			$taxonomy = get_taxonomy( $term->taxonomy );

			/* Get the path to the term archive. Use this to determine if a page is present with it. */
			$path = trailingslashit( $wp_rewrite->front );

			if ( is_category() )
				$path .= get_option( 'category_base' );

			elseif ( is_tag() )
				$path .= get_option( 'tag_base' );

			else {
				if ( $taxonomy->rewrite['with_front'] && $wp_rewrite->front )
					$path = trailingslashit( $wp_rewrite->front );

				$path .= $taxonomy->rewrite['slug'];
			}

			/* Get parent pages by path if they exist. */
			if ( $path )
				$items = array_merge( $items, breadcrumbs_plus_get_parents( '', $path ) );

			/* If the taxonomy is hierarchical, list its parent terms. */
			if ( is_taxonomy_hierarchical( $term->taxonomy ) && $term->parent )
				$items = array_merge( $items, breadcrumbs_plus_get_term_parents( $term->parent, $term->taxonomy ) );

			/* Add the term name to the end. */
			$items['end'] = $term->name;
		}

		/* If viewing a post type archive. */
		elseif ( function_exists( 'is_post_type_archive' ) && is_post_type_archive() ) {

			/* Get the post type object. */
			$post_type_object = get_post_type_object( get_query_var( 'post_type' ) );

			/* If $front has been set, add it to the $path. */
			if ( $post_type_object->rewrite['with_front'] && $wp_rewrite->front )
				$path .= trailingslashit( $wp_rewrite->front );

			/* If there's a slug, add it to the $path. */
			if ( !empty( $post_type_object->rewrite['slug'] ) )
				$path .= $post_type_object->rewrite['slug'];

			/* If there's a path, check for parents. */
			if ( !empty( $path ) )
				$items = array_merge( $items, breadcrumbs_plus_get_parents( '', $path ) );

			/* Add the post type [plural] name to the end. */
			$items['end'] = $post_type_object->labels->name;
		}

		/* If viewing an author archive. */
		elseif ( is_author() ) {

			/* If $front has been set, add it to $path. */
			if ( !empty( $wp_rewrite->front ) )
				$path .= trailingslashit( $wp_rewrite->front );

			/* If an $author_base exists, add it to $path. */
			if ( !empty( $wp_rewrite->author_base ) )
				$path .= $wp_rewrite->author_base;

			/* If $path exists, check for parent pages. */
			if ( !empty( $path ) )
				$items = array_merge( $items, breadcrumbs_plus_get_parents( '', $path ) );

			/* Add the author's display name to the end. */
			$items['end'] = get_the_author_meta( 'display_name', get_query_var( 'author' ) );
		}

		/* If viewing a date-based archive. */
		elseif ( is_date() ) {

			/* If $front has been set, check for parent pages. */
			if ( $wp_rewrite->front )
				$items = array_merge( $items, breadcrumbs_plus_get_parents( '', $wp_rewrite->front ) );

			if ( is_day() ) {
				$items[] = '<a href="' . get_year_link( get_the_time( 'Y' ) ) . '" title="' . get_the_time( esc_attr__( 'Y', 'breadcrumbs-plus' ) ) . '">' . get_the_time( __( 'Y', 'breadcrumbs-plus' ) ) . '</a>';
				$items[] = '<a href="' . get_month_link( get_the_time( 'Y' ), get_the_time( 'm' ) ) . '" title="' . get_the_time( esc_attr__( 'F', 'breadcrumbs-plus' ) ) . '">' . get_the_time( __( 'F', 'breadcrumbs-plus' ) ) . '</a>';
				$items['end'] = get_the_time( __( 'd', 'breadcrumbs-plus' ) );
			}

			elseif ( is_month() ) {
				$items[] = '<a href="' . get_year_link( get_the_time( 'Y' ) ) . '" title="' . get_the_time( esc_attr__( 'Y', 'breadcrumbs-plus' ) ) . '">' . get_the_time( __( 'Y', 'breadcrumbs-plus' ) ) . '</a>';
				$items['end'] = get_the_time( __( 'F', 'breadcrumbs-plus' ) );
			}

			elseif ( is_year() ) {
				$items['end'] = get_the_time( __( 'Y', 'breadcrumbs-plus' ) );
			}
		}
	}

	/* If viewing search results. */
	elseif ( is_search() )
		$items['end'] = sprintf( __( 'Search results for &quot;%1$s&quot;', 'breadcrumbs-plus' ), esc_attr( get_search_query() ) );

	/* If viewing a 404 error page. */
	elseif ( is_404() )
		$items['end'] = __( '404 Not Found', 'breadcrumbs-plus' );

	/* Allow devs to step in and filter the $items array. */
	return apply_filters( 'breadcrumbs_plus_items', $items, $args );
}

/**
 * Gets the items for the breadcrumbs plus if bbPress is installed.
 *
 * @since 0.4
 * @param array $args Mixed arguments for the menu.
 * @return array List of items to be shown.
 */
function breadcrumbs_plus_get_bbpress_items( $args = array() ) {
	global $wp_query, $wp_rewrite;

	/* Set up a new items items array. */
	$items = array();

	/* Get the forum post type object. */
	$post_type_object = get_post_type_object( bbp_get_forum_post_type() );

	/* If not viewing the forum root/archive page and a forum archive exists, add it. */
	if ( !empty( $post_type_object->has_archive ) && !bbp_is_forum_archive() )
		$items[] = '<a href="' . get_post_type_archive_link( bbp_get_forum_post_type() ) . '">' . bbp_get_forum_archive_title() . '</a>';

	/* If viewing the forum root/archive. */
	if ( bbp_is_forum_archive() ) {
		$items[] = bbp_get_forum_archive_title();
	}

	/* If viewing the topics archive. */
	elseif ( bbp_is_topic_archive() ) {
		$items[] = bbp_get_topic_archive_title();
	}

	/* If viewing a topic tag archive. */
	elseif ( bbp_is_topic_tag() ) {
		$items[] = bbp_get_topic_tag_name();
	}

	/* If viewing a topic tag edit page. */
	elseif ( bbp_is_topic_tag_edit() ) {
		$items[] = '<a href="' . bbp_get_topic_tag_link() . '">' . bbp_get_topic_tag_name() . '</a>';
		$items[] = __( 'Edit', 'breadcrumbs-plus' );
	}

	/* If viewing a "view" page. */
	elseif ( bbp_is_single_view() ) {
		$items[] = bbp_get_view_title();
	}

	/* If viewing a single topic page. */
	elseif ( bbp_is_single_topic() ) {

		/* Get the queried topic. */
		$topic = $wp_query->get_queried_object();
		$topic_id = $wp_query->get_queried_object_id();
		$parent = absint( $topic->post_parent );

		/* Get the parent items for the topic, which would be its forum (and possibly forum grandparents). */
		if ( 0 != $parent )
			$items = array_merge( $items, breadcrumbs_plus_get_parents( bbp_get_topic_forum_id( $topic_id ) ) );

		/* If viewing a split, merge, or edit topic page, show the link back to the topic.  Else, display topic title. */
		if ( bbp_is_topic_split() || bbp_is_topic_merge() || bbp_is_topic_edit() )
			$items[] = '<a href="' . bbp_get_topic_permalink( $topic_id ) . '">' . bbp_get_topic_title( $topic_id ) . '</a>';
		else
			$items[] = bbp_get_topic_title( $topic_id );

		/* If viewing a topic split page. */
		if ( bbp_is_topic_split() )
			$items[] = __( 'Split', 'breadcrumbs-plus' );

		/* If viewing a topic merge page. */
		elseif ( bbp_is_topic_merge() )
			$items[] = __( 'Merge', 'breadcrumbs-plus' );

		/* If viewing a topic edit page. */
		elseif ( bbp_is_topic_edit() )
			$items[] = __( 'Edit', 'breadcrumbs-plus' );
	}

	/* If viewing a single reply page. */
	elseif ( bbp_is_single_reply() ) {

		/* Get the queried reply object ID. */
		$reply_id = $wp_query->get_queried_object_id();

		/* Get the parent items for the reply, which should be its topic. */
		$items = array_merge( $items, breadcrumbs_plus_get_parents( bbp_get_reply_topic_id( $reply_id ) ) );

		/* If viewing a reply edit page, link back to the reply. Else, display the reply title. */
		if ( bbp_is_reply_edit() ) {
			$items[] = '<a href="' . bbp_get_reply_url( $reply_id ) . '">' . bbp_get_reply_title( $reply_id ) . '</a>';
			$items[] = __( 'Edit', 'breadcrumbs-plus' );

		} else {
			$items[] = bbp_get_reply_title( $reply_id );
		}
	}

	/* If viewing a single forum. */
	elseif ( bbp_is_single_forum() ) {

		/* Get the queried forum ID and its parent forum ID. */
		$forum_id = $wp_query->get_queried_object_id();
		$forum_parent_id = bbp_get_forum_parent_id( $forum_id );

		/* If the forum has a parent forum, get its parent(s). */
		if ( 0 !== $forum_parent_id)
			$items = array_merge( $items, breadcrumbs_plus_get_parents( $forum_parent_id ) );

		/* Add the forum title to the end. */
		$items[] = bbp_get_forum_title( $forum_id );
	}

	/* If viewing a user page or user edit page. */
	elseif ( bbp_is_single_user() || bbp_is_single_user_edit() ) {

		if ( bbp_is_single_user_edit() ) {
			$items[] = '<a href="' . bbp_get_user_profile_url() . '">' . bbp_get_displayed_user_field( 'display_name' ) . '</a>';
			$items[] = __( 'Edit', 'breadcrumbs-plus' );
		} else {
			$items[] = bbp_get_displayed_user_field( 'display_name' );
		}
	}

	/* Return the bbPress breadcrumbs plus items. */
	return apply_filters( 'breadcrumbs_plus_bbpress_items', $items, $args );
}

/**
 * Turns %tag% from permalink structures into usable links for the breadcrumbs plus.  This feels kind of
 * hackish for now because we're checking for specific %tag% examples and only doing it for the 'post' 
 * post type.  In the future, maybe it'll handle a wider variety of possibilities, especially for custom post
 * types.
 *
 * @since 0.5
 * @param int $post_id ID of the post whose parents we want.
 * @param string $path Path of a potential parent page.
 * @param array $args Mixed arguments for the menu.
 * @return array $items Array of links to the post breadcrumb.
 */
function breadcrumbs_plus_map_rewrite_tags( $post_id = '', $path = '', $args = array() ) {

	/* Set up an empty $items array. */
	$items = array();

	/* Make sure there's a $path and $post_id before continuing. */
	if ( empty( $path ) || empty( $post_id ) )
		return $items;

	/* Get the post based on the post ID. */
	$post = get_post( $post_id );

	/* If no post is returned, an error is returned, or the post does not have a 'post' post type, return. */
	if ( empty( $post ) || is_wp_error( $post ) || 'post' !== $post->post_type )
		return $items;

	/* Trim '/' from both sides of the $path. */
	$path = trim( $path, '/' );

	/* Split the $path into an array of strings. */
	$matches = explode( '/', $path );

	/* If matches are found for the path. */
	if ( is_array( $matches ) ) {

		/* Loop through each of the matches, adding each to the $items array. */
		foreach ( $matches as $match ) {

			/* Trim any '/' from the $match. */
			$tag = trim( $match, '/' );

			/* If using the %year% tag, add a link to the yearly archive. */
			if ( '%year%' == $tag )
				$items[] = '<a href="' . get_year_link( get_the_time( 'Y', $post_id ) ) . '" title="' . get_the_time( esc_attr__( 'Y', 'breadcrumbs-plus' ), $post_id ) . '">' . get_the_time( __( 'Y', 'breadcrumbs-plus' ), $post_id ) . '</a>';

			/* If using the %monthnum% tag, add a link to the monthly archive. */
			elseif ( '%monthnum%' == $tag )
				$items[] = '<a href="' . get_month_link( get_the_time( 'Y', $post_id ), get_the_time( 'm', $post_id ) ) . '" title="' . get_the_time( esc_attr__( 'F Y', 'breadcrumbs-plus' ), $post_id ) . '">' . get_the_time( __( 'F', 'breadcrumbs-plus' ), $post_id ) . '</a>';

			/* If using the %day% tag, add a link to the daily archive. */
			elseif ( '%day%' == $tag )
				$items[] = '<a href="' . get_day_link( get_the_time( 'Y', $post_id ), get_the_time( 'm', $post_id ), get_the_time( 'd', $post_id ) ) . '" title="' . get_the_time( esc_attr__( 'F j, Y', 'breadcrumbs-plus' ), $post_id ) . '">' . get_the_time( __( 'd', 'breadcrumbs-plus' ), $post_id ) . '</a>';

			/* If using the %author% tag, add a link to the post author archive. */
			elseif ( '%author%' == $tag )
				$items[] = '<a href="' . get_author_posts_url( $post->post_author ) . '" title="' . esc_attr( get_the_author_meta( 'display_name', $post->post_author ) ) . '">' . get_the_author_meta( 'display_name', $post->post_author ) . '</a>';


			/* If using the %category% tag, add a link to the first category archive to match permalinks. */
			elseif ( '%category%' == $tag && 'category' !== $args["singular_{$post->post_type}_taxonomy"] ) {

				/* Get the post categories. */
				$terms = get_the_category( $post_id );

				/* Check that categories were returned. */
				if ( $terms ) {

					/* Gget the first category. */
					usort( $terms, '_usort_terms_by_ID' );
					$term = get_term( $terms[0], 'category' );

					/* If the category has a parent, add the hierarchy. */
					if ( 0 !== $term->parent )
						$items = array_merge( $items, breadcrumbs_plus_get_term_parents( $term->parent, 'category' ) );

					/* Add the category archive link. */
					$items[] = '<a href="' . get_term_link( $term, 'category' ) . '" title="' . esc_attr( $term->name ) . '">' . $term->name . '</a>';
				}
			}
		}
	}

	/* Return the $items array. */
	return apply_filters( 'breadcrumbs_plus_map_rewrite_tags', $items, $post_id, $path, $args );
}

/**
 * Gets parent pages of any post type or taxonomy by the ID or Path.  The goal of this function is to create 
 * a clear path back to home given what would normally be a "ghost" directory.  If any page matches the given 
 * path, it'll be added. But, it's also just a way to check for a hierarchy with hierarchical post types.
 *
 * @since 0.1
 * @param int $post_id ID of the post whose parents we want.
 * @param string $path Path of a potential parent page.
 * @return array $items Array of parent page links.
 */
function breadcrumbs_plus_get_parents( $post_id = '', $path = '' ) {

	/* Set up an empty items array. */
	$items = array();

	/* Trim '/' off $path in case we just got a simple '/' instead of a real path. */
	$path = trim( $path, '/' );

	/* If neither a post ID nor path set, return an empty array. */
	if ( empty( $post_id ) && empty( $path ) )
		return $items;

	/* If the post ID is empty, use the path to get the ID. */
	if ( empty( $post_id ) ) {

		/* Get parent post by the path. */
		$parent_page = get_page_by_path( $path );

		/* If a parent post is found, set the $post_id variable to it. */
		if ( !empty( $parent_page ) )
			$post_id = $parent_page->ID;
	}

	/* If a post ID and path is set, search for a post by the given path. */
	if ( $post_id == 0 && !empty( $path ) ) {

		/* Separate post names into separate paths by '/'. */
		$path = trim( $path, '/' );
		preg_match_all( "/\/.*?\z/", $path, $matches );

		/* If matches are found for the path. */
		if ( isset( $matches ) ) {

			/* Reverse the array of matches to search for posts in the proper order. */
			$matches = array_reverse( $matches );

			/* Loop through each of the path matches. */
			foreach ( $matches as $match ) {

				/* If a match is found. */
				if ( isset( $match[0] ) ) {

					/* Get the parent post by the given path. */
					$path = str_replace( $match[0], '', $path );
					$parent_page = get_page_by_path( trim( $path, '/' ) );

					/* If a parent post is found, set the $post_id and break out of the loop. */
					if ( !empty( $parent_page ) && $parent_page->ID > 0 ) {
						$post_id = $parent_page->ID;
						break;
					}
				}
			}
		}
	}

	/* While there's a post ID, add the post link to the $parents array. */
	while ( $post_id ) {

		/* Get the post by ID. */
		$page = get_page( $post_id );

		/* Add the formatted post link to the array of parents. */
		$parents[]  = '<a href="' . get_permalink( $post_id ) . '" title="' . esc_attr( get_the_title( $post_id ) ) . '">' . get_the_title( $post_id ) . '</a>';

		/* Set the parent post's parent to the post ID. */
		$post_id = $page->post_parent;
	}

	/* If we have parent posts, reverse the array to put them in the proper order. */
	if ( isset( $parents ) )
		$items = array_reverse( $parents );

	/* Return the parent posts. */
	return apply_filters( 'breadcrumbs_plus_get_parents', $items, $post_id, $path );
}

/**
 * Searches for term parents of hierarchical taxonomies.  This function is similar to the WordPress 
 * function get_category_parents() but handles any type of taxonomy.
 *
 * @since 0.1
 * @param int $parent_id The ID of the first parent.
 * @param object|string $taxonomy The taxonomy of the term whose parents we want.
 * @return array $items Array of links to parent terms.
 */
function breadcrumbs_plus_get_term_parents( $parent_id = '', $taxonomy = '' ) {

	/* Set up some default arrays. */
	$items = array();
	$parents = array();

	/* If no term parent ID or taxonomy is given, return an empty array. */
	if ( empty( $parent_id ) || empty( $taxonomy ) )
		return $items;

	/* While there is a parent ID, add the parent term link to the $parents array. */
	while ( $parent_id ) {

		/* Get the parent term. */
		$parent = get_term( $parent_id, $taxonomy );

		/* Add the formatted term link to the array of parent terms. */
		$parents[] = '<a href="' . get_term_link( $parent, $taxonomy ) . '" title="' . esc_attr( $parent->name ) . '">' . $parent->name . '</a>';

		/* Set the parent term's parent as the parent ID. */
		$parent_id = $parent->parent;
	}

	/* If we have parent terms, reverse the array to put them in the proper order. */
	if ( !empty( $parents ) )
		$items = array_reverse( $parents );

	/* Return the parent terms. */
	return apply_filters( 'breadcrumbs_plus_get_term_parents', $items, $parent_id, $taxonomy );
}

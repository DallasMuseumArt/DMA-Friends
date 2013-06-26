<?php
/**
 * The template for displaying all pages.
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site will use a
 * different template.
 *
 * @package WordPress
 * @subpackage Twenty_Twelve
 * @since Twenty Twelve 1.0
 */

function badgestack_have_badges() {
	badgestack_get_top_level_badges();
}

function badgestack_get_top_level_badges() {


	add_action( 'posts_join', 'badgestack_include_p2p_in_join');
	add_action( 'posts_where', 'badgestack_include_p2p_where');
	$badges = new WP_Query( 
		array( 'post_type' => 'badge',
			'posts_per_page' => -1,
			'post_status' => 'publish'
		) 
	);
	remove_action( 'posts_join', 'badgestack_include_p2p_in_join', 10);
	remove_action( 'posts_where', 'badgestack_include_p2p_where', 10);
	echo '<PRE>';
	var_dump( $badges->posts );
	die;
}

function badgestack_include_p2p_in_join( $joins ) {
	global $wpdb;
	$joins .= " LEFT JOIN {$wpdb->p2p} ON {$wpdb->posts}.ID = $wpdb->p2p.p2p_from ";
	return $joins;
}
function badgestack_include_p2p_where( $where ) {
	global $wpdb;
	$where .= " AND {$wpdb->p2p}.p2p_from Is NULL";
	return $where;
}
// select a badge that does not have an entry in p2p_to
// 

// POST 					p2p
// post_id 				post_id 				
get_header(); ?>

	<div id="primary" class="site-content">
		<div id="content" role="main">
			<?php while ( badgestack_have_badges() ) : the_badge(); ?>
				<?php get_template_part( 'content', 'badge' ); ?>
			<?php endwhile; // end of the loop. ?>

		</div><!-- #content -->
	</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>

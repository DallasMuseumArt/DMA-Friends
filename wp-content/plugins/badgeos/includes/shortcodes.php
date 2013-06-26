<?php
/**
 * BadgeStack Shortcodes
 *
 * @package BadgeStack
 */

add_shortcode( 'badgestack_quests', 'badgestack_quests_archive' );

//display all Quests in the system
function badgestack_quests_archive() {
	
	//get all quest entries
	$args = array(
		'post_type'			=>	'quest',
		'orderby'			=>	'menu_order',
		'order'				=>	'ASC',
		'posts_per_page'	=>	'-1',
	);

	$the_quests = new WP_Query( $args );

	$quests = null;
	
	// The Loop for Quests
	while ( $the_quests->have_posts() ) : $the_quests->the_post();
			
		$attr = array( 'class'	=>	'alignleft' );
	
		$quests .= '<p>';
		
		$quests .= '<a href="'.get_permalink().'">' .get_the_post_thumbnail( get_the_ID(), array( 100, 100 ), $attr ). '</a>';
		
		$quests .= '<a href="'.get_permalink().'">' .get_the_title() .'</a><br />';
		
		$quests .= get_the_excerpt();
		
		$quests .= '</p>';
	
	endwhile;

	// Reset Post Data
	wp_reset_postdata();
	
	return $quests;
	
}


add_shortcode( 'badgestack_badges', 'badgestack_badges_archive' );

//display all Badges in the system
function badgestack_badges_archive() {
	
	//get all quest entries
	$args = array(
		'post_type'			=>	'badge',
		'orderby'			=>	'menu_order',
		'order'				=>	'ASC',
		'posts_per_page'	=>	'-1',
	);

	$the_badges = new WP_Query( $args );

	$badges = null;
	
	// The Loop for Quests
	while ( $the_badges->have_posts() ) : $the_badges->the_post();
			
		$attr = array( 'class'	=>	'alignleft' );
	
		$badges .= '<p>';
		
		$badges .= '<a href="'.get_permalink().'">' .get_the_post_thumbnail( get_the_ID(), array( 100, 100 ), $attr ). '</a>';
		
		$badges .= '<a href="'.get_permalink().'">' .get_the_title() .'</a><br />';
		
		$badges .= get_the_excerpt();
		
		$badges .= '</p>';
	
	endwhile;

	// Reset Post Data
	wp_reset_postdata();
	
	return $badges;
	
}

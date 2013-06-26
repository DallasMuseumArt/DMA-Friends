<?php
/*
Template Name: My Profile
*/

add_filter( 'body_class', 'dma_add_dashboard_class' );
function dma_add_dashboard_class( $classes ) {
	$classes[] = 'profile';
	return $classes;
}

remove_action( 'genesis_loop', 'genesis_do_loop' );
add_action( 'genesis_loop', 'dma_my_badge_output' );
function dma_my_badge_output() {
	global $dma_user;
	?>
	<div class="container">
		<?php echo $dma_user->edit_profile(); ?>
	</div><!-- .containter -->
	<?php
}

genesis();

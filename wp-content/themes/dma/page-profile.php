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
add_action( 'genesis_loop', 'dma_user_profile_front' );
function dma_user_profile_front() {
	?>
	<div class="container">
		<?php echo $GLOBALS['dma_user']->edit_profile(); ?>
	</div><!-- .containter -->
	<?php
}

/**
 * Previous iteration of profile page with activity stream (unused but saved for posterity)
 */
function dma_user_profile_stream() {
	?>
	<div class="container">
		<div class="left">
			<?php echo $GLOBALS['dma_user']->edit_profile(); ?>
		</div><!-- .left -->
		<div class="right">
			<h1><?php _e( 'My Activity Stream', 'dma' ); ?></h1>
			<div class="stream scroll-vertical">
				<?php echo $GLOBALS['dma_user']->activity_stream(); ?>
			</div><!-- .stream -->
		</div><!-- .right -->
	</div><!-- .containter -->
	<?php
}

genesis();

<?php
/**
 * Template Name: Activity Stream
 */

add_filter( 'body_class', 'dma_add_dashboard_class' );
function dma_add_dashboard_class( $classes ) {
	$classes[] = 'activity-stream';
	return $classes;
}

remove_action( 'genesis_loop', 'genesis_do_loop' );
add_action( 'genesis_loop', 'dma_my_badge_output' );
function dma_my_badge_output() {
	?>
	<div class="container">
		<h1><?php the_title() ?></h1>
		<div class="left">
			<?php echo $GLOBALS['dma_user']->activity_stream(); ?>
		</div><!-- .left -->
		<div class="right">
			<ul class="filter-buttons-wrap button-group buttons">
				<?php
				dma_filter_stream_item( 'View All', 'all', 'first' );
				dma_filter_stream_item( 'Badges', 'stream-badge', 'icon-cd' );
				dma_filter_stream_item( 'Rewards', 'stream-reward', 'icon-trophy' );
				dma_filter_stream_item( 'Activities', 'stream-activity', 'icon-check' );
				dma_filter_stream_item( 'Events', 'stream-event', 'icon-dma' );
				dma_filter_stream_item( 'Art Likes', 'stream-like', 'icon-heart' );
				dma_filter_stream_item( 'Check-ins', 'stream-checkin', 'icon-location' );
				?>
			</ul>
		</div><!-- .right -->
	</div><!-- .containter -->
	<?php
}

function dma_filter_stream_item( $item, $slug, $class = false ) {

	$current = isset( $_GET['filter'] ) ? $_GET['filter'] : 'all';

	$classes = 'filter-'. $slug. ' button large';
	$classes .= ( $current == $slug ) ? ' current' : '';
	$classes .= $class && $class != 'first' ? ' '.$class : '';

	$url = site_url( '/activity-stream' );

	$url = $slug == 'all' ? $url : add_query_arg( 'filter', $slug, $url );

	dma_li_a( 'filter', $item, $slug, array( $classes ), $class == 'first' ? 'first' : '', $url );
}

genesis();

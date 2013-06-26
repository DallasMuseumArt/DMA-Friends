<?php
/*
Template Name: My Badges
*/

add_filter( 'genesis_edit_post_link', '__return_false' );

add_filter( 'body_class', 'dma_add_dashboard_class' );
function dma_add_dashboard_class( $classes ) {
	$classes[] = 'badges';
	return $classes;
}

remove_action( 'genesis_loop', 'genesis_do_loop' );
add_action( 'genesis_loop', 'dma_my_badge_output' );
function dma_my_badge_output() {

	$badges = $GLOBALS['dma_user']->earned_badges();
	if ( $badges ) {
		?>
		<div class="container">
			<?php genesis_do_loop(); ?>
			<div class="clear"></div>
			<div class="badge-list">
				<?php echo $badges; ?>
			</div><!-- .badge-list -->
		</div>
		<div id="what-are-badges" class="popup close">
			<?php dashboard_popup_content( 'What Are Badges', true ); ?>
			<a class="button secondary close-popup" href="#">Close</a>
		</div>
		<?php
	} else {
		echo '
		<div>',
			dma_badges_help(),
			'<h1>No Badges</h1>
		</div>
		';
	}
}

add_action( 'genesis_post_title', 'dma_mybadges_help', 5 );
function dma_mybadges_help() {
	echo dma_badges_help();
}

function dma_badges_help() {
	$help = '
	<a class="help small pop" href="#what-are-badges" data-popheight="auto"><div class="q icon-help-circled"></div><span>What are these, and how do I earn more?</span></a>
	<div id="what-are-badges" class="popup close">';
		$help .= dashboard_popup_content( 'What Are Badges' );
		$help .=
		'<a class="button secondary close-popup" href="#">Close</a>
	</div>
	';
	return $help;
}

genesis();

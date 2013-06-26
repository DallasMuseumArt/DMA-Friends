<?php
/*
Template Name: View Progress
*/

// Remove core Genesis functionality
remove_action( 'genesis_loop', 'genesis_do_loop' );

// Filter the body class to include custom classes for this page
add_filter( 'body_class', 'dma_activity_page_class' );
function dma_activity_page_class( $classes ) {
	if ( isset( $_GET['panel'] ) )
		$classes[] = 'view-progress panel-' . $_GET['panel'];
	else $classes[] = 'view-progress panel-stats';
	return $classes;
}

// Enqueue our colorbox
add_action('wp_enqueue_scripts', 'dma_colorbox_add');
function dma_colorbox_add() {

	wp_deregister_style( 'mvp-colorbox' );
	wp_register_style( 'mvp-colorbox', get_stylesheet_directory_uri(). '/lib/css/mvp-colorbox.css', array( 'colorbox5' ) );

	if ( function_exists( 'wds_colorbox' ) )
		wds_colorbox(5);
	wp_enqueue_style( 'mvp-colorbox' );

}

/**
 * Helper function for adding an 'active' state to a particular tab
 *
 * @param  string $tab_name The name of the tab
 * @return string           The name of the tab plus "active" if applicable
 */
function active_tab_classes( $tab_name ) {

	// Set our class to match the tab name
	$classes = $tab_name;

	// If we should be displaying a particular tab, and it's this tab, add 'active' to the class output
	if ( !isset( $_GET['panel'] ) && $tab_name == 'stats' )
		return 'stats active';

	if ( isset( $_GET['panel'] ) && $_GET['panel'] == $tab_name )
		$classes .= ' active';

	return $classes;

}

// Hook in our custom content
add_action( 'genesis_loop', 'dma_user_dashboard' );
function dma_user_dashboard() {

	// See if we're logged in
	if ( is_user_logged_in() ) {

		// Grab our current user data
		$dma_user = $GLOBALS['dma_user'];

		?>
		<div id="panels_wrap">
			<ul id="panels_nav" class="nav">
				<li id="panel_stats" class="<?php echo active_tab_classes( 'stats' ); ?> ui-tabs-active"><a href="#tabs-1" class="<?php echo active_tab_classes( 'stats' ); ?>">Stats</a></li>
				<li id="panel_challenges" class="<?php echo active_tab_classes( 'challenges' ); ?>"><a href="#tabs-2" class="<?php echo active_tab_classes( 'challenges' ); ?>">Challenges</a></li>
				<li id="panel_badges" class="<?php echo active_tab_classes( 'badges' ); ?>"><a href="#tabs-3" class="<?php echo active_tab_classes( 'badges' ); ?>">Badges</a></li>
			</ul>
			<?php get_template_part( 'panel', 'stats' ); ?>
			<?php get_template_part( 'panel', 'challenges' ); ?>
			<?php get_template_part( 'panel', 'badges' ); ?>
		</div><!-- #panels_wrap -->
		<?php
	}

}

genesis();

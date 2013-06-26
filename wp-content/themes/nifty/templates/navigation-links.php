<?php
/**
 * Navigation Links Template
 *
 * This template is used to show your your next/previous post links on singular pages and
 * the next/previous posts links on the home/posts page and archive pages.
 * 
 * It also integrates with the WP PageNavi plugin if activated.
 *
 * @package Nifty
 * @subpackage Template
 */

if ( is_attachment() ) : ?>

	<div class="pagination">
		<?php previous_post_link( '%link', '<span class="previous">' . __( '&laquo; Return to entry', 'nifty' ) . '</span>' ); ?>
	</div><!-- .pagination -->

<?php elseif ( is_singular() && !is_singular( 'page' ) ) : ?>

	<div class="pagination">
		<?php previous_post_link( '%link', '<span class="previous">' . __( '&laquo; Previous', 'nifty' ) . '</span>' ); ?>
		<?php next_post_link( '%link', '<span class="next">' . __( 'Next &raquo;', 'nifty' ) . '</span>' ); ?>
	</div><!-- .pagination -->

<?php elseif ( !is_singular() && function_exists( 'wp_pagenavi' ) ) : wp_pagenavi( '<div class="pagination">', '</div>' ); ?>

<?php elseif ( !is_singular() && current_theme_supports( 'loop-pagination' ) ) : loop_pagination(); ?>

<?php elseif ( !is_singular() && $nav = get_posts_nav_link( array( 'sep' => '', 'prelabel' => '<span class="previous">' . __( '&laquo; Previous', 'nifty' ) . '</span>', 'nxtlabel' => '<span class="next">' . __( 'Next &raquo;', 'nifty' ) . '</span>' ) ) ) : ?>

	<div class="pagination">
		<?php echo $nav; ?>
	</div><!-- .pagination -->

<?php endif; ?>
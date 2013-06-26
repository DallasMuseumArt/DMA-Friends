<?php
/**
 * Secondary Aside Template
 *
 * The Secondary Aside template houses the HTML used for the 'Secondary' widget area.
 *
 * @package Nifty
 * @subpackage Template
 */

if ( is_active_sidebar( 'secondary' ) ) : ?>

	<?php do_action( 'nifty_before_secondary' ); ?>

	<div id="sidebar-secondary" class="sidebar">

		<?php dynamic_sidebar( 'secondary' ); ?>

	</div><!-- #sidebar-secondary .aside -->

	<?php do_action( 'nifty_after_secondary' ); ?>

<?php endif; ?>

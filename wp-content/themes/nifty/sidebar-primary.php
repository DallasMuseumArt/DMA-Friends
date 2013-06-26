<?php
/**
 * Primary Aside Template
 *
 * The Primary Aside template houses the HTML used for the 'Primary' widget area.
 *
 * @package Nifty
 * @subpackage Template
 */

if ( is_active_sidebar( 'primary' ) ) : ?>

	<?php do_action( 'nifty_before_primary' ); ?>

	<div id="sidebar-primary" class="sidebar">

		<?php dynamic_sidebar( 'primary' ); ?>

	</div><!-- #sidebar-primary .aside -->

	<?php do_action( 'nifty_after_primary' ); ?>

<?php endif; ?>

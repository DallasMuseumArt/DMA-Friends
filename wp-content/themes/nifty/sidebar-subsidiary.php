<?php
/**
 * Subsidiary Aside Template
 *
 * The Subsidiary Aside template houses the HTML used for the 'Subsidiary' widget area.
 *
 * @package Nifty
 * @subpackage Template
 */

if ( is_active_sidebar( 'subsidiary' ) ) : ?>

	<?php do_action( 'nifty_before_subsidiary' ); ?>

	<div id="sidebar-subsidiary" class="sidebar">

		<?php dynamic_sidebar( 'subsidiary' ); ?>

	</div><!-- #subsidiary .aside -->

	<?php do_action( 'nifty_after_subsidiary' ); ?>

<?php endif; ?>

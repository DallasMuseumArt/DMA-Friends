<?php
/**
 * Secondary Menu Template
 *
 * Displays the Menu Secundary if it has active menu items.
 *
 * @package Nifty
 * @subpackage Template
 */

if ( has_nav_menu( 'menu-secondary' ) ) : ?>

	<?php do_action( 'nifty_before_menu_secondary' ); ?>

	<div id="menu-secondary" class="menu-container">

		<div class="wrapper">

			<?php wp_nav_menu( array( 'theme_location' => 'menu-secondary', 'container_class' => 'menu', 'menu_class' => '', 'menu_id' => 'menu-secondary-items', 'link_before' => '<span>', 'link_after' => '</span>', 'fallback_cb' => '' ) ); ?>

		</div>

	</div><!-- #menu-secondary .menu-container -->

	<?php do_action( 'nifty_after_menu_secondary' ); ?>

<?php endif; ?>

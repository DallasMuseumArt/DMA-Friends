<?php
/**
 * Primary Menu Template
 *
 * Displays the Menu Primary if it has active menu items.
 *
 * @package Nifty
 * @subpackage Template
 */

if ( has_nav_menu( 'menu-primary' ) ) : ?>

	<?php do_action( 'nifty_before_menu_primary' ); ?>

	<div id="menu-primary" class="menu-container">

		<div class="wrapper">

			<?php wp_nav_menu( array( 'theme_location' => 'menu-primary', 'container_class' => 'menu', 'menu_class' => '', 'menu_id' => 'menu-primary-items', 'link_before' => '<span>', 'link_after' => '</span>', 'fallback_cb' => '' ) ); ?>

		</div>

	</div><!-- #menu-primary .menu-container -->

	<?php do_action( 'nifty_after_menu_primary' ); ?>

<?php endif; ?>

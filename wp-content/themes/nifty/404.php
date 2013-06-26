<?php
/**
 * The 404 template is used when a reader visits an invalid URL on your site.
 *
 * @package Nifty
 * @subpackage Template
 */

get_header(); ?>

	<?php do_action( 'nifty_before_content' ); ?>

	<div id="content">

		<?php do_action( 'nifty_open_content' ); ?>

		<div class="hfeed">

			<div class="error-404 not-found hentry">

				<h1 class="entry-title"><?php _e( 'Page Not Found', 'nifty' ); ?></h1>

				<div class="entry-content">

					<p><?php _e( 'Looks like the page you\'re looking for has been moved or had its name changed. Or maybe it\'s just fate. You could use the search box in the header to search for what you\'re looking for.', 'nifty' ); ?></p>

					<?php get_search_form(); ?>

				</div> <!-- .entry-content -->

			</div> <!-- .hentry -->

		</div> <!-- .hfeed -->

		<?php do_action( 'nifty_close_content' ); ?>

	</div><!-- #content -->

	<?php do_action( 'nifty_after_content' ); ?>

<?php get_footer(); ?>

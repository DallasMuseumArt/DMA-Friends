<?php
/**
 * Loop Error Template
 *
 * Displays an error message when no posts are found.
 *
 * @package Nifty
 * @subpackage Template
 */
?>

<div id="post-0" class="post error404 not-found">

	<h2 class="entry-title"><?php _e( 'Page Not Found', 'nifty' ) ?></h2>

	<p><?php _e( "Seems like you have done something that's uncool, because we can't find whatever you are after", 'nifty' ); ?></p>

	<?php get_search_form(); ?>

</div>


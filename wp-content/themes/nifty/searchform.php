<?php
/**
 * Search Form Template
 *
 * The search form template displays the search form.
 *
 * @package Nifty
 * @subpackage Template
 */

global $search_num;
++$search_num;

$num = ( $search_num ) ? '-' . $search_num : ''; ?>

<div class="search" id="search<?php echo $num; ?>">

	<form method="get" class="search-form" id="search-form<?php echo $num; ?>" action="<?php echo home_url(); ?>/">
	<div>
		<input id="search-text<?php echo $num; ?>" class="search-text" type="text" name="s" tabindex="7" value="<?php if ( is_search() ) echo esc_attr( get_search_query() ); ?>" />
		<input class="search-submit button" name="submit" type="submit" id="search-submit<?php echo $num; ?>" tabindex="8" value="<?php esc_attr_e( 'Search', 'nifty' ); ?>" />
	</div>
	</form><!-- .search-form -->

</div><!-- .search -->

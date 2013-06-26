<?php

/* Collect User Email Page */

remove_action( 'genesis_loop', 'genesis_do_loop' );
remove_action( 'genesis_sidebar', 'genesis_do_sidebar' );
remove_action( 'genesis_header', 'dma_user_profile', 8 );
remove_action( 'genesis_header', 'genesis_header_markup_open', 5 );
remove_action( 'genesis_header', 'dma_header_links' );
remove_action( 'genesis_header', 'genesis_header_markup_close', 15 );

add_filter( 'body_class', 'dma_add_login_class' );
function dma_add_login_class( $classes ) {
	// Cheating to remove the user profile blocks
	// Not symantic, but not a "web site" so it's ok
	// No really, it's ok
	$classes[] = 'login-please';
	// Add our page body class
	$classes[] = 'update-email';

	return $classes;
}


add_action( 'genesis_loop', 'dma_user_dashboard' );
function dma_user_dashboard() {

	// See if we're logged in
	if ( is_user_logged_in() ) {

		// Grab our current user data
		$dma_user = $GLOBALS['dma_user'];

		?>

		<a href="<?php echo site_url( '/?skipped=true' ); ?>" id="email_skip" class="button btn-sm">Skip</a>
		<div id="email_form">
			<h1 class="arrow">What is your email address?</h1>
			<p>We'll let you know when you've won a badge or there's a new challenge ready for you.</p>
			<form action="<?php echo site_url(); ?>" method="post">
				<div class="input-highlight"><input type="text" id="email" name="email" value="" placeholder="you@example.com" /></div>
				<?php wp_nonce_field( 'save_profile_data', 'profile_data' ); ?>
				<button type="submit" id="Submit" name="Submit" class="btn-arrow green"><span>Submit</span></button>
			</form>
		</div><!-- #email_form -->
		<?php
	}

}

genesis();

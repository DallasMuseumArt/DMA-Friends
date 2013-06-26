<?php
/*
Template Name: Collect User Email
*/

remove_action( 'genesis_loop', 'genesis_do_loop' );


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
			<p>We'll let you know when you've won a badge or when there's a new challenge ready for you.</p>
			<form action="<?php echo site_url(); ?>" method="post">
				<div class="input-highlight"><input type="text" name="email" placeholder="user@example.com" value="justin@dsgnwrks.pro"></div>
				<!-- <input type="text" id="email" name="email" value="" placeholder="you@example.com" /> -->
				<?php wp_nonce_field( 'save_profile_data', 'profile_data' ); ?>
				<button type="submit" id="Submit" name="Submit" class="btn-arrow"><span>Submit</span></button>
			</form>
		</div><!-- #email_form -->
		<?php
	}

}

genesis();

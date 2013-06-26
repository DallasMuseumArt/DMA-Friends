<?php
/**
 * Author Profile
 * 
 * Displays an author profile block with a link to the author archives,
 * the avatar and the biographical info from user's profile.
 *
 * @package Nifty
 * @subpackage Template
 */

if ( $description = get_the_author_meta( 'description' ) ) : ?>

	<div class="author-profile vcard">

		<?php echo get_avatar( get_the_author_meta( 'user_email' ), '80', '', get_the_author_meta( 'display_name' ) ) ?>

		<h4 class="author-name fn n"><?php the_author_posts_link(); ?></h4>

		<div class="author-description author-bio">
			<?php echo $description; ?>
		</div>

	</div> <!-- .author-profile  vcard-->

<?php endif; ?>

<?php
/**
 * Footer Template
 *
 * The footer template is generally used on every page of your site. Nearly all other
 * templates call it somewhere near the bottom of the file.
 *
 * @package Nifty
 * @subpackage Template
 */
?>

		<?php get_sidebar( 'primary' ); // Loads the sidebar-primary.php template ?>

		<?php get_sidebar( 'secondary' ); // Loads the sidebar-secondary.php template ?>

		<?php do_action( 'nifty_close_container' ); ?>

		</div> <!-- .wrapper -->

	</div> <!-- #container -->

	<?php get_sidebar( 'subsidiary' ); // Loads the sidebar-subsidiary.php template ?>

	<?php do_action( 'nifty_before_footer' ); ?>

	<div id="footer">

		<?php do_action( 'nifty_open_footer' ); ?>

		<div class="wrapper">
			<?php do_action( 'nifty_footer' ); ?>
		</div>

		<?php do_action( 'nifty_close_footer' ); ?>

	</div> <!-- #footer -->

	<?php do_action( 'nifty_after_footer' ); ?>

</div> <!-- #body-container -->

<?php do_action( 'nifty_close_body' ); ?>

<?php wp_footer(); // Always have wp_footer() just before the closing </body> tag of your theme. ?>

</body>
</html>

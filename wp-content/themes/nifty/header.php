<?php
/**
 * Header Template
 *
 * Nearly all other templates call it somewhere near the top of the file. It is used mostly as an opening
 * wrapper, which is closed with the footer.php file.
 *
 * @package Nifty
 * @subpackage Template
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<title><?php nifty_document_title(); ?></title>
<?php wp_head(); // Always have wp_head() just before the closing </head> tag of your theme. ?>
</head>

<body class="<?php nifty_body_class(); ?>">

<?php do_action( 'nifty_open_body' ); ?>

<div id="body-container">

	<?php get_template_part( 'menu', 'primary' ); // Loads the menu-primary.php template. ?>

	<?php do_action( 'nifty_before_header' ); ?>

	<div id="header">

		<div class="wrapper">

			<?php do_action( 'nifty_header' ); ?>

		</div> <!-- .wrapper -->

	</div> <!-- #header -->

	<?php do_action( 'nifty_after_header' ); ?>

	<?php get_template_part( 'menu', 'secondary' ); // Loads the menu-secondary.php template. ?>

	<div id="container">

		<div class="wrapper">

		<?php do_action( 'nifty_open_container' ); ?>

		<?php if ( current_theme_supports( 'breadcrumbs-plus' ) ) breadcrumbs_plus(); ?>

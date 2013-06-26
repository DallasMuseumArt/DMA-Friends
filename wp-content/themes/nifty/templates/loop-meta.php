<?php
/**
 * Loop Meta Template
 *
 * Displays information at the top of the page about archive and search results when viewing those pages.
 *
 * @package Nifty
 * @subpackage Template
 */
?>

	<?php if ( is_home() && !is_front_page() ) : ?>

		<?php global $wp_query; ?>

		<div class="archive-info blog-info">

			<h2 class="archive-title blog-title"><?php echo get_post_field( 'post_title', $wp_query->get_queried_object_id() ); ?></h2>

			<div class="archive-description blog-description">

				<?php echo get_post_field( 'post_excerpt', $wp_query->get_queried_object_id() ); ?>

			</div><!-- .archive-description -->

		</div><!-- .archive-info -->

	<?php elseif ( is_category() ) : ?>

		<div class="archive-info category-info">

			<h1 class="archive-title category-title"><?php single_cat_title(); ?></h1>

			<div class="archive-description category-description">

				<?php echo category_description(); ?>

			</div><!-- .archive-description -->

		</div><!-- .archive-info -->

	<?php elseif ( is_tag() ) : ?>

		<div class="archive-info tag-info">

			<h1 class="archive-title tag-title"><?php single_tag_title(); ?></h1>

			<div class="archive-description tag-description">
				<?php echo tag_description(); ?>
			</div><!-- .archive-description -->

		</div><!-- .archive-info -->

	<?php elseif ( is_tax() ) : ?>

		<div class="archive-info taxonomy-info">

			<h1 class="archive-title taxonomy-title"><?php $term = get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' ) ); echo $term->name; ?></h1>

			<div class="archive-description taxonomy-description">

				<?php echo term_description( '', get_query_var( 'taxonomy' ) ); ?>

			</div><!-- .archive-description -->

		</div><!-- .archive-info -->

	<?php elseif ( is_author() ) : ?>

		<?php $id = get_query_var( 'author' ); ?>

		<div class="archive-info author-info">
			
			<h1 class="archive-title user-title author-title fn n"><?php the_author_meta( 'display_name', $id ); ?></h1>

			<div class="archive-description user-description author-description">

				<?php echo get_avatar( get_the_author_meta( 'user_email', $id ), 80, '', get_the_author_meta( 'display_name', $id ) ); ?>

				<div class="user-bio author-bio"><?php the_author_meta( 'description', $id ); ?></div>

			</div><!-- .archive-description -->

		</div><!-- .archive-info -->

	<?php elseif ( is_search() ) : ?>

		<div class="search-info">

			<h1 class="search-title"><?php echo esc_attr( get_search_query() ); ?></h1>

			<div class="search-description">

				<p><?php printf( __( 'You are browsing the search results for &quot;%1$s&quot;', 'nifty' ), esc_attr( get_search_query() ) ); ?></p>

			</div> <!-- .search-description -->

		</div> <!-- .search-info -->

	<?php elseif ( is_date() ) : ?>

		<div class="archive-info date-info">

			<h1 class="archive-title date-title"> <?php
			if ( is_day() ) :
				printf( __( 'Daily Archives: <span>%s</span>', 'nifty' ), get_the_date() );

			elseif ( is_month() ) :
				printf( __( 'Monthly Archives: <span>%s</span>', 'nifty' ), get_the_date( 'F Y' ) );

			elseif ( is_year() ) :
				printf( __( 'Yearly Archives: <span>%s</span>', 'nifty' ), get_the_date( 'Y' ) );

			endif; ?>
			</h1>

			<div class="archive-description date-description">

				<p> <?php
				if ( is_day() ) :
					printf( __( 'You are browsing the archive for %1$s.', 'nifty' ), get_the_date() );

				elseif ( is_month() ) :
					printf( __( 'You are browsing the archive for %1$s.', 'nifty' ), get_the_date( 'F Y' ) );

				elseif ( is_year() ) :
					printf( __( 'You are browsing the archive for %1$s.', 'nifty' ), get_the_date( 'Y' ) );

				endif; ?>
				</p>

			</div><!-- .archive-description -->

		</div><!-- .archive-info -->

	<?php elseif ( is_post_type_archive() ) : ?>

		<?php $post_type = get_post_type_object( get_query_var( 'post_type' ) ); ?>

		<div class="archive-info">

			<h1 class="archive-title"><?php post_type_archive_title(); ?></h1>

			<?php if ( !empty( $post_type->description ) ) : ?>

			<div class="archive-description">

				<p><?php echo $post_type->description; ?></p>

			</div><!-- .archive-description -->

			<?php endif; ?>

		</div><!-- .archive-info -->

	<?php elseif ( is_archive() ) : ?>

		<div class="archive-info">

			<h1 class="archive-title"><?php _e( 'Archives', 'nifty' ); ?></h1>

			<div class="archive-description">

				<p><?php _e( 'You are browsing the site archives.', 'nifty' ); ?></p>

			</div><!-- .archive-description -->

		</div><!-- .archive-info -->

	<?php endif; ?>


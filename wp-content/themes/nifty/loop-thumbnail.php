<?php
/**
 * The loop that displays a thumbnail.
 * 
 * This can be overridden in child themes with loop-thumbnail.php.
 * 
 * @package Nifty
 * @subpackage Template
 */

global $post;

if ( has_post_thumbnail( $post->ID ) ) :

	/* Get the Thumbnail. */
	$thumbnail = get_the_post_thumbnail( $post->ID, 'thumbnail', array( 'class' => 'thumbnail', 'title' => '' ) );

	/* Link the image to this post. */
	$thumbnail = '<a href="' . get_permalink( $post->ID ) . '" title="' . strip_tags( get_the_title( $post->ID ) ) . '">' . $thumbnail . '</a>';

	/* Displays the Thumbnail. */
	echo $thumbnail;

endif; ?>

<?php
/**
 * Improving Caption - Improving up the WP [caption] shortcode.
 * 
 * This plugin changes the width to match that of the 'width' attribute passed in
 * through the shortcode, allowing themes to better handle how their captions are designed.
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU 
 * General Public License version 2, as published by the Free Software Foundation.  You may NOT assume 
 * that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without 
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * You should have received a copy of the GNU General Public License along with this program; if not, write 
 * to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 *
 * @package ImprovingCaption
 * @version 0.1
 * @author Luis Alberto Ochoa Esparza <soy@luisalberto.org>
 * @copyright Copyright (c) 2010-2011, Luis Alberto Ochoa Esparza
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

/* Filter the caption shortcode output. */
add_filter( 'img_caption_shortcode', 'improving_caption', 10, 3 );

/**
 * Improve up the default WordPress [caption] shortcode.
 *
 * @since 0.1
 * @param string $output The output of the default caption (empty string at this point).
 * @param array $attr Array of arguments for the [caption] shortcode.
 * @param string $content The content placed after the opening [caption] tag and before the closing [/caption] tag.
 * @return string $output The formatted HTML for the caption.
 */
function improving_caption( $output, $attr, $content ) {

	/* We're not worried abut captions in feeds, so just return the output here. */
	if ( is_feed() )
		return $output;

	/* Set up the default arguments. */
	$defaults = array(
		'id' => '',
		'align' => 'alignnone',
		'width' => '',
		'caption' => ''
	);

	/* Merge the defaults with user input. */
	$attr = shortcode_atts( $defaults, $attr );

	/* If the width is less than 1 or there is no caption, return the content wrapped between the [caption] tags. */
	if ( 1 > $attr['width'] || empty( $attr['caption'] ) )
		return $content;

	/* Set up the attributes for the caption <div>. */
	$attributes = ( !empty( $attr['id'] ) ? ' id="' . esc_attr( $attr['id'] ) . '"' : '' );
	$attributes .= ' class="wp-caption ' . esc_attr( $attr['align'] ) . '"';
	$attributes .= ' style="width: ' . esc_attr( $attr['width'] ) . 'px"';

	$output = '<div' . $attributes .'>';
	$output .= do_shortcode( $content );
	$output .= '<p class="wp-caption-text">' . $attr['caption'] . '</p>';
	$output .= '</div>';

	return $output;
}


<?php
/**
 * Adds the SEO meta box to the post editing screen for public post types. This feature allows the post author 
 * to set a custom title, description, and keywords for the post, which will be viewed on the singular post page.  
 *
 * @package Nifty
 * @subpackage Admin
 */

/* Add the post meta box creation function to the 'add_meta_boxes' hook. */
add_action( 'add_meta_boxes', 'nifty_create_post_meta_box_seo' );

/* Saves the post meta box data. */
add_action( 'save_post', 'nifty_save_post_meta_box_seo', 10, 2 );

/**
 * Creates a meta box on the post (page, other post types) editing screen for allowing the easy input of 
 * commonly-used post metadata.
 *
 * @since 12.09
 * @uses get_post_types() Gets an array of post type objects.
 * @uses add_meta_box() Adds a meta box to the post editing screen.
 */
function nifty_create_post_meta_box_seo() {

	/* Gets available public post types. */
	$post_types = get_post_types( array( 'public' => true ), 'objects' );

	/* For each available post type, create a meta box on its edit page. */
	foreach ( $post_types as $type )
		add_meta_box( "nifty-{$type->name}-meta-box-seo", sprintf( __( '%1$s SEO', 'nifty' ), $type->labels->singular_name ), 'nifty_post_meta_box_seo', $type->name, 'normal', 'high' );

}

/**
 * Displays the post meta box on the edit post page.
 *
 * @since 12.09
 * @parameter object $object Post object that holds all the post information.
 * @parameter array $box The particular meta box being shown and its information.
 */
function nifty_post_meta_box_seo( $object ) { ?>

	<input type="hidden" name="<?php echo "nifty-{$object->post_type}-meta-box-seo"; ?>" value="<?php echo wp_create_nonce( basename( __FILE__ ) ); ?>" />

	<div class="nifty-post-settings">

	<p>
		<label for="nifty-document-title"><?php _e( 'Document Title:', 'nifty' ); ?></label>
		<br />
		<input type="text" name="nifty-document-title" id="nifty-document-title" value="<?php echo esc_attr( get_post_meta( $object->ID, '_nifty_title', true ) ); ?>" size="30" tabindex="30" style="width: 99%;" />
	</p>

	<p>
		<label for="nifty-description-title"><?php _e( 'Meta Description:', 'nifty' ); ?></label>
		<br />
		<textarea name="nifty-meta-description" id="nifty-meta-description" cols="60" rows="2" tabindex="30" style="width: 99%;" /><?php echo esc_attr( get_post_meta( $object->ID, '_nifty_description', true ) ); ?></textarea>
	</p>

	<p>
		<label for="nifty-meta-keywords"><?php _e( 'Meta Keywords:', 'nifty' ); ?></label>
		<br />
		<input type="text" name="nifty-meta-keywords" id="nifty-meta-keywords" value="<?php echo esc_attr( get_post_meta( $object->ID, '_nifty_keywords', true ) ); ?>" size="30" tabindex="30" style="width: 99%;" />
	</p>

	<p>
		<label for="nifty-redirect"><?php _e( '301 Redirect:', 'nifty' ); ?></label>
		<br />
		<input type="text" name="nifty-redirect" id="nifty-redirect" value="<?php echo esc_attr( get_post_meta( $object->ID, '_nifty_redirect', true ) ); ?>" size="30" tabindex="30" style="width: 99%;" />
	</p>

	</div><!-- .nifty-post-settings --> <?php
}

/**
 * Saves the post SEO meta box settings as post metadata.
 *
 * @since 12.09
 * @param int $post_id The ID of the current post being saved.
 * @param int $post The post object currently being saved.
 */
function nifty_save_post_meta_box_seo( $post_id, $post ) {

	/* Verify nonce before preceding. */
	if ( !isset( $_POST["nifty-{$post->post_type}-meta-box-seo"] ) || !wp_verify_nonce( $_POST["nifty-{$post->post_type}-meta-box-seo"], basename( __FILE__ ) ) )
		return $post_id;

	/* Get the post type object. */
	$post_type = get_post_type_object( $post->post_type );

	/* Check if the current user has permission to edit the post. */
	if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
		return $post_id;

	/* Get the post meta box arguments. */
	$metadata = array(
		'_nifty_title'       => strip_tags( $_POST['nifty-document-title']   ),
		'_nifty_description' => strip_tags( $_POST['nifty-meta-description'] ),
		'_nifty_keywords'    => strip_tags( $_POST['nifty-meta-keywords']    ),
		'_nifty_redirect'    => strip_tags( $_POST['nifty-redirect']         )
	);

	foreach ( $metadata as $key => $new_meta_value ) {

		$meta_value = get_post_meta( $post_id, $key, true );

		if ( $new_meta_value && '' == $meta_value )
			add_post_meta( $post_id, $key, $new_meta_value, true );

		else if ( $new_meta_value && $new_meta_value != $meta_value )
			update_post_meta( $post_id, $key, $new_meta_value );

		else if ( '' == $new_meta_value && $meta_value )
			delete_post_meta( $post_id, $key, $meta_value );
	}
}


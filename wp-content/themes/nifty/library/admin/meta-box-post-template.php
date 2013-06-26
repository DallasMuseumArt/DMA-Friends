<?php
/**
 * Adds the template meta box to the post editing screen for public post types. This feature allows users and 
 * devs to create custom templates for any post type, not just pages as default in WordPress core.
 *
 * @package Nifty
 * @subpackage Admin
 */

/* Add the post meta box creation function to the 'add_meta_boxes' hook. */
add_action( 'add_meta_boxes', 'nifty_create_post_meta_box_template' );

/* Saves the post meta box data. */
add_action( 'save_post', 'nifty_save_post_meta_box_template', 10, 2 );

/**
 * Adds the post template meta box for all public post types, excluding the 'page' post type since WordPress 
 * core already handles page templates.
 *
 * @since 12.09
 * @uses get_post_types() Gets an array of post type objects.
 * @uses add_meta_box() Adds a meta box to the post editing screen.
 */
function nifty_create_post_meta_box_template() {

	/* Gets available public post types. */
	$post_types = get_post_types( array( 'public' => true ), 'objects' );

	/* For each available post type, create a meta box on its edit page. */
	foreach ( $post_types as $type ) {

		if ( 'page' !== $type->name )
			add_meta_box( "nifty-{$type->name}-meta-box-template", sprintf( __( '%1$s Template', 'nifty' ), $type->labels->singular_name ), 'nifty_post_meta_box_template', $type->name, 'side', 'default' );
	}
}

/**
 * Displays the post meta box on the edit post page.
 *
 * @since 12.09
 * @parameter object $object Post object that holds all the post information.
 * @parameter array $box The particular meta box being shown and its information.
 */
function nifty_post_meta_box_template( $object ) {

	/* Get the post type object. */
	$post_type_object = get_post_type_object( $object->post_type );

	/* If the post type object returns a singular name or name. */
	if ( !empty( $post_type_object->labels->singular_name ) || !empty( $post_type_object->name ) ) {

		/* Get a list of available custom templates for the post type. */
		$templates = nifty_get_post_templates( $object->post_type );
	} ?>

	<input type="hidden" name="<?php echo "nifty-{$object->post_type}-meta-box-template"; ?>" value="<?php echo wp_create_nonce( basename( __FILE__ ) ); ?>" />

	<div class="nifty-post-settings">

	<p>
		<?php if ( 0 !== count( $templates ) ) : ?>

		<select name="nifty-post-template" id="nifty-post-template" class="widefat">
			<option value=""></option>
			<?php foreach ( $templates as $label => $template ) : ?>
				<option value="<?php echo esc_attr( $template ); ?>" <?php selected( esc_attr( get_post_meta( $object->ID, "_wp_{$post_type_object->name}_template", true ) ), esc_attr( $template ) ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>

		<?php else : ?>

		<p><?php _e( 'No templates exist for this post type.', 'nifty' ); ?></p>

		<?php endif; ?>
	</p>

	</div><!-- .nifty-post-settings --> <?php
}

/**
 * Saves the post template meta box settings as post metadata.
 *
 * @since 12.09
 * @param int $post_id The ID of the current post being saved.
 * @param int $post The post object currently being saved.
 */
function nifty_save_post_meta_box_template( $post_id, $post ) {

	/* Verify nonce before preceding. */
	if ( !isset( $_POST["nifty-{$post->post_type}-meta-box-template"] ) || !wp_verify_nonce( $_POST["nifty-{$post->post_type}-meta-box-template"], basename( __FILE__ ) ) )
		return $post_id;

	/* Get the post type object. */
	$post_type = get_post_type_object( $post->post_type );

	/* Check if the current user has permission to edit the post. */
	if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
		return $post_id;

	/* Get the posted meta value. */
	if ( !isset( $_POST['nifty-post-template'] ) )
		return $post_id;

	$new_meta_value = strip_tags( $_POST['nifty-post-template'] );

	/* Set the $meta_key variable based off the post type name. */
	$meta_key = "_wp_{$post->post_type}_template";

	/* Get the meta value of the meta key. */
	$meta_value = get_post_meta( $post_id, $meta_key, true );

	if ( $new_meta_value && '' == $meta_value )
		add_post_meta( $post_id, $meta_key, $new_meta_value, true );

	else if ( $new_meta_value && $new_meta_value != $meta_value )
		update_post_meta( $post_id, $meta_key, $new_meta_value );

	else if ( '' == $new_meta_value && $meta_value )
		delete_post_meta( $post_id, $meta_key, $meta_value );
}


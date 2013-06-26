<?php
/**
 * Nifty's extra widgets and removes the default styles that are packaged with the
 * Recent Comments widget.
 *
 * @package Nifty
 * @subpackage Functions
 */

/* Register Nifty widgets. */
add_action( 'widgets_init', 'nifty_register_widgets' );

/**
 * Registers Nifty's extra widgets.
 *
 * @since 12.09
 * @uses register_widget() Registers widgets with WordPress
 * @link http://codex.wordpress.org/Function_Reference/register_widget
 */
function nifty_register_widgets() {

	/* Register widgets. */
	register_widget( 'Nifty_Widget_Authors' );
	register_widget( 'Nifty_Widget_User_Profile' );
}

/**
 * Authors Widget Class
 *
 * The authors widget was created to give users the ability to list the authors of their blog because
 * there was no equivalent WordPress widget that offered the functionality.
 *
 * @since 12.09
 * @link http://codex.wordpress.org/Template_Tags/wp_list_authors
 */
class Nifty_Widget_Authors extends WP_Widget {

	/**
	 * Set up the widget's unique name, ID, class, description, and other options.
	 * 
	 * @since 12.09
	 */
	function Nifty_Widget_Authors() {
		$widget_ops = array( 'classname' => 'authors', 'description' => __( 'Author lists', 'nifty' ) );
		$control_ops = array( 'id_base' => 'nifty-authors' );
		$this->WP_Widget( 'nifty-authors', __( 'Authors', 'nifty' ), $widget_ops, $control_ops );
	}

	/**
	 * Outputs the widget based on the arguments input through the widget controls.
	 * 
	 * @since 12.09
	 */
	function widget( $args, $instance ) {

		extract( $args, EXTR_SKIP );

		$args = array();

		$args['style'] = $instance['style'];
		$args['feed'] = $instance['feed']; 
		$args['feed_image'] = $instance['feed_image'];
		$args['optioncount'] = isset( $instance['optioncount'] ) ? $instance['optioncount'] : false;
		$args['exclude_admin'] = isset( $instance['exclude_admin'] ) ? $instance['exclude_admin'] : false;
		$args['show_fullname'] = isset( $instance['show_fullname'] ) ? $instance['show_fullname'] : false;
		$args['hide_empty'] = isset( $instance['hide_empty'] ) ? $instance['hide_empty'] : false;
		$args['html'] = true;
		$args['echo'] = false;

		$authors_widget = $before_widget;

		if ( $instance['title'] )
			$authors_widget .= $before_title . apply_filters( 'widget_title',  $instance['title'], $instance, $this->id_base ) . $after_title;

		$authors = str_replace( array( "\r", "\n", "\t" ), '', wp_list_authors( apply_filters( 'widget_list_authors_args', $args ) ) );

		if ( 'list' == $args['style'] && $args['html'] )
			$authors = '<ul class="xoxo authors">' . $authors . '</ul><!-- .xoxo .authors -->';

		$authors_widget .= $authors;

		$authors_widget .= $after_widget;

		echo $authors_widget;
	}

	/**
	 * Updates the widget control options for the particular instance of the widget.
	 * 
	 * @since 12.09
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance = $new_instance;

		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['feed'] = strip_tags( $new_instance['feed'] );
		$instance['feed_image'] = strip_tags( $new_instance['feed_image'] );
		$instance['optioncount'] = ( isset( $new_instance['optioncount'] ) ? 1 : 0 );
		$instance['exclude_admin'] = ( isset( $new_instance['exclude_admin'] ) ? 1 : 0 );
		$instance['show_fullname'] = ( isset( $new_instance['show_fullname'] ) ? 1 : 0 );
		$instance['hide_empty'] = ( isset( $new_instance['hide_empty'] ) ? 1 : 0 );

		return $instance;
	}

	/**
	 * Displays the widget control options in the Widgets admin screen.
	 * 
	 * @since 12.09
	 */
	function form( $instance ) {

		/* Defaults */
		$defaults = array(
			'title' => __( 'Authors', 'nifty' ),
			'optioncount' => false,
			'exclude_admin' => false,
			'show_fullname' => true,
			'hide_empty' => true,
			'style' => 'list',
			'feed' => '',
			'feed_image' => ''
		);
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<div class="columns-2">
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'nifty' ); ?></label>
			<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'feed' ); ?>">Feed:</label>
			<input type="text" class="widefat code" id="<?php echo $this->get_field_id( 'feed' ); ?>" name="<?php echo $this->get_field_name( 'feed' ); ?>" value="<?php echo esc_attr( $instance['feed'] ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'feed_image' ); ?>">Feed Image:</label>
			<input type="text" class="widefat code" id="<?php echo $this->get_field_id( 'feed_image' ); ?>" name="<?php echo $this->get_field_name( 'feed_image' ); ?>" value="<?php echo esc_attr( $instance['feed_image'] ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'style' ); ?>">Style:</label> 
			<select class="widefat" id="<?php echo $this->get_field_id( 'style' ); ?>" name="<?php echo $this->get_field_name( 'style' ); ?>">
				<?php foreach ( array( 'list' => __( 'List', 'nifty'), 'none' => __( 'None', 'nifty' ) ) as $option_value => $option_label ) { ?>
					<option value="<?php echo $option_value; ?>" <?php selected( $instance['style'], $option_value ); ?>><?php echo $option_label; ?></option>
				<?php } ?>
			</select>
		</p>
		</div>

		<div class="columns-2 column-last">
		<p>
			<label for="<?php echo $this->get_field_id( 'optioncount' ); ?>">
			<input class="checkbox" type="checkbox" <?php checked( $instance['optioncount'], true ); ?> id="<?php echo $this->get_field_id( 'optioncount' ); ?>" name="<?php echo $this->get_field_name( 'optioncount' ); ?>" /> <?php _e( 'Show post count', 'nifty' ); ?></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'exclude_admin' ); ?>">
			<input class="checkbox" type="checkbox" <?php checked( $instance['exclude_admin'], true ); ?> id="<?php echo $this->get_field_id( 'exclude_admin' ); ?>" name="<?php echo $this->get_field_name( 'exclude_admin' ); ?>" /> <?php _e( 'Exclude admin', 'nifty' ); ?></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'show_fullname' ); ?>">
			<input class="checkbox" type="checkbox" <?php checked( $instance['show_fullname'], true ); ?> id="<?php echo $this->get_field_id( 'show_fullname' ); ?>" name="<?php echo $this->get_field_name( 'show_fullname' ); ?>" /> <?php _e( 'Show full name', 'nifty' ); ?></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'hide_empty' ); ?>">
			<input class="checkbox" type="checkbox" <?php checked( $instance['hide_empty'], true ); ?> id="<?php echo $this->get_field_id( 'hide_empty' ); ?>" name="<?php echo $this->get_field_name( 'hide_empty' ); ?>" /> <?php _e( 'Hide empty', 'nifty' ); ?></label>
		</p>
		</div>
		<div style="clear:both;">&nbsp;</div>
	<?php
	}
}

class Nifty_Widget_User_Profile extends WP_Widget {

	function Nifty_Widget_User_Profile() {
		$widget_ops = array( 'classname' => 'profile', 'description' => __( 'Displays an author profile block with a link to the author archives, the avatar and the biographical info from user\'s profile', 'nifty' ) );
		$control_ops = array( 'id_base' => 'nifty-user-profile' );
		$this->WP_Widget( 'nifty-user-profile', __( 'User Profile', 'nifty' ), $widget_ops, $control_ops );
	}

	function widget( $args, $instance ) {
		extract($args);
		$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );

		echo $before_widget;
		if ( $title )
			echo $before_title . $title . $after_title;

		nifty_author_profile();

		echo $after_widget;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
		$title = $instance['title']; ?>

		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:', 'nifty' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></label></p> <?php
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$new_instance = wp_parse_args( (array) $new_instance, array( 'title' => '' ) );
		$instance['title'] = strip_tags( $new_instance['title'] );
		return $instance;
	}

}

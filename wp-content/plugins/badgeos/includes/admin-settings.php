<?php
/**
 * BadgeStack Admin Settings Pages
 *
 * @package BadgeStack
 */

add_action( 'admin_init', 'badgestack_register_settings' );

/**
 * Register BadgeStack Settings with Settings API.
 *
 *
 *
 */
function badgestack_register_settings() {

	register_setting( 'badgestack_settings_group', 'badgestack_settings', 'badgestack_settings_validate' );

}

/**
 * BadgeStack Settings validation
 *
 *
 *
 */
function badgestack_settings_validate( $input ) {

	//sanitize the settings data submitted
	$input['api_key']      = sanitize_text_field( $input['api_key'] );
	$input['minimum_role'] = sanitize_text_field( $input['minimum_role'] );
	$input['debug_mode']   = sanitize_text_field( $input['debug_mode'] );

	$input['dashboard_slug'] = str_replace( '/', 'forwardslash', $input['dashboard_slug'] );
	$input['dashboard_slug'] = sanitize_title( $input['dashboard_slug'] );
	$input['dashboard_slug'] = str_replace( 'forwardslash', '/', $input['dashboard_slug'] );
	$input['dashboard_slug'] = trim( $input['dashboard_slug'], '/' );

	$input['user_profile_slug'] = str_replace( '/', 'forwardslash', $input['user_profile_slug'] );
	$input['user_profile_slug'] = sanitize_title( $input['user_profile_slug'] );
	$input['user_profile_slug'] = str_replace( 'forwardslash', '/', $input['user_profile_slug'] );
	$input['user_profile_slug'] = trim( $input['user_profile_slug'], '/' );
	return $input;

}

/**
 * BadgeStack main settings page output.
 *
 *
 *
 */
function badgestack_settings_page() {
	flush_rewrite_rules();
	if ( badgestack_is_debug_mode() )
		echo 'debug mode is on';

	// TODO should we be using do_settings_fields here to be more efficient?
	?>
	<div class="wrap" >
		<div id="icon-options-general" class="icon32"></div>
		<h2><?php _e( 'BadgeOS Settings', 'badgestack' ); ?></h2>

		<form method="post" action="options.php">
			<?php settings_fields( 'badgestack_settings_group' ); ?>
			<?php $badgestack_settings = get_option( 'badgestack_settings' ); ?>
			<table class="form-table">
				<tr valign="top"><th scope="row"><label for="api_key"><?php _e( 'API Key: ', 'badgestack' ); ?></label></th>
					<td><input id="api_key" type="text" name="badgestack_settings[api_key]" value="<?php echo isset( $badgestack_settings['api_key'] ) ? esc_attr( $badgestack_settings['api_key'] ) : ''; ?>" /></td>
				</tr>
				<tr valign="top"><th scope="row"><label for="minimum_role"><?php _e( 'Lowest Role to View BadgeOS: ', 'badgestack' ); ?></label></th>
					<td>
                        <select id="minimum_role" name="badgestack_settings[minimum_role]">
                            <option value="manage_options" <?php selected( $badgestack_settings['minimum_role'], 'manage_options' ); ?>><?php _e( 'Administrator', 'badgestack' ); ?></option>
                            <option value="delete_others_posts" <?php selected( $badgestack_settings['minimum_role'], 'delete_others_posts' ); ?>><?php _e( 'Editor', 'badgestack' ); ?></option>
                            <option value="publish_posts" <?php selected( $badgestack_settings['minimum_role'], 'publish_posts' ); ?>><?php _e( 'Author', 'badgestack' ); ?></option>
                            <option value="edit_posts" <?php selected( $badgestack_settings['minimum_role'], 'edit_posts' ); ?>><?php _e( 'Contributor', 'badgestack' ); ?></option>
                            <option value="read" <?php selected( $badgestack_settings['minimum_role'], 'read' ); ?>><?php _e( 'Subscriber', 'badgestack' ); ?></option>
                        </select>
					</td>
				</tr>
				<tr valign="top"><th scope="row"><label for="dashboard_slug"><?php _e( 'Dashboard URL', 'badgestack' ); ?></label></th>
					<td>
						<?php echo trailingslashit( site_url() ); ?><input id="dashboard_slug" type="text" name="badgestack_settings[dashboard_slug]" value="<?php echo isset( $badgestack_settings['dashboard_slug'] ) ? esc_attr( $badgestack_settings['dashboard_slug'] ) : ''; ?>" />/<BR>
						<p class="description">The URL your users will visit for their achievements dashboard.</p>
					</td>
				</tr>

				<tr valign="top"><th scope="row"><label for="user_profile_slug"><?php _e( 'User Profile URL', 'badgestack' ); ?></label=></th>
					<td>
						<?php echo trailingslashit( site_url() ); ?><input id="user_profile_slug" type="text" name="badgestack_settings[user_profile_slug]" value="<?php echo isset( $badgestack_settings['user_profile_slug'] ) ? esc_attr( $badgestack_settings['user_profile_slug'] ) : ''; ?>" />/[username]/
						<p class="description">The URL your users will visit for their user profile.</p>
					</td>

				</tr>
				<tr valign="top"><th scope="row"><label for="debug_mode"><?php _e( 'Debug Mode', 'badgestack' ); ?></label></th>
					<td>
                        <select id="debug_mode" name="badgestack_settings[debug_mode]">
                            <option value="disabled" <?php selected( $badgestack_settings['debug_mode'], 'disabled' ); ?>><?php _e( 'Disabled', 'badgestack' ) ?></option>
                            <option value="enabled" <?php selected( $badgestack_settings['debug_mode'], 'enabled' ); ?>><?php _e( 'Enabled', 'badgestack' ) ?></option>
                        </select>
					</td>
				</tr>
				<?php do_action( 'badgestack_settings', $badgestack_settings ); ?>
			</table>
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e( 'Save Settings', 'badgestack' ); ?>" />
			</p>
			<!-- TODO: Add settings to select WP page for archives of each achievement type.
				See BuddyPress' implementation of this idea.  -->
		</form>
	</div>
	<?php
}

/**
 * BadgeStack Add-ons settings page.
 *
 *
 *
 */
function badgestack_add_ons_page() {
	?>
	<div class="wrap" >
		<div id="icon-options-general" class="icon32"></div>
		<h2><?php _e( 'BadgeStack Add-Ons', 'badgestack' ); ?></h2>
	</div>
	<?php
}

/**
 * BadgeStack Help and Support settings page.
 *
 *
 *
 */
function badgestack_help_support_page() {
	?>
	<div class="wrap" >
		<div id="icon-options-general" class="icon32"></div>
		<h2><?php _e( 'BadgeStack Help and Support', 'badgestack' ); ?></h2>
	</div>
	<?php
}


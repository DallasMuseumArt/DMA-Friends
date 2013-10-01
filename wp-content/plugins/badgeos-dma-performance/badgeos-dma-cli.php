<?php

/**
 * DMA Data Migrator
 *
 * @package wp-cli
 */
class DMA_Migration extends WP_CLI_Command {

	/**
	 * Run all migration sub-commands
	 *
	 * @since 1.0.0
	 */
	public function migrate_everything( $args, $assoc_args ) {
		WP_CLI::line( 'Migrating everything, starting... now! ' . date( 'h:i:sa' ) );
		$start_time = time();
		$this->create_tables();
		$this->migrate_user_meta();
		$this->migrate_post_meta();
		$this->migrate_step_cpt();
		$this->migrate_step_triggers();
		$this->migrate_log_entries();
		$this->migrate_stream_entries();

		if ( $assoc_args['cleanup'] )
			$this->data_cleanup();

		WP_CLI::line( 'Migration complete! ' . date( 'h:i:sa' ) );
		WP_CLI::line( 'Time elapsed: ' . gmdate( 'H:i:s', ( time() - $start_time ) ) );
		WP_CLI::line( 'Be sure to run "wp dma data_cleanup" to remove the now defunct data.' );
	}

	public function create_tables() {
		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		// Create the new Log Entries table
		$log_table_name = $wpdb->prefix . 'dma_log_entries';
		$log_table_sql =
		"
		CREATE TABLE $log_table_name (
			ID bigint(20) unsigned NOT NULL auto_increment,
			user_id bigint(20) unsigned NOT NULL default '0',
			object_id bigint(20) unsigned NOT NULL default '0',
			action VARCHAR( 100 ) NOT NULL,
			title text NOT NULL,
			artwork_id VARCHAR( 50 ) NOT NULL,
			awarded_points bigint(20) NOT NULL default '0',
			total_points bigint(20) NOT NULL default '0',
			admin_id bigint(20) unsigned NOT NULL default '0',
			timestamp datetime NOT NULL default '0000-00-00 00:00:00',
			PRIMARY KEY  (ID),
			INDEX (  ID , user_id , object_id, action )
		);
		";
		dbDelta( $log_table_sql );

		// Create the new Activity Stream table
		$activity_table_name = $wpdb->prefix . 'dma_activity_stream';
		$activity_table_sql =
		"
		CREATE TABLE $activity_table_name (
			ID bigint(20) unsigned NOT NULL auto_increment,
			user_id bigint(20) unsigned NOT NULL default '0',
			object_id bigint(20) unsigned NOT NULL default '0',
			action VARCHAR( 100 ) NOT NULL,
			artwork_id VARCHAR( 50 ) NOT NULL,
			timestamp datetime NOT NULL default '0000-00-00 00:00:00',
			PRIMARY KEY  (ID),
			INDEX (  ID , user_id , object_id, action )
		);
		";
		dbDelta( $activity_table_sql );
		WP_CLI::line( 'Created new custom tables.' );
	}

	/**
	 * Import log entries into custom table
	 *
	 * @since 1.0.0
	 */
	public function migrate_log_entries() {
		global $wpdb;

		WP_CLI::line( 'Log entry migrator started.' );

		$entries = $wpdb->get_results(
			"
			SELECT   *
			FROM     $wpdb->posts
			WHERE    post_type = 'badgestack-log-entry'
				     OR post_type = 'checkin'
			ORDER BY ID ASC
			"
		);

		$count = 0;
		$found = count( $entries );

		WP_CLI::line( 'Log entry migrator found ' . $found . ' log entries.' );

		$import_progress = \WP_CLI\Utils\make_progress_bar( 'Importing ' . $found . ' log entries.', $found );
		foreach ( $entries as $entry ) {
			$wpdb->insert(
				$wpdb->prefix . 'dma_log_entries',
				$this->map_log_entry_data( $entry ),
				array(
					'%d', // user_id
					'%d', // object_id
					'%s', // action
					'%s', // title
					'%s', // artwork_id
					'%d', // awarded_points
					'%d', // total_points
					'%d', // admin_id
					'%s', // timestamp
				)
			);
			$import_progress->tick();
			$count++;
		}
		$import_progress->finish();
		WP_CLI::line( $count .' log entries imported.' );

	}

	/**
	 * Import activity stream entries into custom table
	 *
	 * @since 1.0.0
	 */
	public function migrate_stream_entries() {
		global $wpdb;

		WP_CLI::line( 'Activity Stream migrator started.' );

		$entries = $wpdb->get_results(
			"
			SELECT *
			FROM   {$wpdb->prefix}dma_log_entries
			"
		);
		$count = 0;
		$found = count( $entries );

		WP_CLI::line( 'Activiy Stream migrator found ' . $found . ' entries.' );

		$import_progress = \WP_CLI\Utils\make_progress_bar( 'Importing ' . $found . ' entries into Activity Stream.', $found );
		foreach ( $entries as $entry ) {
			// Only import the item if its relevant to the activity stream
			if ( in_array( $entry->action, array( 'activity', 'checked-in', 'claimed-reward', 'event', 'unlocked' ) ) ) {
				$wpdb->insert(
					$wpdb->prefix . 'dma_activity_stream',
					array(
						'user_id'    => $entry->user_id,
						'object_id'  => $entry->object_id,
						'action'     => $entry->action,
						'artwork_id' => $entry->artwork_id,
						'timestamp'  => $entry->timestamp
					),
					array(
						'%d', // user_id
						'%d', // object_id
						'%s', // action
						'%s', // artwork_id
						'%s', // timestamp
					)
				);
				$count++;
			}
			$import_progress->tick();
		}
		$import_progress->finish();
		WP_CLI::line( $count .' Activity Stream entries imported.' );
	}


	/**
	 * Map post data to match table setup
	 *
	 * @since  1.0.0
	 * @param  object $entry A post object
	 * @return array         All of the mapped data fields
	 */
	private function map_log_entry_data( $entry = null ) {
		global $wpdb;

		// Setup some default values
		$action = $admin_id = $points_awarded = $total_points = '';

		// Grab the post action
		if ( preg_match( '/^[^\s]+\s([\w\-]+)/', $entry->post_title, $action_match ) ) {
			if ( 'just' == $action_match[1] )
				$action = 'activity';
			elseif ( 'claimed' == $action_match[1] )
				$action = 'claimed-reward';
			else
				$action = $action_match[1];
		}

		// If we're dealing with points...
		if ( preg_match( '/(-?[0-9]+((,[0-9]+)+)?) points for a new total of (-?[0-9]+((,[0-9]+)+)?) points/', $entry->post_title, $points_match ) ) {

			// Set our action to "earned points"
			$action = 'points';

			// Get our totals
			$points_awarded = preg_replace('/[^\d.\-]/', '', $points_match[1] );
			$total_points = preg_replace('/[^\d.\-]/', '', $points_match[4] );

			// Check for admin
			if ( preg_match( '/^([^\s]+)\sawarded\b/', $entry->post_title, $admin_match ) ) {
				$admin_name = $admin_match[1];
				$admin      = get_user_by( 'login', $admin_name );
				$admin_id   = is_object( $admin ) ? $admin->ID : 1;
			}

		}

		// Attempt to get connected post ID
		$object_id = get_post_meta( $entry->ID, '_badgestack_log_achievement_id', true )
			? get_post_meta( $entry->ID, '_badgestack_log_achievement_id', true )
			: $wpdb->get_var( "SELECT p2p_from FROM {$wpdb->prefix}p2p WHERE p2p_type = 'activity-to-checkin' AND p2p_to = {$entry->ID}" );

		// If we're dealing with "liked a work of art"
		$artwork_id = ( 'activity' == $action && 2344 == $object_id )
			? get_post_meta( $entry->ID, '_dma_accession_id', true )
			: null;

		// Map our data (sanitization happens in $wpdb->insert)
		$mapped_data = array(
			'user_id'        => $entry->post_author,
			'object_id'      => $object_id,
			'action'         => $action,
			'title'          => $entry->post_title,
			'artwork_id'     => $artwork_id,
			'awarded_points' => $points_awarded,
			'total_points'   => $total_points,
			'admin_id'       => $admin_id,
			'timestamp'      => $entry->post_date,
		);

		// Send back our mapped data
		return $mapped_data;
	}

	/**
	 * Update legacy user meta for each user
	 *
	 * @since 1.0.0
	 */
	public function migrate_user_meta() {
		global $wpdb;

		WP_CLI::line( 'Beginnning user meta migration.' );

		// Get all steps
		$users = $wpdb->get_results(
			"
			SELECT     user.ID as ID,
			           meta.meta_value as achievements
			FROM       $wpdb->users as user
			INNER JOIN $wpdb->usermeta as meta
			           ON user.ID = meta.user_id
			           AND meta.meta_key = '_badgestack_achievements'
			GROUP BY   user.ID
			"
		);

		$count = 0;
		$found = count( $users );

		WP_CLI::line( 'Found ' . $found . ' users with achievement meta.' );

		$import_progress = \WP_CLI\Utils\make_progress_bar( 'Updating achievement meta for ' . $found . ' users.', $found );
		foreach ( $users as $user ) {
			update_user_meta( $user->ID, '_badgeos_achievements', array( 1 => maybe_unserialize( $user->achievements ) ) );
			$import_progress->tick();
			$count++;
		}
		$import_progress->finish();

		// Update user points
		WP_CLI::line( 'Migrating user points meta.' );
		$wpdb->update(
			$wpdb->usermeta,
			array( 'meta_key' => '_badgeos_points' ),
			array( 'meta_key' => '_dma_points' )
		);

		// Update user rewards
		WP_CLI::line( 'Migrating user rewards meta.' );
		$wpdb->update(
			$wpdb->usermeta,
			array( 'meta_key' => '_badgeos_rewards' ),
			array( 'meta_key' => '_badgestack_rewards' )
		);

		WP_CLI::line( 'All user meta migrated.' );

	}

	/**
	 * Update legacy post meta for each CPT
	 *
	 * @since 1.0.0
	 */
	public function migrate_post_meta() {
		global $wpdb;

		// Setup our post meta map
		$meta_map = array(
			'_badgeos_duration_activity'          => '_badgeos_count',
			'_badgestack_credly_is_giveable'      => '_badgeos_credly_is_giveable',
			'_dma_badge_trigger_type'             => '_badgeos_earned_by',
			'_dma_badge_icon_id'                  => '_thumbnail_id',
			'_dma_checkin_count'                  => '_badgeos_count',
			'_dma_active'                         => '_badgeos_active',
			'_dma_special'                        => '_badgeos_special',
			'_dma_hidden'                         => '_badgeos_hidden',
			'_dma_points'                         => '_badgeos_points',
			'_dma_time_restriction'               => '_badgeos_time_restriction',
			'_dma_time_restriction_date_begin'    => '_badgeos_time_restriction_date_begin',
			'_dma_time_restriction_date_end'      => '_badgeos_time_restriction_date_end',
			'_dma_time_restriction_days'          => '_badgeos_time_restriction_days',
			'_dma_time_restriction_hour_begin'    => '_badgeos_time_restriction_hour_begin',
			'_dma_time_restriction_hour_end'      => '_badgeos_time_restriction_hour_end',
			'_dma_time_restriction_limit_checkin' => '_badgeos_time_restriction_limit_checkin',
			'_dma_activity_lockout'               => '_badgeos_activity_lockout',
			'_dma_congratulations_text'           => '_badgeos_congratulations_text',
			'_dma_fun_badge_repeat_earning'       => '_badgeos_maximum_earnings',
			'_dma_maximum_time'                   => '_badgeos_maximum_time',
			'_dma_time_between_steps_max'         => '_badgeos_time_between_steps_max',
			'_dma_time_between_steps_min'         => '_badgeos_time_between_steps_min',
		);

		// Update every instance of the old key with the new key
		$found = count( $meta_map );
		$import_progress = \WP_CLI\Utils\make_progress_bar( 'Migrating post meta.', $found );
		foreach ( $meta_map as $old_key => $new_key ) {
			$wpdb->update(
				$wpdb->postmeta,
				array( 'meta_key' => $new_key ),
				array( 'meta_key' => $old_key )
			);
			$import_progress->tick();
		}
		$import_progress->finish();

		// Update any posts earned by "steps" to be earned by "triggers"
		WP_CLI::line( 'Updating "earned by" meta.' );
		$wpdb->update(
			$wpdb->postmeta,
			array( 'meta_value' => 'triggers' ),
			array(
				'meta_key'   => '_badgeos_earned_by',
				'meta_value' => 'steps'
			)
		);
		WP_CLI::line( 'All "earned by" meta updated.' );

	}

	/**
	 * Migrate legacy 'dma-step' posts to new 'step' post type
	 *
	 * @since 1.0.0
	 */
	public function migrate_step_cpt() {
		global $wpdb;

		WP_CLI::line( 'dma-step migration started.' );

		$wpdb->update(
			$wpdb->posts,
			array( 'post_type' => 'step' ),
			array( 'post_type' => 'dma-step' )
		);

		WP_CLI::line( 'dma-step migration complete.' );
	}

	/**
	 * Update step trigger type for each registered step
	 *
	 * @since 1.0.0
	 */
	public function migrate_step_triggers() {
		global $wpdb;

		WP_CLI::line( 'Beginnning step trigger migration.' );

		// Get all steps
		$steps = $wpdb->get_results(
			"
			SELECT ID
			FROM   $wpdb->posts
			WHERE  post_type = 'step'
			"
		);

		$count = 0;
		$found = count( $steps );
		$import_progress = \WP_CLI\Utils\make_progress_bar( 'Setting Step trigger type.', $found );
		foreach ( $steps as $step ) {
			update_post_meta( $step->ID, '_badgeos_trigger_type', 'activity' );
			$import_progress->tick();
			$count++;
		}
		$import_progress->finish();
		WP_CLI::line( $count .' Step triggers updated' );
	}

	/**
	 * Delete all log-entry and checkin posts, along with meta and term relationships
	 *
	 * @since 1.0.0
	 */
	public function data_cleanup() {
		global $wpdb;

		WP_CLI::line( 'Data cleanup started – ' . date( 'h:i:sa' ) );
		$start_time = time();

		$entries = $wpdb->get_results(
			"
			SELECT   ID
			FROM     $wpdb->posts
			WHERE    post_type = 'badgestack-log-entry'
				     OR post_type = 'checkin'
			ORDER BY ID ASC
			"
		);

		$count = 0;
		$found = count( $entries );

		WP_CLI::line( 'Found ' . $found . ' posts.' );

		$import_progress = \WP_CLI\Utils\make_progress_bar( 'Deleting records for ' . $found . ' posts.', $found );
		foreach ( $entries as $post ) {
			$wpdb->delete( $wpdb->posts, array( 'ID' => $post->ID ) );
			$wpdb->delete( $wpdb->postmeta, array( 'post_id' => $post->ID ) );
			$wpdb->delete( $wpdb->term_relationships, array( 'object_id' => $post->ID ) );
			$wpdb->delete( $wpdb->prefix . 'p2p', array( 'p2p_from' => $post->ID ) );
			$wpdb->delete( $wpdb->prefix . 'p2p', array( 'p2p_to' => $post->ID ) );
			$import_progress->tick();
			$count++;
		}
		$import_progress->finish();
		WP_CLI::line( $count .' posts deleted.' );

		$obi_count = $wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_dma_send_to_mozilla_obi' ) );
		WP_CLI::line( $obi_count .' achievement mozilla obi meta entries deleted.' );

		$badgestack_count = $wpdb->query( "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE '_badgestack%%'");
		WP_CLI::line( $badgestack_count . ' user badgestack trigger meta entries deleted.' );

		$achievement_count = $wpdb->delete( $wpdb->usermeta, array( 'meta_key' => '_badgestack_achievements' ) );
		WP_CLI::line( $achievement_count .' user achievement meta entries deleted.' );

		$active_count = $wpdb->delete( $wpdb->usermeta, array( 'meta_key' => '_dma_active_badges' ) );
		WP_CLI::line( $active_count .' user badge activity meta entries deleted.' );

		WP_CLI::line( 'Data cleanup complete! ' . date( 'h:i:sa' ) );
		WP_CLI::line( 'Time elapsed: ' . gmdate( 'H:i:s', ( time() - $start_time ) ) );
	}

	/**
	 * Delete all achievement data for a given user
	 *
	 * e.g. wp dma clear_achievements -user_id=4
	 * @since  1.0.0
	 */
	function clear_achievements( $args, $assoc_args ) {
		global $wpdb;

		$user_id = absint( $assoc_args['user_id'] );

		$stream_count = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}dma_activity_stream WHERE user_id = %d", $user_id ) );
		WP_CLI::line( $stream_count . ' stream entries deleted.' );

		$log_count = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}dma_log_entries WHERE user_id = %d", $user_id ) );
		WP_CLI::line( $log_count . ' log entries deleted.' );

		$badgestack_count = $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE '_badgeos%%' AND user_id = %d", $user_id ) );
		WP_CLI::line( $badgestack_count . ' meta entries deleted.' );

	}

	/**
	 * Import reward log entires to the activity stream
	 *
	 * @since  1.0.0
	 */
	function import_claimed_rewards() {
		global $wpdb;

		WP_CLI::line( 'Reward Migrator started.' );

		$entries = $wpdb->get_results(
			"
			SELECT *
			FROM   {$wpdb->prefix}dma_log_entries
			WHERE  action = 'claimed-reward'
			"
		);
		$count = 0;
		$found = count( $entries );

		WP_CLI::line( 'Reward Migrator found ' . $found . ' entries.' );

		// If we have reward entries to import...
		if ( $found ) {

			// Delete existing entries (to prevent duplicates)
			$wpdb->delete(
				$wpdb->prefix . 'dma_activity_stream',
				array( 'action' => 'claimed-reward' )
			);
			WP_CLI::line( 'Deleted old reward entries from acticity stream.' );

			// Import new entries
			$import_progress = \WP_CLI\Utils\make_progress_bar( 'Importing ' . $found . ' entries into Activity Stream.', $found );
			foreach ( $entries as $entry ) {

				$wpdb->insert(
					$wpdb->prefix . 'dma_activity_stream',
					array(
						'user_id'    => $entry->user_id,
						'object_id'  => $entry->object_id,
						'action'     => $entry->action,
						'artwork_id' => $entry->artwork_id,
						'timestamp'  => $entry->timestamp
					),
					array(
						'%d', // user_id
						'%d', // object_id
						'%s', // action
						'%s', // artwork_id
						'%s', // timestamp
					)
				);

				$count++;
				$import_progress->tick();
			}
			$import_progress->finish();
		}

		WP_CLI::line( $count .' Reward entries imported.' );
	}

}
WP_CLI::add_command( 'dma', 'DMA_Migration' );

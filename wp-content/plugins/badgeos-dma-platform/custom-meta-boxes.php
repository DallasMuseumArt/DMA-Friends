<?php

function dma_achievement_earned_by( $earned_by ) {

	$earned_by[] = array( 'name' => 'Registered New Account',         'value' => 'registered' );
	$earned_by[] = array( 'name' => 'Provided Email',                 'value' => 'provided_email' );
	$earned_by[] = array( 'name' => 'Connected Social Media Account', 'value' => 'connected_social' );
	$earned_by[] = array( 'name' => 'Allowed SMS',                    'value' => 'allowed_texting' );
	$earned_by[] = array( 'name' => 'Completed user profile',         'value' => 'completed_profile' );

	return $earned_by;
}
add_filter( 'badgeos_achievement_earned_by', 'dma_achievement_earned_by' );

function dma_achievement_data_meta_box_fields( $fields, $prefix, $post_types ) {
	$fields[] = array(
		'name' => 'Minimum Time Between Steps (minutes)',
		'desc' => 'The minumum number of minutes that must pass before earning a step.',
		'id'   => $prefix . 'time_between_steps_min',
		'type' => 'text_small',
	);
	$fields[] = array(
		'name' => 'Maximum Time Between Steps (minutes)',
		'desc' => 'The maximum number of minutes that may pass before failing the badge.',
		'id'   => $prefix . 'time_between_steps_max',
		'type' => 'text_small',
	);
	$fields[] = array(
		'name' => 'Maximum Time to Complete Badge (days)',
		'desc' => 'The maximum amount of time to complete all steps after the first has been awarded.',
		'id'   => $prefix . 'maximum_time',
		'type' => 'text_small',
	);
	$fields[] = array(
		'name'    => 'Beginning Date',
		'desc'    => 'The first day this badge may be earned (if blank: no limit).',
		'id'      => $prefix . 'time_restriction_date_begin',
		'type'    => 'text_date_timestamp',
	);
	$fields[] = array(
		'name'    => 'Ending Date',
		'desc'    => 'The last day this badge may be earned (if blank: no limit).',
		'id'      => $prefix . 'time_restriction_date_end',
		'type'    => 'text_date_timestamp',
	);
	$fields[] = array(
		'name'    => 'Special Badge?',
		'desc'    => '',
		'id'      => $prefix . 'special',
		'type'    => 'select',
		'options' => array(
			array( 'name' => 'No', 'value' => 'no', ),
			array( 'name' => 'Featured', 'value' => 'featured', ),
			array( 'name' => 'Limited Edition', 'value' => 'limited_edition', )
		),
	);

	return $fields;
}
add_filter( 'badgeos_achievement_data_meta_box_fields', 'dma_achievement_data_meta_box_fields', 10, 3 );

/**
 * Register custom meta boxes used throughout BadgeOS
 *
 * @since  2.0.0
 * @param  array  $meta_boxes The existing metabox array we're filtering
 * @return array              An updated array containing our new metaboxes
 */
function dma_custom_metaboxes( array $meta_boxes ) {

	// Start with an underscore to hide fields from custom fields list
	$prefix = '_badgeos_';

	// Grab our achievement types as an array
	$achievement_types = badgeos_get_achievement_types_slugs();

	// Setup our $post_id, if available
	$post_id = isset( $_GET['post'] ) ? $_GET['post'] : 0;

	// Activity Metadata
	$meta_boxes[] = array(
		'id'         => 'activity_data',
		'title'      => 'Activity Data',
		'pages'      => array( 'activity', ), // Post type
		'context'    => 'normal',
		'priority'   => 'high',
		'show_names' => true, // Show field names on the left
		'fields'     => array(
			array(
				'name' => 'Activity Code',
				'desc' => 'The unique three-character code for this activity.',
				'id'   => '_dma_accession_id',
				'type' => 'text_small'
			),
			array(
				'name' => 'Points',
				'desc' => 'Number of points earned for this activity.',
				'id'   => $prefix . 'points',
				'type' => 'text',
			),
			array(
				'name' => 'Check-in Lockout (minutes)',
				'desc' => 'Allow only check-in with same activity code once per X minutes.',
				'id'   => $prefix . 'activity_lockout',
				'type' => 'text',
			),
			array(
				'name'    => 'Time Restriction',
				'desc'    => '',
				'id'      => $prefix . 'time_restriction',
				'type'    => 'select',
				'options' => array(
					array( 'name' => 'None', 'value' => '' ),
					array( 'name' => 'Specific Days/Hours', 'value' => 'hours' ),
					array( 'name' => 'Specific Date Range', 'value' => 'dates' )
				)
			),
			array(
				'name'    => 'Days',
				'desc'    => 'Select which days of the week this activity may be completed.',
				'id'      => $prefix . 'time_restriction_days',
				'type'    => 'multicheck',
				'options' => array(
						'Sunday'    => 'Sunday',
						'Monday'    => 'Monday',
						'Tuesday'   => 'Tuesday',
						'Wednesday' => 'Wednesday',
						'Thursday'  => 'Thursday',
						'Friday'    => 'Friday',
						'Saturday'  => 'Saturday',
					)
			),
			array(
				'name'    => 'Start Hour',
				'desc'    => 'e.g. 9am, 9:00am, 9:00',
				'id'      => $prefix . 'time_restriction_hour_begin',
				'type'    => 'text',
			),
			array(
				'name'    => 'End Hour',
				'desc'    => 'e.g. 5pm, 5:00pm, 17:00',
				'id'      => $prefix . 'time_restriction_hour_end',
				'type'    => 'text',
			),
			array(
				'name'    => 'Beginning Date',
				'desc'    => 'The first day this activity may be completed.',
				'id'      => $prefix . 'time_restriction_date_begin',
				'type'    => 'text_date_timestamp',
			),
			array(
				'name'    => 'Ending Date',
				'desc'    => 'The last day this activity may be completed.',
				'id'      => $prefix . 'time_restriction_date_end',
				'type'    => 'text_date_timestamp',
			)
		)
	);

	// Event Metadata
	$meta_boxes[] = array(
		'id'         => 'event_data',
		'title'      => 'Event Data',
		'pages'      => array( 'dma-event', ), // Post type
		'context'    => 'normal',
		'priority'   => 'high',
		'show_names' => true, // Show field names on the left
		'fields'     => array(
			array(
				'name' => 'Event Code',
				'desc' => 'Unique three-character code for this event.',
				'id'   => $prefix . 'accession_id',
				'type' => 'text_small',
			),
			array(
				'name'    => 'Time Restriction',
				'desc'    => '',
				'id'      => $prefix . 'time_restriction',
				'type'    => 'select',
				'options' => array(
					array( 'name' => 'Ongoing (always available)', 'value' => '' ),
					array( 'name' => 'Specific Date Range', 'value' => 'dates' )
				)
			),
			array(
				'name'    => 'Beginning Date',
				'desc'    => 'The first day this badge may be earned (if blank: no limit).',
				'id'      => $prefix . 'time_restriction_date_begin',
				'type'    => 'text_date_timestamp',
			),
			array(
				'name'    => 'Ending Date',
				'desc'    => 'The last day this badge may be earned (if blank: no limit).',
				'id'      => $prefix . 'time_restriction_date_end',
				'type'    => 'text_date_timestamp',
			),
			array(
				'name'    => 'Limit Checkin to Date Range?',
				'desc'    => '',
				'id'      => $prefix . 'time_restriction_limit_checkin',
				'type'    => 'multicheck',
				'options' => array( 'yes' => 'Yes, only allow check-in during event date(s).' )
			),
			array(
				'name'    => 'Prompt from associated location?',
				'desc'    => '',
				'id'      => $prefix . 'prompt_on_login',
				'type'    => 'multicheck',
				'options' => array( 'yes' => 'Yes, prompt user about event on login at associated location.' )
			)
		),
	);

	// Start with an underscore to hide fields from custom fields list
	$prefix = '_dma_';

	// Rewards Metadata
	$meta_boxes[] = array(
		'id'         => 'reward_metadata_id',
		'title'      => 'Reward Metadata',
		'pages'      => array( 'badgeos-rewards' ), // Post type
		'context'    => 'normal',
		'priority'   => 'high',
		'show_names' => true, // Show field names on the left
		'fields' => array(
			array(
				'name' => 'Points Required',
				'desc' => 'How many points are required to redeem this reward? (e.g. 40000)',
				'id'   => $prefix . 'reward_points',
				'type' => 'text',
			),
			array(
				'name' => 'Bar Code Value',
				'desc' => 'The value of the barcode printed on the coupon.',
				'id'   => $prefix . 'reward_barcode',
				'type' => 'text',
			),
			array(
				'name' => 'Start Date',
				'desc' => 'If set, Reward can only be redeemed after this date.',
				'id'   => $prefix . 'reward_start_date',
				'type' => 'text_date',
			),
			array(
				'name' => 'End Date',
				'desc' => 'If set, Reward can only be redeemed before this date.',
				'id'   => $prefix . 'reward_end_date',
				'type' => 'text_date',
			),
			array(
				'name' => 'Number of Days Valid',
				'desc' => 'If set, the maximum number of days this reward is valid.',
				'id'   => $prefix . 'reward_days_valid',
				'type' => 'text_small',
			),
			array(
				'name' => 'Inventory',
				'desc' => 'Number of reward items in inventory',
				'id'   => $prefix . 'reward_inventory',
				'type' => 'text',
			),
			array(
				'name' => 'Hidden?',
				'desc' => 'Yes, hide this Reward from users until they meet the points requirement.',
				'id'   => $prefix . 'reward_hidden',
				'type' => 'checkbox',
			),
			array(
				'name' => 'The Fine Print',
				'desc' => 'This is displayed on the Reward detail view and on the printed coupon.',
				'id'   => $prefix . 'reward_fine_print',
				'type' => 'textarea',
			),
			array(
				'name' => 'Email upon redemption?',
				'desc' => 'Whether to send an email to the user if they redeem this reward',
				'id'   => $prefix . 'reward_enable_email',
				'type' => 'select',
				'options' => array(
					array( 'name' => 'Yes', 'value' => 'yes', ),
					array( 'name' => 'No', 'value' => 'no', )
				),
			),
			array(
				'name' => 'Redemption Email',
				'desc' => 'If not blank, send an email to the recipient upon redemption with the specified text.',
				'id'   => $prefix . 'reward_redemption_email',
				'type' => 'textarea',
			)

		)
	);

	// Location Metadata
	$meta_boxes[] = array(
		'id'         => 'location_data',
		'title'      => 'Location Data',
		'pages'      => array( 'dma-location', ), // Post type
		'context'    => 'normal',
		'priority'   => 'high',
		'show_names' => true, // Show field names on the left
		'fields'     => array(
			array(
				'name' => 'Location Code',
				'desc' => 'Unique three-character code for this location.',
				'id'   => $prefix . 'location_id',
				'type' => 'text_small',
			),
			array(
				'name' => 'Redirect to URL',
				'desc' => 'The full URL a user should see after login at this location (e.g. http://friends.dma.org/some-page).',
				'id'   => $prefix . 'location_redirect',
				'type' => 'text',
			),
			array(
				'name' => 'Membership Card Printer Name',
				'desc' => 'The name of the printer at this location for Membership Cards.',
				'id'   => $prefix . 'location_printer_ip',
				'type' => 'text',
			),
			array(
				'name' => 'Reward Printer Name',
				'desc' => 'The name of the printer at this location for Rewards.',
				'id'   => $prefix . 'location_printer_reward',
				'type' => 'text',
			)
		),
	);

	return $meta_boxes;
}
add_filter( 'cmb_meta_boxes', 'dma_custom_metaboxes' );

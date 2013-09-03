<?php

/**
 * Register custom meta boxes used throughout BadgeOS
 *
 * @since  2.0.0
 * @param  array  $meta_boxes The existing metabox array we're filtering
 * @return array              An updated array containing our new metaboxes
 */
function dma_rewards_custom_metaboxes( array $meta_boxes ) {

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

	return $meta_boxes;
}
add_filter( 'cmb_meta_boxes', 'dma_rewards_custom_metaboxes' );

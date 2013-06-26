// After our page is ready, fire up the change handler for the requirement selector
jQuery(document).ready(function(){
	bind_requirement_type_to_change();
});

// Add a step
function badgeos_add_new_step( badge_id ) {
	jQuery.post(
		ajaxurl,
		{
			action: 'add_step',
			badge_id: badge_id
		 },
		function( response ) {
			jQuery( response ).appendTo( '#steps_list' );

			// Dynamically add the menu order for the new step to be one higher
			// than the last in line
			new_step_menu_order = Number( jQuery( '#steps_list li.step-row' ).eq( -2 ).children( 'input[name="order"]' ).val() ) + 1;
			jQuery( '#steps_list li.step-row:last' ).children( 'input[name="order"]' ).val( new_step_menu_order );

			// bind the jQuery on change handler for the new <select> element
			bind_requirement_type_to_change();
		}
	);
}

// Delete a step
function badgeos_delete_step( step_id ) {
	jQuery.post(
		ajaxurl,
		{
			action: 'delete_step',
			step_id: step_id
		},
		function( response ) {
			jQuery( '.step-' + step_id ).remove();
		}
	);
}

// Update all steps
function badgeos_update_steps(e) {
	jQuery( '.save-steps-spinner' ).css( 'display', 'inline' );
	json = {
		action: 'update_steps',
		steps: []
	};
	jQuery( '.step-row' ).each( function() {
		json.steps.push({
			"title"             : jQuery(this).children( 'input[name="step-title"]' ).val(),
			"order"             : jQuery(this).children( 'input[name="order"]' ).val(),
			"step_id"           : jQuery(this).attr( 'data-step-id' ),
			"requirement_type"  : jQuery(this).children( '.select-requirement-type' ).val(),
			"required_duration" : jQuery(this).children( '.required-duration' ).val(),
			"requirement_value" : jQuery(this).children( '.select-' + jQuery(this).children( '.select-requirement-type' ).val() ).val(),
			"measurement"       : jQuery(this).children( '.select-measurement' ).val()
			// "first_time_only"   : jQuery(this).children( '.first-time-only' ).val()
		});
	});

	jQuery.post(
		ajaxurl,
		json,
		function( response ) {
			jQuery( '.save-steps-spinner' ).css( 'display', 'none' );
		}
	);
}

// Update the step requierement when the selection is updated
function bind_requirement_type_to_change() {
	jQuery( '.select-requirement-type' ).change( function() {
		step_id = jQuery( this ).attr('data-step-id');
		jQuery( this ).siblings('.select-activity, .select-activity-type, .select-fitness-type').hide();
		jQuery( '.select-' + jQuery( this ).val() + '-' + step_id ).show();
	});
}

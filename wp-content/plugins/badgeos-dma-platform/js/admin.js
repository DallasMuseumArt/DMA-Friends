;(function($) {
$(document).ready(function(){

	// Make our steps list sortable
	$("#steps_list").sortable({

		// When the list order is updated
		update : function () {

			// Loop through each element
			$('#steps_list li').each(function( index, value ) {

				// Write it's current position to our hidden input value
				$(this).children('input[name="order"]').val( index );

			});

		}
	});

	// Helper function for hiding all our extra CMB fields
	function hide_cmb_date_restriction_extras() {
		$('label[for="_badgeos_time_restriction_days"]').parent().parent('tr').hide();
		$('label[for="_badgeos_time_restriction_hour_begin"]').parent().parent('tr').hide();
		$('label[for="_badgeos_time_restriction_hour_end"]').parent().parent('tr').hide();
		$('label[for="_badgeos_time_restriction_date_begin"]').parent().parent('tr').hide();
		$('label[for="_badgeos_time_restriction_date_end"]').parent().parent('tr').hide();
		$('label[for="_badgeos_time_restriction_limit_checkin"]').parent().parent('tr').hide();
	}

	// Show/Hide relevant date restrictions for Activities and Events
	$("#_badgeos_time_restriction").change( function() {
		if ( '' === $(this).val() ) {
			hide_cmb_date_restriction_extras();
		} else if ( 'hours' === $(this).val() ) {
			hide_cmb_date_restriction_extras();
			$('label[for="_badgeos_time_restriction_days"]').parent().parent('tr').show();
			$('label[for="_badgeos_time_restriction_hour_begin"]').parent().parent('tr').show();
			$('label[for="_badgeos_time_restriction_hour_end"]').parent().parent('tr').show();
		} else if ( 'dates' === $(this).val() ) {
			hide_cmb_date_restriction_extras();
			$('label[for="_badgeos_time_restriction_date_begin"]').parent().parent('tr').show();
			$('label[for="_badgeos_time_restriction_date_end"]').parent().parent('tr').show();
			$('label[for="_badgeos_time_restriction_limit_checkin"]').parent().parent('tr').show();
		}
	}).change();

	// Force numerical input on reward points input
	$("#_dma_reward_points").keyup( function() {
		$(this).val( $(this).val().replace( /[^\d]+/, '' ) );
	});

	// AJAX function for handling activity submission
	$('.activity-submit').click( function( event ) {

		// Stop the default submission from happening
		event.preventDefault();

		// show our loading spinner
		$('.spinner').show();
		$('.dma-code-notices').html('');

		// Grab our input and submit the AJAX request
		var accession_id = $('input[name=accession_id]').val();
		var user_id = $('input[name=user_id]').val();
		$.ajax({
			type : "post",
			dataType : "json",
			url : myAjax.ajaxurl,
			data : { "action": "dma_code_ajax_handler", "accession_id": accession_id, "user_id": user_id, "is_admin": true },
			success : function(response) {

				// hide our loading spinner
				$('.spinner').hide();

				// Return our notices
				$('.dma-code-notices').html( response );

				// Clear out our input
				input.val('');
			}
		});

	});

});
})(jQuery);

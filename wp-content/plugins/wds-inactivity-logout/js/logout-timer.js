;(function($) {
$(document).ready(function(){


	// Setup our variables
	var logout_data                = window.logout_data,                                         // var name for grabbing all our other data
		alert_container            = logout_data.logout_alert_container,                         // id of the warning container
		countdown_container        = logout_data.countdown_container,                            // element that will hold the actual countdown value
		logged_in_idle_timer       = ( parseFloat( logout_data.logged_in_idle_timer ) * 1000 ),  // how long to wait before displaying the logout countdown
		logged_out_idle_timer      = ( parseFloat( logout_data.logged_out_idle_timer ) * 1000 ), // how long to wait before displaying the logout countdown
		logged_in_countdown        = ( parseFloat( logout_data.logged_in_countdown ) ),          // number of seconds to wait before redirecting the user
		logged_out_countdown       = ( parseFloat( logout_data.logged_out_countdown ) ),         // number of seconds to wait before redirecting the user
		extra_seconds              = ( parseFloat( logout_data.extra_seconds ) ),                // number of seconds to add to countdown for wrong answers
		redirectTo                 = logout_data.logout_url.replace(/\&amp;/g,'&'),              // URL to relocate the user to once they have timed out
		keepAliveURL               = logout_data.keepalive_url,                                  // URL to call to keep the session alive, if the link in the warning bar is clicked
		expiredMessage             = logout_data.expired_message,                                // message to show user when the countdown reaches 0
		logged_in                  = logout_data.is_user_logged_in,                              // True if user is logged in
		inactivity_logout_enabled  = logout_data.inactivity_logout_enabled,                     // True if we want to prevent a logout from happening
		running                    = false,                                                      // var to check if the countdown is running
		timer,                                                                                   // reference to the setInterval timer so it can be stopped
		counter;                                                                                 // reference to the timer counter so it can be adjusted

	// start the idle timer.
	if ( logged_in )
		$.idleTimer(logged_in_idle_timer);
	else
		$.idleTimer(logged_out_idle_timer);

	// bind to idleTimer's idle.idleTimer event
	$(document).bind("idle.idleTimer", function(){

		// If we want to disable the inactivity logout, we can skip the rest
		if ( 1 != inactivity_logout_enabled )
			return false;

		// if the user is idle and a countdown isn't already running
		if( $.data(document,'idleTimer') === 'idle' && !running ){
			counter = logged_in ? logged_in_countdown : logged_out_countdown;
			running = true;

			// Set the alert title and counter
			$(alert_container + ' h1').html( 'Are you still there?' );
			$(alert_container + ' strong').html(counter);

			// Hide all other popups
			$('.popup').not(alert_container).hide();

			// show our warning
			$.popOpen($(alert_container), 700, 1200);

			// create a timer that runs every second
			timer = setInterval(function(){
				counter -= 1;

				// if the counter is 0, redirect the user
				if(counter === 0){
					$(alert_container).html( expiredMessage );
					window.location.href = redirectTo;
				} else {
					$(alert_container + ' strong').html( counter );
				}
			}, 1000);
		}
	});

	// if the continue link is clicked...
	$(".stay-logged-in", alert_container).click(function(event){

		// Stop the form from submitting
		event.preventDefault();

		// Grab our inputs
		var user_id = $('input[name=user_id]').val();
		var pin = $('input[name=inactivity_pin]').val();

		// Submit the AJAX request
		$.ajax({
			type : "post",
			dataType : "json",
			url : myAjax.ajaxurl,
			data : { "action": "dma_is_user_pin_valid", "user_id": user_id, "pin": pin },
			success : function(response) {

				// If our pin was valid...
				if ( true === response ) {

					// stop the timer
					clearInterval(timer);

					// stop countdown
					running = false;

					// hide the warning
					$.hidePop($(alert_container));

					// ajax call to keep the server-side session alive
					// $.get( keepAliveURL );

					// Submit our form
					$(alert_container+' form').get(0).submit();

				// Otherwise, the PIN is invalid...
				} else {
					$('#inactivity_pin').addClass('invalid');
					$('p.validation-error').html('This PIN is invalid');
					counter += extra_seconds;
				}
			}
		});

	});

});
})(jQuery);

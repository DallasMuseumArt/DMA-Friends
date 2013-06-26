;(function($) {
$(document).ready(function(){

	// Setup our fancy user registration form
	var $step = 1;
	// $('.registration-step').hide();
	$('#registration-step').html($('.registration-step.step-'+$step));

	// Helper function for changing our active step
	function change_steps( $step_to_show, $step_to_hide ) {
		console.log($step_to_show);
		// do nothing if the steps are the same
		if ( $step_to_hide === $step_to_show )
			return;
		// $('.registration-step.step-' + $step_to_show).show();
		// $('.registration-step.step-' + $step_to_hide).hide();
		$('#registration-step').after($('.registration-step.step-' + $step_to_hide));
		$('#registration-step').html($('.registration-step.step-' + $step_to_show));

		maybe_hide_previous_button();
		show_continue_or_register();
		maybe_disable_continue();
		maybe_resize_window($step_to_show, $step_to_hide);
	}

	// @DEV helper function to fire the do_show_step function (after a small time delay to be sure popup is initiated)
	function show_step(step, popheight) {
		if (typeof(step)==='undefined')
			return;
	   setTimeout(function(){
			do_show_step(step, popheight);
	   }, 1000);
	}
	// @DEV helper function that displays a certain step
	function do_show_step(step, popheight) {
		$('.registration-step').hide();
		$('.registration-step.step-' + step).show();
		if (typeof(popheight)!=='undefined')
			$.setPopupDimensions( $('#do-registration'), 1609 );
	}
	// @DEV
	// show_step(6,1609);

	// When someone clicks the "Continue" button
	$('.registration-next').click( function() {
		continue_on();
	});
	// When someone clicks the "Previous" button
	$('.registration-previous').click( function() {
		go_on_back();
	});

	// Some form keyboard navigation
	$(document).keyup(function(e) {
		var k = e.which;
		// If the user pressed enter, and the next button is not disabled
		if (k == 13 && ! $('.registration-next').is(':disabled') )
			continue_on();
	});

	// Moves forward a step
	function continue_on() {
		if ( 7 > $step ) {
			$step++;
			change_steps( $step, ($step - 1) );
		}
	}

	// Moves back a step
	function go_on_back() {
		if ( 1 < $step ) {
			$step--;
			change_steps( $step, ($step + 1) );
		}
	}

	// When someone clicks the "Cancel" button
	$(document).bind('wds_closepopup', function() {
		var $step_to_hide = $step;
		$step = 1;
		change_steps( $step, $step_to_hide );
		// remove any errors from form
		document.getElementById('registration').reset();
	});

	// If the forms being cleared, clear our errors
	$(document).bind('reset', function() {
		reset_all_errors();
	});

	// If we're on the avatar step, make popup taller
	function maybe_resize_window($step_to_show, $step_to_hide) {
		var $this_form = $('#do-registration');
		// resize for avatar page
		if ( $step === 6 || $step_to_hide === 7 && $step_to_show === 6 )
			$.setPopupDimensions( $this_form, 1340 );
		// resize for TOS page
		if ( $step === 7 )
			$.setPopupDimensions( $this_form, 1609 );
		// if moving away from avatar page, resize to set dimensions
		if ( $step_to_hide === 6 && $step_to_show === 5  )
			$.setPopupDimensions( $this_form );
	}

	// Decide whether we should show or hide the "Previous" button for registration
	function maybe_hide_previous_button() {
		if ( 1 == $step )
			$('.registration-previous').hide();
		else
			$('.registration-previous').show();
	}
	maybe_hide_previous_button();

	// Decide whether we should show the "Continue" button or "Register"
	function show_continue_or_register() {
		if ( 7 == $step ) {
			$('.registration-next').hide();
			$('.registration-submit').show();
		} else {
			$('.registration-next').show();
			$('.registration-submit').hide();
		}
	}
	show_continue_or_register();

	// Helper function to test of the provided email is valid
	function is_email_valid() {

		// Cache our selectors as variables for use below
		var $email = $('#email');
		var $email_value = $email.val();

		// The bare minimum an email length could ever be is a@b.c (5 characters)
		if ( 5 <= $email_value.length ) {

			// See if we have both an "@" and at least one "." present
			if ( $email_value.indexOf("@") >= 0 && $email_value.indexOf(".") >= 0 ) {

				// See if the email already exists in our WP user data...
				$.ajax({
					type : "post",
					dataType : "json",
					url : myAjax.ajaxurl,
					data : { action: "dma_user_email_exists", email: $email_value },
					success : function(response) {

						// If the email does exists, this input is invalid...
						if ( true === response ) {
							$($email).removeClass('valid').addClass( 'invalid' );
							$('p.email-error').html('This email is already registered.').css({'visibility': 'visible'});
							return false;

						// Otherwise, this email is good to go...
						} else {
							reset_email_error($email);
							return true;
						}
					}
				});

			// If we hit this point, we don't have a valid email (no "@" nor ".")
			} else {
				$($email).removeClass('valid').addClass( 'invalid' );
				$('p.email-error').html( 'Please enter a valid email.' ).css({'visibility': 'visible'});
				return false;
			}

		} else {
			// If we hit this point, we don't have enough characters in our input
			$($email).removeClass('valid');
			return false;
		}

	}

	// reset errors for a specific element
	function reset_specific_error( $ele, $error ) {
		$($ele).addClass('valid').removeClass('invalid');
		$($error).css({'visibility': 'hidden'});
	}

	// hides and resets our email error
	function reset_email_error( $email ) {
		reset_specific_error( $email, 'p.email-error');
	}

	// Resets all form error messages (only email at this time)
	function reset_all_errors() {
		reset_email_error( $('#email') );
	}

	// See if our PINs match
	function is_pin_valid() {

		// Cache our selectors as variables for use below
		$pin1 = $('#pin1');
		$pin2 = $('#pin2');
		$pin1_value = $($pin1).val();
		$pin2_value = $($pin2).val();

		// Make sure both PINs have at least 4 characters
		if ( 4 <= $('#pin1').val().length && 4 <= $('#pin2').val().length ) {

			// If they don't match, or contain non-numeric characters, they're both invalid
			if ( $pin1_value != $pin2_value || $pin1_value.match(/[^\d]+/i) ) {
				$pin1.removeClass('valid').addClass('invalid');
				$pin2.removeClass('valid').addClass('invalid');
				return false;

			// Otherwise, they match and we're good to go
			} else {
				$pin1.removeClass('invalid').addClass('valid');
				$pin2.removeClass('invalid').addClass('valid');
				return true;
			}
		} else {
			// If we made it here, our PINs aren't long enough
			$pin1.removeClass('valid').removeClass('invalid');
			$pin2.removeClass('valid').removeClass('invalid');
			return false;
		}

	}

	// See if our Phone is valid and unique
	function is_phone_valid() {

		// Cache our selectors as variables for use below
		var $phone = $('#phone');
		var $phone_value = $phone.val();

		// If we have anything in the textbox
		if ( $phone_value.length > 0 ) {

			// If we don't have exactly 10 characters...
			if ( 10 !== $phone_value.length ) {
				$($phone).removeClass('valid').addClass( 'invalid' );
				$('p.phone-error').html( 'Please enter either a valid, 10-digit number (e.g. 2225551234).' ).css({'visibility': 'visible'});
				return false;

			// Otherwise we have 10 digits, a presumably valid phone number
			} else {

				// See if the phone already exists in our WP user data...
				$.ajax({
					type : "post",
					dataType : "json",
					url : myAjax.ajaxurl,
					data : { action: "dma_user_phone_exists", phone: $phone_value },
					success : function(response) {

						// If the phone does exists, this input is invalid...
						if ( true === response ) {
							$($phone).removeClass('valid').addClass( 'invalid' );
							$('p.phone-error').html('This number is already registered.').css({'visibility': 'visible'});
							$('.registration-next').attr( 'disabled', 'disabled' ); // Note: this is here because validation being a dick.
							return false;
						}
					}
				});

				// If we hit this block, we have a vaid 10-digit number that isn't being used by anyone
				// This is outside an else statement because things were acting very squirrelly. Hate. HAAAAAAATE! -BR
				$($phone).removeClass('invalid').addClass( 'valid' );
				$('p.phone-error').html('').css({'visibility': 'hidden'});
				return true;

			}

		// If we hit this point, our input is empty and therefore valid (because it's optional)
		} else {
			$($phone).removeClass('invalid').addClass('valid');
			$('p.phone-error').html('').css({'visibility': 'hidden'});
			return true;
		}

	}

	// Decide whether or not "Continue" should be disabled
	function maybe_disable_continue() {

		// Setup our conditions for each step
		if (
			( 1 == $step &&                                 // If we're on Step 1...
				( 0 === $('#first_name').val().length ||    // And we don't have a first name...
				  0 === $('#last_name').val().length ) ) || // Or we don't have a last name...
			( 2 == $step && false === is_email_valid() ) || // Or we're on Step 2 and we don't have a valid email...
			( 3 == $step && false === is_pin_valid() ) ||   // Or we're on Step 3 and our PINs are invalid...
			( 4 == $step && false === is_phone_valid() )    // Or we're on Step 4 and our Phone is invalid...

		) {
			// You shall not pass!
			$('.registration-next').attr( 'disabled', 'disabled' );
		} else {
			// Otherwise, we can let the user continue...
			$('.registration-next').removeAttr( 'disabled' );
		}
	}
	maybe_disable_continue();

	// Listen for changes to our inputs to determine whether or not we can continue to the next step
	$('#first_name').keyup( function() { maybe_disable_continue(); });
	$('#last_name').keyup( function() { maybe_disable_continue(); });
	$('#email').keyup( function() { is_email_valid(); maybe_disable_continue(); });
	$('#pin1').keyup( function() { is_pin_valid(); maybe_disable_continue(); });
	$('#pin2').keyup( function() { is_pin_valid(); maybe_disable_continue(); });
	$('#phone').keyup( function() { this.value = this.value.replace( /[^\d]/g, '' ).substring( 0, 10 ); is_phone_valid(); maybe_disable_continue(); });
	$('#zip').keyup( function() { this.value = this.value.replace( /[^\d]/g, '' ).substring( 0, 5 ); });

});
})(jQuery);

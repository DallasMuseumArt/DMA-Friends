;(function($) {
$(document).ready(function(){

	// AJAX function for handling activity submission
	$('form.activity-submit').submit( function( event ) {
		// show our loading spinner
		$('.spinner').show();
		// Stop the default submission from happening
		event.preventDefault();

		// Grab our input and submit the AJAX request
		var input = $('input[name=accession_id]');
		$.ajax({
			type : "post",
			dataType : "json",
			url : myAjax.ajaxurl,
			data : { "action": "dma_code_ajax_handler", "accession_id": input.val() },
			success : function(response) {

				// hide our loading spinner
				$('.spinner').hide();

				// Return our notices
				$('.dma-code-notices').html( response.message );

				// Update our user's points total
				$('.user-points .points').html( response.points );

				// Hide the DMA tips
				$('.dma-tips').hide();

				// Clear out our input
				input.val('');
			}
		});

	});

	// AJAX function for handling badge bookmarks
	$('form.bookmark-this .submit').click( function( event ) {

		// Stop the default submission from happening
		event.preventDefault();

		// Grab our form values
		var button    = $(this);
		var form      = button.parent('form');
		var user_id   = form.children('input[name=user_id]').val();
		var object_id = form.children('input[name=object_id]').val();
		var action    = form.children('input[name=action]').val();

		$.ajax({
			type : "post",
			dataType : "json",
			url : myAjax.ajaxurl,
			data : { "action": "dma_update_user_bookmarks", "user_id": user_id, "object_id": object_id, "add_or_delete": action },
			success : function(response) {

				// If we just added a bookmark...
				if ( 'add' === action ) {
					$('.pop.object-'+object_id).addClass('bookmarked');   // update details_output css class
					$('.popup.object-'+object_id).addClass('bookmarked'); // update details_modal css class
					form.children('input[name=action]').val('delete');    // update form fields
					button.html('Bookmarked');                            // update submit button text

				// Otherwise, we just removed a bookmark
				} else {
					$('.pop.object-'+object_id).removeClass('bookmarked');   // update details_output css class
					$('.popup.object-'+object_id).removeClass('bookmarked'); // update details_modal css class
					form.children('input[name=action]').val('add');          // update form fields
					button.html('Bookmark This');                            // update submit button text
				}
			}
		});

	});

	// Helper function to test of the provided email is valid
	function is_email_valid() {

		// Cache our selectors as variables for use below
		var $email = $('#user_email');
		var $email_value = $email.val();
		var $user_id = $('#user_id').val();

		// The bare minimum an email length could ever be is a@b.c (5 characters)
		if ( 5 <= $email_value.length ) {

			// See if we have both an "@" and at least one "." present
			if ( $email_value.indexOf("@") >= 0 && $email_value.indexOf(".") >= 0 ) {

				// See if the email already exists in our WP user data...
				$.ajax({
					type : "post",
					dataType : "json",
					url : myAjax.ajaxurl,
					data : { action: "dma_user_email_exists", email: $email_value, user_id: $user_id },
					success : function(response) {

						// If the email does exists, this input is invalid...
						if ( true === response ) {
							$($email).removeClass('valid').addClass( 'invalid' );
							$('p.email-error').html('<span>This email is already registered.</span>');
							return false;

						// Otherwise, this email is good to go...
						} else {
							$('p.email-error').html('');
							return true;
						}
					}
				});

			// If we hit this point, we don't have a valid email (no "@" nor ".")
			} else {
				$($email).removeClass('valid').addClass( 'invalid' );
				$('p.email-error').html( '<span>Please enter a valid email.</span>' );
				return false;
			}

		} else {
			// If we hit this point, we don't have enough characters in our input
			$($email).removeClass('valid');
			$('p.email-error').html('');
			return false;
		}


	}

	// See if our Phone is valid and unique
	function is_phone_valid() {

		// Cache our selectors as variables for use below
		var $phone = $('#phone');
		var $phone_value = $phone.val();
		var $user_id = $('#user_id').val();

		// If we have anything in the textbox
		if ( $phone_value.length > 0 ) {

			// If we don't have exactly 10 characters...
			if ( 10 !== $phone_value.length ) {
				$($phone).removeClass('valid').addClass( 'invalid' );
				$('p.phone-error').html( '<span>Please enter either a valid, 10-digit number (e.g. 2225551234).</span>' ).css({'visibility': 'visible'});
				return false;

			// Otherwise we have 10 digits, a presumably valid phone number
			} else {

				// See if the phone already exists in our WP user data...
				$.ajax({
					type : "post",
					dataType : "json",
					url : myAjax.ajaxurl,
					data : { action: "dma_user_phone_exists", phone: $phone_value, user_id: $user_id },
					success : function(response) {

						// If the phone does exists, this input is invalid...
						if ( true === response ) {
							$($phone).removeClass('valid').addClass( 'invalid' );
							$('p.phone-error').html('<span>This number is already registered.</span>').css({'visibility': 'visible'});
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
				$('p.pin-error').html('<span>PIN codes do not match. Please try again.</span>').addClass('icon-attention');
				return false;

			// Otherwise, they match and we're good to go
			} else {
				$pin1.removeClass('invalid').addClass('valid');
				$pin2.removeClass('invalid').addClass('valid');
				$('p.pin-error').html('').removeClass('icon-attention');
				return true;
			}
		} else {
			// If we made it here, our PINs aren't long enough
			$pin1.removeClass('valid').removeClass('invalid');
			$pin2.removeClass('valid').removeClass('invalid');
			$('p.pin-error').html('').removeClass('icon-attention');
			return false;
		}

	}
	$('#pin1').keyup( function() { is_pin_valid(); maybe_disable_submit(); });
	$('#pin2').keyup( function() { is_pin_valid(); maybe_disable_submit(); });
	$('#user_email').keyup( function() { is_email_valid(); });
	$('#phone').keyup( function() { is_phone_valid(); });

	// Decide whether or not "Submit" should be disabled
	function maybe_disable_submit() {

		// Setup our conditions for each step
		if ( false === is_pin_valid() ) {
			// You shall not pass!
			$('form.submit-pin .submit').attr( 'disabled', 'disabled' );
		} else {
			// Otherwise, we can let the user continue...
			$('form.submit-pin .submit').removeAttr( 'disabled' );
		}
	}

	// AJAX function for handling user profile edits
	$('form.edit-user-profile .submit').click( function( event ) {
		// show our loading spinner
		$('.spinner').show();
		// Stop the default submission from happening
		event.preventDefault();

		// Grab our form values
		var button    = $(this);
		var form      = button.parent('form');
		var formdata  = form.serialize();

		$.ajax({
			type : "post",
			dataType : "json",
			url : myAjax.ajaxurl,
			data : { "action": "dma_save_user_profile", "formdata": formdata },
			success : function(response) {
				// hide our loading spinner
				$('.spinner').hide();
				// show our 'saved' notification briefly
				$('.notification').fadeTo('fast', 1).animate({opacity: 1.0}, 1000).fadeTo('slow', 0);
				// console.log(response);
			}
		});

	});

	// Validate our PIN codes and pass the valid value back to our hidden field
	$('form.submit-pin .submit').click( function( event ) {

		// show our loading spinner
		$('.spinner').show();
		// Stop the default submission from happening
		event.preventDefault();

		// Grab our form values
		var button    = $(this);
		var form      = button.parent('form');
		var formdata  = form.serialize();

		// If we have a valid PIN, update it
		if ( true === is_pin_valid() ) {

			// Update our pin in the main form
			$('input[name=pin]').val( $('#pin1').val() );

			// Save our new pin, in case they forget to save the main form
			$.ajax({
				type : "post",
				dataType : "json",
				url : myAjax.ajaxurl,
				data : { "action": "dma_save_user_profile", "formdata": formdata },
				success : function(response) {
					// hide our loading spinner
					$('.spinner').hide();
					// show our 'saved' notification briefly
					$('.notification').fadeTo('fast', 1).animate({opacity: 1.0}, 2000).fadeTo('slow', 0);
					$.hidePop();
				}
			});
		}

	});

	// Validate our PIN codes and pass the valid value back to our hidden field
	$('form.submit-avatar .submit').click( function( event ) {
		// show our loading spinner
		$('.spinner').show();
		// Stop the default submission from happening
		event.preventDefault();

		// Grab our form values
		var button     = $(this);
		var form       = button.parent('form');
		var formdata   = form.serialize();
		var avatar_id  = $('form.submit-avatar input[name=avatar]:checked').val();
		var avatar_img = $('form.submit-avatar input[name=avatar]:checked').next('label').find('img').attr('src');

		// Update our avatar selection in the main form
		$('#avatar').val( avatar_id );
		$('.user-avatar').attr( 'src', avatar_img );

		// Save our avatar selection, in case they forget to save the main form
		$.ajax({
			type : "post",
			dataType : "json",
			url : myAjax.ajaxurl,
			data : { "action": "dma_save_user_profile", "formdata": formdata },
			success : function(response) {
				// hide our loading spinner
				$('.spinner').hide();
				// show our 'saved' notification briefly
				$('.notification').fadeTo('fast', 1).animate({opacity: 1.0}, 2000).fadeTo('slow', 0);
				$.hidePop();
			}
		});

	});

	// Send badge to Credly
	$('.send-credly .button').click( function( event ) {
		// Stop the default submission from happening
		event.preventDefault();

		var button     = $(this);
		var badgeID    = button.attr('href').replace('#','');

		console.log(badgeID);

		// move close button out of site
		$('.button.close-popup').hide();
		// show our loading spinner
		$('.send-credly .spinner').show();

		// Visit Credly
		$.ajax({
			type : 'post',
			dataType : 'json',
			url : myAjax.ajaxurl,
			data : {
				'action': 'credly_badge_send_handler',
				'badge_id': badgeID
			},
			success : function(response) {
				console.log(response);

				// hide our loading spinner
				$('.send-credly .spinner').hide();
				// show our 'saved' notification briefly
				$('.notification').fadeTo('fast', 1).animate({opacity: 1.0}, 1000).fadeTo('slow', 0).html(response);
				setTimeout( function() {
					$('.button.close-popup').show();
				}, 2200);
			}
		});

	});

});
})(jQuery);

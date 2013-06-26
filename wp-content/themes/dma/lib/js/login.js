jQuery(document).ready(function($) {

	$(document).on('wds_pre_openpopup', function( event, data ){
		// console.log(data);
		// if ( $(data).attr('id') !== 'what-is-dma'  )
			$('.info-box.login-box').toggle();
	});

	/** Show error (but only until popup is changed) **/
	var query = window.location.search;
	var HideError = false;

	if ( ( query.search( 'auth_error' ) !== -1 ) && HideError === false ) {
		showError();
	}

	$(document).bind('wds_closepopup', function(){
		hideError();
		HideError = true;
		$('.info-box.login-box').toggle();
	});

	function showError() {
		$('.login-error').show();
		$('.login-error + p').hide();
		$('#login, #pin').css({'border-color':'#e4002b','border-width':'4px'});
	}

	function hideError() {
		$('.login-error').hide();
		$('.login-error + p').show();
		$('#login, #pin').removeAttr('style');
	}

	// launch invisible iframe with scanner show url
	$('.login.pop').click(function() {
		show_hide_scanner( 'showscanner' );
	});

	// launch invisible iframe with scanner hide url
	// When someone clicks the "Cancel" button
	$(document).bind('wds_closepopup', hide_scanner);
	// or clicking the 'i don't have my card' link
	$('.manual-login.pop').click(hide_scanner);

	function hide_scanner() {
		show_hide_scanner( 'hidescanner' );
	}
	function show_hide_scanner( showhide ) {
		$('<iframe>').attr('src', 'dma://' + showhide).appendTo(document.documentElement).remove();
	}


});

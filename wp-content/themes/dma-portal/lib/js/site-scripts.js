/* Site Scripts Table of Contents

* DMA Lightbox Setup Functions
* Close Popup Function
* Left/Right Nav
* Go To Top Nav
* General Tabbing function
* Badge/Reward Sorting
* Reward height adjustments
* Global Functions

*/

jQuery(document).ready(function($) {
	'use strict';

	/** DMA Lightbox Setup Functions **/

	// if url has a hash, do not scroll to it
	function resetWindow() {
		setTimeout(function() {
			if (location.hash) {
				window.scrollTo(0, 0);
			}
		}, 1);
	}
	resetWindow();

	// create our overlay
	var overlay = document.createElement('div');
	$(overlay).attr('id', 'overlay');
	$('#wrap').append(overlay);

	// screen dimensions
	var sw = 1536;
	var sh = $(window).height();
	// standard popup dimensions
	var popup_w = 900;
	var popup_h = 900;
	// console.log(sh);

	// jQuery universal funtion to popup a div
	$.popOpen = function( popup, height, width ) {
		if ( ! iExists( popup ) )
			return;
		// first, close all instances
		// $('.popup').hide();

		$.setPopupDimensions( popup, height, width );

		trigger('wds_pre_openpopup', popup);
		// show overlay and move our popup
		$(overlay).show().after(popup);
		// add overlay body class
		$('body').addClass('overlay');
		// popup.after(overlay);
		popup.addClass('popup').show();
		resetWindow();
		trigger('wds_post_openpopup', popup);

	};

	// if width or height is set, will resize and reposition the element
	$.setPopupDimensions = function( popup, height, width, stop ) {
		// set our new width and/or height
		if ( width && height ) {
			// console.log('width && height');
			popup.css({ width: width, height: height });
			updatePosition( popup );
		}
		if ( width && !height ) {
			// console.log('width && !height');

			popup.css({ width: width });
			updatePosition( popup, 'width' );
		}
		if ( height && !width ) {
			// console.log('height && !width');

			popup.css({ height: height });
			updatePosition( popup, 'height' );
		}

		if ( !height && !width ) {
			// console.log('!height && !width');

			if ( stop ) {
				popup.css({ height: popup_h });
				updatePosition( popup, 'height' );
				return;
			}

			width = popup.data('popwidth') ? popup.data('popwidth') : false;
			// check data height attributes (overrides classname height)
			height = popup.data('popheight') ? popup.data('popheight') : false;

			$.setPopupDimensions( popup, height, width, true );
		}
		return;
	};

	function updatePosition( obj, dimension ) {
		if ( dimension === 'width' ) {
			genNewl(obj);
			return;
		}
		if ( dimension === 'height' ) {
			genNewT(obj);
			return;
		}

		genNewl(obj);
		genNewT(obj);
	}

	function genNewl( obj ) {
		var newwidth = obj.outerWidth();
		var left = Math.floor( ( sw - newwidth ) / 2 );
		obj.css({ left: left });
	}

	function genNewT( obj ) {
		var scrollTop = $(window).scrollTop();
		// console.log( scrollTop );
		var newheight = obj.outerHeight();
		var top = Math.floor( ( sh - newheight ) / 2 ) + scrollTop;
		obj.css({ top: top });
	}

	/** Close Popup Function **/

	// Hide popup when clicking close buttons, or a button that opens another popup
	$('.close-popup, .popup .pop').click(function(event) {
		event.preventDefault();
		$.hidePop();
	});

	// Hide popup when clicking overlay unless explicitly turned off with 'ltd' class
	$('#overlay').click(function(event) {
		$.hidePop( true );
	});

	// Hide popup when hitting the 'escape' key unless explicitly turned off with 'ltd' class
	$(document).keyup(function(e) {
		if (e.which == 27)
			$.hidePop( true );
	});

	// function to close all popups
	$.hidePop = function( restricted ) {
		// are we checking for 'ltd' class?
		restricted = typeof restricted !== 'undefined' ? true : false;
		// if we're checking for restricted and are restricted, don't hide
		if ( restricted && $('.popup:visible').hasClass('ltd') )
			return;
		// Ok, let's hide the popup
		$('#overlay, .popup').hide();
		// and remove the overlay body class
		$('body').removeClass('overlay');
		trigger('wds_closepopup');
	};

	$('.pop').click(function(e) {
		e.preventDefault();
		// get our popup div from the pop link's href
		var popup = $($(this).attr('href'));
		// check data width attributes
		var width = $(this).data('popwidth') ? $(this).data('popwidth') : false;
		// check classname
		var height = $(this).hasClass('pop-tall') ? 1165 : false;
		// check data height attributes (overrides classname height)
		height = $(this).data('popheight') ? $(this).data('popheight') : height;

		$.popOpen( popup, height, width );
	});

	// check for hashes to display lightboxes
	var hash = window.location.hash;
	if ( hash && iExists($(hash)) ) {
		setTimeout(popHash, 500);
	}

	function popHash() {
		$.popOpen($(hash));
	}

	/** Left/Right Nav **/

	$('.nav-right').click( function(e) {
		// get our next url
		var url = $('.main-menu .menu-item.active').next().attr('href');
		// if it doesn't exist, get our first url
		url = typeof url === 'undefined' ? $('.main-menu .menu-item:first-child').attr('href') : url;
		// go to our new url
		window.location = url;
	});

	$('.nav-left').click( function(e) {
		// get our previous url
		var url = $('.main-menu .menu-item.active').prev().attr('href');
		// if it doesn't exist, get our last url
		url = typeof url === 'undefined' ? $('.main-menu .menu-item:last-child').attr('href') : url;
		// go to our new url
		window.location = url;
	});

	/** Go To Top Nav **/

	// when clicking top button
	$('.back-to-top').click( function(e) {
		event.preventDefault();
		// animated scroll to top
		$('html, body').animate({scrollTop:0}, 200);
	});

	// when scrolling the page
	$(window).scroll(function () {
		var top = $(this).scrollTop();
		// if we've gone more than 72px, fade in the top button
		if (top > 72 )
			$('.back-to-top').fadeIn('slow');
		// if we're near the top, fade out the top button
		if (top < 72 )
			$('.back-to-top').fadeOut(200);
	});

	/** Badge/Reward Sorting **/

	var badges = $('.badge-list .badge.pop, .reward-list .reward.pop, .activity-stream .stream');
	var buttons = $('.filter-buttons-wrap .button');

	buttons.click(function(event) {

		// don't follow the link's href
		event.preventDefault();

		// set this clicked button as the current button class
		buttons.removeClass('current');
		$(this).addClass('current');

		// get this buttons corresponding badges
		var query = $.parseQueryString( $(this).attr('href') );

		if ( query.hasOwnProperty('filter') ) {
			// hide all badges
			var Class = '.badge.pop.' + query['filter'] + ', .reward.pop.' + query['filter'] + ', .stream.' + query['filter'];
			badges.hide();
			// then show our specific filtered badges
			$(Class).show();

			// If we're viewing the steps-remaining filter, sort by most completed first
			if ( 'is-active' == query['filter'] )
				$('.badge-list>a').tsort({data:'percentcomplete',order:'desc'});

		} else {
			// if clicking the 'All' button, show them all
			badges.show();
		}

		// add odd/even classes
		resetRewardMargins();

	});

	function resetRewardMargins() {
		// only on Rewards page
		if ( ! iExists( $('.reward-list') ) )
			return;

		setTimeout( function() {
			$('.reward-list .reward.pop:visible:even').addClass('odd').removeClass('even');
			$ ('.reward-list .reward.pop:visible:odd').addClass('even').removeClass('odd');
		}, 1);
	}
	resetRewardMargins();

	/** Reward height adjustments **/

	// Loop through each reward
	$('.reward.pop').each(function( index, value ) {
		var titleHeight = $('.title', this).outerHeight();
		// if the title is two lines long
		if ( titleHeight > 50 ) {
			var descHeight = 160 - titleHeight;
			// set our description to a smaller max-height
			$('.description', this ).css({'max-height': descHeight+'px'});
		}
	});

	// Parse a query string and return parts as an associative array.
	$.parseQueryString = function( string ) {
		var vars = [], hash, parse = string.search(/\?/i);

		// if we don't find a query string, return false
		if ( parse === -1 ) {
			return false;
		}

		// if we do, break the pieces into an array
		var hashes = string.slice(string.indexOf('?') + 1).split('&');
		for( var i = 0; i < hashes.length; i++ ) {
			hash = hashes[i].split('=');
			vars.push(hash[0]);
			vars[hash[0]] = hash[1];
		}
		// return the array
		return vars;
	};

	function iExists(item) {
		return item.length > 0 ? true : false;
	}

	function trigger(event, data) {
		data = data || {};
		$.event.trigger(event, data);
	}

});

/*global jQuery, CustomizerEverywhereControls_exports */
/*exported CustomizerEverywhereControls */
var CustomizerEverywhereControls = (function ($) {
	'use strict';

	var self = {
		options: {
			back_button_closes_customizer_preview_window: true
		}
	};

	$.extend( self, CustomizerEverywhereControls_exports );

	self.init = function () {
		if ( self.options.back_button_closes_customizer_preview_window ) {
			self.letBackButtonCloseWindow();
		}
	};

	/**
	 * When the user clicks "Preview & Customize", they are opened in a new window to the customizer
	 * loaded with the post preview. In the customizer, the back button (close/cancel) does not take back
	 * but just takes you out of the customizer by navigating you away to a non-customizer page.
	 * In the context of when the customizer is opened for a post preview, however, the back button should
	 * just close the window that was opened and return you back to the window which originally opened the
	 * customizer preview. This adds a nice close button to clean up after doing a preview.
	 */
	self.letBackButtonCloseWindow = function () {
		var originalOpenerUrl;
		if ( ! window.opener ) {
			return;
		}
		originalOpenerUrl = window.opener.location.href;

		$( '.customize-controls-close' ).on( 'click', function ( e ) {
			if ( window.opener && window.opener.location.href === originalOpenerUrl ) {
				window.opener.focus();
				window.close();
				e.preventDefault();
			}
			// @todo else history.back()?
		});
	};

	$(function () {
		self.init();
	});

	return self;
}(jQuery));

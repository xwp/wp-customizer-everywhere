/*global jQuery, CustomizeEverywhereControls_exports */
var CustomizeEverywhereControls = (function ($) {
	'use strict';

	var self = {
		options: {
			back_button_closes_customizer_preview_window: true
		}
	};

	$.extend(self, CustomizeEverywhereControls_exports);

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
		var original_opener_url;
		if ( ! window.opener ) {
			return;
		}
		original_opener_url = window.opener.location.href;

		$( '.back.button').on('click', function (e) {
			if ( window.opener && window.opener.location.href === original_opener_url ) {
				window.opener.focus();
				window.close();
				e.preventDefault();
			}
		});
	};

	$(function () {
		self.init();
	});

	return self;
}(jQuery));

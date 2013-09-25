/*global jQuery, CustomizePreviewPosts_exports, doPreview */
var CustomizePreviewPosts = (function ($) {
	'use strict';

	var self = {
		customize_url_tpl: null,
		i18n: {
			preview_button_label: null
		}
	};

	$.extend(self, CustomizePreviewPosts_exports);

	self.init = function () {
		var preview_url, preview_btn;
		preview_btn = $('#post-preview');
		preview_btn.text( self.i18n.preview_button_label );

		// This is needed because the link is not filtered by preview_post_link if the post is published
		if ( ! /customize\.php$/.test( preview_btn.prop('pathname') ) ) {
			preview_url = self.customize_url_tpl;
			preview_url = preview_url.replace( '{url}', encodeURIComponent( preview_btn.prop( 'href' ) ) );
			preview_url = preview_url.replace( '{return}', encodeURIComponent( window.location.href ) );
			preview_btn.attr( 'href', preview_url );
		}

		// Let each post have its own preview window
		preview_btn.attr('target', 'wp-preview-' + $('#post_ID').val() );

		// @todo in the customizer, if they click Close it should self.close() and focus on opener

		/**
		 * Override global doPreview function
		 * Copied from autosave.js, which fortunately lacks scoping doPreview in the closure
		 * Replace hard-coded link target of 'wp-preview' with whatever is in the preview_btn's target (set above)
		 * THIS IS A HACK! It will likely no longer work in 3.8 due to refactor in http://core.trac.wordpress.org/ticket/25272
		 *
		 */
		if ( typeof window.doPreview !== 'undefined' ) {
			window.doPreview = function () {
				$('input#wp-preview').val('dopreview');
				$('form#post').attr('target', preview_btn.attr('target')).submit().attr('target', '');

				/*
				 * Workaround for WebKit bug preventing a form submitting twice to the same action.
				 * https://bugs.webkit.org/show_bug.cgi?id=28633
				 */
				var ua = navigator.userAgent.toLowerCase();
				if ( ua.indexOf('safari') != -1 && ua.indexOf('chrome') == -1 ) {
					$('form#post').attr('action', function(index, value) {
						return value + '?t=' + new Date().getTime();
					});
				}

				$('input#wp-preview').val('');
			};
		}
	};

	$(function () {
		self.init();
	});

	return self;
}(jQuery));

/*global jQuery, CustomizeEverywhereEditPost_exports, doPreview */
var CustomizeEverywhereEditPost = (function ($) {
	'use strict';

	var self = {
		customize_url_tpl: null,
		i18n: {
			preview_button_label: null
		}
	};

	$.extend(self, CustomizeEverywhereEditPost_exports);

	self.init = function () {
		self.setPreviewLinkText();
		self.setPreviewLinkTarget();
		self.setPreviewLinkHref();
		self._overridePreviewFunction();
	};

	/**
	 * Change button to read "Preview" to read "Preview & Customize"
	 */
	self.setPreviewLinkText = function () {
		$('#post-preview').text( self.i18n.preview_button_label );
	};

	/**
	 * # Always have Preview link open in a new window with a unique name
	 */
	self.setPreviewLinkTarget = function () {
		$('#post-preview').attr('target', 'wp-preview-' + $('#post_ID').val() );
	};

	/**
	 * This is needed because the link is not filtered by preview_post_link if the post is published
	 */
	self.setPreviewLinkHref = function () {
		var preview_url, preview_btn;
		preview_btn = $('#post-preview');
		if ( ! /customize\.php$/.test( preview_btn.prop('pathname') ) ) {
			preview_url = self.customize_url_tpl;
			preview_url = preview_url.replace( '{url}', encodeURIComponent( preview_btn.prop( 'href' ) ) );
			preview_url = preview_url.replace( '{return}', encodeURIComponent( window.location.href ) );
			preview_btn.attr( 'href', preview_url );
		}
	};

	/**
	 * Override global doPreview function
	 * Copied from autosave.js, which fortunately lacks scoping doPreview in the closure
	 * Replace hard-coded link target of 'wp-preview' with whatever is in the preview_btn's target (set above)
	 * THIS IS A HACK! It will likely no longer work in 3.8 due to refactor in http://core.trac.wordpress.org/ticket/25272
	 * Requested patch in http://core.trac.wordpress.org/ticket/25272#comment:7
	 */
	self._overridePreviewFunction = function () {
		if ( typeof window.doPreview !== 'undefined' ) {
			window.doPreview = function () {
				$('input#wp-preview').val('dopreview');
				$('form#post').attr('target', $('#post-preview').attr('target')).submit().attr('target', '');

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

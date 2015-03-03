/*global jQuery, CustomizerEverywhereEditPost_exports */
/*exported CustomizerEverywhereEditPost */
var CustomizerEverywhereEditPost = (function ($) {
	'use strict';

	var self = {
		customize_url_tpl: null,
		i18n: {
			preview_button_label: null
		},
		options: {}
	};

	$.extend( self, CustomizerEverywhereEditPost_exports );

	self.init = function () {
		self.setPreviewLinkText();
		self.setPreviewLinkHref();
	};

	/**
	 * Change button to read "Preview" to read "Preview & Customize"
	 */
	self.setPreviewLinkText = function () {
		$( '#post-preview' ).text( self.i18n.preview_button_label );
	};

	/**
	 * This is needed because the link is not filtered by preview_post_link if the post is published
	 */
	self.setPreviewLinkHref = function () {
		var preview_url, preview_btn;
		preview_btn = $( '#post-preview' );
		if ( ! /customize\.php$/.test( preview_btn.prop( 'pathname' ) ) ) {
			preview_url = self.customize_url_tpl;
			preview_url = preview_url.replace( '{url}', encodeURIComponent( preview_btn.prop( 'href' ) ) );
			preview_url = preview_url.replace( '{return}', encodeURIComponent( window.location.href ) );
			preview_btn.attr( 'href', preview_url );
		}
	};

	$(function () {
		self.init();
	});

	return self;
}(jQuery));

<?php
/**
 * Plugin Name: Customize Preview Posts
 * Description: Turn "Preview Changes" into "Customize and Preview Changes"
 * Version:     0.1
 * Author:      X-Team
 * Author URI:  http://x-team.com/wordpress/
 * License:     GPLv2+
 * Text Domain: customize-preview-posts
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2013 X-Team (http://x-team.com/)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

# Turn the Preview link into a Customize & Preview
# Moves Customize to top-level link in Admin Bar next to Edit
# Publish & Save button should then publish the draft, as well as any customizer changes
# Top of customizer should have a Back link to go back to post.php
# Customize Close button should: if (window.opener){ window.opener.focus(); window.close(); }
# Always have Preview link open in a new window with a unique name

class Customize_Preview_Posts {

	/**
	 * Plugin boot-up
	 */
	static function setup() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_script' ) );
		add_filter( 'preview_post_link', array( __CLASS__, 'add_preview_link_to_customize_url' ) );
	}

	/**
	 * @param null|string meta key, if omitted all meta are returned
	 * @return array|mixed meta value(s)
	 */
	static function get_plugin_meta( $key = null ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$data = get_plugin_data( __FILE__ );
		if ( ! is_null( $key ) ) {
			return $data[$key];
		} else {
			return $data;
		}
	}

	/**
	 * @return string the plugin version
	 */
	static function get_version() {
		return self::get_plugin_meta( 'Version' );
	}

	/**
	 * Get the URL to the plugin's dir and a path inside of it
	 * @param string [$path]
	 * @return string URL to $path
	 */
	static function plugin_path_url( $path = null ) {
		$plugin_dirname = basename( dirname( __FILE__ ) );
		$base_dir = trailingslashit( plugin_dir_url( '' ) ) . $plugin_dirname;
		if ( $path ) {
			return trailingslashit( $base_dir ) . ltrim( $path, '/' );
		}
		else {
			return $base_dir;
		}
	}

	/**
	 * Route all preview links through the customizer
	 * @param string $url
	 * @filter preview_post_link
	 * @return string
	 */
	static function add_preview_link_to_customize_url( $url ) {
		if ( basename( parse_url( $url, PHP_URL_PATH ) ) !== 'customize.php' ) {
			$args = compact( 'url' );
			if ( 'post' === get_current_screen()->base ) {
				$args['return'] = urlencode( get_edit_post_link( get_post()->ID, 'raw' ) ); // shouldn't have to urlencode() here
			}
			$url = add_query_arg( $args, admin_url( 'customize.php' ) );
		}
		return $url;
	}

	/**
	 * @param $page_hook
	 * @action admin_enqueue_scripts
	 */
	static function enqueue_script( $page_hook ) {
		if ( ! in_array( $page_hook, array( 'post.php', 'post-new.php' ) ) ) {
			return;
		}

		wp_enqueue_script(
			'customize-preview-changes',
			self::plugin_path_url( 'customize-preview-posts.js' ),
			array( 'jquery' ),
			self::get_version(),
			true
		);

		global $wp_scripts;
		$exports = array(
			'customize_url_tpl' => admin_url( 'customize.php?url={url}&return={return}' ),
			'i18n' => array(
				'preview_button_label' => __( 'Customize & Preview', 'customize-preview-posts' ),
			),
		);
		$wp_scripts->add_data(
			'customize-preview-changes',
			'data',
			sprintf( 'var CustomizePreviewPosts_exports = %s;', json_encode($exports) )
		);
	}
}
add_action( 'plugins_loaded', array( 'Customize_Preview_Posts', 'setup' ) );

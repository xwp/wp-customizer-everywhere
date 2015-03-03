<?php
/**
 * Plugin Name: Customizer Everywhere
 * Description: Promote and enhance the use of customizer in more places; open post previews in the customizer and promote customize link to top of admin bar.
 * Version:     0.1.2
 * Author:      XWP
 * Author URI:  https://xwp.co/
 * License:     GPLv2+
 * Text Domain: customizer-everywhere
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2013 XWP (https://xwp.co/)
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

class Customizer_Everywhere {

	static $options = array();

	/**
	 * Plugin boot-up
	 */
	static function setup() {
		self::$options = array(
			'admin_bar_move_customize_following_edit' => true,
			'admin_bar_customize_node_priority' => 81,  // edit post is priority 80
			'back_button_closes_customizer_preview_window' => true,
			'customizer_title_tracks_previewed_document' => true,
		);
		self::$options = apply_filters( 'customize_everything_options', self::$options );

		if ( self::current_user_can() ) {
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
			add_filter( 'preview_post_link', array( __CLASS__, 'add_preview_link_to_customize_url' ) );
			add_action( 'customize_preview_init', array( __CLASS__, 'customize_preview_init' ) );
			add_action( 'customize_controls_enqueue_scripts', array( __CLASS__, 'customize_controls_enqueue_scripts' ) );

			if ( self::$options['admin_bar_move_customize_following_edit'] ) {
				add_action( 'admin_bar_menu', array( __CLASS__, 'admin_bar_menu' ), self::$options['admin_bar_customize_node_priority'] );
			}
		}
	}

	/**
	 * @param null|string [$key] if omitted all meta are returned
	 * @return array|mixed meta value(s)
	 */
	static function get_plugin_meta( $key = null ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$data = get_plugin_data( __FILE__ );
		return is_null( $key ) ? $data : $data[ $key ];
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
	 * wp_localize_script turns its data into an array of strings
	 * @param string $handle
	 * @param string $var
	 * @param mixed $data
	 */
	static function export_js( $handle, $var, $data ) {
		global $wp_scripts;
		$wp_scripts->add_data(
			$handle,
			'data',
			sprintf( 'var %s = %s;', $var, wp_json_encode( $data ) )
		);
	}

	/**
	 * Helper function to see if a user can use this functionality
	 * @return bool
	 */
	static function current_user_can() {
		return current_user_can( 'edit_theme_options' ); // cap is hard-coded in customize.php
	}

	/**
	 * Route all preview links through the customizer
	 * @param string $url
	 * @filter preview_post_link
	 * @return string
	 */
	static function add_preview_link_to_customize_url( $url ) {
		if ( 'customize.php' !== basename( parse_url( $url, PHP_URL_PATH ) ) ) {

			$args = array();
			$args['url'] = $url;
			if ( 'post' === get_current_screen()->base ) {
				$args['return'] = get_edit_post_link( get_post()->ID, 'raw' );
			}
			$url = admin_url( 'customize.php' ) . '?' . http_build_query( $args );
		}
		return $url;
	}

	/**
	 * @action customize_preview_init
	 */
	static function customize_preview_init() {
		wp_enqueue_script(
			'customizer-everywhere-preview',
			self::plugin_path_url( 'preview.js' ),
			array( 'jquery', 'customize-preview' ),
			self::get_version(),
			true
		);
		self::export_js(
			'customizer-everywhere-preview',
			'CustomizerEverywherePreview_exports',
			array(
				'i18n' => array(
					'parent_frame_document_title_tpl' => __( 'Customize: {title}', 'customizer-everywhere' ),
				),
				'options' => self::$options,
			)
		);
	}

	/**
	 * @param $page_hook
	 * @action admin_enqueue_scripts
	 */
	static function admin_enqueue_scripts( $page_hook ) {
		if ( ! in_array( $page_hook, array( 'post.php', 'post-new.php' ) ) ) {
			return;
		}

		wp_enqueue_script(
			'customizer-everywhere-edit-post',
			self::plugin_path_url( 'edit-post.js' ),
			array( 'jquery' ),
			self::get_version(),
			true
		);

		self::export_js(
			'customizer-everywhere-edit-post',
			'CustomizerEverywhereEditPost_exports',
			array(
				'customize_url_tpl' => admin_url( 'customize.php?url={url}&return={return}' ),
				'i18n' => array(
					'preview_button_label' => __( 'Preview & Customize', 'customizer-everywhere' ),
				),
				'options' => self::$options,
			)
		);
	}

	/**
	 * @action customize_controls_enqueue_scripts
	 */
	static function customize_controls_enqueue_scripts() {
		wp_enqueue_script(
			'customizer-everywhere-controls',
			self::plugin_path_url( 'controls.js' ),
			array( 'jquery' ),
			self::get_version(),
			true
		);
		self::export_js(
			'customizer-everywhere-controls',
			'CustomizerEverywhereControls_exports',
			array(
				'options' => self::$options,
			)
		);
	}

	/**
	 * Move the Customize link in the admin bar right after the Edit Post link
	 * @param WP_Admin_Bar $wp_admin_bar
	 * @action admin_bar_menu
	 */
	static function admin_bar_menu( $wp_admin_bar ) {
		$customize_node = $wp_admin_bar->get_node( 'customize' );
		if ( $customize_node ) {
			$wp_admin_bar->remove_node( 'customize' );
			$customize_node->parent = false;
			$customize_node->meta['title'] = __( 'View current page in the customizer', 'customizer-everywhere' );
			$wp_admin_bar->add_node( (array) $customize_node );
		}
	}
}
add_action( 'plugins_loaded', array( 'Customizer_Everywhere', 'setup' ) );

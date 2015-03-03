<?php

namespace CustomizerEverywhere;

/**
 * Main plugin bootstrap file.
 */
class Plugin {

	const VERSION = '0.2';

	/**
	 * @var array
	 */
	public $config = array();

	/**
	 * @var string
	 */
	public $slug;

	/**
	 * @var string
	 */
	public $dir_path;

	/**
	 * @var string
	 */
	public $dir_url;

	/**
	 * @var string
	 */
	protected $autoload_class_dir = 'php';

	/**
	 * @param array $config
	 */
	public function __construct( $config = array() ) {

		$location = $this->locate_plugin();
		$this->slug = $location['dir_basename'];
		$this->dir_path = $location['dir_path'];
		$this->dir_url = $location['dir_url'];

		$default_config = array(
			'admin_bar_move_customize_following_edit' => true,
			'admin_bar_customize_node_priority' => 81,  // edit post is priority 80
			'back_button_closes_customizer_preview_window' => true,
		);

		$this->config = array_merge( $default_config, $config );

		add_action( 'after_setup_theme', array( $this, 'init' ) );
	}

	/**
	 * @action after_setup_theme
	 */
	function init() {
		spl_autoload_register( array( $this, 'autoload' ) );
		$this->config = \apply_filters( 'customize_everything_options', $this->config ); /** @deprecated */
		$this->config = \apply_filters( 'customizer_everywhere_config', $this->config, $this );

		if ( current_user_can( 'customize' ) ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			add_filter( 'preview_post_link', array( $this, 'add_preview_link_to_customize_url' ) );
			add_action( 'customize_controls_enqueue_scripts', array( $this, 'customize_controls_enqueue_scripts' ) );

			if ( $this->config['admin_bar_move_customize_following_edit'] ) {
				add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), $this->config['admin_bar_customize_node_priority'] );
			}
		}
	}

	/**
	 * Route all preview links through the customizer
	 * @param string $url
	 * @filter preview_post_link
	 * @return string
	 */
	function add_preview_link_to_customize_url( $url ) {
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
	 * @param $page_hook
	 * @action admin_enqueue_scripts
	 */
	function admin_enqueue_scripts( $page_hook ) {
		if ( ! in_array( $page_hook, array( 'post.php', 'post-new.php' ) ) ) {
			return;
		}

		wp_enqueue_script(
			'customizer-everywhere-edit-post',
			$this->dir_url . 'js/edit-post.js',
			array( 'jquery' ),
			self::VERSION,
			true
		);

		$this->export_js(
			'customizer-everywhere-edit-post',
			'CustomizerEverywhereEditPost_exports',
			array(
				'customize_url_tpl' => admin_url( 'customize.php?url={url}&return={return}' ),
				'i18n' => array(
					'preview_button_label' => __( 'Preview & Customize', 'customizer-everywhere' ),
				),
				'options' => $this->config,
			)
		);
	}

	/**
	 * @action customize_controls_enqueue_scripts
	 */
	function customize_controls_enqueue_scripts() {
		wp_enqueue_script(
			'customizer-everywhere-controls',
			$this->dir_url . 'js/controls.js',
			array( 'jquery' ),
			self::VERSION,
			true
		);
		$this->export_js(
			'customizer-everywhere-controls',
			'CustomizerEverywhereControls_exports',
			array(
				'options' => $this->config,
			)
		);
	}

	/**
	 * Move the Customize link in the admin bar right after the Edit Post link
	 * @param \WP_Admin_Bar $wp_admin_bar
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

	/**
	 * wp_localize_script turns its data into an array of strings
	 * @param string $handle
	 * @param string $var
	 * @param mixed $data
	 */
	static function export_js( $handle, $var, $data ) {
		/**
		 * @var \WP_Scripts $wp_scripts
		 */
		global $wp_scripts;
		$wp_scripts->add_data(
			$handle,
			'data',
			sprintf( 'var %s = %s;', $var, wp_json_encode( $data ) )
		);
	}

	########################################: Following should be in base class

	/**
	 * @return \ReflectionObject
	 */
	function get_object_reflection() {
		static $reflection;
		if ( empty( $reflection ) ) {
			$reflection = new \ReflectionObject( $this );
		}
		return $reflection;
	}

	/**
	 * Autoload for classes that are in the same namespace as $this, and also for
	 * classes in the Twig library.
	 *
	 * @param  string $class
	 * @return void
	 */
	function autoload( $class ) {
		if ( ! preg_match( '/^(?P<namespace>.+)\\\\(?P<class>[^\\\\]+)$/', $class, $matches ) ) {
			return;
		}
		if ( $this->get_object_reflection()->getNamespaceName() !== $matches['namespace'] ) {
			return;
		}
		$class_name = $matches['class'];

		$class_path = \trailingslashit( $this->dir_path );
		if ( $this->autoload_class_dir ) {
			$class_path .= \trailingslashit( $this->autoload_class_dir );
		}
		$class_path .= sprintf( 'class-%s.php', strtolower( str_replace( '_', '-', $class_name ) ) );
		if ( is_readable( $class_path ) ) {
			require_once $class_path;
		}
	}

	/**
	 * Version of plugin_dir_url() which works for plugins installed in the plugins directory,
	 * and for plugins bundled with themes.
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function locate_plugin() {
		$reflection = new \ReflectionObject( $this );
		$file_name = $reflection->getFileName();
		if ( '/' !== \DIRECTORY_SEPARATOR ) {
			$file_name = str_replace( \DIRECTORY_SEPARATOR, '/', $file_name ); // Windows compat
		}
		$plugin_dir = preg_replace( '#(.*plugins[^/]*/[^/]+)(/.*)?#', '$1', $file_name, 1, $count );
		if ( 0 === $count ) {
			throw new \Exception( "Class not located within a directory tree containing 'plugins': $file_name" );
		}

		// Make sure that we can reliably get the relative path inside of the content directory
		$content_dir = trailingslashit( WP_CONTENT_DIR );
		if ( '/' !== \DIRECTORY_SEPARATOR ) {
			$content_dir = str_replace( \DIRECTORY_SEPARATOR, '/', $content_dir ); // Windows compat
		}
		if ( 0 !== strpos( $plugin_dir, $content_dir ) ) {
			throw new \Exception( 'Plugin dir is not inside of WP_CONTENT_DIR' );
		}
		$content_sub_path = substr( $plugin_dir, strlen( $content_dir ) );
		$dir_url = content_url( trailingslashit( $content_sub_path ) );
		$dir_path = $plugin_dir;
		$dir_basename = basename( $plugin_dir );
		return compact( 'dir_url', 'dir_path', 'dir_basename' );
	}

}

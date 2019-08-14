<?php
/**
 * Plugin Name: JetTabs For Elementor
 * Plugin URI:  https://jettabs.zemez.io/
 * Description: JetTabs - Tabs and Accordions for Elementor Page Builder
 * Version:     2.0.2
 * Author:      Zemez
 * Author URI:  https://zemez.io/zemezjet/
 * Text Domain: jet-tabs
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die();
}

// If class `Jet_Tabs` doesn't exists yet.
if ( ! class_exists( 'Jet_Tabs' ) ) {

	/**
	 * Sets up and initializes the plugin.
	 */
	class Jet_Tabs {

		/**
		 * A reference to an instance of this class.
		 *
		 * @since  1.0.0
		 * @access private
		 * @var    object
		 */
		private static $instance = null;

		/**
		 * Holder for base plugin URL
		 *
		 * @since  1.0.0
		 * @access private
		 * @var    string
		 */
		private $plugin_url = null;

		/**
		 * Plugin version
		 *
		 * @var string
		 */
		private $version = '2.0.2';

		/**
		 * Holder for base plugin path
		 *
		 * @since  1.0.0
		 * @access private
		 * @var    string
		 */
		private $plugin_path = null;

		/**
		 * Framework component
		 *
		 * @since  1.1.8
		 * @access public
		 * @var    object
		 */
		public $framework;

		/**
		 * Sets up needed actions/filters for the plugin to initialize.
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		public function __construct() {

			// Load the CX Loader.
			add_action( 'after_setup_theme', array( $this, 'framework_loader' ), -20 );

			// Internationalize the text strings used.
			add_action( 'init', array( $this, 'lang' ), -999 );
			// Load files.
			add_action( 'init', array( $this, 'init' ), -999 );

			// Register activation and deactivation hook.
			register_activation_hook( __FILE__, array( $this, 'activation' ) );
			register_deactivation_hook( __FILE__, array( $this, 'deactivation' ) );
		}

		/**
		 * Load the theme modules.
		 *
		 * @since 1.0.0
		 */
		public function framework_loader() {
			require $this->plugin_path( 'framework/loader.php' );

			$this->framework = new Jet_Tabs_CX_Loader(
				array(
					$this->plugin_path( 'framework/modules/interface-builder/cherry-x-interface-builder.php' ),
					$this->plugin_path( 'framework/modules/db-updater/cherry-x-db-updater.php' ),
				)
			);
		}

		/**
		 * Returns plugin version
		 *
		 * @return string
		 */
		public function get_version() {
			return $this->version;
		}

		/**
		 * Manually init required modules.
		 *
		 * @return void
		 */
		public function init() {

			$this->load_files();

			jet_tabs_settings()->init();
			jet_tabs_assets()->init();
			jet_tabs_integration()->init();

			if ( is_admin() ) {

				require $this->plugin_path( 'includes/updater/plugin-update.php' );

				jet_tabs_updater()->init( array(
					'version' => $this->get_version(),
					'slug'    => 'jet-tabs',
				) );

				// Init plugin changelog
				require $this->plugin_path( 'includes/updater/plugin-changelog.php' );

				jet_tabs_plugin_changelog()->init( array(
					'name'     => 'JetTabs For Elementor',
					'slug'     => 'jet-tabs',
					'version'  => $this->get_version(),
					'author'   => '<a href="https://zemez.io/zemezjet/">Zemez</a>',
					'homepage' => 'https://jettabs.zemez.io/',
					'banners'  => array(
						'high' => $this->plugin_url( 'assets/images/jet-tabs.png' ),
						'low'  => $this->plugin_url( 'assets/images/jet-tabs.png' ),
					),
				) );

				// Init DB upgrader
				require $this->plugin_path( 'includes/db-upgrader.php' );

				new Jet_Tabs_DB_Upgrader();

			}

			//Init Rest Api
			new \Jet_Tabs\Rest_Api();

			do_action( 'jet-tabs/init', $this );

		}

		/**
		 * Show recommended plugins notice.
		 *
		 * @return void
		 */
		public function required_plugins_notice() {
			require $this->plugin_path( 'includes/lib/class-tgm-plugin-activation.php' );
			add_action( 'tgmpa_register', array( $this, 'register_required_plugins' ) );
		}

		/**
		 * Register required plugins
		 *
		 * @return void
		 */
		public function register_required_plugins() {

			$plugins = array(
				array(
					'name'     => 'Elementor',
					'slug'     => 'elementor',
					'required' => true,
				),
			);

			$config = array(
				'id'           => 'jet-tabs',
				'default_path' => '',
				'menu'         => 'jet-tabs-install-plugins',
				'parent_slug'  => 'plugins.php',
				'capability'   => 'manage_options',
				'has_notices'  => true,
				'dismissable'  => true,
				'dismiss_msg'  => '',
				'is_automatic' => false,
				'strings'      => array(
					'notice_can_install_required'     => _n_noop(
						'JetTabs for Elementor requires the following plugin: %1$s.',
						'JetTabs for Elementor requires the following plugins: %1$s.',
						'jet-tabs'
					),
					'notice_can_install_recommended'  => _n_noop(
						'JetTabs for Elementor recommends the following plugin: %1$s.',
						'JetTabs for Elementor recommends the following plugins: %1$s.',
						'jet-tabs'
					),
				),
			);

			tgmpa( $plugins, $config );

		}

		/**
		 * Load required files
		 *
		 * @return void
		 */
		public function load_files() {
			require $this->plugin_path( 'includes/settings.php' );
			require $this->plugin_path( 'includes/assets.php' );
			require $this->plugin_path( 'includes/integration.php' );
			require $this->plugin_path( 'includes/rest-api/rest-api.php' );
			require $this->plugin_path( 'includes/rest-api/endpoints/base.php' );
			require $this->plugin_path( 'includes/rest-api/endpoints/elementor-template.php' );
		}

		/**
		 * Check if theme has elementor
		 *
		 * @return boolean
		 */
		public function has_elementor() {
			return defined( 'ELEMENTOR_VERSION' );
		}

		public function elementor() {
			return \Elementor\Plugin::$instance;
		}

		/**
		 * Returns path to file or dir inside plugin folder
		 *
		 * @param  string $path Path inside plugin dir.
		 * @return string
		 */
		public function plugin_path( $path = null ) {

			if ( ! $this->plugin_path ) {
				$this->plugin_path = trailingslashit( plugin_dir_path( __FILE__ ) );
			}

			return $this->plugin_path . $path;
		}
		/**
		 * Returns url to file or dir inside plugin folder
		 *
		 * @param  string $path Path inside plugin dir.
		 * @return string
		 */
		public function plugin_url( $path = null ) {

			if ( ! $this->plugin_url ) {
				$this->plugin_url = trailingslashit( plugin_dir_url( __FILE__ ) );
			}

			return $this->plugin_url . $path;
		}

		/**
		 * Loads the translation files.
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		public function lang() {
			load_plugin_textdomain( 'jet-tabs', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}

		/**
		 * Get the template path.
		 *
		 * @return string
		 */
		public function template_path() {
			return apply_filters( 'jet-tabs/template-path', 'jet-tabs/' );
		}

		/**
		 * Returns path to template file.
		 *
		 * @return string|bool
		 */
		public function get_template( $name = null ) {

			$template = locate_template( $this->template_path() . $name );

			if ( ! $template ) {
				$template = $this->plugin_path( 'templates/' . $name );
			}

			if ( file_exists( $template ) ) {
				return $template;
			} else {
				return false;
			}
		}

		/**
		 * Do some stuff on plugin activation
		 *
		 * @since  1.0.0
		 * @return void
		 */
		public function activation() {
		}

		/**
		 * Do some stuff on plugin activation
		 *
		 * @since  1.0.0
		 * @return void
		 */
		public function deactivation() {
		}

		/**
		 * Returns the instance.
		 *
		 * @since  1.0.0
		 * @access public
		 * @return object
		 */
		public static function get_instance() {
			// If the single instance hasn't been set, set it now.
			if ( null == self::$instance ) {
				self::$instance = new self;
			}
			return self::$instance;
		}
	}
}

if ( ! function_exists( 'jet_tabs' ) ) {

	/**
	 * Returns instanse of the plugin class.
	 *
	 * @since  1.0.0
	 * @return object
	 */
	function jet_tabs() {
		return Jet_Tabs::get_instance();
	}
}

jet_tabs();

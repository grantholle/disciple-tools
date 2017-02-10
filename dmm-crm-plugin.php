<?php
/**
 * Plugin Name: DRM
 * Plugin URI: https://github.com/ChasmSolutions/DMM-CRM-Plugin
 * Description: DRM is a contact relationship management system for disciple making movements.
 * Version: 0.0.1
 * Author: Chasm.Solutions & Kingdom.Training
 * Author URI: https://github.com/ChasmSolutions
 * Requires at least: 4.0.0
 * Tested up to: 4.7.0
 *
 * @package   DRM
 * @author 	  Chasm Solutions <chasm.crew@chasm.solutions>
 * @link      https://github.com/ChasmSolutions
 * @copyright 2017 Chasm Solutions
 * @license   GPL-3.0
 * @version   0.0.1
 * 
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly



/**
 * Returns the main instance of DRM_Plugin to prevent the need to use globals.
 *
 * @since  0.0.1
 * @return object DRM_Plugin
 */

    // Adds the DRM Plugin after plugins load
    add_action( 'plugins_loaded', 'DRM_Plugin' );

    // Creates the instance
    function DRM_Plugin() {
        return DRM_Plugin::instance();
    }


/**
 * Main DRM_Plugin Class
 *
 * @class DRM_Plugin
 * @version	1.0.0
 * @since 0.0.1
 * @package	DRM_Plugin
 * @author Chasm.Solutions & Kingdom.Training
 */
final class DRM_Plugin {
	/**
	 * DRM_Plugin The single instance of DRM_Plugin.
	 * @var 	object
	 * @access  private
	 * @since  0.0.1
	 */
	private static $_instance = null;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   0.0.1
	 */
	public $token;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   0.0.1
	 */
	public $version;

	/**
	 * The plugin directory URL.
	 * @var     string
	 * @access  public
	 * @since   0.0.1
	 */
	public $plugin_url;

	/**
	 * The plugin directory path.
	 * @var     string
	 * @access  public
	 * @since   0.0.1
	 */
	public $plugin_path;

    /**
     * Activation of roles.
     * @var     string
     * @access  public
     * @since   0.0.1
     */
    private $roles;

	// Admin - Start
	/**
	 * The admin object.
	 * @var     object
	 * @access  public
	 * @since   0.0.1
	 */
	public $admin;

	/**
	 * The settings object.
	 * @var     object
	 * @access  public
	 * @since   0.0.1
	 */
	public $settings;
	// Admin - End

	// Post Types - Start
	/**
	 * The post types we're registering.
	 * @var     array
	 * @access  public
	 * @since   0.0.1
	 */
	public $post_types = array();
	// Post Types - End
	/**
	 * Constructor function.
	 * @access  public
	 * @since   0.0.1
	 */
	public function __construct () {
		$this->token 			= 'drm';
		$this->plugin_url 		= plugin_dir_url( __FILE__ );
		$this->plugin_path 		= plugin_dir_path( __FILE__ );
		$this->version 			= '0.0.1';



        // Admin - Start
		if ( is_admin() ) {


            require_once('includes/config/config-admin.php');
            $this->admin = DRM_Plugin_Admin::instance();


            /**
             * Load plugin library that "requires plugins" at activation
             */
            require_once ('includes/config/config-required-plugins.php');
        }
		// Admin - End


        /**
         * Load admin panel functions to control the experience of the admin panel.
         */
        require_once ('includes/config.php');


        // Sets the config panel
        require_once('includes/config/config-settings.php');
        $this->settings = DRM_Plugin_Settings::instance();

        // Sets the site to private.
        require_once( 'includes/config/config-private-site.php' );

        // Run Once At Activation
        require_once( 'includes/services/service-runonce.php' );
        $this->run_once = new run_once;


        if ($this->run_once->run('activation') ) {
            // Roles and capabilities
            require_once ('includes/config/config-roles.php');
            $this->roles = DRM_Roles::instance();
            $this->roles->set_roles();
        }

		
		// Post Types - Start
		require_once('includes/classes/class-contact-post-type.php');
		require_once('includes/classes/class-group-post-type.php');
//		require_once( 'includes/classes/class-drm-location-post-type.php' ); //TODO: Reactivate when ready for development
		require_once('includes/classes/class-taxonomy.php');

		// Register an example post type. To register other post types, duplicate this line.
		$this->post_types['contacts'] = new DRM_Plugin_Contact_Post_Type( 'contacts', __( 'Contact', 'drm' ), __( 'Contacts', 'drm' ), array( 'menu_icon' => 'dashicons-groups' ) );
		$this->post_types['groups'] = new DRM_Plugin_Group_Post_Type( 'groups', __( 'Group', 'drm' ), __( 'Groups', 'drm' ), array( 'menu_icon' => 'dashicons-admin-multisite' ) );
//		$this->post_types['locations'] = new DRM_Plugin_Location_Post_Type( 'locations', __( 'Location', 'drm' ), __( 'Locations', 'drm' ), array( 'menu_icon' => 'dashicons-admin-site' ) ); //TODO: Reactivate when ready for development
		// Post Types - End



		register_activation_hook( __FILE__, array( $this, 'install' ) );

		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );









	} // End __construct()

	/**
	 * Main DRM_Plugin Instance
	 *
	 * Ensures only one instance of DRM_Plugin is loaded or can be loaded.
	 *
	 * @since 0.0.1
	 * @static
	 * @see DRM_Plugin()
	 * @return DRM_Plugin instance
	 */
	public static function instance () {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	} // End instance()

	/**
	 * Load the localisation file.
	 * @access  public
	 * @since   0.0.1
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'drm', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	} // End load_plugin_textdomain()

	/**
	 * Cloning is forbidden.
	 * @access public
	 * @since 0.0.1
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 * @access public
	 * @since 0.0.1
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	} // End __wakeup()

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   0.0.1
	 */
	public function install () {
        $this->_log_version_number();
    } // End install()

	/**
	 * Log the plugin version number.
	 * @access  private
	 * @since   0.0.1
	 */
	private function _log_version_number () {
		// Log the version number.
		update_option( $this->token . '-version', $this->version );
	} // End _log_version_number()
} // End Class



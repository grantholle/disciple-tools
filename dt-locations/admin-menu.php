<?php

/**
 * Disciple_Tools_Tabs
 *
 * @class   Disciple_Tools_Tabs
 * @version 0.1
 * @since   0.1
 * @package Disciple_Tools_Tabs
 * @author  Chasm.Solutions
 */

if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

class Disciple_Tools_Location_Tools_Menu {

    public $path;

    /**
     * Disciple_Tools The single instance of Disciple_Tools.
     *
     * @var    object
     * @access private
     * @since  0.1
     */
    private static $_instance = null;

    /**
     * Main Disciple_Tools_Tabs Instance
     *
     * Ensures only one instance of Disciple_Tools_Tabs is loaded or can be loaded.
     *
     * @since  0.1
     * @static
     * @see    Disciple_Tools()
     * @return Disciple_Tools_Location_Tools_Menu instance
     */
    public static function instance () {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    /**
     * Constructor function.
     *
     * @access public
     * @since  0.1
     */
    public function __construct () {
        $this->path  = plugin_dir_path( __DIR__ );

        add_action( 'admin_menu', [ $this, 'load_admin_menu_item' ] );
    } // End __construct()

    /**
     * Load Admin menu into Settings
     */
    public function load_admin_menu_item () {
        add_submenu_page( 'edit.php?post_type=locations', __( 'Import', 'disciple_tools' ), __( 'Import', 'disciple_tools' ), 'manage_options', 'disciple_tools_locations', [ $this, 'page_content' ] );
    }

    /**
     * Builds the tab bar
     *
     * @since 0.1
     */
    public function page_content() {


        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }

        /**
         * Begin Header & Tab Bar
         */
        if (isset( $_GET["tab"] )) {$tab = $_GET["tab"];
        } else {$tab = 'import';}

        $tab_link_pre = '<a href="edit.php?post_type=locations&page=disciple_tools_locations&tab=';
        $tab_link_post = '" class="nav-tab ';

        $html = '<div class="wrap">
            <h2>Import Locations</h2>
            <h2 class="nav-tab-wrapper">';

        $html .= $tab_link_pre . 'import' . $tab_link_post;
        if ($tab == 'import' || !isset( $tab )) {$html .= 'nav-tab-active';}
        $html .= '">Import</a>';
    
        $html .= $tab_link_pre . 'usa' . $tab_link_post;
        if ($tab == 'usa' ) {$html .= 'nav-tab-active';}
        $html .= '">USA</a>';
    
        $html .= $tab_link_pre . 'global' . $tab_link_post;
        if ($tab == 'global' ) {$html .= 'nav-tab-active';}
        $html .= '">Global</a>';
    
        $html .= $tab_link_pre . 'local_area' . $tab_link_post;
        if ($tab == 'local_area' ) {$html .= 'nav-tab-active';}
        $html .= '">Local Area</a>';

    

        $html .= '</h2>';

        echo $html; // Echo tabs

        $html = '';
        // End Tab Bar

        /**
         * Begin Page Content
         */
        switch ($tab) {

            case "local_area":
                
                break;
            case "global":
        
                break;
            case "import":
                $class_object = new Disciple_Tools_Locations_Tab_Import();
                $html .= '' . $class_object->page_contents();
                break;
            default:
                
                break;
        }

        $html .= '</div>'; // end div class wrap

        echo $html; // Echo contents
    }
}

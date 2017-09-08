<?php
/**
* Custom endpoints file
*
* @package  Disciple_Tools
* @category Plugin
* @author   Chasm.Solutions & Kingdom.Training
* @since    0.1
*/
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Disciple_Tools_Groups_Endpoints {

    private static $_instance = null;

    public static function instance () {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private $version = 1;
    private $context = "dt-hooks";
    private $namespace;
    private $contacts_instance;
    private $api_keys_controller;

    public function __construct()
    {
        $this->namespace = $this->context . "/v" . intval( $this->version );
        add_action( 'rest_api_init', [$this,  'add_api_routes'] );

        require_once( 'groups.php' );
        $this->groups_instance = new Disciple_Tools_Groups;

        $this->api_keys_controller = Disciple_Tools_Api_Keys::instance();
    }

    public function add_api_routes() {
        register_rest_route(
            $this->namespace, '/groups', [
                "methods" => "GET",
                "callback" => [$this, 'get_viewable_groups'],
            ]
        );
        register_rest_route(
            $this->namespace, '/groups-compact', [
                'methods' => 'GET',
                'callback' => [$this, 'get_groups_compact']
            ]
        );
        register_rest_route(
            $this->namespace, '/group/(?P<id>\d+)', [
                'methods' => 'POST',
                'callback' => [$this, 'update_group']
            ]
        );
        register_rest_route(
            $this->namespace, '/group/(?P<id>\d+)', [
                'methods' => 'GET',
                'callback' => [$this, 'get_group']
            ]
        );
    }

    public function get_viewable_groups( WP_REST_Request $request ) {
        $groups = Disciple_Tools_Groups::get_viewable_groups( true );
        if (is_wp_error( $groups )) {
            return $groups;
        }
        return $this->add_related_info_to_groups( $groups );
    }


    private function add_related_info_to_groups( WP_Query $groups ): array {
        p2p_type( 'groups_to_locations' )->each_connected( $groups, array(), 'locations' );
        p2p_type( 'contacts_to_groups' )->each_connected( $groups, array(), 'contacts' );
        $rv = array();
        foreach ($groups->posts as $group) {
            $meta_fields = get_post_custom( $group->ID );
            $group_array = $group->to_array();
            unset( $group_array['contacts'] );
            $group_array['permalink'] = get_post_permalink( $group->ID );
            $group_array['locations'] = array();
            foreach ( $group->locations as $location ) {
                $group_array['locations'][] = $location->post_title;
            }
            $group_array['leaders'] = array();
            $group_array['member_count'] = 0;
            foreach ( $group->contacts as $contact ) {
                if ((int) p2p_get_meta( $contact->p2p_id, 'leader' )) {
                    $group_array['leaders'][] = array(
                        'post_title' => $contact->post_title,
                        'permalink' => get_permalink( $contact->ID ),
                    );
                }
                $group_array['member_count']++;
            }
            foreach ( $meta_fields as $meta_key => $meta_value ) {
                if ( $meta_key == 'group_status' ) {
                    $group_array[$meta_key] = $meta_value[0];
                } else if ( $meta_key == 'last_modified' ) {
                    $group_array[$meta_key] = (int) $meta_value[0];
                }
            }
            $rv[] = $group_array;
        }
        return $rv;
    }


    public function get_groups_compact( WP_REST_Request $request ){
        $params = $request->get_params();
//        @todo check permissions
        $search = "";
        if (isset( $params['s'] )){
            $search = $params['s'];
        }
        $groups = Disciple_Tools_Groups::get_groups_compact( $search );
        return $groups;
    }

    public function update_group( WP_REST_Request $request ){
        $params = $request->get_params();
        $body = $request->get_json_params();
        if (isset( $params['id'] )){
            $result = Disciple_Tools_Groups::update_group( $params['id'], $body, true );
            if ( is_wp_error( $result ) ){
                return $result;
            } else {
                return new WP_REST_Response( $result );
            }
        } else {
            return new WP_Error( "update_contact", "Missing a valid contact id", ['status' => 400] );
        }
    }

    /**
     * Get a single group by ID
     *
     * @param  WP_REST_Request $request
     * @access public
     * @since  0.1
     * @return array|WP_Error The group on success
     */
    public function get_group( WP_REST_Request $request ){
        $params = $request->get_params();
        if (isset( $params['id'] )){
            $result = Disciple_Tools_groups::get_group( $params['id'], true );
            return $result; // Could be permission WP_Error
        } else {
            return new WP_Error( "get_group_error", "Please provide a valid id", ['status' => 400] );
        }
    }
}

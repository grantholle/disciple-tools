<?php
/**
 * Contains create, update and delete functions for groups, wrapping access to
 * the database
 *
 *
 * @package  Disciple_Tools
 * @category Plugin
 * @author   Chasm.Solutions & Kingdom.Training
 * @since    0.1
 */
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/**
 * Class Disciple_Tools_Contacts
 *
 * Functions for creating, finding, updating or deleting contacts
 */


class Disciple_Tools_Groups {

    public static $address_types;

    public function __construct(){
        add_action(
            'init', function(){
                self::$address_types = dt_address_metabox()->get_address_type_list( "groups" );
            }
        );

    }

    public static function get_groups_compact ( $search ){
        $query_args = array(
            'post_type' => 'groups',
            'orderby' => 'ID',
            's' => $search
        );
        $query = new WP_Query( $query_args );
        $list = [];
        foreach ($query->posts as $post){
            $list[] = ["ID" => $post->ID, "name" => $post->post_title];
        }
        return $list;
    }

    public static function can_view_group( $group_id ){
        if ( current_user_can( 'view_any_group' )){
            return true;
        } else {
            return true;
//            @todo check is the user can see this group
//            $user = wp_get_current_user();
//            $assigned_to = get_post_meta( $group_id, "assigned_to", true );
//            if ( $assigned_to === "user-".$user->ID ){
//                return true;
//            }
//          @todo check if the user is following this group
        }
        return false;
    }

    public static function can_update_group( $group_id ){
//        @todo check if the user can update the group
        return true;
    }

    public static function can_access_groups() {
        return current_user_can( "edit_group" ) && current_user_can( "read_group" ) && current_user_can( "edit_groups" );
    }

    public static function can_view_all_groups() {
        return current_user_can( "read_private_groups" );
    }

    public static function get_viewable_groups( bool $check_permissions = true ) {
        if ($check_permissions && ! self::can_access_groups()) {
            return new WP_Error( __FUNCTION__, __( "You do not have access to these groups" ), ['status' => 403] );
        }
        $current_user = wp_get_current_user();

        $query_args = array(
            'post_type' => 'groups',
            'nopaging' => true,
        );
        if (! self::can_view_all_groups()) {
            // TODO filter just by own groups
            return new WP_Error( __FUNCTION__, __( "Unimplemented" ) );
        }
        return new WP_Query( $query_args );
    }

    public static function get_group( int $group_id, bool $check_permissions = true ){
        if ($check_permissions && ! self::can_view_group( $group_id )) {
            return new WP_Error( __FUNCTION__, __( "No permissions to read group" ), ['status' => 403] );
        }

        $group = get_post( $group_id );
        if ( $group ){
            $fields = [];

            $locations = get_posts(
                [
                    'connected_type' => 'groups_to_locations',
                    'connected_items' => $group,
                    'nopaging' => true,
                    'suppress_filters' => false
                ]
            );
            foreach($locations as $l) {
                $l->permalink = get_permalink( $l->ID );
            }
            $fields[ "locations" ] = $locations;

            $members = get_posts(
                [
                    'connected_type' => 'contacts_to_groups',
                    'connected_items' => $group,
                    'nopaging' => true,
                    'suppress_filters' => false
                ]
            );
            foreach($members as $l) {
                $l->permalink = get_permalink( $l->ID );
            }
            $fields[ "members" ] = $members;


            $meta_fields = get_post_custom( $group_id );
            foreach ($meta_fields as $key =>$value){
                if ( strpos( $key, "address" ) === 0){
                    if ( strpos( $key, "_details" ) === false ){

                        $details = [];
                        if ( isset( $meta_fields[$key.'_details'][0] )){
                            $details = unserialize( $meta_fields[$key.'_details'][0] );
                        }
                        $details["value"] = $value[0];
                        $details["key"] = $key;
                        if ( isset( $details["type"] )){
                            $details["type_label"] = self::$address_types[$details["type"]]["label"];
                        }
                        $fields[ "address" ][] = $details;
                    }
                } else if ($key === "assigned_to") {
                    if ($value){
                        $meta_array = explode( '-', $value[0] ); // Separate the type and id
                        $type = $meta_array[0]; // Build variables
                        if (isset( $meta_array[1] )){
                            $id = $meta_array[1];
                            if ( $type == 'user' ) {
                                $user = get_user_by( 'id', $id );
                                $fields[$key] = [ "id" => $id, "type" => $type, "display" => $user->display_name, "assigned-to" => $value[0] ];
                            }
                        }
                    }
                } else {
                    $fields[$key] = $value[0];
                }
            }
            $fields["ID"] = $group->ID;
            $fields["name"] = $group->post_title;
            return $fields;
        } else {
             return new WP_Error( __FUNCTION__, __( "No group found with ID" ), [ 'contact_id' => $group_id ] );
        }
    }


    /**
     * Make sure there are no extra or misspelled fields
     * Make sure the field values are the correct format
     *
     * @param  $fields, the group meta fields
     * @param  $post_id, the id of the group
     * @access private
     * @since  0.1
     * @return array
     */
    private static function check_for_invalid_fields( $fields, int $post_id = null ){
        $bad_fields = [];
        $group_fields = Disciple_Tools_Groups_Post_Type::instance()->get_custom_fields_settings( isset( $post_id ), $post_id );
        $group_model_fields['title'] = "";
        foreach($fields as $field => $value){
            if (!isset( $group_fields[$field] )){
                $bad_fields[] = $field;
            }
        }
        return $bad_fields;
    }

    /**
     * Update an existing Group
     *
     * @param  int $group_id, the post id for the group
     * @param  array $fields, the meta fields
     * @param  bool $check_permissions
     * @access public
     * @since  0.1
     * @return int | WP_Error of group ID
     */
    public static function update_group( int $group_id, array $fields, bool $check_permissions = true ){

        if ($check_permissions && ! self::can_update_group( $group_id )) {
            return new WP_Error( __FUNCTION__, __( "You do have permission for this" ), ['status' => 403] );
        }

        $post = get_post( $group_id );
        if (isset( $fields['id'] )){
            unset( $fields['id'] );
        }

        if (!$post){
            return new WP_Error( __FUNCTION__, __( "Group does not exist" ) );
        }
        $bad_fields = self::check_for_invalid_fields( $fields, $group_id );
        if (!empty( $bad_fields )){
            return new WP_Error( __FUNCTION__, __( "These fields do not exist" ), ['bad_fields' => $bad_fields] );
        }

        if ( isset( $fields['title'] ) ){
            wp_update_post( ['ID'=>$group_id, 'post_title'=>$fields['title']] );
        }

        foreach($fields as $field_id => $value){
            update_post_meta( $group_id, $field_id, $value );
        }
        return self::get_group( $group_id, true );
    }


}

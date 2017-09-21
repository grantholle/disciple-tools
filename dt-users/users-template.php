<?php
/**
 * Presenter template for theme support
 *
 * @package  Disciple_Tools
 * @category Plugin
 * @author   Chasm.Solutions & Kingdom.Training
 * @since    0.1
 */
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/** Functions to output data for the theme. @see Buddypress bp-members-template.php or bp-groups-template.php for an example of the role of this page  */


/**
 * Prepares the keys of user connections for WP_Query
 * This function builds the array for the meta_query used in WP_Query to retrieve only records associated with
 * the user or the teams the user is connected to.
 *
 * Example return:
 * Array
 *   (
 *       [relation] => OR
[0] => Array
(
[key] => assigned_to
[value] => user-1
)

[1] => Array
(
[key] => assigned_to
[value] => group-1
)
)
 *
 * @return array
 */
function dt_get_user_associations () {

    // Set variables
    global $wpdb;
    $user_connections = [];

    // Set constructor
    $user_connections['relation'] = 'OR';

    // Get current user ID and build meta_key for current user
    $user_id = get_current_user_id();
    $user_key_value = 'user-' . $user_id;
    $user_connections[] = ['key' => 'assigned_to', 'value' => $user_key_value ] ;

    // Build arrays for current groups connected to user
    $sql = $wpdb->prepare(
        "SELECT
            `$wpdb->term_relationships`.`term_taxonomy_id`
        FROM
            `$wpdb->term_relationships`
        WHERE
            object_id = %d",
        $user_id
    );
    $results = $wpdb->get_results( $sql, ARRAY_A );

    foreach ($results as $result) {
        $user_connections[] = ['key' => 'assigned_to', 'value' => 'group-' . $result['term_taxonomy_id']  ];
    }

    // Return array to the meta_query
    return $user_connections;
}


/**
 * Gets team contacts for a specified user_id
 *
 * Example return:
 * Array
(
[relation] => OR
[0] => Array
(
[key] => assigned_to
[value] => user-1
)

[1] => Array
(
[key] => assigned_to
[value] => group-1
)
)
 *
 * @return array
 */
function dt_get_team_contacts( $user_id ) {
    // get variables
    global $wpdb;
    $user_connections = [];
    $user_connections['relation'] = 'OR';
    $members = [];

    // First Query
    // Build arrays for current groups connected to user
    $sql = $wpdb->prepare(
        "SELECT
            DISTINCT `$wpdb->term_relationships`.`term_taxonomy_id`
        FROM
            `$wpdb->term_relationships`
        INNER JOIN
            `$wpdb->term_taxonomy`
        ON
            `$wpdb->term_relationships`.`term_taxonomy_id` = `$wpdb->term_taxonomy`.`term_taxonomy_id`
        WHERE
            object_id  = %d
            AND taxonomy = 'user-group'",
        $user_id
    );
    $results = $wpdb->get_results( $sql, ARRAY_A );


    // Loop
    foreach ($results as $result) {
        // create the meta query for the group
        $user_connections[] = ['key' => 'assigned_to', 'value' => 'group-' . $result['term_taxonomy_id']  ];

        // Second Query
        // query a member list for this group
        $sql = $wpdb->prepare(
            "SELECT
                `$wpdb->term_relationships`.object_id
            FROM
                `$wpdb->term_relationships`
            WHERE
                term_taxonomy_id = %d",
            $result['term_taxonomy_id']
        );

        // build list of member ids who are part of the team
        $results2 = $wpdb->get_results( $sql, ARRAY_A );

        // Inner Loop
        foreach ($results2 as $result2) {

            if($result2['object_id'] != $user_id) {
                $members[] = $result2['object_id'];
            }
        }
    }

    $members = array_unique( $members );

    foreach($members as $member) {
        $user_connections[] = ['key' => 'assigned_to', 'value' => 'user-' . $member  ];
    }

    // return
    return $user_connections;

}

/**
 * Get current user notification options
 *
 * @return mixed
 */
function dt_get_notification_options() {
    $user_id = get_current_user_id();
    
    // check for default options
    if(!get_user_meta( get_current_user_id(), 'dt_notification_options' )) {
        $notifications_default = [
            'new' => [
                'web' => true,
                'email' => true,
            ],
            'mentions' => [
                'web' => true,
                'email' => true,
            ],
            'updates' => [
                'web' => true,
                'email' => true,
            ],
            'changes' => [
                'web' => true,
                'email' => true,
            ],
            'milestones' => [
                'web' => true,
                'email' => true,
            ]
        ];
        add_user_meta( $user_id, 'dt_notification_options', $notifications_default, true );
    }
    
    return get_user_meta( get_current_user_id(), 'dt_notification_options', true );
}

/**
 * Echos user display name
 * @param $user_id
 */
function dt_user_display_name( $user_id ) {
    echo dt_get_user_display_name( $user_id );
}

    /**
     * Returns user display name
     * @param $user_id
     *
     * @return string
     */
function dt_get_user_display_name( $user_id ) {
    $user = get_userdata( $user_id );
    return $user->display_name;
}

function dt_modify_profile_fields( $profile_fields )
{
    
    // Add new fields
    $profile_fields['personal_phone'] = 'Personal Phone';
    $profile_fields['personal_email'] = 'Personal Email';
    $profile_fields['personal_facebook'] = 'Personal Facebook';
    $profile_fields['work_alias'] = 'Work Alias';
    $profile_fields['work_phone'] = 'Work Phone';
    $profile_fields['work_email'] = 'Work Email';
    $profile_fields['work_facebook'] = 'Work Facebook';
    $profile_fields['work_twitter'] = 'Twitter Username';
    $profile_fields['work_whatsapp'] = 'Work WhatsApp';
    $profile_fields['work_viber'] = 'Work Viber';
    $profile_fields['work_telegram'] = 'Work Telegram';
    $profile_fields['contact_id'] = 'Contact Id';
    
    return $profile_fields;
    
}
if (is_admin()) {
    // Add elements to the contact section of the profile.
    add_filter( 'user_contactmethods', [$this, 'modify_profile_fields'] );
}

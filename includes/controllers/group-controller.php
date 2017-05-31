<?php
/**
* Class Group_Controller
*
* Functions for creating, finding, updating or deleting groups
*/

class Group_Controller
{
    public static $group_fields;

    public function __construct()
    {
        add_action('init', function () {
            self::$group_fields = Disciple_Tools_Group_Post_Type::instance()->get_custom_fields_settings();
        });

    }
}
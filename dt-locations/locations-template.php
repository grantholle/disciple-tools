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
     * Creates a dropdown of the states with the state key as the value.
     *
     * @return string
     */
    function dt_get_states_key_dropdown_not_installed () {
        
        $dir_contents = json_decode( file_get_contents( plugin_dir_path( __FILE__ ) . 'json/data-file-directory.json' ) ); // get directory & build dropdown
        
        $dropdown = '<select name="states-dropdown">';
        
        
        foreach ($dir_contents->USA_states as $value) {
            $disabled = '';
            
            $dropdown .= '<option value="' . $value->key . '" ';
            if (get_option( '_installed_us_county_'.$value->key )) {$dropdown .= ' disabled';
                $disabled = ' (Installed)';}
            elseif (isset( $_POST['states-dropdown'] ) && $_POST['states-dropdown'] == $value->key) {$dropdown .= ' selected';}
            $dropdown .= '>' . $value->name . $disabled;
            $dropdown .= '</option>';
        }
        $dropdown .= '</select>';
        
        return $dropdown;
    }
    
    /**
     * Creates a dropdown of the states with the state key as the value.
     *
     * @return string
     */
    function dt_get_states_key_dropdown_installed () {
        
        $dir_contents = json_decode( file_get_contents( plugin_dir_path( __FILE__ ) . 'json/data-file-directory.json' ) ); // get directory & build dropdown
        
        $dropdown = '<select name="states-dropdown">';
        
        
        foreach ($dir_contents->USA_states as $value) {
            $disabled = '';
            
            $dropdown .= '<option value="' . $value->key . '" ';
            if (!get_option( '_installed_us_county_'.$value->key )) {$dropdown .= ' disabled';
                $disabled = ' (Not Installed)';}
            elseif (isset( $_POST['states-dropdown'] ) && $_POST['states-dropdown'] == $value->key) {$dropdown .= ' selected';}
            $dropdown .= '>' . $value->name . $disabled;
            $dropdown .= '</option>';
        }
        $dropdown .= '</select>';
        
        return $dropdown;
    }
    
    /**
     * Creates a dropdown for the countries
     *
     * @return string
     */
    function dt_get_country_key_dropdown () {
        
        $dir_contents = json_decode( file_get_contents( plugin_dir_path( __FILE__ ) . 'json/data-file-directory.json' ) ); // get directory & build dropdown
        
        $dropdown = '<select name="country-dropdown">';
        
        foreach ($dir_contents->countries as $value) {
            $dropdown .= '<option value="' . $value->key . '" ';
            if (isset( $_POST['country-dropdown'] ) && $_POST['country-dropdown'] == $value->key) {$dropdown .= 'selected';}
            $dropdown .= '>' . $value->name;
            $dropdown .= '</option>';
        }
        $dropdown .= '</select>';
        
        return $dropdown;
    }
    
    
    /**
     * Returns directory in an array
     *
     * @usage $directory = dt_get_data_file_directory ();
    print_r($directory->USA_states->{'08'}->name);
     *
     * @return array|mixed|object
     */
    function dt_get_data_file_directory () {
        return json_decode( file_get_contents( plugin_dir_path( __FILE__ ) . 'json/data-file-directory.json' ) );
    }
    
    function dt_get_us_county_file_directory () {
        return json_decode( file_get_contents( plugin_dir_path( __FILE__ ) . 'json/usa-county-codes.json' ) );
    }
    
    /**
     * Returns the full file path for KML file using the state key
     *
     * @param  $key
     * @return string
     */
    function dt_get_file_path_by_key ( $state ) {
        $directory = dt_get_data_file_directory(); // call directory
        return plugin_dir_path( __FILE__ ) . 'data/' . $directory->USA_states->{$state}->file; // build url
    }
    
    /**
     * Gets zoom size for chart
     *
     * @param  int   Number supplied from the AREALAND attribute of the census data. Based on this number we can calculate approximate zoom level.
     * @return int
     */
    function dt_get_zoom_size_LL ( $tract_size ) {
        if($tract_size > 1000000000) {
            return 8;
        } elseif ($tract_size > 100000000) {
            return 10;
        } elseif ($tract_size > 50000000) {
            return 12;
        } elseif ($tract_size > 10000000) {
            return 13;
        } else {
            return 14;
        }
    }
    
    /**
     * Gets the meta information for a polygon or array of polygons
     *
     * @param  $geoid        (int) Can be full 9 digit geoid or 5 digit state/county code
     * @return array
     */
    function dt_get_coordinates_meta ( $geoid ) {
        global $wpdb;
        
        //* query */
        $county_coords = $wpdb->get_results( "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key LIKE 'polygon_$geoid%'", ARRAY_A );
        
        /* build full json of coodinates*/
        $rows = count( $county_coords );
        $string = '[';
        $i = 0;
        foreach($county_coords as $value) {
            $string .= $value['meta_value'];
            if($rows > $i + 1 ) {$string .= ','; }
            $i++;
        }
        $string .= ']';
        
        $coords_objects = json_decode( $string );
        
        /* set values */
        $high_lng_e = -9999999; //will hold max val
        $high_lat_n = -9999999; //will hold max val
        $low_lng_w = 9999999; //will hold max val
        $low_lat_s = 9999999; //will hold max val
        
        /* filter for high and lows*/
        foreach ($coords_objects as $coords) {
            foreach($coords as $k=>$v)
            {
                if($v->lng > $high_lng_e)
                {
                    $high_lng_e = $v->lng;
                }
                if($v->lng < $low_lng_w)
                {
                    $low_lng_w = $v->lng;
                }
                if($v->lat > $high_lat_n)
                {
                    $high_lat_n = $v->lat;
                }
                if($v->lat < $low_lat_s)
                {
                    $low_lat_s = $v->lat;
                }
            }
        }
        //    print ' | n : '. $high_lat_n;
        //    print ' | s : '. $low_lat_s;
        //    print ' | e : '. $high_lng_e;
        //    print ' | w : '. $low_lng_w;
        
        
        // calculate centers
        $lng_size = $high_lng_e - $low_lng_w;
        $half_lng_difference = $lng_size / 2;
        $center_lng = $high_lng_e - $half_lng_difference;
        //    print ' | lng size: '.$lng_size ;
        
        $lat_size = $high_lat_n - $low_lat_s;
        $half_lat_difference = $lat_size / 2;
        $center_lat = $high_lat_n - $half_lat_difference;
        //    print ' | lat size: '.$lat_size ;
        
        // get zoom level
        if($lat_size > 3 || $lng_size > 3) {
            $zoom = 6;
        } elseif ($lat_size > 2 || $lng_size > 2) {
            $zoom = 7;
        } elseif ($lat_size > 1 || $lng_size > 1) {
            $zoom = 8;
        } elseif ($lat_size > .4 || $lng_size > .4) {
            $zoom = 9;
        } elseif ($lat_size > .2 || $lng_size > .2) {
            $zoom = 10;
        } elseif ($lat_size > .1 || $lng_size > .1) {
            $zoom = 11;
        } elseif ($lat_size > .07 || $lng_size > .07) {
            $zoom = 12;
        } elseif ($lat_size > .01 || $lng_size > .01) {
            $zoom = 13;
        } else {
            $zoom = 14;
        }
        
        //    print ' | zoom: '.$zoom ;
        
        $meta = ["center_lng" => (float) $center_lng,"center_lat" => (float) $center_lat,"ne" => $high_lat_n.','.$high_lng_e,"sw" => $low_lat_s.','.$low_lng_w ,"zoom" => (float) $zoom];
        
        return $meta;
    }
    
    /**
     * Get coordinates from KML file
     *
     * @param  $state
     * @param  $geoid
     * @return string
     */
    function dt_get_placemark_zoom ( $geoid, $state ) {
        
        $file = get_file_path_by_key_LL( $state );
        
        $kml_object = simplexml_load_file( $file );
        
        $ALAND = '';
        
        foreach ($kml_object->Document->Folder->Placemark as $mark) {
            $element_geoid = $mark->ExtendedData->SchemaData->SimpleData[4];
            
            if ($element_geoid == $geoid) { // FILTER RETURN TO TRACT NUMBER
                $ALAND = $mark->ExtendedData->SchemaData->SimpleData[7];
            }
        }
        
        $zoom = get_zoom_size_LL( $ALAND );
        
        return $zoom;
    }

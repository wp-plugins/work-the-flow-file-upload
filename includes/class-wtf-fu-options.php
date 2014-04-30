<?php

/*  Copyright 2013  Lynton Reed  (email : lynton@wtf-fu.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require_once plugin_dir_path( __FILE__ ) . 'class-wtf-fu-option-definitions.php';
require_once plugin_dir_path( __FILE__ ) . 'wtf-fu-common-utils.php';


/**
 * Class providing access for the workflow and user settings data.
 * Data is maintained using WordPress 'options' and 'user-options' tables.
 * 
 * This class provides public access for retrieving and updating options.
 * see 
 * 
 * See class-wtf-fu-options-admin.php for the admin only access methods.
 * 
 */
class Wtf_Fu_Options {
     
/*
 * User Options Methods.
 * These methods access the wp_usermeta table for updating and retrieveing 
 * workflow and stage metadata on a per user basis.
 */   
    
    /**
     * Update or create a wp user_option for the current stage number for a user and workflow.
     * @param unknown_type $user_id     defaults to 0 for current user.
     * @param unknown_type $workflow_id
     * @param unknown_type $stage_no
     */
    static function update_user_workflow_stage($workflow_id, $stage_no, $user_id = 0) {	   
        global $wpdb; // declare access to the global database variable so we can correctly retrieve table prefix.
       
        if ((int) $user_id === 0) {
            $user_id = get_current_user_id(); //getUserInfo('ID');
        }       
        $key = Wtf_Fu_Option_Definitions::get_user_workflow_options_key($workflow_id); 
        $ret = update_user_meta( $user_id, $key , array('id' => $workflow_id, 'stage' => $stage_no) );
        
        return $ret;
    }


    /**
     * Get a users stage in a workflow.
     * Returns false if the user workflow options are not found.
     * Defaults to current user.
     * @param unknown_type $user_id
     * @param unknown_type $workflow_id
     */
    static function get_user_workflow_stage($workflow_id, $user_id = 0){
        $stage = false;
        $ops = self::get_user_workflow_options($workflow_id, $user_id, true);
        if ($ops === false || !array_key_exists('stage', $ops)) {
            log_me("Users 'stage' setting for the workflow $workflow_id not found.");
        }
        return $ops['stage'];
    }
    
    /**
     * Returns the users options associated with the state of progress in 
     * a given workflow from the user_meta table.
     * 
     * returns whatever is in the table for this key
     * at time of writing this should be
     * 
     * array('id' => <workflow_id> 'stage' <current stage for user>)
     * 
     * Defaults to current user if user_id == 0 (default)
     * 
     */
    static function get_user_workflow_options($workflow_id, $user_id = 0, 
            $create_if_not_exists = false) {
        
        /*
         * Default to current user if 0 is passed in.
         */
        if ($user_id == 0) {
            $user_id = get_current_user_id(); 
        }

        /* 
         * If user_id is still 0 then the user is not logged in.
         * Return false and let workflow controller handle the action to 
         * take for non logged in users.
         */
        if ($user_id == 0) {
            return false;
        }

        $key = Wtf_Fu_Option_Definitions
                ::get_user_workflow_options_key($workflow_id);
        
        global $wpdb; 
        
        $ops = get_user_meta($user_id, $key, true);
        
        // log_me(array("user_meta ret=" => array($ops, $key, $user_id)));
        if ($ops == false) {
            if ($create_if_not_exists === true) {  
                $ops = array('id' => $workflow_id, 'stage' => 0);
                // log_me("Creating User workflow options for user_id = $user_id workflow_id = $workflow_id using key = $key");
                $ret = add_user_meta($user_id, $key, $ops);
                if ($ret === false) {
                    log_me("WARNING Unable to create User workflow options metadata for user_id = $user_id workflow_id = $workflow_id using key = $key"); 
                }                
            }        
        }
        return $ops;
    }

/* 
 * End User Options Methods. 
 */
    
/*
 * Non User related methods for setting options for workflows and stages. 
 */
    
    /**
     * 
     * @param type $workflow_id
     * @param type $stage
     * @return type bool or stage options array if stage exists.
     */
    static function stage_exists($workflow_id, $stage_id ) {
        return get_option(Wtf_Fu_Option_Definitions
                ::get_workflow_stage_key($workflow_id, $stage_id));
    }

    
    static function get_workflow_stage_options($workflow_id, $stage_id){
        return get_option(
            Wtf_Fu_Option_Definitions
                ::get_workflow_stage_key($workflow_id, $stage_id));
    }
    
    static function get_upload_options() {
        return get_option(
                Wtf_Fu_Option_Definitions::get_upload_options_key());
    }
    
    static function get_plugin_options() {
        return get_option(
                Wtf_Fu_Option_Definitions::get_plugin_options_key());
    }

    /**
     * Return the workflow options of a workflow id.
     * or a single option matching array element if $key is not null.
     * returns the full array of workflow options by default.
     * @param type $workflow_id
     * @return array of workflow options for this id.
     */
    static function get_workflow_options($workflow_id, $key = null) {
        $option_key = 
                Wtf_Fu_Option_Definitions::get_workflow_options_key($workflow_id);       
        
        $option = get_option($option_key);      
        if (!empty($key)) {
            return $option[$key];
        }
        return $option;
    }
    
    
    /**
     * Get all the stages options in a given workflow.
     * If keys_only is true then only the keys and id's are returned
     * and the options are not. By default $keys_only is true and options
     * values are not retrieved. 
     */
    static function get_workflow_stages($workflow_id, $keys_only = true) {        
        global $wpdb;
        $keys = array();        
        
        /*
         * NOTE :
         * MySQL will ignore the parenthesis in the REGEXP but we will use them 
         * to parse out the id later with preg_match.
         */
        $pattern = '^' . 
            Wtf_Fu_Option_Definitions
                ::get_workflow_stage_key($workflow_id, '([0-9]+)') . '$';
        
        
        $results = $wpdb->get_results(
            $wpdb->prepare("SELECT option_name FROM $wpdb->options WHERE option_name REGEXP %s" , $pattern));
        
        foreach ($results as $row) { 
            $match = array();
            if ( preg_match( '/' . $pattern . '/', $row->option_name, $match) ){
                if (! $keys_only) {
                    $keys[$row->option_name] = array( 
                        'key_id' => $match[1], 
                        'options' => get_option($row->option_name)
                    );
                } else { 
                    $keys[$row->option_name] = array('key_id' => $match[1]);
                }
            }            
        }
        return $keys;
    } 

    
    /**
     * returns an array of all exisiting workflow stage ids.
     * sorted in numerical order.
     */
    static function getWorkFlowStageIDs($wf_index) {
        $ids = array();
        $stage_keys = self::get_workflow_stages($wf_index);

        foreach ($stage_keys as $v) {
            $ids[] = (int) $v['key_id']; 
        }
        asort($ids, SORT_NUMERIC);

      //  log_me(array('getWorkFlowStageIDs' => $ids));
        return $ids;
    }   
    
    
} // end class.

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

if (!is_admin()) {
    die('You do not have administrator access to this file.');
}

require_once plugin_dir_path(__FILE__) . '../../includes/class-wtf-fu-options.php';

/**
 * class-wtf-fu-options-admin
 * 
 * Administrator only methods to update and retrieve options from the database.
 * For public access methods see the common includes directory file
 * class-wtf-fu-options.php
 *
 * @author user
 */
class Wtf_Fu_Options_Admin {

    /**
     * Add a new empty workflow option for the given new ID.
     * Assumes ID is valid and does not already exist.
     * Returns the new option key if successful or exits with error.
     */
    public static function add_new_workflow_option($workflow_id, $options = null) {
        $key = Wtf_Fu_Option_Definitions::get_workflow_options_key($workflow_id);
        if (false === add_option($key, $options)) {
            log_me("ERROR : adding new workflow failed for key = $key");
        }
        return $key;
    }

    /**
     * get the next available workflow id for creating new workflows
     * if use_vacant_slots === true then the first empty slot is returned
     * otherwise the highest id + 1 is retuned.
     */
    public static function get_next_available_workflow_id($use_vacant_slots = true) {
        
        $ids = Wtf_Fu_Options::get_all_workflow_ids();

        if ($use_vacant_slots === true) {
            for ($i = 1;; $i++) {
                if (!in_array($i, $ids)) {
                    return $i;
                }
            } // forever until unused one is found.
        }
        // add to new slot at the end of exisiting one.
        $new_id = 1;
        if (!empty($ids)) {
            $new_id = $ids[count($ids) - 1] + 1;
        }
        return $new_id;
    }

    /**
     * Utility to change an option name in the database.
     * 
     * Maybe will be used later for updating an stage option key
     * when deleting a stage and resequencing the other stages
     * to keep them consecutive.
     * 
     * Currently not required or used.
     *  
     * @param type $old_key
     * @param type $new_key
     */
    public static function update_option_key($old_key, $new_key) {
        global $wpdb;
        $ret = $wpdb->query($wpdb->prepare(
                        "update $wpdb->options SET option_name=%s WHERE option_name=%s", $new_key, $old_key)
        );
        return ret;
    }


    /**
     * Deletes a workflow from the options table.
     * @param type $id
     */
    public static function delete_workflow($id) {
        log_me("Deleting workflow $id");
        if (empty($id)) {
            log_me("ERROR: Cannot delete an empty workflow id");
        }

        // first delete the workflow stages.
        $stages = Wtf_Fu_Options::get_workflow_stages($id, true);
        foreach ($stages as $k => $v) {
            $ret = delete_option($k);
            if ($ret === false) {
                log_me("ERROR : could not delete workflow [$id] stage [{$v['key_id']}] with option key =$k");
            }
        }

        // delete the workflow options.
        $key = Wtf_Fu_Option_Definitions::get_workflow_options_key($id);
        $ret = delete_option($key);

        if ($ret === false) {
            log_me("ERROR : could not delete workflow [$id] with option key=$key");
        }
    }

    /**
     * Remove a stage from a workflow and adjust other stages id's if needed so 
     * that they remain consecutive.
     * 
     * @param type $wfid
     * @param type $stage_id
     */
    public static function delete_stage_and_reorder($wfid, $stage_id) {

        $key = Wtf_Fu_Option_Definitions::get_workflow_stage_key($wfid, $stage_id);
        delete_option($key);

        self::reorder_stages($wfid);
    }

    /**
     * Forces all stages to be ordered consecutively starting from 0.
     * 
     * If a stage is modified then any users at this modified stage are checked 
     * and updated to the new stage.
     *
     * returns true if any reordering was done or false otherwise.
     * 
     * @param type $wfid the numeric workflow id.
     */
    public static function reorder_stages($wfid) {

        $stages = Wtf_Fu_Options::get_stage_ids_in_order($wfid);
        $count = count($stages);

        if ($count < 1) {
            return false;
        }

        $ret = false;
        $i = -1;

        //log_me(array('stages' => $stages));
        foreach ($stages as $v) {

            $i++;
            
            // log_me("i=$i, v['key_id'] ={$v['key_id']}");
            
            if ($v == $i) {
                continue;
            }

            /*
             * found a gap e.g. $i = 5 'key_id' = 6
             * note that 'key_id' is alway >= $i
             * 
             * move this stage to stage $i
             */
            $stage_key = Wtf_Fu_Option_Definitions::get_workflow_stage_key($wfid, $v);
            $stage_options = get_option($stage_key);

            $new_key = Wtf_Fu_Option_Definitions::get_workflow_stage_key($wfid, $i);

            // log_me("reorder_stages() moving stage {$k} -> {$new_key} "); 

            add_option($new_key, $stage_options);

            if (false === delete_option($stage_key)) {
                log_me("ERROR reordering stages, could not remove the relocated stage with key $stage_key when moving from stage $v to $i");
            }

            /*
             * update any workflow users currently at this stage to the new stage 
             * value. 
             */
            Wtf_Fu_Options_Admin::update_all_users_workflow_stage_settings($wfid, $v, $i);
            $ret = true; // modification has occurred.
        }

        return $ret;
    }

    /**
     * Updates all a workflows active users setting with a stage nuber change.
     * 
     * This is called when a stage number needs alteration after a workflow stage
     * is deleted and the stage numbers have been re-rodered.
     *
     * 
     * @param type $wfid
     * @param type $current_stage
     * @param type $new_stage
     */
    private static function update_all_users_workflow_stage_settings(
    $wfid, $current_stage, $new_stage) {


        $wf_users = Wtf_Fu_Options::get_workflow_users($wfid);

        // log_me(array( '$wf_users' => $wf_users));

        foreach ($wf_users as $user_id => $v) {


            if ($v['workflow_settings']['stage'] == $current_stage) {

                Wtf_Fu_Options::update_user_workflow_stage($wfid, $new_stage, $user_id);

                log_me("adjusting user stage for wfid = $wfid, new_stage = $new_stage, user_id = $user_id");
            }
        }
    }
    
    
   /**
     * Creates a new workflow from a restored array from a json file upload
     * 
     * @param type $workflow  the php object representing the restored file.
     */
    public static function create_workflow($workflow) {
        
        $version = $workflow['version'];
        $workflow_options = $workflow['options'];
        $workflow_stages = $workflow['stages'];
        
        log_me($workflow_options);
        
        // Creates a new workflow with a clone of the options.
        $workflow_id = Wtf_Fu_Options_Admin::add_new_workflow($workflow_options);
                
        foreach ($workflow_stages as $stage_key => $values) {
            $stage_key = Wtf_Fu_Options_Admin::add_new_workflow_stage_options($workflow_id, $values);          
        }
        
        /*
         *  If versions are different then filter against the default options.
         */
        if( !version_compare($version, Wtf_Fu::VERSION, '==')) {   
            Wtf_Fu_Options_Admin::sync_workflow($workflow_id);
        }
        
        return $workflow_id;
    }         

    /**
     * 
     */

    /**
     * Adds a new workflow, if no options passed the defaults are used.
     * Note no stages are added just the workflow options.
     * 
     * @param type $options 
     * @return type string the new workflow index.
     */
    public static function add_new_workflow($options = null) {

        if (!isset($options)) {

            // use the default workflow options. 
            $options = Wtf_Fu_Option_Definitions
                    ::get_instance()->get_page_option_fields_default_values(
                    wtf_fu_DEFAULTS_WORKFLOW_KEY);
        }

        $new_wf_index = self::get_next_available_workflow_id();

        /* set the id */
        $options['id'] = $new_wf_index;

        self::add_new_workflow_option($new_wf_index, $options);

        return $new_wf_index; // the new workflow id.
    }


    /**
     * Clones a workflow using an existing one values.
     * @param unknown_type $wf_index
     */
    public static function clone_workflow($current_wf_index) {
        if (empty($current_wf_index)) {
            die("Cannot clone an empty workflow id");
        }

        // array of the existing workflow options to clone.
        $options = get_option(Wtf_Fu_Option_Definitions::get_workflow_options_key($current_wf_index));

        if ($options === false) {
            die("could not find workflow to clone.");
        }

        $new_wf_index = self::get_next_available_workflow_id();

        $options['id'] = $new_wf_index;
        $options['name'] = "Copy of " . $options['name'];

        $new_wf_key = self::add_new_workflow_option($new_wf_index, $options);

        // now clone the stages.
        // retrieve all the workflow stages with option values from the database.
        $allstages = Wtf_Fu_Options::get_workflow_stages($current_wf_index, false);

        foreach ($allstages as $old_stage) {
            self::add_new_workflow_stage_options($new_wf_index, $old_stage);
        }
    }

    /**
     * adds new stage options for a workflow.
     * assumes the index for the stage is available in $stage_options => 'key_id'
     * @param type $new_wf_index
     * @param type $stage_options
     */
    public static function add_new_workflow_stage_options($wf_index, $stage) {

        //log_me(array("adding stage key-id = {$stage['key_id']} to workflow $wf_index" => $stage));

        if (!array_key_exists('key_id', $stage)) {
            die("stage index to add is unavailable in passed stage options => 'key_id'");
        }
        $new_stage_key = Wtf_Fu_Option_Definitions::get_workflow_stage_key($wf_index, $stage['key_id']);
        if (!add_option($new_stage_key, $stage['options'])) {
            die("adding cloned stage failed");
        }
    }

    /**
     * Syncs up a workflow and its stages with the default options values.
     * 
     * @param type $workflow_id
     */
    public static function sync_workflow($workflow_id) {

        $default_workflow_options = Wtf_Fu_Option_Definitions::get_instance()->get_page_option_fields_default_values(wtf_fu_DEFAULTS_WORKFLOW_KEY);
        $default_stage_options = Wtf_Fu_Option_Definitions::get_instance()->get_page_option_fields_default_values(wtf_fu_DEFAULTS_STAGE_KEY);

        $workflow_key = Wtf_Fu_Option_Definitions::get_workflow_options_key($workflow_id);

        $options = get_option($workflow_key);

        Wtf_Fu_Options::update_options_from_default_options($workflow_key, $options, $default_workflow_options);

        foreach (Wtf_Fu_Options::get_workflow_stages($workflow_id, false) as $k => $v) {
            // updates workflow stage options to be in sync with the current installations default option keys.
            Wtf_Fu_Options::update_options_from_default_options($k, $v['options'], $default_stage_options);
        }
    }

}

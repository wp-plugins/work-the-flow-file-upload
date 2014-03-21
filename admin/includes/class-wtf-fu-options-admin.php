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

if (! is_admin()) {
    die ('You do not have administrator access to this file.');
}

require_once plugin_dir_path( __FILE__ ) . '../../includes/class-wtf-fu-options.php';



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
     * return array of all users that have user option settings for 
     * the given workflow id.
     * 
     * returns array keyed on user id with
     * array ( <user_id> => 'workflow_settings' => array ('id' => <workflow_id> ', 'stage' => <stage>),
     *                      'user' => WPUser )
     * 
     * @param type $workflow_id
     */
    static function get_workflow_users($workflow_id) {
        $users = array();
        $all_users = get_users();

        foreach ($all_users as $user) {
            $user_id = $user->ID;        
            $options = Wtf_Fu_Options::get_user_workflow_options($workflow_id, $user_id, false);
            if ($options) {
                $users[$user_id]['workflow_settings'] = $options;
                $users[$user_id]['user'] = $user;
            }
        }       
        return $users;
    }  
    
    /**
     * return all a users workflows settings as
     * array (<user_settings_table_key> => array( 'id' => id, 'stage' => stage)
     * 
     * @param type $user_id
     */
    static function get_user_workflows_settings($user_id) {
        $ret = array();
        $all_workflows = self::get_all_workflow_ids();
        foreach ($all_workflows as $wfid) {
            $options = Wtf_Fu_Options
                ::get_user_workflow_options($wfid, $user_id, false);
            if ($options) {
                $ret[$wfid] = $options;
            }
        }   
    }
    
    
   /**
     * Add a new empty workflow option for the given new ID.
     * Assumes ID is valid and does not already exist.
     * Returns the new option key if successful or exits with error.
     */
    public static function add_new_workflow_option($workflow_id, $options = null) {
        $key = Wtf_Fu_Option_Definitions::get_workflow_options_key($workflow_id);
        if (false === add_option($key, $options)) {
            die ("adding new workflow failed for key = $key" );
        }
        return $key;
    }
    
    /**
     * get the next available workflow id for creating new workflows
     * if use_vacant_slots === true then the first empty slot is returned
     * otherwise the highest id + 1 is retuned.
     */
    public static function get_next_available_workflow_id($use_vacant_slots = true) {
        $ids = self::get_all_workflow_ids();
        
        if ($use_vacant_slots === true) {
            for($i = 1; ; $i++) {
                if (!in_array ($i, $ids)) {
                    return $i;
                }                       
            } // forever until unused one is found.
        }
        // add to new slot at the end of exisiting one.
        $new_id = 1;
        if(!empty($ids)) {
            $new_id = $ids[count($ids)-1] + 1;
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
            "update $wpdb->options SET option_name=%s WHERE option_name=%s" , $new_key, $old_key)
        );
    
        //log_me(array('update_option_key()' => 
        //    array('old_key' => $old_key, 'new_key' => $new_key, 'ret' => $ret)));
       
        return ret;
    }    
    
    
    
   /**
    * Retrieves all workflow ids and options as an array keyed on the 
    * workflows option keys.
    * 
    * If keys only == true (default) just the 'key_id' (ie the workflow id)
    * is returned in the array.
    * 
    * Otherwise all the options for the key are retrieved and included in
    * the returned array with the key 'options' as well as the 'key_id'
    * 
    * @global type $wpdb
    * @return array('option_key_into_the_db' => array( 'key_id' => 2, 'options' => options array.)
    */
    public static function get_all_workflows($keys_only = true) {
        
        global $wpdb;
        $keys = array();
        
        // MySQL will ignore the parenthesis in the REGEXP but we will use them 
        // to parse out the id later with preg_match.
        $pattern = '^' . Wtf_Fu_Option_Definitions::get_workflow_options_key('([1-9][0-9]*)') . '$';
     //   log_me($pattern);
        
        $results = $wpdb->get_results(
            $wpdb->prepare("SELECT option_name FROM $wpdb->options WHERE option_name REGEXP %s" , $pattern));
        
      //  log_me($results);
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
    * returns ALL the stages from ALL workflows.
    * ie every stage in the database,
    * inteneded for use by functions that need to access every stage 
    * e.g. upgrading the db during installation.
     * 
    * If keys only == true (default) just the 'key_id' (ie the workflow id)
    * is returned in the array.
    * 
    * Otherwise all the options for the key are retrieved and included in
    * the returned array with the key 'options' as well as the 'key_id'
    * 
    * @global type $wpdb
    * @return array('option_key_into_the_db' => array( 'key_id' => 2, 'options' => options array.)
    */
    public static function get_all_workflow_stages($keys_only = true) {
        
        global $wpdb;
        $keys = array();
        
        // MySQL will ignore the parenthesis in the REGEXP but we will use them 
        // to parse out the id later with preg_match.
        $pattern = '^' . Wtf_Fu_Option_Definitions::get_workflow_stage_key('([1-9]+)', '([0-9]+)') . '$';
     //   log_me($pattern);
        
        $results = $wpdb->get_results(
            $wpdb->prepare("SELECT option_name FROM $wpdb->options WHERE option_name REGEXP %s" , $pattern));
        
      //  log_me($results);
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
     * returns an array of all exisiting workflow ids.
     */
    public static function get_all_workflow_ids() {
        $ids = array();

        // get all workflows , keys only.
        $option_keys = self::get_all_workflows(true);    
        foreach ($option_keys as $k  => $v) {
            $ids[] = (int) $v['key_id'];   
        }
        asort($ids, SORT_NUMERIC);
        
      //  log_me(array('getWorkFlowIDs' => $ids));
        return $ids;
    }
    
     /**
     * Deletes a workflow from the options table.
     * @param type $id
     */
    public static function delete_workflow($id) {
        log_me("Deleting workflow $id");
        if (empty($id)) {
            die ("Cannot delete an empty workflow id");
        }      
        
        // first delete the workflow stages.
        $stages = Wtf_Fu_Options::get_workflow_stages($id, true);         
        foreach ($stages as $k => $v ) {
            $ret = delete_option($k);
            if ($ret === false) {
                log_me ("ERROR : could not delete workflow [$id] stage [{$v['key_id']}] with option key =$k");
            }
        }   
        
        // delete the workflow options.
        $key = Wtf_Fu_Option_Definitions::get_workflow_options_key($id);
        $ret = delete_option($key);

        if ($ret === false) {
            log_me ("ERROR : could not delete workflow [$id] with option key=$key");
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
     * returns true if any reordering was required false otherwise.
     * 
     * @param type $wfid the numeric workflow id.
     */
    public static function reorder_stages($wfid) {
        
        
        $stages = Wtf_Fu_Options::get_workflow_stages($wfid, true); 
        $count = count($stages);
        
        if ($count < 1 ) {
            return false;
        }
        
        //$first_stage = $stages[0];
        //$last_stage = $stages[$count - 1];
        
        $ret = false;        
        $i = -1;
        
        foreach ( $stages as $k => $v ) {
            
            $i++;
            
            log_me(array("$v" => $v) );
            
            if ( $v['key_id'] == $i) {
                continue;
            }
            
            /*
             * found a gap e.g. $i = 5 'key_id' = 6
             * note that 'key_id' is alway >= $i
             * 
             * move this stage to stage $i
             */
            $stage_options = get_option($k);
            
         
            $new_key = Wtf_Fu_Option_Definitions::get_workflow_stage_key($wfid, $i);
            
            log_me("reorder_stages() moving stage {$k} -> {$new_key} "); 

            add_option($new_key, $stage_options);
                                              
            if ( false === delete_option($k) ) {
                die ("REORDER ERROR : could not delete old option key $k");
            }
            
            /*
             * update any workflow users currently at this stage to the new stage 
             * value. 
             */
            Wtf_Fu_Options_Admin::update_all_users_workflow_stage_settings($wfid, $v['key_id'], $i);   
            
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
        
        
        $wf_users = Wtf_Fu_Options_Admin::get_workflow_users($wfid);
        
       // log_me(array( '$wf_users' => $wf_users));
        
        foreach ($wf_users as $user_id  => $v) {
            

            if ( $v['workflow_settings']['stage'] == $current_stage ) {

                Wtf_Fu_Options::update_user_workflow_stage($wfid, $new_stage, $user_id);
                
                log_me("adjusting user stage for wfid = $wfid, new_stage = $new_stage, user_id = $user_id" );
               
            }
        }
        
    }

    
    /**
     * Inserts a copy of the demo workflow. Into the next available workflow slot.
     */
    public static function add_new_demo_workflow() {
        
        /* get default workflow options. */
        $options = Wtf_Fu_Option_Definitions
                ::get_instance()->get_page_option_fields_default_values( 
                        wtf_fu_DEFAULTS_WORKFLOW_KEY );

        if ($options === false) {
            die ("could not load default workflow options.");
        }
     
        $wf_index = self::get_next_available_workflow_id();
        
        /* set the id */
        $options['id'] = $wf_index;
        $options['name'] = "Wtf-Fu Demo Workflow";
        
        self::add_new_workflow_option($wf_index, $options);
        
        /* Add the demo stages options */
        /* get default workflow stage options. */
        $default_stage_options = Wtf_Fu_Option_Definitions
                ::get_instance()->get_page_option_fields_default_values( 
                        wtf_fu_DEFAULTS_STAGE_KEY );

        if ($default_stage_options === false) {
            die ("could not load default workflow stage options.");
        }     
        
        /* Initialize stages with the default stage options */
        $stages = array ();
        for ($i = 0; $i < 9; $i++) {
            $stages[$i] = $default_stage_options;
        }
        
        /* Override defaults where necessary to add the demo content. */
        
        /* 
         * stage 0 
         */
        $stages[0]['stage_title'] = 'Introduction';
        $stages[0]['header'] = 'Hello Welcome <strong>[wtf_fu type="get" value="display_name"]</strong>, '
               .' thankyou for purchasing/subscribing/joining our <strong>[wtf_fu type="get" value="workflow" id="' 
               . $wf_index . '" key="name"]</strong> package.';
        $stages[0]['content_area'] = 
"<p>The Wtf-Fu plugin provides step wise workflow, so it makes sense for us to use it here to showcase the plugins features, and to provide stepwise instruction on how to use it.</p>
<p>The plugin works by including shortcodes inside your wordpress pages or posts.</p>
<p>The workflow shortcode '[<code>wtf_fu id='number'</code>]' is all you need to get started and this entire workflow is generated from a single wordpress page with [<code>wtf_fu id='1'</code>] inside it.</p>
<p>The workflow stages may embed other shortcodes like [<code>wtf_fu_upload</code>] to generate a file upload page.</p>
<p>These may also be embedded directly in your other pages or posts as well as inside a workflow stage content.</p>
<p>The first step in the demo is to upload some files. To achieve this we will make use of the upload shortcode [<code>wtf_fu_upload</code>] to embed an Html5 upload form.</p>
<p>Go to the next page now to see it in action.</p>";
         
        $stages[0]['back_active'] = false;
        $stages[0]['next_js'] = '';
        $stages[0]['next_label'] = 'Start the Tour';
        $stages[0]['footer'] = "Click 'start the tour' above to see the file upload form in action.";
        
 
        /* 
         * stage 1 
         */
        $stages[1]['stage_title'] = 'The [<code>wtf_fu_upload</code>] shortcode';
        $stages[1]['header'] = "<p>Here you can upload some files so you can see the [<code>wtf_fu_upload</code>] shortcode at work.</p>";
        
        $stages[1]['content_area'] = "<p>Add files by dragging and dropping files to this page, or using the 'add files' button.</p>
<p>Please don't upload images that may be considered offensive by others.</p>
<p><span style=\"text-decoration: underline;\"><strong>As this is a public demo some restriction have been put in place.</strong></span></p>
<ol>
<li>Uploaded files will periodically be deleted from the server.</li>
<li>You may only upload image files of the type (jpg, gif or png).</li>
<li>For this demo, the maximum size for uploaded files is restricted to 5Mb.</li>
</ol>
<p>The full shortcode with attributes used to achieve this are :</p>
<p><code>[<strong>wtf_fu_upload</strong> wtf_upload_dir=\"demofiles\" accept_file_types=\"jpg|jpeg|png|gif\" max_file_size=\"5\" max_number_of_files=\"30\" auto_orient=\"1\" thumbnail_crop=\"1\"]</code></p>
<p>[wtf_fu_upload wtf_upload_dir=\"demofiles\" accept_file_types=\"jpg|jpeg|png|gif\" max_file_size=\"5\" max_number_of_files=\"30\" auto_orient=\"1\" thumbnail_crop=\"1\"]</p>";
        
        $stages[1]['next_js'] = '';
        $stages[1]['next_label'] = 'Next';
        $stages[1]['footer'] = "Click 'Next' once you are finished uploading your files. You will be able to return to here to upload more files at a later stage if you wish.";
        
        /* 
         * Stage 2 
         */
        $stages[2]['stage_title'] = 'Displaying the User\'s Uploaded files.';
        $stages[2]['header'] = "<p>Now that you have uploaded some files, we need a way to display them to back to you. To do this we can make use of the 
            <code>[<strong>wtf_fu_showfiles</strong>]</code> shortcode.";
            
        $stages[2]['content_area'] = '<p>The short code <code>[<strong>wtf_fu_showfiles</strong>]</code> can be used to show the files that the user has uploaded.</p>
<p>The following attributes are available :</p>
<table border="0" cellspacing="10" cellpadding="5" align="left"><caption><code>[<strong>wtf_fu_showfiles</strong>]</code> attributes</caption>
<thead>
<tr style="background-color: #7f7fdb;">
<td>attribute name</td>
<td>default value</td>
<td>action</td>
</tr>
</thead>
<tbody>
<tr>
<td>reorder</td>
<td>"false"</td>
<td>Set to "true" to allow users to drag and rearrange their files into their preferred order</td>
</tr>
<tr>
<td>wtf_upload_dir</td>
<td>"public"</td>
<td>
<p>The directory name of the files to display. This is relative to the users wp-upload directory</p>
</td>
</tr>
<tr>
<td>wtf_upload_subdir</td>
<td>""</td>
<td>
<p>An optional subdirectory path relative to the wtf_upload_dir</p>
</td>
</tr>
<tr>
<td>gallery</td>
<td>"false"</td>
<td>
<p>Set to "true" to provide gallery display of the full sized images when thumbnails are clicked. Note that the file_type attribute must also be set to "image".</p>
</td>
</tr>
<tr>
<td>file_type</td>
<td>"image"</td>
<td>
<p>Set to image to display preview thumbnails of the files if available.</p>
</td>
</tr>
</tbody>
</table>

<p>&nbsp;</p>
<p>If <code>reorder</code> is set to "true" then the user will be able to drag and drop thumbnails into their desired order. Once they submit their reordering the users file timestamps will then be modified at one second intervals to reflect the users desired order when sorted in last_modified time ascending order.</p>
<p>This means that if a user then navigates back to the upload page that the files will appear in the newly sorted order.</p>
<p>A text file with list of files in the desired order will also be written to the users upload directory, so that there is a record of the order even if the timestamps are lost during archiving or other means.</p>
<p>The <code>gallery</code> attribute is used to provide display of the full size images when a thumbnail is clicked.</p>
<p>We will provide an example of these attributes on the next 3 pages.</p>
<p>Also of note we have turned off the the next button javascript pop up by setting the <code>next_js</code> field for the following stages to an empty string.</p>';
                
        $stages[2]['next_js'] = '';
        $stages[2]['next_label'] = 'Next';
        $stages[2]['footer'] = "Click 'Next' to view the basic default wtf_fu_showfiles shortcode without minimal attributes set.";
               
         /* 
         * Stage 3 
         */
        $stages[3]['stage_title'] = 'Basic Display of Image thumbnails.';
        $stages[3]['header'] = "<p>The <code>[<strong>wtf_fu_showfiles</strong>]</code> shortcode without any qualifying attributes will just display the images thumnails without the ability to reorder the files and without the Gallery.</p>";
            
        $stages[3]['content_area'] = ' <p>Below are the file thumnails without the gallery and without reodering enabled. The only attribute required is the <code>wtf_upload_dir</code> to specify the location of the files.'
                . "this should match the value used in the original [<code>wtf_fu_upload</code>] shortcode where the files were uploaded.</p>"
                . '<p>The full short code to achieve the action below is :</p>'
                . '<p><code>[<strong>wtf_fu_show_files</strong> wtf_upload_dir="demo files" file_type="image"]</code></p>'
                . '<p>[wtf_fu_show_files wtf_upload_dir="demofiles" file_type="image"]</p>';     

        $stages[3]['next_js'] = '';
        $stages[3]['next_label'] = 'Next';
        $stages[3]['footer'] = "Click 'Next' to view the wtf_fu_showfiles shortcode with the Gallery attribute set.";
               
          /* 
         * Stage 4
         */
        $stages[4]['stage_title'] = 'Display of Image thumbnails with Gallery enabled.';
        $stages[4]['header'] = "<p>The <code>[<strong>wtf_fu_showfiles</strong>]</code> shortcode can also have the Gallery attribute enabled.";
            
        $stages[4]['content_area'] = 
            '<p>Below are the file thumnails with the gallery and without reodering enabled.</p>'
            . '<p>The full short code to achieve the action below is :</p>'
            . '<p><code>[<strong>wtf_fu_show_files</strong> wtf_upload_dir="demofiles" file_type="image" gallery="true"]</code></p>'
            . '<p>[wtf_fu_show_files wtf_upload_dir="demofiles" file_type="image" gallery="true"]</p>';     

        $stages[4]['next_js'] = '';
        $stages[4]['next_label'] = 'Next';
        $stages[4]['footer'] = "Click 'Next' to learn about the wtf_fu_showfiles shortcode with the <code>reorder</code> attribute set.";           
         
          /* 
         * Stage 5
         */
        $stages[5]['stage_title'] = 'Reordering of files.';
        $stages[5]['header'] = "<p>The <code>[<strong>wtf_fu_showfiles</strong>]</code> shortcode can also have the <code>reorder</code> attribute enabled. ";

        $stages[5]['content_area'] = 
           "This will then allow users to drag and drop the thumbnail images into their preferred order.</p>"
            .'<p>Because uploading multiple files is asynchronous they may have landed in an unpredictable order.</p>
            <p>If the attribute <code>reorder=true</code> is set then users will be able to reorder their files.</p>
            <p>Once the reordering is submitted, the user file timestamps will then be modified at one second intervals to reflect the users desired order when sorted in last_modified time ascending order.</p>
            <p>A text file with list of files in the desired order will also be written to the users upload directory, 
            so that there is a record of the user desired order even if the timestamps are lost during archiving or by other means.</p>'
                        . '<p>Below are the file thumbnails with the gallery enabled and with re-odering enabled.</p>'
                        . '<p>Once you finished you can submit the reordering and then go back and view the previous step to verify that the file order has indeed changed.</p>'
                        . '<p>The full short code to achieve the action below is :</p>'
                        . '<p><code>[<strong>wtf_fu_show_files</strong> wtf_upload_dir="demofiles" file_type="image" gallery="true" reorder="true"]</code></p>'
                        . '<p>[wtf_fu_show_files wtf_upload_dir="demofiles" file_type="image" gallery="true" reorder="true"]</p>';     

        $stages[5]['next_js'] = '';
        $stages[5]['next_label'] = 'Next';
        $stages[5]['footer'] = "Click 'Next' to learn about using wtf-fu workflow stage pre- and post- processing hooks.";
         
        /* 
         * Stage 6
         */
        
        $stages[6]['stage_title'] = 'Pre and Post Processing Hooks';
        $stages[6]['header'] = "<p>You may add your own user defined functions to any workflow stage <code>pre_hook</code> or <code>post_hook</code></p>"
                . "This can be used for example to generate emails or to archive user files or some other activity.</p>";
                
        $stages[6]['content_area'] = 
            "You simply create a function in your themes function.php file or inside a file in the mu_plugins directory and then specify the function name (without parenthesis) in either the pre_hook or post_hook field."
            . "pre-hook functions are run BEFORE normal stage processing occurs, and post-hook functions are run after."
            . "Note that these hook functions will block while running, so be careful when archiving a large number of files or other activities that may keep the user waiting for extended periods."          
            . '<p>An example hook function file that emails the user and admimistrator is included in the examples directory.<p>'
            .'<p>This contains a function called <code>wtf_fu_sendActivationMail</code> will be called as a post-hook when you proceed from here to the next page.</p>'
            .'<p>You will have to paste this function into your themes functions.php file (or an mu-plugins file) as descrived above for this to work.<p>'
            .'<p>If this demo is running inside your own installation and you haven\'t already done this then do this now before proceeding to the next stage.</p>'
            .'<p>We have also added a next button confirmation javascript here to alert the user that the files will be archived once they submit</p>';
   

        $stages[6]['next_js'] = '" onClick="return confirm(\'This will submit your files for archiving, are you sure you want to proceed ?\');"';
        $stages[6]['post_hook'] = 'wtf_fu_sendActivationMail';
        $stages[6]['next_label'] = 'Next';
        $stages[6]['footer'] = "Click 'Next' to trigger the post-hook function to archive your files and email yourself and the administrator.";
        
        /* 
         * Stage 7
         */
        
        $stages[7]['stage_title'] = 'Check your email';
        $stages[7]['header'] = "<p>If all went well then you should shortly recieve and email sent by the post-hook function <code>wtf_fu_sendActivationMail</code></p>";
                
                
        $stages[7]['next_js'] = '';
        $stages[7]['content_area'] = 
            "<p>If not please go back and review the steps to make sure that the function is available in either the mu-plugins directory or as part of your themes functions.php file.</p>"                
            . "<p>If you are also the administrator of your site, you should also recieve email with a link to the auto archive of the uploaded images.</p>"
            . '<p>The administrator should also recieve a cc email of the mail sent to the user.</p>';

        $stages[7]['footer'] = "Click 'Next' to go to the Summary page.";
        
        /* 
         * Stage 8
         */
        $stages[8]['stage_title'] = 'Summary';
        $stages[8]['header'] = "<p>We have covered most of the functionality you need to get started with the Wtf-Fu plugin.</p>";

        $stages[8]['content_area'] = 
                "<p>Normally at this point in a workflow, after you have processed a users uploads etc, you may decide not to allow the user to go back to previous stages any more.</p>"
                . "<p>You may also choose to not allow a user to go forward either untill you have completed some manual admin task (i.e. done something with the users submitted files).</p>"
                . "<p>User stages are incremented and decremented as they move backward and forward through the workflow stage pages.</p>"
                . "<p>They can also be manually set to a specific stage number from the back end <code>Manage Users</code> page</p>"
                . "<p>To restrict user movement throughout the workflow use the stage fields <code>back_active</code> and <code>next_active</code></p>"
                . "<p>We won't fully demo that here because we want you to move freely back and forward through the demo. :).</p>";                
            '<p> As this is the end of the demo now we have restricted your foward movement by unchecking the <code>next_active</code> checkbox for stage 8 (this one).</p>'
            . '<p>We hope that the demo has helped you to grasp the key concepts behind the plugin. </p>'
            . '<p>If you have any suggestions for improvements to this demo, we welcome your feedback on the <a href="http://wtf-fu.com/demo/">demo website page</a></p>';

        $stages[8]['next_js'] = '';
        $stages[8]['next_active'] = 0;
        $stages[8]['footer'] = "We are finished ! So you cannot Click 'Next' anymore. You can, however, go back to any of the previous stages to further modify your demo files.";
        
        
        foreach ($stages as $stage_id => $stage_options) {
            $new_stage_key = Wtf_Fu_Option_Definitions::get_workflow_stage_key($wf_index, $stage_id );
            if (!add_option($new_stage_key, $stage_options)) {
                die ("adding demo stage failed");
            }
        }   
    }
    
    
    public static function add_new_workflow() {

        /* get default workflow options. */
        $options = Wtf_Fu_Option_Definitions
                ::get_instance()->get_page_option_fields_default_values( 
                        wtf_fu_DEFAULTS_WORKFLOW_KEY );

        if ($options === false) {
            die ("could not load default workflow options.");
        }
     
        $new_wf_index = self::get_next_available_workflow_id();
        
        /* set the id */
        $options['id'] = $new_wf_index;
        
        self::add_new_workflow_option($new_wf_index, $options);       
    }
    
    /**
     * merge existing options with default ones and discards any not defined in the 
     * current options defaults array.
     * 
     * @param type $options
     * @param type $default_options
     */
    public static function update_options_from_default_options($key, $options, $default_options) {
        
        $updated_options = array();
        
        foreach ($default_options as $k => $v) {
            if (array_key_exists($k, $options)) {
                $updated_options[$k] = $options[$k];
            } else {
                $updated_options[$k] = $v;
            }
        }
        
        update_option($key, $updated_options);
    }
    
    
    /**
     * Clones a workflow using an existing one values.
     * @param unknown_type $wf_index
     */
    public static function clone_workflow($current_wf_index) {
        if (empty($current_wf_index)) {
            die ("Cannot clone an empty workflow id");
        }
        
        // array of the existing workflow options to clone.
        $options = get_option(Wtf_Fu_Option_Definitions::get_workflow_options_key($current_wf_index));

        if ($options === false) {
            die ("could not find workflow to clone.");
        }
     
        $new_wf_index = self::get_next_available_workflow_id();
        
        $options['id'] = $new_wf_index;
        $options['name'] = "Copy of " . $options['name'];
        
        $new_wf_key = self::add_new_workflow_option($new_wf_index, $options);

        // now clone the stages.
        // retrieve all the workflow stages with option values from the database.
        $allstages = Wtf_Fu_Options::get_workflow_stages($current_wf_index, false);
        
        foreach ($allstages as $old_stage ) {
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
                    
        log_me(array("adding stage key-id = {$stage['key_id']} to workflow $wf_index" => $stage) );

        if (!array_key_exists('key_id' , $stage)) {
            die ("stage index to add is unavailable in passed stage options => 'key_id'");            
        } 
        $new_stage_key = Wtf_Fu_Option_Definitions::get_workflow_stage_key($wf_index, $stage['key_id'] );
        if (!add_option($new_stage_key, $stage['options'])) {
            die ("adding cloned stage failed");
        }	
    }   
    
    
    
}

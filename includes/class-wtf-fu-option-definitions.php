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

define('wtf_fu_BASE', 'wtf-fu_');
define('wtf_fu_DEFAULT_SEGMENT', '_default');
define('wtf_fu_OPTIONS_SEGMENT', '_options');
define('wtf_fu_WORKFLOW_SEGMENT', 'workflow_');
define('wtf_fu_STAGE_SEGMENT', '_stage_'); 

/** Default Plugin Options */
define('wtf_fu_OPTIONS_DATA_PLUGIN_KEY', wtf_fu_BASE . 'plugin' . wtf_fu_OPTIONS_SEGMENT);
/** Default Upload Options */
define('wtf_fu_OPTIONS_DATA_UPLOAD_KEY', wtf_fu_BASE . 'upload'
        . wtf_fu_DEFAULT_SEGMENT . wtf_fu_OPTIONS_SEGMENT);
/** Base for user option table keys */
define('wtf_fu_USER_BASE', wtf_fu_BASE . 'user_');

// Define sprintf format strings for creating dynamic workflow and stage 
// keys from.

/** The user options table key for a given workflow id. */
define('wtf_fu_USER_WORKFLOW_SETTINGS_KEY_FORMAT', wtf_fu_USER_BASE . wtf_fu_WORKFLOW_SEGMENT . '%s' . '_settings');
/** The workflow options for a given workflow id. */
define('wtf_fu_WORKFLOW_OPTIONS_KEY_FORMAT', wtf_fu_BASE . wtf_fu_WORKFLOW_SEGMENT . '%s' . wtf_fu_OPTIONS_SEGMENT);
/** The stage options for a given workflow id and stage number. */
define('wtf_fu_WORKFLOW_STAGE_OPTIONS_KEY_FORMAT', wtf_fu_BASE . wtf_fu_WORKFLOW_SEGMENT . '%s' .
        wtf_fu_STAGE_SEGMENT . '%s' . wtf_fu_OPTIONS_SEGMENT);

/**
 * Page keys used for top level page tabs used 
 * for grouping options together into pages.
 */
define('wtf_fu_PAGE_PLUGIN_KEY', 'plugin-options');
define('wtf_fu_PAGE_UPLOAD_KEY', 'upload-options');
define('wtf_fu_PAGE_WORKFLOWS_KEY', 'workflows');
define('wtf_fu_PAGE_WORKFLOW_OPTION_KEY', 'workflow-options');
define('wtf_fu_PAGE_WORKFLOW_STAGE_OPTION_KEY', 'workflow-stage-options');
define('wtf_fu_PAGE_USERS_KEY', 'user-options');
define('wtf_fu_PAGE_TEMPLATES_KEY', 'templates');
define('wtf_fu_PAGE_DOCUMENATION_KEY', 'documentation');



/**
 * Keys to default values and help labels for plugin, upload, workflow, and stage 
 */
define('wtf_fu_DEFAULTS_PLUGIN_KEY', 'wtf-fu-plugin-defaults');
define('wtf_fu_DEFAULTS_UPLOAD_KEY', 'wtf-fu-upload-defaults');
define('wtf_fu_DEFAULTS_STAGE_KEY', 'wtf-fu-stage-defaults');
define('wtf_fu_DEFAULTS_WORKFLOW_KEY', 'wtf-fu-workflow-defaults');
define('wtf_fu_DEFAULTS_SHORTCODE_SHOWFILES_KEY', 'wtf-fu-showfiles-defaults');
define('wtf_fu_DEFAULTS_SHORTCUTS_KEY', 'wtf-fu-template-fields');

define('wtf_fu_DEFAULT_WORKFLOW_TEMPLATE', '<div class="panel panel-default tbs">
        <div class="panel-heading">
            <h1 class="panel-title"><strong>%%WORKFLOW_NAME%%</strong></h1>
            %%WORKFLOW_BUTTON_BAR%%
            <h2>%%WORKFLOW_STAGE_TITLE%%</h2>
            %%WORKFLOW_STAGE_HEADER%%
        </div>
        <div class="panel-body tbs">
            %%WORKFLOW_STAGE_CONTENT%%
        </div>        
        <div class="panel-footer">
            %%WORKFLOW_BUTTON_BAR%%
            <p><small>%%WORKFLOW_STAGE_FOOTER%%</small></p>
            <p><small>%%WTF_FU_POWERED_BY_LINK%%</small></p>
        </div>
    </div>');

define ('wtf_fu_DEFAULT_EMAIL_TEMPLATE' , 'Hi <strong>%%USER_NAME%%</strong>,
<br/>
<p>Congratulations !</p>
<br/>
<p>You have just successfully completed the <strong>%%WORKFLOW_STAGE_TITLE%%</strong> stage of 
the <strong>%%WORKFLOW_NAME%%</strong> at %%SITE_URL%%.</p>
<p>We have received the following files you have uploaded for our attention :</p>
<p>
[wtf_fu_show_files email_format="1"]
</p>
<p>Please check that this list is complete, and feel free to contact us at <br/>
%%ADMIN_EMAIL%% if you have any concerns.</p>
<br/>
<br/>
<p>regards,</p> 
%%ADMIN_NAME%% <br/>
<p><small>%%ADMIN_EMAIL%%</small></p>
<hr/>
<p><small>This has been an automated email response from %%SITE_URL%%
<br/>%%WTF_FU_POWERED_BY_LINK%%</small></p>
<hr/>'
);

/**
 * Class consists of static methods to return option keys and values.
 */
class Wtf_Fu_Option_Definitions {

   /**
     * Instance of this class.
     */
    protected static $instance = null;
    
    private $all_pages_default_options;
    private $all_pages_default_labels;
    private $menu_page_values;
    
    
    /**
     * Initialize the plugin by setting localization and loading public scripts
     * and styles.
     */
    private function __construct() {
        
        // log_me('__construct  Wtf_Fu_Option_Definitions ');      
               
        /**
         * The field names / default values 
         * @var type static array
         */
        $this->all_pages_default_options = array(
            wtf_fu_DEFAULTS_PLUGIN_KEY => array(
                'remove_all_data_on_uninstall' => '0',
                'include_plugin_style' => '1',
                'show_powered_by_link' => '0'
            ),
            wtf_fu_DEFAULTS_UPLOAD_KEY => array(
                'deny_public_uploads'=> '1',
                'use_public_dir'=> '0',
                'wtf_upload_dir' => 'wtf-fu_files',
                'wtf_upload_subdir' => 'default',
                'accept_file_types' => 'jpg|jpeg|mpg|mp3|png|gif|wav|ogg',
                'deny_file_types' => 'htaccess|php|php3|php4|php5|cgi|aspx|asp|aspx|sh|phtml|shtml|pl|py|pyc|pyo',
                'inline_file_types' => 'jpg|jpeg|mpg|mp3|png|gif|wav|ogg',
                'image_file_types' => 'gif|jpg|jpeg|png',
                'max_file_size' => '5',
                'max_number_of_files' => '30',
                'auto_orient' => '1',
                'create_medium_images' => '0',
                'medium_width' => '800',
                'medium_height' => '600',
                'thumbnail_crop' => '1',
                'thumbnail_width' => '80',
                'thumbnail_height' => '80',
            ),
            wtf_fu_DEFAULTS_WORKFLOW_KEY => array(
                'name' => 'Unnamed Workflow',
                'id' => '',
                'notes' => 'Enter any private workflow notes here.',
                'description' => 'Enter a description for this workflow.',
                'testing_mode' => '0',                              
                'include_plugin_style_default_overrides' => '1',                
                'default_back_label' => "Go Back",
                'default_next_label' => "Next",
            ),
            wtf_fu_DEFAULTS_STAGE_KEY => array(
                'stage_title' => 'Stage %%WORKFLOW_STAGE_NUMBER%%.',
                'header' => 'In this stage we will ...',
                'content_area' => 'Enter the workflow stage main body of content here.',
                'footer' => 'Press next to proceed.',
                'next_active' => '1',
                'next_label' => '',
                'next_js' => '',
                'back_active' => '1',
                'back_label' => '',
                'back_js' => '',
                'pre_hook' => '',
                'post_hook' => '',
            ), 
            wtf_fu_DEFAULTS_SHORTCODE_SHOWFILES_KEY => array(
                'wtf_upload_dir' => '', // set after initialised.
                'wtf_upload_subdir' => '', // set after initialised.
                'reorder' => '0',
                'gallery' => '1',
                'file_type' => 'auto',
                'email_format' => '0',
                'show_numbers' => '1',
                'audio_controls' => '1',
                'vertical' => '0',
                'download_links' => '0',
                'use_public_dir' => '0'
                )
        );
        
        
        // Keep in sync with the file upload defaults.
        $this->all_pages_default_options[wtf_fu_DEFAULTS_SHORTCODE_SHOWFILES_KEY]['wtf_upload_dir'] = 
                $this->all_pages_default_options[wtf_fu_DEFAULTS_UPLOAD_KEY]['wtf_upload_dir'];
        $this->all_pages_default_options[wtf_fu_DEFAULTS_SHORTCODE_SHOWFILES_KEY]['wtf_upload_subdir'] = 
                $this->all_pages_default_options[wtf_fu_DEFAULTS_UPLOAD_KEY]['wtf_upload_subdir'];  
        
        $this->all_pages_default_options = apply_filters('wtf_fu_all_pages_default_options_filter', $this->all_pages_default_options);

        /**
         * Set up the help texts for all fields. Keys should be identical to the 
         * above array.
         */
        $this->all_pages_default_labels = array(
            wtf_fu_DEFAULTS_PLUGIN_KEY => array(
                'remove_all_data_on_uninstall' =>
                'Allows the removal of all the plugin and user 
                 workflow options data during an uninstall. <br/>
                 It is recommended to leave this off unless you are really sure you want to remove all your data when you uninstall this plugin.
                 If off then it is safe to delete and reinstall this plugin without losing any workflow data.',
                'include_plugin_style' => 'Include the bootstrap css used by workflow. <br/>'
                . 'It is recommended to leave this ON unless you have style conflicts with your theme.',                
                'show_powered_by_link' => 'Supports this plugin by allowing the inclusion of a powered by link to wtf-fu.com when the %%WTF_FU_POWERED_BY_LINK%% shortcut is used in your templates.'
                . 'if false then the link will never be included, even when the shortcut is used in a template.'
            ),
            wtf_fu_DEFAULTS_UPLOAD_KEY => array(
                'deny_public_uploads' => 'Restrict access to logged in users only. 
                 WARNING ! Do not uncheck this feature unless you have other 3rd party plugins that are protecting access to your pages, 
                 have implemented your own conditional logic around your embedded wtf-fu shortcodes, 
                 or you understand the risks and really do want to allow ANY user to be able to 
                 upload files to your site.',
                'use_public_dir'=> 'Causes uploads to use the <code>/uploads/public</code> as the root directory instead of <code>/uploads/[user_id]</code>.
                    This has the effect of causing uploads to be shared amoungst all users registered or not.',
                'wtf_upload_dir' =>
                'The default upload directory name. 
                    This will be under the users upload directory.
                    eg For a user with a user id = 4 this would be 
                    yoursiteroot/wp-content/uploads/4/<strong>wtf_upload_dir</strong>/',
                'wtf_upload_subdir' =>
                'You may optionally specify a default upload sub directory name.
                    If specified this will be appended to the upload_dir.
                     e.g. <code>yoursitedirecory/wp-content/uploads/[user_id]/wtf_upload_dir/<strong>default</strong>/</code>"',
                'accept_file_types' =>
                'Allowed upload file type extensions separated by a | character. 
                    This is later expanded to a regular expression so other regexp 
                    may be used in here as well.
                    e.g.<code><strong>gif|jp?g|png|mp3</strong></code>
                    This will be expanded internally to the regular expression <code>/\.(gif|jpe?g|png)$/i"</code>',
                'deny_file_types' =>
                'Black list of file types extensions that may never be uploaded under any circumstances. 
                    This should include php or any other script files extensions that can be executed by the webserver.
                    Unlike all other options, this option cannot be overridden in the wtf_fu_upload shortcode attributes and these values here will apply to ALL upload instances on your site.
                    You can further secure your site by generating an .htaccess file to prevent apache servers from executing scripts in your wp_content/uploads directory.',
                'inline_file_types' =>
                'A partial regexp which defines file extentions 
                    that may be displayed inline by a browser when downloaded. 
                    <code>e.g. <strong>gif|jpg|png|mp3</strong></code>',
                'image_file_types' =>
                'A partial regexp which defines which files are handled as image
                     files. e.g. <strong>gif|jpg|png|mp3</strong>',
                'max_file_size' =>
                'The Maximum allowed size for a single file in Mb.
                    Note that your web host may also impose limits on http upload file sizes.
                    This can be adjusted by modifying the <code>upload_max_filesize</code> 
                    and <code>post_max_size</code> variable in either php.ini or .htaccess 
                    There is a <a href="http://www.wpbeginner.com/wp-tutorials/how-to-increase-the-maximum-file-upload-size-in-wordpress/" target="_blank">tutorial here</a> that may help',
                'max_number_of_files' =>
                'The Maximum number of files for each user that may be uploaded to 
                    <code>wtf_upload_dir</code>',
                'auto_orient' =>
                'Automatically rotate images based on EXIF meta data.',
                'create_medium_images' =>
                'If checked causes additional medium sized images to be produced.',
                'medium_width' =>
                'Maximum width for Medium sized images.',
                'medium_height' =>
                'Maximum height for Medium sized images.',
                'thumbnail_crop' =>
                'Crop thumbnail images to the exact required size. 
                    Otherwise images will be scaled in original aspect ratio.',
                'thumbnail_width' =>
                'Maximum width for thumbnail images.',
                'thumbnail_height' =>
                'Maximum height for thumbnail images.',
            ),
            wtf_fu_DEFAULTS_WORKFLOW_KEY => array(
                'name' => 'Enter a name for this workflow. '
                . 'This value can be referenced in stage content and email and workflow templates using the <code>%%WORKFLOW_NAME%% shortcut</code>',
                'id' => 'Workflow id\'s are assigned automatically. This value cannot be modified.',
                'notes' => 'Your notes about this workflow (admin use only).',
                'description' => 'Enter a description for this workflow. (admin use only).',
                'testing_mode' => 'Enables testing mode, in testing mode forward and next button are always shown to enable easier testing',
                'include_plugin_style_default_overrides' => 'Check to include the default workflow style overloads.',                
                'default_back_label' => 'Default Back Button text label. ( used if not overridden in stages )',
                'default_next_label' => 'Default Next Button text label. ( used if not overridden in stages )',
            ),
            wtf_fu_DEFAULTS_STAGE_KEY => array(
                'stage_title' =>
                'The text for the title bar for this stage.'
                . 'This value can be referenced in stage content and email and workflow templates (pro version only.) using the <code>%%WORKFLOW_STAGE_TITLE%% shortcut</code>',
                'header' => 'Content to be displayed in the header.',
                'content_area' =>
                'The main content for the stage. 
                    You may embed other wtf-fu shortcodes in here. 
                    e.g. <code><strong>[wtf_fu_upload ...]</strong></code> to embed a file upload.
                    or <code><strong>[wtf_fu_show_files ...]</strong></code> to display a users previously uploaded files.',
                'footer' =>
                'Text that will appear in the stage footer section.',
                'next_active' => 'Allow users to go forward a stage from here. Activates the Next button for this stage.',
                'next_label' => 'The label for the next button for this stage',
                'next_js' => 'Add any javascript to attach to the next button.<br/> 
                    e.g. <strong>onClick="return confirm(\'Are you sure you want to proceed ?\');"</strong>
                    <br><small style="color:red">( Warning! : Please test your javascript thoroughly on all browsers. )',
                'back_active' => 'Allow users to go back a stage from here. Causes the Back button to display.',
                'back_label' => 'The label for the back button for this stage',
                'back_js' => 'Add any javascript to attach to the back button.<br/> 
                    e.g. <strong>onClick="return confirm(\'Are you sure you want to go back now ?\');"</strong>
                    <br><small style="color:red">( Warning! : Please test your javascript thoroughly on all browsers. )',
                'pre_hook' => 'Enter a user defined function to be executed before a user 
                enters this stage.',
                'post_hook' => 'Enter a user defined function to be executed after a user 
                leaves this stage.',          
            ),
           wtf_fu_DEFAULTS_SHORTCODE_SHOWFILES_KEY => array(
                'wtf_upload_dir' => '', // set after initialised.
                'wtf_upload_subdir' => '', // set after initialised.
                'reorder' => 'set to 1 (true) to add a re-ordering button and make the list sortable. '
               . 'When submitted files last modified timestamp is updated to reflect the new order. '
               . 'Additionally a file is created in the root upload directory containing a list of the files in the resorted order.',
                'gallery' => 'set to 1 (true) to show images in a gallery display when clicked.',
                'file_type' => 'Deprecated : File types are autodetected and displayed since 1.2.6.',
                'email_format' => '0 (false) or 1 (true) If set then an attempt to inline the css formatting will be made so that it can be used in email output.'
               . 'Do not use this for displaying on web pages. This is mainly used by the email templates and hooked user functions which call the shortcode function directly.',
                'show_numbers' => '0 (false) or 1 (true). '
               . 'Causes the order number to be displayed in the top left corner.',
                'audio_controls' => 'Causes audio contols to be displayed for audio files.',
                'vertical' => '0 (false) or 1 (true). '
               . 'Force the display into vertical mode with one file per row.',
                'download_links' => '0 (false) or 1 (true). '
               . 'Display a download link for downloading files.',
                'use_public_dir' => '0 (false) or 1 (true). '
               . 'Force listing of files from the uploads/public directory rather than the uploads/user_id directory.'
                ),  
            wtf_fu_DEFAULTS_SHORTCUTS_KEY => array(          
                '%%USER_ID%%' => 'The current users user ID.',
                '%%USER_NAME%%' => 'The current users display name.',
                '%%USER_EMAIL%%' => 'The current users email address.',
                '%%ADMIN_NAME%%' => 'The site administrators display name.',        
                '%%ADMIN_EMAIL%%' => 'The site administrators email address.',
                '%%SITE_URL%%' => 'The url link for this web site.',
                '%%SITE_NAME%%' => 'The name of this web site.',
                '%%WORKFLOW_NAME%%' => 'The name of the current workflow.',
                '%%WORKFLOW_STAGE_TITLE%%' =>'The current workflow stage title.',
                '%%WORKFLOW_STAGE_NUMBER%%' => 'The current stage number.',
                '%%WORKFLOW_STAGE_HEADER%%' => 'The current workflow stage header content (Workflow Templates only)',
                '%%WORKFLOW_BUTTON_BAR%%' => 'The button bar with PREV and NEXT buttons (Workflow Templates only)',
                '%%WORKFLOW_STAGE_CONTENT%%' => 'The current workflow stage main content (Workflow Templates only)',
                '%%WORKFLOW_STAGE_FOOTER%%' => 'The current workflow stage footer content (Workflow Templates only)',
                '%%WTF_FU_POWERED_BY_LINK%%' => 'Includes a WFT-FU Powered by link to wtf-fu.com. (If allowed on the Plugin System Options page.)',
                '%%ALL_WORKFLOW_USERS_EMAILS%%' => 'A list of users emails addresses that have commenced using the curent workflow.',
                '%%ALL_SITE_USERS_EMAILS%%' => 'A list of all the sites registered users emails addresses.',
                '%%USER_GROUP_XXXX_EMAILS%%' => 'A list of all the users of group XXXX emails addresses. Substitute XXXX with the required user group.',
                '%%ARCHIVE_USERS_FILES%%' => 'Causes all of a users files to be auto archived into a zip file and returns a download link to the zip file. WARNING this will cause delays while archiving completes, only use this if the delay time is acceptable. Intended to allow templated emails to automatically archive and email an admin users uploaded files. Files can be manually archived from the admin pages which may be a better solution to avoid delays to the user.'
               )                           
        );
        
        // Keep in sync with the file upload defaults.
        $this->all_pages_default_labels[wtf_fu_DEFAULTS_SHORTCODE_SHOWFILES_KEY]['wtf_upload_dir'] = 
                $this->all_pages_default_labels[wtf_fu_DEFAULTS_UPLOAD_KEY]['wtf_upload_dir'];
        $this->all_pages_default_labels[wtf_fu_DEFAULTS_SHORTCODE_SHOWFILES_KEY]['wtf_upload_subdir'] = 
                $this->all_pages_default_labels[wtf_fu_DEFAULTS_UPLOAD_KEY]['wtf_upload_subdir']; 
        
        // add in extra hooked label default values.
        $this->all_pages_default_labels = apply_filters('wtf_fu_all_pages_default_labels_filter', $this->all_pages_default_labels);

        /**
         * The option page keys and settings for setting up submenus.
         */
        $this->menu_page_values = array(
            wtf_fu_PAGE_PLUGIN_KEY => array(
                'title' => 'System Options',
            ),
            wtf_fu_PAGE_UPLOAD_KEY => array(
                'title' => 'File Upload',
            ),
            wtf_fu_PAGE_WORKFLOWS_KEY => array(
                'title' => 'Workflows',
            ),
            wtf_fu_PAGE_USERS_KEY => array(
                'title' => 'Manage Users',
            ),
            wtf_fu_PAGE_TEMPLATES_KEY => array(
                'title' => 'Templates', // <small>PRO only</small>',
            ),            
            wtf_fu_PAGE_DOCUMENATION_KEY => array(
                'title' => 'Documentation',
            )            
        );
       
        $this->menu_page_values = apply_filters('wtf_fu_menu_page_values_filter', $this->menu_page_values);
   }

    /**
     * Return a singleton instance of this class.
     *
     * @since     1.0.0
     *
     * @return    object    A single instance of this class.
     */
    public static function get_instance() {

        // If the single instance hasn't been set, set it now.
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }
    
    public static function get_workflow_options_key($workflow_id) {
        return sprintf(wtf_fu_WORKFLOW_OPTIONS_KEY_FORMAT, $workflow_id);
    }

    public static function get_workflow_stage_key($workflow_id, $stage_id) {
        return sprintf(wtf_fu_WORKFLOW_STAGE_OPTIONS_KEY_FORMAT, $workflow_id, $stage_id);
    }

    /**
     * The default options for all the fields associated with a particular
     * page (eg plugin_options page or workflow options page etc.
     * 
     * Returns an array keyed on field name, and the fields default value.
     * @param string $page_key  page identifier key
     * @return array ($data_field_key => 'default_value')
     */
    public function get_page_option_fields_default_values($page_key) {
        return ($this->all_pages_default_options[$page_key]);
    }
    
    public function get_page_option_fields_default_labels($page_key) {
        return ($this->all_pages_default_labels[$page_key]);
    }    

    /**
     * Use to retrieve a single field default value.
     * 
     * @param type string $page_key  page identifier key
     * @param type string $field_name name of the field
     * @return type
     */
//    public static function get_page_option_field_default_value($page_key, $field_name) {
//        return (self::$all_pages_default_options[$page_key][$field_name]);
//    }

    /**
     * Use to retrieve a single field label value.
     * 
     * @param type $page_key  page identifier key
     * @return type
     */
    public function get_page_option_field_label_value($page_key, $field_name) {     
        return ($this->all_pages_default_labels[$page_key][$field_name]);
    }
    
    /**
     * Returns array of all the menu pages keys.
     */
    public function get_menu_page_values() {
        return $this->menu_page_values;
    }
    
    /**
     * Return the user meta data option key for a given workflow id.
     * Note that this key is the same for all users.
     * 
     * @param  scalar $workflow_id  - the id identifying the workflow.
     */
    static function get_user_workflow_options_key($workflow_id) {
        return sprintf(wtf_fu_USER_WORKFLOW_SETTINGS_KEY_FORMAT, $workflow_id);
    }
     
    static function get_upload_options_key() {
        return  wtf_fu_OPTIONS_DATA_UPLOAD_KEY;
    }
    
    static function get_plugin_options_key() {
        return  wtf_fu_OPTIONS_DATA_PLUGIN_KEY;
    }    
    
    

}

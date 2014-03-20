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


/**
 * Keys to default values and help labels for plugin, upload, workflow, and stage 
 */
define('wtf_fu_DEFAULTS_PLUGIN_KEY', 'wtf-fu-plugin-defaults');
define('wtf_fu_DEFAULTS_UPLOAD_KEY', 'wtf-fu-upload-defaults');
define('wtf_fu_DEFAULTS_STAGE_KEY', 'wtf-fu-stage-defaults');
define('wtf_fu_DEFAULTS_WORKFLOW_KEY', 'wtf-fu-workflow-defaults');

define('wtf_fu_DEFAULT_WORKFLOW_TEMPLATE', '<div class="panel panel-default tbs">
        <div class="panel-heading">
            <h1 class="panel-title">
            <strong>[&nbsp;%%WTF_FU_WORKFLOW_NAME%%&nbsp;]</strong></h1>
            %%WTF_FU_WORKFLOW_BUTTON_BAR%%
            <h2>%%WTF_FU_WORKFLOW_STAGE_TITLE%%</h2>
            %%WTF_FU_WORKFLOW_STAGE_HEADER%%
        </div>
        <div class="panel-body tbs">
            %%WTF_FU_WORKFLOW_STAGE_CONTENT%%
        </div>        
        <div class="panel-footer">
            %%WTF_FU_WORKFLOW_BUTTON_BAR%%
            <p><small>%%WTF_FU_WORKFLOW_STAGE_FOOTER%%</small></p>
            <p><small>%%WTF_FU_POWERED_BY_LINK%%</small></p>
        </div>
    </div>');

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
        
        log_me('__construct  Wtf_Fu_Option_Definitions ');      
               
        /**
         * The field names / default values 
         * @var type static array
         */
        $this->all_pages_default_options = array(
            wtf_fu_DEFAULTS_PLUGIN_KEY => array(
                'remove_all_data_on_uninstall' => '0',
                'show_powered_by_link' => '0'
            ),
            wtf_fu_DEFAULTS_UPLOAD_KEY => array(
                'deny_public_uploads'=> '1',
                'wtf_upload_dir' => 'wtf-fu_files',
                'wtf_upload_subdir' => 'default',
                'accept_file_types' => 'jpg|jpeg|mpg|mp3|png|gif|wav|ogg',
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
                'id' => '',
                'testing_mode' => '0',
                'name' => 'New workflow ....',
                'default_back_label' => "Go Back",
                'default_next_label' => "Next",
            ),
            wtf_fu_DEFAULTS_STAGE_KEY => array(
                'stage_title' => '',
                'header' => 'In this stage we will ....',
                'content_area' => 'Enter the workflow stage main body of content here.',
                'footer' => 'Press next to proceed.',
                'next_active' => '1',
                'next_label' => '',
                'next_js' => '" onClick="return confirm(\'Are you sure you want to proceed ?\');"',
                'back_active' => '1',
                'back_label' => '',
                'back_js' => '',
                'pre_hook' => '',
                'post_hook' => '',
            )
        );

        $this->all_pages_default_options = apply_filters('wtf_fu_all_pages_default_options_filter', $this->all_pages_default_options);


        /**
         * Set up the help texts for all fields. Keys should be identical to the 
         * above array.
         */
        $this->all_pages_default_labels = array(
            wtf_fu_DEFAULTS_PLUGIN_KEY => array(
                'remove_all_data_on_uninstall' =>
                'Check this to allow the removal of all the plugin and user 
                     workflow options data during uninstall.',
                'show_powered_by_link' => 'Support this plugin by including a powered by link to wtf-fu.com on your site.'
            ),
            wtf_fu_DEFAULTS_UPLOAD_KEY => array(
                'deny_public_uploads' => 'Restrict access to logged in users only. 
                 WARNING ! Do not uncheck this feature unless you have other 3rd party plugins that are protecting access to your pages, 
                 have implemented your own conditional logic around your embedded wtf-fu shortcodes, 
                 or you understand the risks and really do want to allow ANY user to be able to 
                 upload files to your site.',
                'wtf_upload_dir' =>
                'The default upload directory name. 
                    This will be under the users upload directory.
                    eg For a user with a user id = 4 this would be 
                    yoursiteroot/wp-content/uploads/4/<strong>wtf_upload_dir</strong>/',
                'wtf_upload_subdir' =>
                'You may optionally specify a default upload sub directory name.
                    If specified this will be appended to the upload_dir.
                     e.g. <code>yoursitedirecory/wp-content/uploads/4/wtf_upload_dir/<strong>default</strong>/</code>"',
                'accept_file_types' =>
                'Allowed upload file type extensions separated by a | character. 
                    This is later expanded to a regular expression so other regexp 
                    may be used in here as well.
                    e.g.<code><strong>gif|jp?g|png|mp3</strong></code>
                    This will be expanded internally to the regular expression <code>/\.(gif|jpe?g|png)$/i"</code>',
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
                    There is a <a href=\"http://www.wpbeginner.com/wp-tutorials/how-to-increase-the-maximum-file-upload-size-in-wordpress/\">tutorial here</a> that may help',
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
                'id' =>
                'Workflow id is assigned automatically to the first available empty.
                    workflow id when the workflow is first added or cloned.
                    This value cannot be modified.',
                'testing_mode' =>
                'Check to enable testing mode. 
                    In testing mode forward and next button will always be shown.',
                'name' =>
                'The name for this workflow.
                    You can retrieve this name by including the shortcode 
                    <code>[wtf_fu type=\"get\" value=\"workflow\" id=\"workflow_id_here\" key=\"name\"]</code>
                    in your stage content fields',
                'default_back_label' => 'Default Back Button text label. (can be overridden in stages)',
                'default_next_label' => 'Default Next Button text label. (can be overridden in stages)',
            ),
            wtf_fu_DEFAULTS_STAGE_KEY => array(
                'stage_title' =>
                'The text for the title bar for this stage.
                    The value for this field may be automatically displayed using the <code>%%WTF_FU_WORKFLOW_STAGE_TITLE%%</code>
                    in the workflow page_html_template (pro version only.)',
                'header' => 'Content to be displayed in the header.',
                'content_area' =>
                'The main content for the stage. 
                    You may embed other shortcodes in here. 
                    e.g. <code><strong>[wtf_fu_upload]</strong></code> to embed a file upload or 
                    <code><strong>[wtf_fu type=\'get\' value=\'workflow\' id=\'1\' key=\'name\']</strong></code>
                     to embed the name of the workflow with id=1',
                'footer' =>
                'Text that will appear in the stage footer section.',
                'next_active' => 'Allow user to go forward a stage from here. Activates the Next Button for this stage.',
                'next_label' => 'The label for the next button for this stage',
                'next_js' => '',
                'back_active' =>
                'Allow user to go back a stage from here. Causes the Back Button to display.',
                'back_label' => 'The label for the back button for this stage',
                'back_js' => 'Adhoc javascript to attach to the back button. 
                    e.g. onClick="return confirm(\'Are you sure ?\');"',
                'pre_hook' => 'Enter a user defined function to be executed before a user 
                enters this stage.',
                'post_hook' => 'Enter a user defined function to be executed after a user 
                leaves this stage.',          
            )
        );
        
        $this->all_pages_default_labels = apply_filters('wtf_fu_all_pages_default_labels_filter', $this->all_pages_default_labels);

        //log_me(array('all_pages_default_options' => $this->all_pages_default_options));
        //log_me(array("default_field_labels" => $this->all_pages_default_labels));

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
            )
        );
        
        $this->menu_page_values = apply_filters('wtf_fu_menu_page_values_filter', $this->menu_page_values);
        //log_me($this->menu_page_values);
        
   }

    /**
     * Return a sungleton instance of this class.
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

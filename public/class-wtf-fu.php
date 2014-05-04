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
define('wtf_fu_PUBLIC_ASSETS_URL', plugin_dir_url(__FILE__) . 'assets/');
define('wtf_fu_JQUERY_FILE_UPLOAD_URL', wtf_fu_PUBLIC_ASSETS_URL . 'jQuery-File-Upload-9.5.0/');
define('wtf_fu_JQUERY_FILE_UPLOAD_DEPENDS_URL', wtf_fu_PUBLIC_ASSETS_URL . 'blueimp-depends/');
define('wtf_fu_BOOTSTRAP_URL', wtf_fu_JQUERY_FILE_UPLOAD_DEPENDS_URL . 'bootstrap_3.0.3/');

define('wtf_fu_JQUERY_FILE_UPLOAD_HANDLER_FILE', plugin_dir_path(__FILE__) . 'includes/UploadHandler.php');


require_once( plugin_dir_path(__FILE__) . 'includes/class-wtf-fu-fileupload-shortcode.php' );
require_once( plugin_dir_path(__FILE__) . 'includes/class-wtf-fu-workflow-shortcode.php' );
require_once( plugin_dir_path(__FILE__) . 'includes/class-wtf-fu-show-files-shortcode.php' );

/**
 * Wtf_Fu class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * For Administrator functionality, refer to `class-wtf-fu-admin.php`
 *
 * @package Wtf_Fu
 * @author  Your Name <email@example.com>
 */
class Wtf_Fu {

    /**
     * Plugin version, used for cache-busting of style and script file
     * references.
     * @var     string
     */
    const VERSION = '1.2.4';

    /**
     * Unique plugin identifier.
     *
     * Used as the text domain when internationalizing strings of text. 
     * Its value should match the Text Domain file header in the main
     * plugin file.
     *
     * @var      string
     */
    protected $plugin_slug = 'work-the-flow-file-upload';

    /**
     * Instance of this class.
     */
    protected static $instance = null;

    /**
     * Initialize the plugin by setting localization and loading public scripts
     * and styles.
     */
    private function __construct() {

        log_me('__construct  Wtf_Fu ');
        log_me('memory=' . memory_get_usage(true) . "\n");
        log_me('peak memory=' . memory_get_peak_usage(true) . "\n");



        // Load plugin text domain.
        add_action('init', array($this, 'load_plugin_textdomain'));

        // Activate plugin when new blog is added
        add_action('wpmu_new_blog', array($this, 'activate_new_site'));

        add_action('wp_ajax_load_ajax_function', array($this, 'wtf_fu_load_ajax_function'));
        add_action('wp_ajax_nopriv_load_ajax_function', array($this, 'wtf_fu_load_ajax_function'));

        add_action('wp_ajax_wtf_fu_workflow', array($this, 'wtf_fu_ajax_workflow_function'));
        add_action('wp_ajax_nopriv_wtf_fu_workflow', array($this, 'wtf_fu_ajax_workflow_function'));

        add_action('wp_ajax_wtf_fu_show_files', array($this, 'wtf_fu_ajax_show_files_function'));
        add_action('wp_ajax_nopriv_wtf_fu_show_files', array($this, 'wtf_fu_ajax_show_files_function'));


        // Load public-facing style sheet and JavaScript.
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Short code hooks to methods which instantiate the required shortcode
        // handler classes and return the handler output.
        add_shortcode('wtf_fu', array($this, 'wtf_fu_shortcode'));
        add_shortcode('wtf_fu_upload', array($this, 'file_upload_shortcode'));
        add_shortcode('wtf_fu_show_files', array($this, 'show_files_shortcode'));
    }

    /**
     * Return the plugin slug.
     *
     * @since    1.0.0
     *
     * @return    Plugin slug variable.
     */
    public function get_plugin_slug() {
        return $this->plugin_slug;
    }

    /**
     * Return an instance of this class.
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

    /**
     * Fired when the plugin is activated.
     *
     * @since    1.0.0
     *
     * @param    boolean    $network_wide    True if WPMU superadmin uses
     *                                       "Network Activate" action, false if
     *                                       WPMU is disabled or plugin is
     *                                       activated on an individual blog.
     */
    public static function activate($network_wide) {

        if (function_exists('is_multisite') && is_multisite()) {

            if ($network_wide) {

                // Get all blog ids
                $blog_ids = self::get_blog_ids();

                foreach ($blog_ids as $blog_id) {

                    switch_to_blog($blog_id);
                    self::single_activate();
                }

                restore_current_blog();
            } else {
                self::single_activate();
            }
        } else {
            self::single_activate();
        }
    }

    /**
     * Fired when the plugin is deactivated.
     *
     * @since    1.0.0
     *
     * @param    boolean    $network_wide    True if WPMU superadmin uses
     *                                       "Network Deactivate" action, false if
     *                                       WPMU is disabled or plugin is
     *                                       deactivated on an individual blog.
     */
    public static function deactivate($network_wide) {

        if (function_exists('is_multisite') && is_multisite()) {

            if ($network_wide) {

                // Get all blog ids
                $blog_ids = self::get_blog_ids();

                foreach ($blog_ids as $blog_id) {

                    switch_to_blog($blog_id);
                    self::single_deactivate();
                }

                restore_current_blog();
            } else {
                self::single_deactivate();
            }
        } else {
            self::single_deactivate();
        }
    }

    /**
     * Fired when a new site is activated with a WPMU environment.
     *
     * @since    1.0.0
     *
     * @param    int    $blog_id    ID of the new blog.
     */
    public function activate_new_site($blog_id) {

        if (1 !== did_action('wpmu_new_blog')) {
            return;
        }

        switch_to_blog($blog_id);
        self::single_activate();
        restore_current_blog();
    }

    /**
     * Get all blog ids of blogs in the current network that are:
     * - not archived
     * - not spam
     * - not deleted
     *
     * @since    1.0.0
     *
     * @return   array|false    The blog ids, false if no matches.
     */
    private static function get_blog_ids() {

        global $wpdb;

        // get an array of blog ids
        $sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

        return $wpdb->get_col($sql);
    }

    /**
     * Fired for each blog when the plugin is activated.
     */
    private static function single_activate() {


        require_once( plugin_dir_path(__FILE__) . '../admin/includes/class-wtf-fu-options-admin.php' );
        $installed_ver = get_option("wtf-fu_version");

        /*
         * We do this whenever the plugin is activated to make sure that database options are always in sync and set to defaults
         * if they have not been futher customized by the user. 
         * 
         * This may not always be necessary, but it is fast and means we wont have problem's if the user values get out of sync with newly defined
         * options or need replacing if they have been deleted somehow.
         * 
         * For new installations this is also necessary so that the default options will exist before the options page is edited.
         * For example this is needed so that default exist for the demo to run "out of the box".
         */

        /*
         * plugin options.
         */
        $default_plugin_options = Wtf_Fu_Option_Definitions::get_instance()->get_page_option_fields_default_values(wtf_fu_DEFAULTS_PLUGIN_KEY);
        $plugin_options = get_option(wtf_fu_OPTIONS_DATA_PLUGIN_KEY);
        Wtf_Fu_Options_Admin::update_options_from_default_options(wtf_fu_OPTIONS_DATA_PLUGIN_KEY, $plugin_options, $default_plugin_options);

        /*
         * upload default options.
         */
        $default_upload_options = Wtf_Fu_Option_Definitions::get_instance()->get_page_option_fields_default_values(wtf_fu_DEFAULTS_UPLOAD_KEY);
        $upload_options = get_option(wtf_fu_OPTIONS_DATA_UPLOAD_KEY);
        Wtf_Fu_Options_Admin::update_options_from_default_options(wtf_fu_OPTIONS_DATA_UPLOAD_KEY, $upload_options, $default_upload_options);

        if ($installed_ver != self::VERSION) {

            /*
             * Do any required upgrades in here.
             */
            switch ($installed_ver) {
                case '0.1.0' :
                default :
                    // doesn't hurt to do this whenever a version change is made to make sure
                    // the db options are in sync with the defined default fields.

                    /*
                     * Workflow options need to be massaged to match the current
                     * default options.
                     */

                    $default_workflow_options = Wtf_Fu_Option_Definitions::get_instance()->get_page_option_fields_default_values(wtf_fu_DEFAULTS_WORKFLOW_KEY);
                    foreach (Wtf_Fu_Options_Admin::get_all_workflows(false) as $k => $v) {
                        // updates workflow options to be in sync with the current installations default option keys.
                        Wtf_Fu_Options_Admin::update_options_from_default_options($k, $v['options'], $default_workflow_options);
                    }

                    /*
                     * Workflow stage options also needed to be resynced with the default values. 
                     */

                    $default_stage_options = Wtf_Fu_Option_Definitions::get_instance()->get_page_option_fields_default_values(wtf_fu_DEFAULTS_STAGE_KEY);
                    foreach (Wtf_Fu_Options_Admin::get_all_workflow_stages(false) as $k => $v) {
                        // updates workflow stage options to be in sync with the current installations default option keys.
                        Wtf_Fu_Options_Admin::update_options_from_default_options($k, $v['options'], $default_stage_options);
                    }

                    break;
            }

            log_me("upgrading from $installed_ver to {self::VERSION}");

            update_option("wtf-fu_version", self::VERSION);
        }
    }

    /**
     * Fired for each blog when the plugin is deactivated.
     */
    private static function single_deactivate() {
        // call deactivate hook so dependant plugins can deactivate themselves.
        do_action("wtf_fu_deactivate");   
    }

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {

        $domain = $this->plugin_slug;
        $locale = apply_filters('plugin_locale', get_locale(), $domain);

        load_textdomain($domain, trailingslashit(WP_LANG_DIR) . $domain . '/' . $domain . '-' . $locale . '.mo');
    }

    /**
     * Register and enqueue public-facing style sheet.
     */
    public function enqueue_styles() {
        if (self::wtf_fu_has_shortcode('wtf_fu')) {

            //wp_enqueue_style($this->plugin_slug . '-bootstrapcdn-style', wtf_fu_BOOTSTRAP_URL . 'css/bootstrap.min.css', array(), self::VERSION);
            wp_enqueue_style($this->plugin_slug . '-bluimp-gallery-style', wtf_fu_JQUERY_FILE_UPLOAD_DEPENDS_URL . 'css/blueimp-gallery.min.css', array(), self::VERSION);
            wp_enqueue_style($this->plugin_slug . '-jquery-fileupload-style', wtf_fu_JQUERY_FILE_UPLOAD_URL . 'css/jquery.fileupload.css', array(), self::VERSION);
            wp_enqueue_style($this->plugin_slug . '-jquery-fileupload-ui-style', wtf_fu_JQUERY_FILE_UPLOAD_URL . 'css/jquery.fileupload-ui.css', array(), self::VERSION);
            
            $plugin_options = Wtf_Fu_Options::get_plugin_options();
            if (wtf_fu_get_value($plugin_options, 'include_plugin_style') == true) {
                wp_enqueue_style($this->plugin_slug . '-tbs-styles', plugins_url($this->plugin_slug) . '/public/assets/css/bootstrap.css', array(), Wtf_Fu::VERSION);
                wp_enqueue_style($this->plugin_slug . '-show-files-css', plugins_url($this->plugin_slug) . '/public/assets/css/wtf-fu-show-files.css', array(), Wtf_Fu::VERSION);               
            }          
        }
    }

    /**
     * Register and enqueues public-facing JavaScript files.
     */
    public function enqueue_scripts() {

        if (self::wtf_fu_has_shortcode('wtf_fu')) {

            
           // wp_enqueue_script($this->plugin_slug . '-plugin-script', plugins_url('assets/js/public.js', __FILE__), array(), self::VERSION);


            if (!wp_script_is('jquery')) {
                wp_enqueue_script('jquery', wtf_fu_JQUERY_FILE_UPLOAD_DEPENDS_URL . 'js/jquery.min.js');
            }
            
            wp_enqueue_script($this->plugin_slug . 'jquery-1.9.1.js', "//code.jquery.com/jquery-1.9.1.js");
            wp_enqueue_script($this->plugin_slug . 'jquery-ui.js', "//code.jquery.com/ui/1.10.4/jquery-ui.js", array('jquery'), self::VERSION, true);            

            wp_enqueue_script($this->plugin_slug . '-jquery-ui-widgit-js', wtf_fu_JQUERY_FILE_UPLOAD_URL . 'js/vendor/jquery.ui.widget.js', array('jquery'), self::VERSION, true);
            wp_enqueue_script($this->plugin_slug . '-blueimp-tmpl-js', wtf_fu_JQUERY_FILE_UPLOAD_DEPENDS_URL . 'js/tmpl.min.js', array('jquery'), self::VERSION, true);
            wp_enqueue_script($this->plugin_slug . '-blueimp-load-image-js', wtf_fu_JQUERY_FILE_UPLOAD_DEPENDS_URL . 'js/load-image.min.js', array('jquery'), self::VERSION, true);
            wp_enqueue_script($this->plugin_slug . '-blueimp-canvas-to-blob-js', wtf_fu_JQUERY_FILE_UPLOAD_DEPENDS_URL . 'js/canvas-to-blob.min.js', array('jquery'), self::VERSION, true);

            wp_enqueue_script($this->plugin_slug . '-tbs-js', plugins_url('assets/js/bootstrap.js', __FILE__), array('jquery'), self::VERSION);

            wp_enqueue_script($this->plugin_slug . '-blueimp-gallery-js', wtf_fu_JQUERY_FILE_UPLOAD_DEPENDS_URL . 'js/jquery.blueimp-gallery.min.js', array('jquery'), self::VERSION, true);
            wp_enqueue_script($this->plugin_slug . '-jquery-iframe-transport-js', wtf_fu_JQUERY_FILE_UPLOAD_URL . 'js/jquery.iframe-transport.js', array('jquery'), self::VERSION, true);

            wp_enqueue_script($this->plugin_slug . '-jquery-file-upload-js', wtf_fu_JQUERY_FILE_UPLOAD_URL . 'js/jquery.fileupload.js', array('jquery'), self::VERSION, true);
            wp_enqueue_script($this->plugin_slug . '-jquery-fileupload-process-js', wtf_fu_JQUERY_FILE_UPLOAD_URL . 'js/jquery.fileupload-process.js', array('jquery'), self::VERSION, true);

            wp_enqueue_script($this->plugin_slug . '-jquery-fileupload-image-js', wtf_fu_JQUERY_FILE_UPLOAD_URL . 'js/jquery.fileupload-image.js', array('jquery'), self::VERSION, true);
            wp_enqueue_script($this->plugin_slug . '-jquery-fileupload-audio-js', wtf_fu_JQUERY_FILE_UPLOAD_URL . 'js/jquery.fileupload-audio.js', array('jquery'), self::VERSION, true);
            wp_enqueue_script($this->plugin_slug . '-jquery-fileupload-video-js', wtf_fu_JQUERY_FILE_UPLOAD_URL . 'js/jquery.fileupload-video.js', array('jquery'), self::VERSION, true);

            wp_enqueue_script($this->plugin_slug . '-jquery-fileupload-validate-js', wtf_fu_JQUERY_FILE_UPLOAD_URL . 'js/jquery.fileupload-validate.js', array('jquery'), self::VERSION, true);
            wp_enqueue_script($this->plugin_slug . '-jquery-fileupload-ui-js', wtf_fu_JQUERY_FILE_UPLOAD_URL . 'js/jquery.fileupload-ui.js', array('jquery'), self::VERSION, true);


            //<!-- The XDomainRequest Transport is included for cross-domain file deletion for IE 8 and IE 9 -->
            //<!--[if (gte IE 8)&(lt IE 10)]>
            // <script src="js/cors/jquery.xdr-transport.js"></script>
            //<![endif]-->
            wp_enqueue_script($this->plugin_slug . '-jquery-xdr-transport-js', wtf_fu_JQUERY_FILE_UPLOAD_URL . 'js/cors/jquery.xdr-transport.js', array('jquery'), self::VERSION, true);

            
            $url = site_url('/wp-includes/js/wp-ajax-response.js');
            wp_enqueue_script('wp-ajax-response', $url, array('jquery'), Wtf_Fu::VERSION, true);
            
           

            $show_files_handle = $this->plugin_slug . '-show-files-js';
            wp_enqueue_script(
                    $show_files_handle, plugin_dir_url(__FILE__) . 'assets/js/wtf-fu-show-files.js', array('jquery', 'wp-ajax-response'), Wtf_Fu::VERSION, true);
            
            $ret = wp_localize_script($show_files_handle, 'showfiles_js_vars', array(
                'url' => admin_url('admin-ajax.php'),
            ));            
           
            $fileupload_handle = $this->plugin_slug . '-file-upload';

            if (!wp_script_is($fileupload_handle, 'enqueued')) {
                log_me('class-wtf-fu registering and enqueuing ' . $fileupload_handle);
                wp_register_script($fileupload_handle, plugin_dir_url(__FILE__) . 'assets/js/wtf-fu-file-upload.js', array('jquery', 'wp-ajax-response'), Wtf_Fu::VERSION, true);
                wp_enqueue_script($fileupload_handle);

                $ret = wp_localize_script($fileupload_handle, 'WtfFuAjaxVars', array('url' => admin_url('admin-ajax.php'),
                    'absoluteurl' => wtf_fu_JQUERY_FILE_UPLOAD_URL . 'cors/result.html?%s'
                ));

                log_me("uploadFilesHtml  wp_localize_script for $fileupload_handle = $ret");
            } else {
                log_me("$fileupload_handle is already enqueued");
            }
            
            
           $workflow_handle = $this->plugin_slug . '-workflow-js';
            wp_enqueue_script($workflow_handle, plugin_dir_url(__FILE__) . 'assets/js/wtf-fu-workflow.js', 
                    array('jquery', 'wp-ajax-response', $fileupload_handle, $show_files_handle), 
                    Wtf_Fu::VERSION, true);

            $ret = wp_localize_script($workflow_handle, 'workflow_js_vars', array(
                'action' => 'wtf_fu_workflow',
                'url' => admin_url('admin-ajax.php'),
                'fn' => 'generate_page'
            ));            
            
        }
    }

    function file_upload_shortcode($attr) {
        $shortcode_instance = new Wtf_Fu_Fileupload_Shortcode($attr);
        $content = $shortcode_instance->generate_content();
        return $content;
    }

    function show_files_shortcode($attr) {
        $shortcode_instance = Wtf_Fu_Show_Files_Shortcode::get_instance();
        $shortcode_instance->set_options($attr);
        $content = $shortcode_instance->generate_content();
        return $content;
    }

    /**
     * Wrapper that delegates the file upload ajax action hook to static method
     * in the class that manages file uploads.
     * 
     * Necessary because WP insists that Ajax hooks be hooked in the main 
     * plugin class.
     */
    function wtf_fu_load_ajax_function() {
        Wtf_Fu_Fileupload_Shortcode::wtf_fu_load_ajax_function();
    }

    function wtf_fu_ajax_workflow_function() {
        Wtf_Fu_Workflow_Shortcode::get_instance()->wtf_fu_ajax_workflow_function();
    }

    function wtf_fu_ajax_show_files_function() {
        Wtf_Fu_Show_files_Shortcode::wtf_fu_ajax_show_files_function();
    }

    function workflow_controller() {
        Wtf_Fu_Workflow_Shortcode::workflow_controller();
    }

    function wtf_fu_shortcode($attr) {

        // default to workflow shortcode
        if (!array_key_exists('type', $attr)) {
            $attr['type'] = 'workflow';
        }

        switch ($attr['type']) {

            case 'workflow' :
                $options = shortcode_atts(array('type' => 'workflow', 'id' => ''), $attr);

                $wf_instance = Wtf_Fu_Workflow_Shortcode::get_instance();
                $wf_instance->init_options($options);
                $content = $wf_instance->generate_content($options);

                if (!empty($content)) {
                    //global $shortcode_tags;
                    //log_me(array('shortcode_tags' => $shortcode_tags));
//                    $content = do_shortcode($content); // Process any embedded short codes.
                    //$content = apply_filters( 'the_content', $content );
                }

                return $content;

            case 'fileupload' :
                return $this->file_upload_shortcode($attr);


            case 'show_files' :
                return $this->show_files_shortcode($attr);
                break;

            case 'get' :
                // 'ID', 'user_login' or 'user_email', 'user_firstname', 'user_lastname', 'display_name'
                switch ($attr['value']) {
                    case 'ID' :
                    case 'user_login' :
                    case 'user_email' :
                    case 'user_firstname' :
                    case 'user_lastname' :
                    case 'display_name' :
                        return getUserInfo($attr['value']);
                    case 'admin_email' :
                        return get_option('admin_email');
                    case 'site_admin_url' :
                        return site_url('wp-admin');
                    case 'stage' :  // users current stage in workflow attr['id']
                        return Wtf_Fu_Options::get_user_workflow_stage($attr['id']);
                    case 'workflow' :
                        $error = shortcode_requires("wtf_fu type=\"get\"", array('value', 'id', 'key'), $attr);
                        if ($error) {
                            return $error;
                        }
                        switch ($attr['key']) {
                            case 'files_count' :
                                return Wtf_Fu_Options::get_workflow_files_count($attr['id']);

                            default : // assume key is a workflow option attribute.
                                return Wtf_Fu_Options::get_workflow_options($attr['id'], $attr['key']);
                        }
                    default :
                        return "unknown 'value' attribute for type='get' value={$attr['value']}";
                }
            default :
                return "undefined shortcode type= {$attr['type']}";
        }

        return null;
    }

    /**
     * Check the current post for the existence of a short code.
     * If we are not directly called as the result of an initial post then 
     * returns false.
     *  
     * @param type $shortcode
     * @return boolean
     */
    private static function wtf_fu_has_shortcode($shortcode = '') {

        if (!get_post()) {
            return false;
        }
        
        $post_to_check = get_post(get_the_ID());

        // false because we have to search through the post content first  
        $found = false;

        // if no short code was provided, return false  
        if (!$shortcode || !$post_to_check) {
            return $found;
        }
        
        // check the post content for the short code  
        if (stripos($post_to_check->post_content, '[' . $shortcode) !== false) {
            // we have found the short code  
            $found = true;
        }

        // return our final results  
        return $found;
    }

}

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

require_once plugin_dir_path(__FILE__)
        . '../includes/class-wtf-fu-option-definitions.php';

require_once plugin_dir_path(__FILE__)
        . 'includes/class-wtf-fu-options-admin.php';

class Wtf_Fu_Admin {

    /**
     * Instance of this class.
     *
     * @since    1.0.0
     *
     * @var      object
     */
    protected static $instance = null;

    /**
     * Slug of the plugin screen.
     *
     * @since    1.0.0
     *
     * @var      string
     */
    protected $plugin_screen_hook_suffix = null;
    protected $plugin_slug;
    protected $options_instance;

    /**
     * Initialize the plugin by loading admin scripts & styles and adding a
     * settings page and menu.
     *
     * @since     1.0.0
     */
    private function __construct() {
        
        //log_me("admin constructor");

        $plugin = Wtf_Fu::get_instance();
        $this->plugin_slug = $plugin->get_plugin_slug();

        $this->options_instance = Wtf_Fu_Option_Definitions::get_instance();

        // Load admin style sheet and JavaScript.
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Add the options page and menu item.
        add_action('admin_menu', array($this, 'add_plugin_admin_menu'));

        // Add an action link pointing to the options page.
        $plugin_basename = plugin_basename(plugin_dir_path(realpath(dirname(__FILE__))) . $this->plugin_slug . '.php');
        add_filter('plugin_action_links_' . $plugin_basename, array($this, 'add_action_links'));

        // add to the admin init hook. This is called when the admin page first loads.
        add_action('admin_init', array($this, 'init_page_options'));

        add_action('wp_ajax_wtf_fu_admin_operations', array($this, 'wtf_fu_admin_operations_callback'));
        
    }        


    /**
     * Ajax callback from js.
     */
    function wtf_fu_admin_operations_callback() {

        ob_start();

        //log_me('wtf_fu_admin_operations_callback');

        $response_message = 'done';

        switch ($_REQUEST['operation']) {
            case 'add_new_workflow' :
                
                if (isset($_REQUEST['add_workflow_name'])) {
                    // Get the json file to clone from the listbox.
                    $file_to_clone = $_REQUEST['add_workflow_name'];
                    if (!empty($file_to_clone)) {
                        $file_to_clone_path = dirname(plugin_dir_path(__FILE__)) . '/examples/workflows/' . $file_to_clone;
                        $workflow_settings = json_decode(file_get_contents($file_to_clone_path), true);                        
                        $wf_index = Wtf_Fu_Options_Admin::create_workflow($workflow_settings);
                        $fname = basename($file_to_clone, '.json');
                        $response_message = "A a new $fname workflow has been added with with ID = $wf_index.";
                    } else {
                        $wf_index = Wtf_Fu_Options_Admin::add_new_workflow();
                        $response_message = "A new empty workflow has been added with ID = $wf_index.";
                    }
                }
                break;
            
            case 'add_new_default_email_template' :
                if (has_filter('wtf_fu_add_new_default_email_template_filter')) {
                    $index = apply_filters('wtf_fu_add_new_default_email_template_filter', null);
                    $response_message = "A new email template with id = $index has been added.";
                } else {
                    log_me(" operation action not found for : {$_REQUEST['operation']}");
                }
                break;
            case 'add_new_default_workflow_template' :
                if (has_filter('wtf_fu_add_new_default_workflow_template_filter')) {
                    $index = apply_filters('wtf_fu_add_new_default_workflow_template_filter', null);
                    $response_message = "A new workflow template with id = $index has been added.";
                } else {
                    log_me(" operation action not found for : {$_REQUEST['operation']}");
                }
                break;

            default :
                log_me("invalid operation {$_REQUEST['operation']}");
        }

        $response = array(
            'what' => 'stuff',
            'action' => 'wtf_fu_admin_operations',
            'id' => '1', //new WP_Error('oops','I had an accident.'),
            'data' => $response_message,
                //    'supplemental'
        );

        $xmlResponse = new WP_Ajax_Response($response);
        $xmlResponse->send();

        ob_end_clean();
        exit;
    }

    /**
     * Return an instance of this class.
     * @since     1.0.0
     * @return    object    A singleton instance of this class.
     */
    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Register and enqueue admin-specific style sheet.
     *
     * @since     1.0.0
     *
     * @return    null    Return early if no settings page is registered.
     */
    public function enqueue_admin_styles() {

        if (!isset($this->plugin_screen_hook_suffix)) {
            return;
        }

        $screen = get_current_screen();
        if ($this->plugin_screen_hook_suffix == $screen->id) {
            wp_enqueue_style($this->plugin_slug . '-admin-styles', plugins_url('assets/css/admin.css', __FILE__), array(), Wtf_Fu::VERSION);
        }
    }

    /**
     * Register and enqueue admin-specific JavaScript.
     * @since     1.0.0
     * @return    null    Return early if no settings page is registered.
     */
    public function enqueue_admin_scripts() {

        if (!isset($this->plugin_screen_hook_suffix)) {
            return;
        }

        $screen = get_current_screen();
        if ($this->plugin_screen_hook_suffix == $screen->id) {
            //  log_me('admin enqueing');

            $jsarr = array('jquery-ui-accordion');           // WP included script tags.
            foreach ( $jsarr as $js ) {
                wp_enqueue_script($js);
            }    
            
            $url = site_url('/wp-includes/js/wp-ajax-response.js');
            wp_enqueue_script('wp-ajax-response', $url, array('jquery'), Wtf_Fu::VERSION, true);

            $script_tag = $this->plugin_slug . '-admin-script';
            wp_enqueue_script($script_tag, plugins_url('assets/js/admin.js', __FILE__), array('jquery', 'wp-ajax-response'), Wtf_Fu::VERSION, true);
                      
            wp_enqueue_script($this->plugin_slug . '-plugin-script', plugins_url('../public/assets/js/public.js', __FILE__), array('jquery'), Wtf_Fu::VERSION, true);           
        } 
    }

    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {

        /*
         * Add a settings page to the Settings menu.
         */
        $wtf_title = 'wtf-fu';
        $wtf_fulltitle = 'Work The Flow / File Upload';

        add_options_page(
                __($wtf_fulltitle, $this->plugin_slug), __($wtf_fulltitle, $this->plugin_slug), //slug used as the text domain.
                'manage_options', $this->plugin_slug, array($this, 'display_plugin_admin_page') // callback.
        );


        /*
         * Add the same page as a menu.
         */
        $this->plugin_screen_hook_suffix = $menu_page_hook = add_menu_page(
                __($wtf_fulltitle, $this->plugin_slug), __($wtf_fulltitle, $this->plugin_slug), //slug used as the text domain.
                'manage_options', $this->plugin_slug, // The ID used to bind submenu items to this menu
                array($this, 'display_plugin_admin_page') // callback.
        );

        /*
         * Add submenu pages.
         */
        $menu_pages = $this->options_instance->get_menu_page_values();
        // log_me(array('pages'=> $menu_pages));
        foreach ($menu_pages as $page_key => $values) {

            $sub_menu_page_hook = add_submenu_page(
                    // The ID of the top-level menu page to which this submenu item belongs
                    $this->plugin_slug,
                    // The value used to populate the browser's title bar when the menu page is active
                    __($values['title'], $this->plugin_slug),
                    // The label of this submenu item displayed in the menu    
                    __($values['title'], $this->plugin_slug),
                    // What roles are able to access this submenu item
                    'administrator',
                    // The ID used to represent this submenu item
                    $this->plugin_slug . '&tab="' . $page_key . '"',
                    //create_function(null, 'display_plugin_admin_page( "' . $page_key . '");')
                    array($this, 'display_plugin_admin_page')
            );
            //        log_me(array('sub-menupagehook' => $sub_menu_page_hook));
        }
        //add_action('load-'.$this->plugin_screen_hook_suffix, array($this, 'init_page_options'));

        add_action('load-' . $this->plugin_screen_hook_suffix, array($this, 'wtf_fu_help_tab'));
    }

    function wtf_fu_help_tab() {

        $screen = get_current_screen();

        /*
         * Check if current screen is Admin Page
         * Don't add help tabs if it's not
         */
        if ($screen->id != $this->plugin_screen_hook_suffix)
            return;

        $page_id = wtf_fu_get_page_identifier_from_request();
        $tabs = array();

        switch ($page_id) {
            case 'plugin-options' :
                $tabs[] = array('id' => 'overview', 'title' => __('Overview'), 'content' => wtf_fu_get_admininterface_info('System Options'));
                break;

            case 'upload-options' :
                $tabs[] = array('id' => 'overview', 'title' => __('Overview'), 'content' => wtf_fu_get_admininterface_info('File Upload'));
                $tabs[] = array('id' => 'upload attributes', 'title' => __('[wtf_fu_upload] Shortcode Attributes'), 'content' => get_shortcode_info_table('wtf_fu_upload'));                

                break;

            case 'workflows' :
                $tabs[] = array('id' => 'overview', 'title' => __('Overview'), 'content' => wtf_fu_get_admininterface_info('Workflows'));               
                break;

            case 'workflows-workflow-options-edit' :
            case 'workflows-workflow-options' :
                $tabs[] = array('id' => 'overview', 'title' => __('Overview'), 'content' => wtf_fu_get_admininterface_info('Workflow Options'));
                $tabs[] = array('id' => 'shortcuts', 'title' => __('Shortcuts'), 'content' => wtf_fu_get_shortcuts_table());               
                break;

            case 'workflows-workflow-stage-options' :
                $tabs[] = array('id' => 'overview', 'title' => __('Overview'), 'content' => wtf_fu_get_admininterface_info('Workflow Stage Options'));
                $tabs[] = array('id' => 'shortcuts', 'title' => __('Shortcuts'), 'content' => wtf_fu_get_shortcuts_table());                
                break;

            case 'user-options' :
                $tabs[] = array('id' => 'overview', 'title' => __('Overview'), 'content' => wtf_fu_get_admininterface_info('Manage Users'));
                $tabs[] = array('id' => 'User Options', 'title' => __('User Files'), 'content' => wtf_fu_get_admininterface_info('User Options'));
                break;
            
            case 'user-options-user' :
                $tabs[] = array('id' => 'overview', 'title' => __('Overview'), 'content' => wtf_fu_get_admininterface_info('User Options'));
                break;
            
            case 'templates' :
                $tabs[] = array('id' => 'overview', 'title' => __('Overview'), 'content' => wtf_fu_get_admininterface_info('Templates'));
                $tabs[] = array('id' => 'templates', 'title' => __('Templates'), 'content' => wtf_fu_get_general_info('Templates')); 
                $tabs[] = array('id' => 'shortcuts', 'title' => __('Shortcuts'), 'content' => wtf_fu_get_shortcuts_table()); 
                                   
                break;

            case 'templates-edit-email' :
                $tabs[] = array('id' => 'overview', 'title' => __('Overview'), 'content' => wtf_fu_get_admininterface_info('Email Templates'));
                $tabs[] = array('id' => 'shortcuts', 'title' => __('Shortcuts'), 'content' => wtf_fu_get_shortcuts_table());                
                break;

            case 'templates-edit-workflow' :
                $tabs[] = array('id' => 'overview', 'title' => __('Overview'), 'content' => wtf_fu_get_admininterface_info('Workflow Templates'));
                $tabs[] = array('id' => 'shortcuts', 'title' => __('Shortcuts'), 'content' => wtf_fu_get_shortcuts_table());
                break;
            case 'documentation' :
                $tabs[] = array('id' => 'overview', 'title' => __('Overview'), 'content' => wtf_fu_get_admininterface_info('Documentation'));
                break;            
        }

        foreach ($tabs as $tab) {
            $screen->add_help_tab($tab);
        }   
    }

    /**
     * Render the settings page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_page() {

        $tab = wtf_fu_get_value($_GET, 'tab');

        if (empty($tab)) {
            $tab = wtf_fu_PAGE_PLUGIN_KEY;
        }

        //log_me("display_plugin_admin_page page_key = '{$tab}' ");
        // the main tabbed top level page.
        include_once( 'views/admin.php' );

        switch ($tab) {

            case wtf_fu_PAGE_WORKFLOWS_KEY :

                $wftab = wtf_fu_get_value($_GET, 'wftab');
                $wf_id = wtf_fu_get_value($_GET, 'wf_id');
                $stage_id = wtf_fu_get_value($_GET, 'stage_id', true);

                switch ($wftab) {
                    case wtf_fu_PAGE_WORKFLOW_OPTION_KEY :
                        $options_data_key = Wtf_Fu_Option_Definitions::get_workflow_options_key($wf_id);
                        include_once( 'views/admin-workflow-edit.php' );
                        echo '<form method="post" action="options.php">';
                        submit_button();
                        settings_fields($options_data_key);
                        do_settings_sections($options_data_key);
                        submit_button();
                        echo '</form>';
                        break;
                    case wtf_fu_PAGE_WORKFLOW_STAGE_OPTION_KEY :
                        $options_data_key = Wtf_Fu_Option_Definitions::get_workflow_stage_key($wf_id, $stage_id);
                        include_once( 'views/admin-workflow-edit.php' );
                        echo '<form method="post" action="options.php">';
                        submit_button();
                        settings_fields($options_data_key);
                        do_settings_sections($options_data_key);
                        submit_button();
                        echo '</form>';
                        break;
                    default : // default to the workflows list page.
                        include_once( 'views/admin-workflows.php' );
                        break;
                }
                break;
            case wtf_fu_PAGE_USERS_KEY :
                $action = wtf_fu_get_value($_GET, 'wtf-fu-action');
                if ($action === 'user') {
                    include_once ( 'views/admin-user-settings.php');
                } else {
                    include_once( 'views/admin-users.php' );
                }
                break;
            case wtf_fu_PAGE_PLUGIN_KEY :
                echo '<form method="post" action="options.php">';
                submit_button();
                //settings_errors();
                settings_fields(wtf_fu_OPTIONS_DATA_PLUGIN_KEY);
                do_settings_sections(wtf_fu_OPTIONS_DATA_PLUGIN_KEY);
                submit_button();
                echo '</form>';
                break;

            case wtf_fu_PAGE_UPLOAD_KEY :
                echo '<form method="post" action="options.php">';
                submit_button();
                //settings_errors();
                settings_fields(wtf_fu_OPTIONS_DATA_UPLOAD_KEY);
                do_settings_sections(wtf_fu_OPTIONS_DATA_UPLOAD_KEY);
                submit_button();
                echo '</form>';
                break;

            case wtf_fu_PAGE_DOCUMENATION_KEY :
                include_once ( 'views/documentation.php');
                break;

            case wtf_fu_PAGE_TEMPLATES_KEY :
                if (!has_action('wtf_fu_dispay_plugin_admin_page')) {
                    include_once ( 'views/admin-templates-nonpro.php');
                }

            default :
        }

        if (has_action('wtf_fu_dispay_plugin_admin_page')) {
            return do_action('wtf_fu_dispay_plugin_admin_page');
        }
    }

    /**
     * Add settings action link to the plugins page.
     *
     * @since    1.0.0
     */
    public function add_action_links($links) {
        log_me(array('add_action_links' => $links));
        return array_merge(
                array(
            'settings' => '<a href="' . admin_url('options-general.php?page=' . $this->plugin_slug) . '">' . __('Settings', $this->plugin_slug) . '</a>'
                ), $links
        );
    }

    /**
     * 
     * Called fron the admin_init hook.
     * 
     * Capture REQUEST vars to pass to callbacks resistration for the 
     * current page.
     * 
     * Here we determine :
     * option_data_key,     - the key into the options for this section.
     * page_key             - the name of the page.
     * default_values_key   - the key to the default options array.
     * wf_id                - id for the workflow if set
     * stage_id,            - stage number
     *  
     * These values are then passed on for Register and Initialization of 
     * the required option page settings. 
     * 
     * Note that we may arrive here either from a direct click on a page or tab
     * or via a callback from options.php once a submit has been made.
     * 
     * Because this is an admin_init hook we need to exit as soon as we have
     * determined from the _REQUEST that this is not one of our pages.
     * 
     */
    public function init_page_options() {

        //log_me(array("init_page_options hook _REQUEST" => $_REQUEST));

        $init_args = array();

        foreach (array('page', 'tab', 'wftab', 'option_page', 'wtf-fu-action', 'wf_id', 'id', 'template-type') as $var) {
            $init_args[$var] = wtf_fu_get_value($_REQUEST, $var);
        }

        /* stage_id may legitimately be 0 so allow empty values to be returned. */
        $init_args['stage_id'] = wtf_fu_get_value($_REQUEST, 'stage_id', true);


        if (
                (!$init_args['page'] && !$init_args['option_page'] )
                // page not for this plugin.
                || ( $init_args['page'] && $init_args['page'] !== $this->plugin_slug )
                // user page does not require options setup.
                || ( $init_args['tab'] && $init_args['tab'] === wtf_fu_PAGE_USERS_KEY )
                // documentation page does not require options setup.               
                || ( $init_args['tab'] && $init_args['tab'] === wtf_fu_PAGE_DOCUMENATION_KEY )
        ) {
            return; // no options init required, or not a page for us.
        }

        // if workflow list then check if we need to do any clones or deletes.
        if ($init_args['tab'] && $init_args['tab'] === wtf_fu_PAGE_WORKFLOWS_KEY && !$init_args['wftab']) {
            $this->do_bulk_workflow_actions();  // will re-direct and exit if any delete or clone actions are done.
            // Workflows list page has no options to set up unless the 'wftab' sub page is defined.
            // there is nothing more to do;
            return;
        }

        if (isset($_REQUEST['delete_stage']) && isset($_GET['wf_id'])) {
            Wtf_Fu_Options_Admin::delete_stage_and_reorder($_GET['wf_id'], $_REQUEST['delete_stage']);
            // remove the delete tab and redirect back to page.
            wp_redirect(remove_query_arg('delete_stage', $_SERVER['REQUEST_URI']));
            exit;
        }

        // Templates list page has not options to set up unless 
        // the 'template-type' is defined.                        
        if ($init_args['tab'] && $init_args['tab'] === wtf_fu_PAGE_TEMPLATES_KEY && $init_args['wtf-fu-action'] !== 'edit') {
            if (has_action('wtf_fu_process_templates_bulk_actions_action')) {
                do_action('wtf_fu_process_templates_bulk_actions_action');
            }
            return;
        }



        $returning_from_submit = false;

        /*
         * If returning from options.php submit our original request info is
         * lost. We need to parse the value for the option data key returned in 
         * 'option_page' to regenerate values for 'tab' and 'wftab'.
         */
        if (!$init_args['page'] && $init_args['option_page']) {

            $returning_from_submit = true;

            $init_args['page'] = $this->plugin_slug;

            $option_parse_results = self::parse_options_page_response($init_args['option_page']);
            // log_me(array('$option_parse_results' => $option_parse_results));
            /* merge the parsed results */
            if ($option_parse_results !== false) {
                $init_args = $option_parse_results + $init_args;
                //log_me(array('after merge init_args=' => $init_args));
            }
        }

        // If they clicked the WTF-FU menu then no tab will be set.
        // default to the Plugin options page.
        if (empty($init_args['tab'])) {
            $init_args['tab'] = wtf_fu_PAGE_PLUGIN_KEY;
        }

        /*
         * Now tab and wftab should all be set (or if not options then set up
         * is not required for the page and we will have already exited early).
         * 
         * Now work out the key for the default values array and the option 
         * database key, (if not already done in parse_options_page_response
         * call above) which will have already 
         * added the following keys where applicable.
         * 
         *      'option_defaults_array_key' => 
         *      'option_data_key' => 
         *      'workflow_id' 
         *      'stage_id'
         *      'section_title'
         * 
         * Only PLUGIN and UPLOAD should still require these values by now.
         * (see parse_options_page_response())
         */
        switch ($init_args['tab']) {

            case wtf_fu_PAGE_PLUGIN_KEY :
                $init_args['option_defaults_array_key'] = wtf_fu_DEFAULTS_PLUGIN_KEY;
                $init_args['option_data_key'] = wtf_fu_OPTIONS_DATA_PLUGIN_KEY;
                $init_args['section_title'] = 'General Plugin Options';
                break;

            case wtf_fu_PAGE_UPLOAD_KEY :
                $init_args['option_defaults_array_key'] = wtf_fu_DEFAULTS_UPLOAD_KEY;
                $init_args['option_data_key'] = wtf_fu_OPTIONS_DATA_UPLOAD_KEY;
                $init_args['section_title'] = 'Default File Upload Settings';
                break;

            case wtf_fu_PAGE_WORKFLOWS_KEY :

                switch ($init_args['wftab']) {
                    case wtf_fu_PAGE_WORKFLOW_OPTION_KEY :
                        $init_args['option_defaults_array_key'] = wtf_fu_DEFAULTS_WORKFLOW_KEY;
                        $init_args['option_data_key'] = Wtf_Fu_Option_Definitions::get_workflow_options_key($init_args['wf_id']);
                        $init_args['section_title'] = "Workflow ( id = {$init_args['wf_id']} ) Edit"; 

                        break;
                    case wtf_fu_PAGE_WORKFLOW_STAGE_OPTION_KEY :
                        $init_args['option_defaults_array_key'] = wtf_fu_DEFAULTS_STAGE_KEY;
                        $init_args['option_data_key'] = Wtf_Fu_Option_Definitions
                                ::get_workflow_stage_key($init_args['wf_id'], $init_args['stage_id']);
                        $init_args['section_title'] = "Workflow ( id = {$init_args['wf_id']}, stage = {$init_args['stage_id']} ) Edit";
                        break;
                    default :
                        die("unrecognized wftab {$init_args['wftab']}");
                }

                break;

            default :
                /*
                 * call filter hook to process additional tab options
                 */
                $init_args = apply_filters('wtf_fu_init_page_options_filter', $init_args);

            // log_me("tab '{$init_args['tab']}' not found for option_data_key setup");
        }

        // pass on the massage request vars on for setting up the callbacks.        
        $this->wtf_fu_initialize_options($init_args);
    }

    /*
     * check if any delete or clone actions are required in the request.
     */

    public function do_bulk_workflow_actions() {
        // log_me('in bulk workflow');
        $redirect = false;
        // One of possibly many requests for a bulk action.
        if (isset($_REQUEST['workflow'])) {

            foreach ($_REQUEST['workflow'] as $wf_id) {

                /* process bulk action ... */
                switch ($this->current_bulk_action()) {
                    case 'delete' :
                        Wtf_Fu_Options_Admin::delete_workflow($wf_id);
                        $redirect = true;
                        break;
                    case 'clone' :
                        Wtf_Fu_Options_Admin::clone_workflow($wf_id);
                        $redirect = true;
                        break;
                    default :
                }
            }
        }

        /* Check if any single action links have been clicked. */
        if (isset($_REQUEST['wtf-fu-action']) && isset($_REQUEST['wf_id'])) {
            switch ($_REQUEST['wtf-fu-action']) {
                case 'delete' :
                    Wtf_Fu_Options_Admin::delete_workflow($_REQUEST['wf_id']);
                    $redirect = true;
                    break;
                case 'clone' :
                    Wtf_Fu_Options_Admin::clone_workflow($_REQUEST['wf_id']);
                    $redirect = true;
                    break;
                default :
            }
        }

        //log_me($_SERVER['REQUEST_URI']);

        if ($redirect) {
            $redirect_uri = sprintf("?page=%s&tab=%s", $_REQUEST['page'], $_REQUEST['tab']);
            //log_me( array('redirect url' => $redirect_uri) );
            wp_safe_redirect($redirect_uri);
            exit;
        }
    }

    /**
     * returns the bulk action request var.
     * @return boolean
     */
    function current_bulk_action() {
        if (isset($_REQUEST['action']) && -1 != $_REQUEST['action'])
            return $_REQUEST['action'];

        if (isset($_REQUEST['action2']) && -1 != $_REQUEST['action2'])
            return $_REQUEST['action2'];

        return false;
    }

    /**
     * generic function to do all options intialization for a page.
     * @param type $page
     */
    public function wtf_fu_initialize_options($init_args) {
//log_me(array('wtf_fu_initialize_options' => $init_args));
        $section_page_key = $init_args['tab'];
        if (!empty($init_args['wftab'])) {
            $section_page_key = $init_args['wftab'];
        }

        // maybe use the section_page_key in here ....
        $section = "{$init_args['tab']}_section";

        $option_defaults = $this->options_instance->
                get_page_option_fields_default_values(
                $init_args['option_defaults_array_key']);

        if (get_option($init_args['option_data_key']) == false) {
            //log_me("adding option for **{$init_args['option_data_key']}**");
            add_option($init_args['option_data_key'], apply_filters($init_args['option_data_key'], $option_defaults));
        }



        self::wtf_fu_do_add_settings_section(
                $section, __($init_args['section_title'], $this->plugin_slug), 
                create_function(null, 'Wtf_Fu_Admin::wtf_fu_section_callback( "' . $section_page_key . '");'), 
                $init_args['option_data_key'] 
                );

        /*
         *  Add all the fields from the default options array for this page.
         */
        foreach (array_keys($option_defaults) as $field) {

            $args = $init_args;
            $args['field'] = $field;  // add in the fieldname.

            self::wtf_fu_do_add_settings_field(
                    $field, __($field, $this->plugin_slug), array($this, 'wtf_fu_options_callback'), $init_args['option_data_key'], $section, $args
            );
        }

        self::wtf_fu_do_register_setting($init_args['option_data_key'], $init_args['option_data_key']);
    }

    /**
     * $page_title      The text to be displayed in the title tags of the page when the menu is selected
     * 
     * $menu_title      The text to be used for the menu
     * 
     * $capability      The capability required for this menu to be displayed to the user.
     * 
     * $menu_slug       The slug name to refer to this menu by (should be unique for this menu)
     * 
     * $callback        function The function to be called to output the content for this page.
     * 
     * Returns:         The resulting page's hook_suffix, 
     *                  or false if the user does not have the capability required.
     * 
     */
    static function wtf_fu_do_add_options_page($page_title, $menu_title, $capability, $menu_slug, $callback) {


        $ret = add_options_page($page_title, $menu_title, $capability, $menu_slug, $callback);
//        log_me(array(
//            'add_options_page ret=' => $ret,
//            'page_title' => $page_title,
//            'menu_title' => $menu_title,
//            'capability' => $capability,
//            'menu_slug' => $menu_slug,
//                // 'callback' => $callback  
//        ));
        return $ret;
    }

    /**
     *   $id,       Slug-name to identify the section. Used in the 'id' attribute of tags.
     * 
     *   $title,    Formatted title of the section. Shown as the heading for the section.
     * 
           *   $callback, Function that echos out any content at the top of the section
     * 
     * NOTE :
     * in this utility method we pass in the callback as a string before using create function
     * so we can log the callback string ....
     * 
     *   $page,     The slug-name of the settings page on which to show the section.
     *              Built-in pages include 'general', 'reading', 'writing', 'discussion',
     *              'media', etc. Create your own using add_options_page();
     */
    static function wtf_fu_do_add_settings_section($id, $title, $callback, $page) {
        add_settings_section($id, $title, $callback, $page);
    }

    /*
     * $id        Slug-name to identify the field. Used in the 'id' attribute of tags.
     *  
     * $title     Formatted title of the field. Shown as the label for the field during output.
     * 
     * $callback  Function that fills the field with the desired form inputs. 
     *            The function should echo its output.
     * 
     * $page      The slug-name of the settings page on which to show the section (general, reading, writing, ...).
     * 
     * $section   The slug-name of the section of the settings page in which to show the box (default, ...).
     * 
     * $args      Additional arguments
     */

    static function wtf_fu_do_add_settings_field($id, $title, $callback, $page, $section, $args) {
        add_settings_field($id, $title, $callback, $page, $section, $args);
    }

    static function wtf_fu_do_register_setting($option_group, $option_name) {
        register_setting($option_group, $option_name);
    }

    /* ------------------------------------------------------------------------ *
     * Section Callbacks
     * ------------------------------------------------------------------------ */

    /**
     * Callback method to render a section for a page.
     * Called with the page tab key it was created under.
     * OBSOLETE not used any more after 2.3.0
     */
    static function wtf_fu_section_callback($section_page) {

        switch ($section_page) {
            case wtf_fu_PAGE_PLUGIN_KEY :
                break;

            case wtf_fu_PAGE_UPLOAD_KEY :
                echo
                __('<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
        <p>Default attribute values for File Uploads.<br/>
        These settings will be used for all file uploads unless specifically overridden in the embedded shortcode itself.</p>
        <p>With the current settings using a shortcode of <strong>[wtf_fu_upload]</strong> (i.e. with NO attributes set) would be equivilent to using :</p>'
                        . '<p><code>' 
                        . wtf_fu_get_shortcode_with_default_attributes('wtf_fu_upload', false)
                        . '</code></p>'
                        . '<p style="color:red;">Note: These are <strong>just the default settings</strong>.<br/> '
                        . 'You can <strong>override any of these attribute values</strong> directly by including them when you use '
                        . 'the <strong>[wtf_fu_upload]</strong> shortcode in your pages or workflow content.<br/>'
                        . 'For example using <strong>[wtf_fu_upload wtf_upload_dir="demo" wtf_upload_subdir="music" accept_file_types="mp3|wav|ogg"]</strong>'
                        . ' will override the default values for the <strong>wtf_upload_dir</strong>, <strong>wtf_upload_subdir</strong> and <strong>accept_file_types</strong>'
                        . ' and use the default values for all the other attributes.'
                        . '<p>It is recommended to use attributes directly inside your <strong>[wtf_fu_upload]</strong> shortcodes rather than override them here so that the intent of the shortcode is clear'
                        . ' at the point where it is being used. You only need to make changes here if you use many shortcodes in your project that use different values '
                        . 'and wish to default the unspecified shortcode attributes here.</p>'
                        . ''
                        . '</div><div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">', 'wtf-fu');
                break;

            case wtf_fu_PAGE_WORKFLOW_OPTION_KEY :
                break;
            case wtf_fu_PAGE_WORKFLOW_STAGE_OPTION_KEY :
                break;
            default:
            //echo '<p>' . __("TODO no description available for this page [key={$section_page}].", 'wtf_fu') . '</p>';
        }
    }

    /**
     * Parse the option_page value returned in the request from
     * options.php 
     * 
     * This contains the data key into the wp_options table.
     * From this we can reverse engineer the tab values that were 
     * originally sent to the form.
     */
    static function parse_options_page_response($option_page) {

        switch ($option_page) {

            case wtf_fu_OPTIONS_DATA_PLUGIN_KEY :
                return array(
                    'tab' => wtf_fu_PAGE_PLUGIN_KEY,
                    'option_data_key' => $option_page
                );

            case wtf_fu_OPTIONS_DATA_UPLOAD_KEY :
                return array(
                    'tab' => wtf_fu_PAGE_UPLOAD_KEY,
                    'option_data_key' => $option_page
                );
            default :
        }

        /*
         * Stage page key  -- test first 
         */
        $pattern = Wtf_Fu_Option_Definitions::get_workflow_stage_key('([1-9][0-9]*)', '([0-9]+)');
        $matches = array();

        if (preg_match("/^{$pattern}$/", $option_page, $matches)) {
            return array(
                'tab' => wtf_fu_PAGE_WORKFLOWS_KEY,
                'wftab' => wtf_fu_PAGE_WORKFLOW_STAGE_OPTION_KEY,
                'option_defaults_array_key' => wtf_fu_DEFAULTS_STAGE_KEY,
                'option_data_key' => $option_page,
                'wf_id' => $matches[1],
                'stage_id' => $matches[2],
                'section_title' => "WorkFlow Edit (ID {$matches[1]} Stage {$matches[2]})",
            );
        }

        /*
         * Workflow page key 
         */
        $pattern = Wtf_Fu_Option_Definitions::get_workflow_options_key('([1-9][0-9]*)');

        if (preg_match("/^{$pattern}$/", $option_page, $matches)) {
            return array(
                'tab' => wtf_fu_PAGE_WORKFLOWS_KEY,
                'wftab' => wtf_fu_PAGE_WORKFLOW_OPTION_KEY,
                'option_defaults_array_key' => wtf_fu_DEFAULTS_WORKFLOW_KEY,
                'option_data_key' => $option_page,
                'wf_id' => $matches[1],
                'section_title' => "WorkFlow Edit (ID {$matches[1]})",
            );
        }

        // If nothing found then fire the action hook for addon plugins to handle.
        if (has_filter('wtf_fu_parse_options_page_response_filter')) {
            return apply_filters('wtf_fu_parse_options_page_response_filter', $option_page);
        }

        log_me(array('parse_options_page_response() unable to parse '
            . '$option_page returned from ' => $option_page));

        return false;
    }

    /**
     * Render a single field for its required page. 
     * 
     * This is the entry callback for all options based fields 
     * on all pages. The options that the callback was constructed with
     * are passed back to in as $args
     * 
     * It passes on rendering of the required field to an 
     * appropriate handler.
     */
    function wtf_fu_options_callback($args) {

        //log_me(array('wtf_fu_options_callback $args =' => $args));
        // The id to use for the html field.
        // this is the name of the field of the array of options
        // eg 'testing_mode'
        $option_id = $args['field'];

        // The option name e.g. wtf_fu_plugin_options['testing_mode'].
        // This is used as the 'name' attribute in the rendered 
        // html input fields.
        $option_name = "{$args['option_data_key']}[{$option_id}]";

        // Retrieve the entire array of this page's options                    
        $options = get_option($args['option_data_key']);

        // The value of the option array field.
        $val = '';

        // Retrieve the specific value for this field if it exists.
        if (array_key_exists($option_id, $options)) {
            $val = $options[$option_id];
        }

        switch ($args['tab']) {

            case wtf_fu_PAGE_PLUGIN_KEY :
                self::wtf_fu_render_plugin_options_field($args, $option_id, $option_name, $val);
                break;

            case wtf_fu_PAGE_UPLOAD_KEY :
                self::wtf_fu_render_upload_default_options_field($args, $option_id, $option_name, $val);
                break;

            case wtf_fu_PAGE_WORKFLOWS_KEY :

                switch ($args['wftab']) {
                    case wtf_fu_PAGE_WORKFLOW_STAGE_OPTION_KEY :
                        self::wtf_fu_render_stage_options_field($args, $option_id, $option_name, $val);
                        break;
                    case wtf_fu_PAGE_WORKFLOW_OPTION_KEY :
                        self::wtf_fu_render_workflow_options_field($args, $option_id, $option_name, $val);
                        break;
                    default :
                        log_me("undetected workflow wftab {$args['wftab']}");
                        break;
                }

                break;

            default :
                do_action('wtf_fu_options_callback_action', $args);

                // log_me("should have just called wtf_fu_options_callback_action for tab type **{$args['tab']}**");
                break;
        }
    }

    static function wtf_fu_render_plugin_options_field($args, $option_id, $option_name, $val) {

        $label = Wtf_Fu_Option_Definitions::get_instance()->get_page_option_field_label_value(
                $args['option_defaults_array_key'], $option_id);

        switch ($option_id) {
            case 'remove_all_data_on_uninstall' :
            case 'include_plugin_style' :
            case 'show_powered_by_link' :
                echo wtf_fu_checkbox($option_id, $option_name, $val, $label);
                break;
            default :
                do_action('wtf_fu_render_plugin_options_field_action', $args, $option_id, $option_name, $val);
        }
    }

    function wtf_fu_render_upload_default_options_field($args, $option_id, $option_name, $val) {

        $label = $this->options_instance->get_page_option_field_label_value(
                $args['option_defaults_array_key'], $option_id);

        switch ($option_id) {
            case 'auto_orient' :
            case 'create_medium_images' :
            case 'thumbnail_crop' :
            case 'deny_public_uploads':
            case 'use_public_dir':
                echo wtf_fu_checkbox($option_id, $option_name, $val, $label);
                break;
            case 'max_file_size' :
            case 'max_number_of_files':
            case 'medium_width' :
            case 'medium_height' :
            case 'thumbnail_width' :
            case 'thumbnail_height' :
                echo wtf_fu_text_input($option_id, $option_name, $val, 4, $label);
                break;
            case 'wtf_upload_dir' :
            case 'wtf_upload_subdir' :
            case 'accept_file_types' :
            case 'deny_file_types' :
            case 'inline_file_types' :
            case 'image_file_types' :
                echo wtf_fu_text_input($option_id, $option_name, $val, 80, $label);
                break;
            default :
                do_action('wtf_fu_render_upload_default_options_field_action', $args, $option_id, $option_name, $val);
        }
    }

    function wtf_fu_render_stage_options_field($args, $option_id, $option_name, $val) {

        $label = $this->options_instance->get_page_option_field_label_value(
                $args['option_defaults_array_key'], $option_id);

        switch ($option_id) {
            case 'next_js' :
            case 'back_js' :

                echo wtf_fu_textarea($option_id, $option_name, $val, $label, 1);
                break;

            case 'content_area' :

                echo "<p>$label</p>";
                wp_editor($val, $option_id, array("textarea_name" => $option_name, 'textarea_rows' => 25, 'wpautop' => false));
                break;

            case 'footer' :
                echo "<p>$label</p>";
                wp_editor($val, $option_id, array("textarea_name" => $option_name, 'textarea_rows' => 3, 'wpautop' => false));
                break;

            case 'header' :

                echo "<p>$label</p>";
                wp_editor($val, $option_id, array("textarea_name" => $option_name, 'textarea_rows' => 5, 'wpautop' => false));
                break;

            case 'next_active' :
            case 'back_active' :

                echo wtf_fu_checkbox($option_id, $option_name, $val, $label);
                break;

            case 'stage_title' :
                echo wtf_fu_text_input($option_id, $option_name, $val, 60, $label);
                break;

            case 'next_label' :
            case 'back_label' :
            case 'pre_hook' :
            case 'post_hook' :
                echo wtf_fu_text_input($option_id, $option_name, $val, 60, $label);
                break;

            default :
                do_action('wtf_fu_render_stage_options_field_action', $args, $option_id, $option_name, $val);
        }
    }

    function wtf_fu_render_workflow_options_field($args, $option_id, $option_name, $val) {
        $label = $this->options_instance->get_page_option_field_label_value(
                $args['option_defaults_array_key'], $option_id);

        switch ($option_id) {
            case 'id' :
                echo wtf_fu_text_only($option_id, $option_name, $val, 6, $label);
                break;
            case 'description' :
            case 'notes' :
                echo "<p>$label</p>";
                wp_editor($val, $option_id, array("textarea_name" => $option_name, 'textarea_rows' => 6, 'wpautop' => false));
                break;
            case 'next_js' :
            case 'back_js' :
            case 'testing_mode' :
            case 'include_plugin_style_default_overrides' :
                echo wtf_fu_checkbox($option_id, $option_name, $val, $label);
                break;

            case 'name' :
            case 'default_back_label' :
            case 'default_next_label' :
                echo wtf_fu_text_input($option_id, $option_name, $val, 60, $label);
                break;

            case 'page_template' :
                if (!has_action('wtf_fu_render_workflow_options_field_action')) {
                    $values = array(array('name' => 'none', 'value' => 0));
                    echo wtf_fu_list_box($option_id, $option_name, $val, $label, $values);
                }
                break;

            default :
        }
        do_action('wtf_fu_render_workflow_options_field_action', $args, $option_id, $option_name, $val);
    }

}

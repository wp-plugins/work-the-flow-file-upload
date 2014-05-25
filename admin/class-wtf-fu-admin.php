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
            case 'add_new_empty_workflow' :
                $response_message = Wtf_Fu_Options_Admin::add_new_workflow();
                //Wtf_Fu_Pro_Options_Admin::add_new_email_template();
                break;
            case 'add_new_demo_workflow' :
                $response_message = Wtf_Fu_Options_Admin::add_new_demo_workflow();
                break;
            case 'add_new_default_email_template' :
                if (has_action('wtf_fu_add_new_default_email_template_action')) {
                    do_action('wtf_fu_add_new_default_email_template_action');
                } else {
                    log_me(" operation action not found for : {$_REQUEST['operation']}");
                }
                break;
            case 'add_new_default_workflow_template' :
                if (has_action('wtf_fu_add_new_default_workflow_template_action')) {
                    do_action('wtf_fu_add_new_default_workflow_template_action');
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

            $url = site_url('/wp-includes/js/wp-ajax-response.js');
            wp_enqueue_script('wp-ajax-response', $url, array('jquery'), Wtf_Fu::VERSION, true);


            $script_tag = $this->plugin_slug . '-admin-script';

            wp_enqueue_script($script_tag, plugins_url('assets/js/admin.js', __FILE__), array('jquery', 'wp-ajax-response'), Wtf_Fu::VERSION, true);
            //wp_localize_script($script_tag, 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'we_value' => 1234 ) ); 
        } else {
           // log_me(array('not enqueing ' => array($this->plugin_screen_hook_suffix, $screen->id)));
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
//        $this->plugin_screen_hook_suffix = self::wtf_fu_do_add_options_page(
//            __($wtf_fulltitle, $this->plugin_slug), 
//            __($wtf_title, $this->plugin_slug), //slug used as the text domain.
//            'manage_options', 
//            $this->plugin_slug, 
//            array($this, 'display_plugin_admin_page') // callback.
//        );

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

        //add_action('load-' . $this->plugin_screen_hook_suffix, array($this, 'wtf_fu_help_tab'));
    }

    function wtf_fu_help_tab() {

        $screen = get_current_screen();
        //log_me(array('wtf_fu_help_tab' => $screen));


        /*
         * Check if current screen is My Admin Page
         * Don't add help tab if it's not
         */
        if ($screen->id != $this->plugin_screen_hook_suffix)
            return;

        $page_id = wtf_fu_get_page_identifier_from_request();

        log_me("page id =$page_id");

        $tabs = array();
        
        $options = array();

        switch ($page_id) {
            case 'plugin-options' :
                $tabs[] = array('id' => 'overview', 'title' => __('Overview'), 'content' => '<p>'
                    . __('Plugin Options page. These settings are for system wide plugin options. They define plugin behaviours for uninstalling, style sheet useage, and licensing.') . '</p>');

                $options = Wtf_Fu_Option_Definitions::get_instance()->get_page_option_fields_default_labels(wtf_fu_DEFAULTS_PLUGIN_KEY);

            case 'upload-options' :
                $options = Wtf_Fu_Option_Definitions::get_instance()->get_page_option_fields_default_labels(wtf_fu_DEFAULTS_UPLOAD_KEY);
                $tabs[] = array('id' => 'overview', 'title' => __('Overview'), 'content' => '<p>'
                    . __('File Upload Default Options. These settings provide default attribute values for the [wtf_fu_upload] shortcode. Set these to the values you most commonly use. These are the setting that will be used by the shortcode if they are not defined manually in the embedded shortcode.') . '</p>');

                break;
        }
        
        foreach ($options as $k => $v) {
            $tabs[] = array('id' => $k, 'title' => __($k), 'content' => $v);
        }

        foreach ($tabs as $tab) {
            $screen->add_help_tab($tab);
        }

        // Add my_help_tab if current screen is My Admin Page
//    $screen->add_help_tab(  );
//        $screen->add_help_tab( array(
//        'id'	=> 'usage',
//        'title'	=> __('Usage'),
//        'content'	=> '<p>' . __( 'Coming soon ... General Usage Information about this page.' ) . '</p>',
//    ) );
//        $screen->add_help_tab( array(
//        'id'	=> 'notes',
//        'title'	=> __('Notes :'),
//        'content'	=> '<p>' . __( 'This is not fully implemented yet in this release.<br/> The help information below will be moving up to here soon, to reduce clutter on the main screen.' ) . '</p>',
//    ) );        
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
                settings_errors();
                settings_fields(wtf_fu_OPTIONS_DATA_PLUGIN_KEY);
                do_settings_sections(wtf_fu_OPTIONS_DATA_PLUGIN_KEY);
                submit_button();
                echo '</form>';
                break;

            case wtf_fu_PAGE_UPLOAD_KEY :
                echo '<form method="post" action="options.php">';
                submit_button();
                settings_errors();
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
                // Workflows list page has not options to set up unless 
                // the 'wftab' sub page is defined.
                || ( $init_args['tab'] && $init_args['tab'] === wtf_fu_PAGE_WORKFLOWS_KEY && !$init_args['wftab'] )
                // Templates list page has not options to set up unless 
                // the 'template-type' is defined.                        
                || ( $init_args['tab'] && $init_args['tab'] === wtf_fu_PAGE_TEMPLATES_KEY && !$init_args['template-type'] )
        ) {
            return; // no options init required, or not a page for us.
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
                $init_args['section_title'] = 'Plugin Options';
                break;

            case wtf_fu_PAGE_UPLOAD_KEY :
                $init_args['option_defaults_array_key'] = wtf_fu_DEFAULTS_UPLOAD_KEY;
                $init_args['option_data_key'] = wtf_fu_OPTIONS_DATA_UPLOAD_KEY;
                $init_args['section_title'] = 'File Upload Settings';
                break;

            case wtf_fu_PAGE_WORKFLOWS_KEY :

                switch ($init_args['wftab']) {
                    case wtf_fu_PAGE_WORKFLOW_OPTION_KEY :
                        $init_args['option_defaults_array_key'] = wtf_fu_DEFAULTS_WORKFLOW_KEY;
                        $init_args['option_data_key'] = Wtf_Fu_Option_Definitions::get_workflow_options_key($init_args['wf_id']);
                        $init_args['section_title'] = "Workflow [{$init_args['wf_id']}]Settings";
                        break;
                    case wtf_fu_PAGE_WORKFLOW_STAGE_OPTION_KEY :
                        $init_args['option_defaults_array_key'] = wtf_fu_DEFAULTS_STAGE_KEY;
                        $init_args['option_data_key'] = Wtf_Fu_Option_Definitions
                                ::get_workflow_stage_key($init_args['wf_id'], $init_args['stage_id']);
                        $init_args['section_title'] = "Workflow [{$init_args['wf_id']} Stage {$init_args['stage_id']}] Settings";
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
            log_me("adding option for **{$init_args['option_data_key']}**");
            add_option($init_args['option_data_key'], apply_filters($init_args['option_data_key'], $option_defaults));
        }



        self::wtf_fu_do_add_settings_section(
                $section, __($init_args['section_title'], $this->plugin_slug), create_function(null, 'Wtf_Fu_Admin::wtf_fu_section_callback( "' . $section_page_key . '");'), $init_args['option_data_key']
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
        log_me(array(
            'add_options_page ret=' => $ret,
            'page_title' => $page_title,
            'menu_title' => $menu_title,
            'capability' => $capability,
            'menu_slug' => $menu_slug,
                // 'callback' => $callback  
        ));
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
     */
    static function wtf_fu_section_callback($section_page) {

        switch ($section_page) {
            case wtf_fu_PAGE_PLUGIN_KEY :
                echo '<p>' . __('You may configure General Plugin Options here.', 'wtf_fu-domain') . '</p>';
                break;

            case wtf_fu_PAGE_UPLOAD_KEY :
                echo
                __('<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
        <p>File Upload Settings</p>
        <p>Here you can set the default values for all the avaiable File Upload Options.</p>
        <p>These are the values that will be used unless they are overridden in a shortcode instance, so you should set these to the values you expect you will commonly use.</p>
        <p>The names of the fields on this page may all be used as attributes to the <code>[wtf_fu_upload]</code> short code to override these defaults.</p>
        <p>For example, a shortcode that would override all possible default values with the factory set default values (silly in practice but explanatory) would look like this</p>'
                        . '<p><code>' .
                        wtf_fu_get_example_short_code_attrs('wtf_fu_upload', Wtf_Fu_Option_Definitions::get_instance()->
                                        get_page_option_fields_default_values(wtf_fu_DEFAULTS_UPLOAD_KEY))
                        . '</code></p></div>', 'wtf-fu');
                break;

            case wtf_fu_PAGE_WORKFLOW_OPTION_KEY :
                echo
                __('<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
        <p>Workflow Settings</p>
        <p>Here you can set the settings that are applicable for all stages of a Workflow.</p>
        <p>You may also define the workflow page template here.</p>
        <p>You may also define some default stage field values that will apply to all stages unless overriden by a particular stage.</p></div>', 'wtf-fu');

                break;
            case wtf_fu_PAGE_WORKFLOW_STAGE_OPTION_KEY :

                echo __('<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
        <p>Stage Settings</p>
        <p>Here is where all the workflow content goes for a given stage.</p>
        <p>You may add content for the Header, Body and Footer sections of your page as well as set the stages title text and button label text.</p>
        <p>You may also set the ability of users to move to next or previous stages, and also attach simple javascript to the buttons (eg to caution a user before important actions).</p>
        <p>You can also provide optional function names to be called before the stage is entered and after the stage is left in the pre-hook and post-hook fields. These may for example be used to send confirmation emails, or do other post processing tasks like archiving.</p>
        <p>See the file <code>/wp-content/plugins/wtf-fu/examples/wtf-fu_hooks_example.php</code> for an example of how to do this.</p></div>', 'wtf-fu');

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
        $pattern = Wtf_Fu_Option_Definitions::get_workflow_stage_key('([1-9]+)', '([0-9]+)');
        $matches = array();

        if (preg_match("/^{$pattern}$/", $option_page, $matches)) {
            return array(
                'tab' => wtf_fu_PAGE_WORKFLOWS_KEY,
                'wftab' => wtf_fu_PAGE_WORKFLOW_STAGE_OPTION_KEY,
                'option_defaults_array_key' => wtf_fu_DEFAULTS_STAGE_KEY,
                'option_data_key' => $option_page,
                'wf_id' => $matches[1],
                'stage_id' => $matches[2],
                'section_title' =>
                "WorkFlow [id={$matches[1]}, stage {$matches[2]}] Settings",
            );
        }

        /*
         * Workflow page key 
         */
        $pattern = Wtf_Fu_Option_Definitions::get_workflow_options_key('([1-9]+)');

        if (preg_match("/^{$pattern}$/", $option_page, $matches)) {
            return array(
                'tab' => wtf_fu_PAGE_WORKFLOWS_KEY,
                'wftab' => wtf_fu_PAGE_WORKFLOW_OPTION_KEY,
                'option_defaults_array_key' => wtf_fu_DEFAULTS_WORKFLOW_KEY,
                'option_data_key' => $option_page,
                'wf_id' => $matches[1],
                'section_title' => "WorkFlow [id={$matches[1]}] Settings",
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
            case 'inline_file_types' :
            case 'image_file_types' :
                echo wtf_fu_text_input($option_id, $option_name, $val, 60, $label);
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

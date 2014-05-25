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

require_once( plugin_dir_path(__FILE__) . '../../includes/class-wtf-fu-options.php' );

/**
 * Description of class-wtf-fu-workflow-shortcode
 *
 * @author user
 */
class Wtf_Fu_Workflow_Shortcode {

    protected $plugin_slug = 'work-the-flow-file-upload'; //TODO make this a static const in main plugin class.
    protected static $instance = null;
    protected $options;

    /**
     * 
     * @param type $attr
     */
    private function __construct() {
        $this->enqueue_scripts();
        add_action('wtf_fu_workflow_init', array($this, 'workflow_controller'));        
    }

    /**
     * Register and enqueues JavaScript files specific to this shortcode.
     * 
     * NOTE : this is NOT called on the wp_enqueue hook as that is called before this
     * class is contructed.
     */
    public function enqueue_scripts() {
        
    }

    /**
     * Callback from ajax javascript.
     * 
     */
    public function wtf_fu_ajax_workflow_function() {
        
        ob_start();

        // log_me(array("wtf_fu_ajax_workflow_function" => $_REQUEST));

        switch ($_REQUEST['fn']) {

            case 'generate_page' :
                do_action("wtf_fu_workflow_init");
                $html = $this->generate_workflow_stage_page();
                break;

            default:
                break;
        }

        $response = array(
            'what' => 'stuff',
            'action' => 'wtf_fu_workflow',
            'id' => '1', //new WP_Error('oops','I had an accident.'),
            'data' => $html,
                //    'supplemental'
        );

        $xmlResponse = new WP_Ajax_Response($response);
        $xmlResponse->send();
        
        ob_end_clean();
        /*
         * Intentional, must always die or exit after an ajax call.
         */
        exit;
    }

    /**
     * Generates a page for the users current stage in a workflow.
     * 
     * @param type $options  shortcode attributes for initial construction.
     * if null then POST vars used instead for subsequent calls via ajax.
     * 
     * @return string  - html page of the content.
     */
    function generate_workflow_stage_page($options = null) {

        if (isset($options)) {
            if (!array_key_exists('id', $options)) {
                die("No 'id' attribute was found. Check that the shortcode contains the 'id' attribute. Try [wtf-fu id=\"1\"]  or  [wtf-fu type=\"workflow\" id=\"1\"]");
            }
            // id fron the passed shortcode options 
            $workflow_id = $options['id'];
        } else { // get the wfid from the post vars.
            if (!isset($_POST['workflow_id'])) {
                log_me('workflow_id not found in POST vars.');
            }
            $workflow_id = $_POST['workflow_id'];
        }


        // This workflows options.
        $wf_options = Wtf_Fu_Options::get_workflow_options($workflow_id);


        if (wtf_fu_get_value($wf_options, 'include_plugin_style_default_overrides') == true) {

            $style_handle = $this->plugin_slug . '-tbs-workflow-defaults';
            /*
             * if style is not already loaded from a prior call.
             */
            if (!wp_script_is($style_handle, 'enqueued')) {
                 
                wp_enqueue_style($style_handle, plugin_dir_url( __FILE__) . '../assets/css/workflow_default.css', array(), Wtf_Fu::VERSION);

                /*
                 * If a plugin has its own style then hook loading the style sheet.
                 */
                if (has_action('wtf_fu_enqueue_styles_action')) {
                    do_action('wtf_fu_enqueue_styles_action');
                } 
            }
        }

        // This user's workflow options including the current stage they are at in this workflow.
        $user_wf_options = Wtf_Fu_Options::get_user_workflow_options($workflow_id, 0, true);

        if ($user_wf_options === false) {
            // User not logged on so we get the stage from the form submit action.
            $stage = 0;

            // If the button_value is set then grab the stage from its value.
            if (isset($_REQUEST['button_value'])) {
                $stage = (int) $_REQUEST['button_value'];
            }
        } else {
            // The current stage after processing any form actions for 'prev' or 'next'
            $stage = $user_wf_options['stage'];
        }


        // Stage specfic options and content settings
        $stage_options = Wtf_Fu_Options::get_workflow_stage_options($workflow_id, $stage);

        $testing_mode = wtf_fu_get_value($wf_options, 'testing_mode');

        $buttons = $this->getButtonBarHtml($workflow_id, $stage, $stage_options, $wf_options);

        $template_id = wtf_fu_get_value($wf_options, 'page_template', true);

        $page_template = wtf_fu_DEFAULT_WORKFLOW_TEMPLATE;
        
        // Override default template if filter available.
        if ($template_id != 0 && has_filter('wtf_fu_get_workflow_template_filter')) {
             $page_template = apply_filters('wtf_fu_get_workflow_template_filter', $template_id);
        }
                   
        // First do the field level replacement in the template and in the content fields.
        $fields = array( 'template' => $page_template );
        
        
        // Do this twice to expand any additional shortcuts included by the first expansion.
        // eg. '%%WORKFLOW_STAGE_TITLE%%'  may expand to include '%%WORKFLOW_STAGE_NUMBER%%'       
        $fields = wtf_fu_replace_shortcut_values($fields, $workflow_id, $stage);
        $fields = wtf_fu_replace_shortcut_values($fields, $workflow_id, $stage); // intentional 2nd call.       
        
        // Buttons need to be done here.
        $replace = array ('%%WORKFLOW_BUTTON_BAR%%' => $buttons);

        $page = str_replace(array_keys($replace), array_values($replace), $fields['template']);
        
        // Attempt any embedded shortcodes, this may or may not see other plugins shortcodes.
        $page = do_shortcode($page);
        
        // Eval any php code inside of [wtf_eval] blocks.
        if (has_filter('wtf_fu_eval_filter')) {
            $page = apply_filters('wtf_fu_eval_filter', $page);
        }

        return $page;
    }

    /**
     * This method is called from the 'init' action hook
     * 
     * It checks if any workflow actions need to be taken such as advancing
     * to a previous or next stage and fires any events associated with 
     * the change in workflow state.
     * 
     * If changes to state were made as a result of POST vars the 
     * method will redirect back to the same page to clear the request.
     */
    public static function workflow_controller() {

        // log_me(array('workflow_controller request=' => $_REQUEST));

        if (!isset($_POST['workflow_id'])) {
            //    log_me('workflow_id not found in workflow_controller request.');
            return;
        }

        $workflow_id = $_POST['workflow_id'];

        $user_settings = Wtf_Fu_Options::get_user_workflow_options($workflow_id, 0, true);

        if ($user_settings === false) {
            // No User is logged on, use the stage from the form var.
            $old = (int) wtf_fu_get_value($_REQUEST, 'stage');
        } else {
            $old = (int) $user_settings['stage'];
        }

        // error_log("old=" . print_r($old, true));
        $old_stage_options = Wtf_Fu_Options::get_workflow_stage_options($workflow_id, $old);

        $new = (int) $old;

        // Inspect POST vars for a stage change.
        // only set to next or prev if it is an increment of 1
        // from the current stage.
        if (isset($_POST['button_name'])) {
            $new = (int) $_POST['button_value'];
        }


//        if (isset($_POST['prev']) && wtf_fu_get_value($old_stage_options, 'back_active')) {
//            $trynew = (int) $_POST['prev'];
//            if ($trynew === $new - 1) {
//                $new = $trynew;
//            }
//        }
//        if (isset($_POST['next']) && wtf_fu_get_value($old_stage_options, 'next_active')) {
//            $trynew = (int) $_POST['next'];
//            if ($trynew === $new + 1) {
//                $new = $trynew;
//            }
//        }
        if ($new < 0) {
            $new = 0;
        }
        // log_me("old : $old > new : $new");

        if ($new !== $old) {

            /*
             * If moving forwards then execute any post or pre 
             * hook functions.
             */
            if ($new > $old) {
                if (!empty($old_stage_options['post_hook'])) {

                    //log_me("calling {$old_stage_options['post_hook']}");

                    self::do_hooked_function($old_stage_options['post_hook']);
                }
                $new_stage_options = Wtf_Fu_Options::get_workflow_stage_options($workflow_id, $new);

                if (!empty($new_stage_options['pre_hook'])) {
                    // log_me("calling {$new_stage_options['pre_hook']}");
                    do_hooked_function($new_stage_options['pre_hook']);
                }
                
                // Do any hooked post processing for the old stage.
                if (has_action('wtf_fu_workflow_stage_post_processing_action')) {
                    do_action('wtf_fu_workflow_stage_post_processing_action', $workflow_id, $old);
                }
            }

            if ($user_settings !== false) {
                //log_me("updating user stage from $old => $new");
                Wtf_Fu_Options::update_user_workflow_stage($workflow_id, $new);
            }
        }
    }

    /**
     * Calls user defined hook function safely.
     * 
     * @param type $function
     * @return type
     */
    static function do_hooked_function($function) {
        $ret = false;

        // buffer output so we dont get any php warnings echo'd that will upset the ajax response.

        ob_start();
        try {
            $ret = call_user_func($function);
        } catch (Exception $exc) {
            log_me($exc->getTraceAsString());
        }
        //log_me (array("do_hooked_function ret" => $ret ));
        //log_me('output buffered response :' . ob_get_contents());
        ob_end_clean(); // discard any output.
        return $ret;
    }

    /**
     * returns the html form for the previous/next buttons  applicable for the curent workflow stage.
     * Enter description here ...
     * @param unknown_type $stage
     */
    private function getButtonBarHtml($workflow_id, $stage = 0, $stage_options, $wf_options) {

        $testing = wtf_fu_get_value($wf_options, 'testing_mode') == '1';

        $back_label = wtf_fu_get_value($stage_options, 'back_label');
        if ($back_label === false) {
            $back_label = wtf_fu_get_value($wf_options, 'default_back_label');
        }

        $next_label = wtf_fu_get_value($stage_options, 'next_label');
        if ($next_label === false) {
            $next_label = wtf_fu_get_value($wf_options, 'default_next_label');
        }

        $action_href = admin_url() . 'admin-ajax.php';

        $ret = "<form id='wtf_workflow_form' action='$action_href' method='post'>"
                . "<input type='hidden' name='action' value='wtf_fu_workflow' />"
                . "<input type='hidden' name='fn' value='generate_page' />"
                . "<input type='hidden' name='workflow_id' value='$workflow_id' />"
                . "<input type='hidden' name='stage' value='$stage' />";


        $ret .= '<div class="workflow-buttons"><span class=workflow-process></span>';
        
        if ($stage - 1 >= 0) {
            if (wtf_fu_get_value($stage_options, 'back_active') == true) {
                $ret .= $this->createButton('prev', $stage - 1, 'arrow-left', $back_label, 'left', 'btn btn-primary', array_key_exists('back_js', $stage_options) ? $stage_options['back_js'] : null);
                $ret .= "&nbsp;";
            } elseif ($testing) {
                $ret .= $this->createButton('prev', $stage - 1, 'arrow-left', 'back [testing only]', 'left', 'btn btn-primary', null);
                $ret .= "&nbsp;";
            }
        }

        if (Wtf_Fu_Options::stage_exists($workflow_id, $stage + 1) !== false) {
            if (wtf_fu_get_value($stage_options, 'next_active') == true) {
                $ret .= $this->createButton('next', $stage + 1, 'arrow-right', $next_label, 'right', 'btn btn-success', array_key_exists('next_js', $stage_options) ? $stage_options['next_js'] : null);
                $ret .= "&nbsp;";
            } elseif ($testing) {
                $ret .= $this->createButton('next', $stage + 1, 'arrow-right', 'next [testing only]', 'right', 'btn btn-success', null);
                $ret .= "&nbsp;";
            }
        }
        $ret .= '</div></form>';
        return $ret;
    }

    private function createButton($name, $value, $icon, $span, $icon_side = 'left', $class = "btn btn-primary", $confirm = null, $type = "submit") {
        $button = '<button id="workflow_submit_button" name="' . $name . '" type="' . $type . '" class="' . $class . '" value="'
                . $value . '"';

        if ($confirm !== null) {
            $button .= $confirm;
        }
        $button .= '>';
        $icon = '<i class="glyphicon glyphicon-' . $icon . '"></i>';
        $span = '<span>' . $span . '</span>';
        if ($icon_side !== 'left') {
            $button .= $span . '&nbsp;&nbsp;&nbsp;' . $icon;
        } else {
            $button .= $icon . '&nbsp;&nbsp;&nbsp;' . $span;
        }
        $button .= '</button>';
        return $button;
    }

    /**
     * Generate initial rendering of the shortcode.
     * 
     * @param type $options
     * @return type
     */
    public function generate_content($options) {

        do_action("wtf_fu_workflow_init");
        $page = $this->generate_workflow_stage_page($options);
        
        return "<div id=\"workflow_response\">$page</div>";
    }

    public function init_options($options) {
        $this->options = $options;
    }

    /**
     * Return a singleton instance of this class.
     * @return    object    A single instance of this class.
     */
    public static function get_instance() {

        // If the single instance hasn't been set, set it now.
        if (null == self::$instance) {
            self::$instance = new self;
            //log_me("new " . __CLASS__ . " instance created.");
            return self::$instance;
        }

        //log_me("existing  " . __CLASS__ . " instance returned.");
        return self::$instance;
    }

}

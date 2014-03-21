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

    protected $plugin_slug = 'wtf-fu';
    protected static $instance = null;
    protected $options;

    /**
     * 
     * @param type $attr
     */
    public function __construct($attr) {
        $this->options = $attr;
        add_action('wtf_fu_workflow_init', array($this, 'workflow_controller'));
    }


    /**
     * Processes all the options for workflows stages and user
     * and then generates a page for the current stage and workflow.
     * @param type $stage
     * @param type $options
     * @return type
     */
    function generate_workflow_stage_page($options) {

        if (!array_key_exists('id', $options)) {
            die("No 'id' attribute was found. Check that the shortcode contains the 'id' attribute. Try [wtf-fu id=\"1\"]  or  [wtf-fu type=\"workflow\" id=\"1\"]");
        }

        // id fron the passed shortcode options 
        $wfid = $options['id'];

        $plugin_options = Wtf_Fu_Options::get_plugin_options();
        $show_powered_by_link = wtf_fu_get_value($plugin_options, 'show_powered_by_link');


        // The workflow wide defaults settings and the html section wrappers.
        $wf_options = Wtf_Fu_Options::get_workflow_options($wfid);

        // This user's workflow options including the current stage they are at in this workflow.
        $user_wf_options = Wtf_Fu_Options::get_user_workflow_options($wfid, 0, true);

        log_me(array('user_wf_options' => $user_wf_options));

        if ($user_wf_options === false) {
            // User not logged on so we get the stage from the form submit action.
            $stage = 0;
            if (isset($_POST['next'])) {
                $stage = (int) $_POST['next'];
            }
            if (isset($_POST['prev'])) {
                $stage = (int) $_POST['prev'];
            }
        } else {
            // The current stage after processing any form actions for 'prev' or 'next'
            $stage = $user_wf_options['stage'];
        }

        // Stage specfic options and content settings
        $stage_options = Wtf_Fu_Options::get_workflow_stage_options($wfid, $stage);

        $content = wtf_fu_get_value($stage_options, 'content_area');
        $title = wtf_fu_get_value($stage_options, 'stage_title');
        $footer = wtf_fu_get_value($stage_options, 'footer');

        $testing_mode = wtf_fu_get_value($wf_options, 'testing_mode');

        $buttons = $this->getButtonBarHtml($wfid, $stage, $stage_options, $wf_options);

        $page_template = wtf_fu_get_value($wf_options, 'page_html_template');

        if ($page_template === false) {
            $page_template = wtf_fu_DEFAULT_WORKFLOW_TEMPLATE;
        }

        $replace = array(
            '%%WTF_FU_WORKFLOW_NAME%%' => wtf_fu_get_value($wf_options, 'name'),
            '%%WTF_FU_WORKFLOW_STAGE_TITLE%%' => $title,
            '%%WTF_FU_WORKFLOW_STAGE_HEADER%%' => wtf_fu_get_value($stage_options, 'header'),
            '%%WTF_FU_WORKFLOW_BUTTON_BAR%%' => $buttons,
            '%%WTF_FU_WORKFLOW_STAGE_CONTENT%%' => $content,
            '%%WTF_FU_WORKFLOW_STAGE_FOOTER%%' => $footer,
            '%%WTF_FU_POWERED_BY_LINK%%' => $show_powered_by_link ? wtf_fu_powered_by_link() : ''
        );

        $page = str_replace(array_keys($replace), array_values($replace), $page_template);
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

        log_me('workflow_controller');

        if (!isset($_POST['workflow_id'])) {
            log_me('workflow_id not found in wtf_fu_controller_form post request.');
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
        if (isset($_POST['prev']) && wtf_fu_get_value($old_stage_options, 'back_active')) {
            $trynew = (int) $_POST['prev'];
            if ($trynew === $new - 1) {
                $new = $trynew;
            }
        }
        if (isset($_POST['next']) && wtf_fu_get_value($old_stage_options, 'next_active')) {
            $trynew = (int) $_POST['next'];
            if ($trynew === $new + 1) {
                $new = $trynew;
            }
        }
        if ($new < 0) {
            $new = 0;
        }
        if ($new !== $old) {

            /*
             * If moving forwards then execute any post or pre 
             * hook functions.
             */
            if ($new > $old) {
                if (!empty($old_stage_options['post_hook'])) {

                    log_me("calling {$old_stage_options['post_hook']}");

                    call_user_func($old_stage_options['post_hook']);
                }
                $new_stage_options = Wtf_Fu_Options::get_workflow_stage_options($workflow_id, $new);

                if (!empty($new_stage_options['pre_hook'])) {
                    log_me("calling {$new_stage_options['pre_hook']}");
                    call_user_func($new_stage_options['pre_hook']);
                }
            }

            if ($user_settings !== false) {
                log_me("updating user stage from $old => $new");
                Wtf_Fu_Options::update_user_workflow_stage($workflow_id, $new);
            }
        }
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

        $ret = '<form action="" method="post">'
                . "<input type='hidden' name='action' value='workflow_controller' />"
                . "<input type='hidden' name='workflow_id' value='$workflow_id' />"
                . "<input type='hidden' name='stage' value='$stage' />";


        $ret .= '<div class="">';
        if (wtf_fu_get_value($stage_options, 'back_active') == true) {
            $ret .= $this->createButton('prev', $stage - 1, 'arrow-left', $back_label, 'left', 'btn btn-primary', array_key_exists('back_js', $stage_options) ? $stage_options['back_js'] : null);
            $ret .= "&nbsp;";
        } elseif ($testing) {
            $ret .= $this->createButton('prev', $stage - 1, 'arrow-left', 'back [testing only]', 'left', 'btn btn-primary', null);
            $ret .= "&nbsp;";
        }
        if (wtf_fu_get_value($stage_options, 'next_active') == true) {
            $ret .= $this->createButton('next', $stage + 1, 'arrow-right', $next_label, 'right', 'btn btn-success', array_key_exists('next_js', $stage_options) ? $stage_options['next_js'] : null);
            $ret .= "&nbsp;";
        } elseif ($testing) {
            $ret .= $this->createButton('next', $stage + 1, 'arrow-right', 'next [testing only]', 'right', 'btn btn-success', null);
            $ret .= "&nbsp;";
        }
        $ret .= '</div></form>';
        return $ret;
    }

    private function createButton($name, $value, $icon, $span, $icon_side = 'left', $class = "btn btn-primary", $confirm = null, $type = "submit") {
        $button = '<button name="' . $name . '" type="' . $type . '" class="' . $class . '" value="'
                . $value . '"';
        if ($confirm !== null) {
            //$button .= " onClick=\"return confirm('$confirm');\"";
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

    public function generate_content() {
        do_action("wtf_fu_workflow_init");
        return $this->generate_workflow_stage_page($this->options);
    }

}

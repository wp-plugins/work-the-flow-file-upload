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

/**
 * Utilities for general use oon both public and admin side.
 */
function wtf_fu_get_version_info() {

    $core = get_option('wtf-fu_version');
    $pro = get_option('wtf-fu-pro_version');
    $proactive = class_exists('Wtf_Fu_Pro');
    $info = '';
    if ($pro && !$proactive) {
        $info = '&nbsp; WARNING&nbsp!&nbsp;&nbsp;pro extension is not activated&nbsp!';
    }

    return sprintf("core:&nbsp;%s&nbsp;&nbsp;&nbsp;pro:&nbsp;%s&nbsp;%s", "v$core", $pro ? "v$pro" : 'not installed', $info);
}

/**
 * Sets last modified timestamps for $files to now - count files seconds.
 * 1 second apart so that they can be ordered by last modified time.
 * 
 * Necessary because filemtime only has precision of 1 second.
 * 
 * @param type $files
 */
function wtf_fu_set_files_order_times($dir, $files) {

    $seconds_offset = count($files);

    $now = time();

    $time = $now - $seconds_offset;

    foreach ($files as $file) {
        wtf_fu_update_timestamp($dir . '/' . $file, $time++);
    }

    //log_me("$now should == $time");
}

function inline_css_style_file($file) {
    return '<style type="text/css">' . file_get_contents($file) . '</style>';
}

function wtf_fu_update_timestamp($filename, $time) {

    if (file_exists($filename)) {
        if (touch($filename, $time) === true) {
           // log_me("SUCCESS : timestamp update for $filename");
        } else {
            log_me("FAILURE : timestamp update for $filename");
        }
    } else {
        log_me("FAILURE : timestamp update for $filename, file not found.");
    }
}

/**
 * replace bad filename character with _
 * 
 * @param type $filename
 * @return type
 */
function wtf_fu_clean_filename($filename) {
    $pattern = '/[\\\\\\/ ]/';
    $clean_name = preg_replace($pattern, '_', $filename);
    //log_me("wtf_fu_clean_filename $filename => $clean_name");
    return $clean_name;
}

function wtf_fu_write_file($filename, $text) {
    $fh = fopen($filename, 'w') or die("can't open file");
    fwrite($fh, $text);
    fclose($fh);
    //log_me("wrote file $filename");
}

function wtf_fu_get_javascript_form_vars($name, $php_array) {

    $js_array = json_encode($php_array);

    $js = <<<EOS
<script type='text/javascript'>
/* <![CDATA[ */
var $name = $js_array;
/* ]]> */             
</script> 
EOS;

    return $js;
}

/**
 * Tests an array for a key and returns the keys value if the key exists 
 * and is not empty.
 * 
 * If $allow_empty is true then empty values will also be returned.
 * This is necessary for values that may legitimately be 0.
 * 
 * Otherwise returns false.
 * 
 * @param array $arr
 * @param string $key
 */
function wtf_fu_get_value($arr, $key, $allow_empty = false) {
    if (!empty($arr)) {
        if (array_key_exists($key, $arr)) {
            if ($allow_empty || !empty($arr[$key])) {
                return $arr[$key];
            }
        }
    }
    return false;
}

/**
 * Wordpress REQUEST vars may have used the deprecated PHP directive magic_quotes_gpc  on
 * which will escape ' to  \' 
 * This function will remove them from an array.
 * @param type $value
 * @return type
 */
function wtf_fu_stripslashes_deep($value) {
    $value = is_array($value) ?
            array_map('wtf_fu_stripslashes_deep', $value) :
            stripslashes($value);
    return $value;
}

/**
 * tests an array for the required attributes.
 * returns false or a error message if any required $keys is 
 * not set in $arr
 * @param type $code
 * @param type $keys
 * @param type $arr
 * @return string|boolean
 */
function shortcode_requires($code, $keys, $arr) {
    $missing = '';
    foreach ($keys as $k) {
        if (isset($arr[$k])) {
            continue;
        } else {
            $missing .= "$k, ";
        }
    }
    if (empty($missing)) {
        return false;
    }
    $ret = "Error - the shortcode for $code is missing attributes for $missing";
    $ret .= "Required shortcode format is [$code ";
    foreach ($keys as $k) {
        $ret .= "$k=\"$k_value\"";
    }
    $ret .= ']';
    return $ret;
}

/**
 * return array with paths relative to users upload directory
 * eg for user with id 4
 * array('basedir' => c:/site/..../wp-content/uploads/4
  'baseurl' => http(s)://...wp-content/uploads/4,
  'upload_dir' => as above with appended upload_dir and subdir,
  'upload_url' => as above with appended upload_dir and subdir

 * @param type $upload_dir
 * @param type $upload_subdir
 * @param type $user_id
 * @return type array -- absolute path and url to be used for an upload location.
 */
function wtf_fu_get_user_upload_paths($upload_dir = '', $upload_subdir = '', $user_id = 0, $use_public_dir = false) {


    // override with the requested dir.
    if ($use_public_dir == true) {
        $user_id = 'public';
    } else {
        $user_id = getUserInfo('ID', $user_id);
        if (!isset($user_id) || $user_id == '') {
            $user_id = 'public';
        }
    }

    $upload_array = wp_upload_dir();

    $path = '/' . $user_id;

    if (!empty($upload_dir)) {
        $path .= "/$upload_dir";
    }
    if (!empty($upload_subdir)) {
        $path .= "/$upload_subdir";
    }

    $ret = array(
        'basedir' => $upload_array['basedir'] . '/' . $user_id,
        'baseurl' => $upload_array['baseurl'] . '/' . $user_id,
        'upload_dir' => $upload_array['basedir'] . $path,
        'upload_url' => $upload_array['baseurl'] . $path
    );
    
    //log_me($ret);
    
    return $ret;
}

/**
 * returns string of login username and first and last names if present
 * as a string suitable for use in filenames.
 * @param unknown_type $id
 */
function getUserNameDetails($id, $stripspace = true) {
    $user = new WP_User($id);
    $username = $user->user_login; // this should always be present.

    $fullname = $user->first_name . ' ' . $user->last_name;
    if (trim($fullname !== '')) {
        $username .= ' [' . $fullname . ']';
    }
    if ($stripspace) {
        $username = str_replace(' ', '_', $username);
    }
    return $username;
}

/**
 * 
 * @param type $id
 * @param type $name
 * @param type $options
 * @param type $val
 * @return string
 * 
 * generate a drop down select list
 */
function wtf_fu_select_field($id, $name, $options, $val, $domain) {
    $html = "<select id=\"$id\" name=\"$name\">";
    $html .= '<option value="default">'
            . __('Select an option...', $domain)
            . '</option>';

    foreach ($options as $option) {
        $html .= "<option value=\"$option\" "
                . selected($val, $option, false) . '>'
                . __($option, $domain) . '</option>';
    }
    $html .= '</select>';
    return $html;
}

/**
 * Get the current logged in user info
 * Defauts to returning the ID.
 * @global type $current_user
 * @param type $type type of info required  'ID', 'user_login' or 'user_email'
 * 'user_firstname', 'user_lastname', 'display_name', 
 * @return type
 */
function getUserInfo($type = 'ID', $user_id = 0) {
    if (!$user_id) {
        global $current_user;
        get_currentuserinfo();
        $user = $current_user;
    } else {
        $user = new WP_User($user_id);
    }
    $ret = $user->$type;
    return $ret;
}

/**
 * Creates an archive of the current users upload directory.
 * @param type $user_id
 * @return string - a link to the created archive file.
 * WARNING :
 * 
 * 1. Auto archiving may cause heavy resource usage on your site.
 * 
 * 2. The Front end will block waiting for this to complete.
 * 
 * Archives can be manually created as needed from the Wtf-Fu admin pages, 
 * this is often a better approach as archives are then only created when 
 * required.
 */
function wtf_fu_do_archive_user_files($user_id, $zipname) {
    /*
     * Include some existing wtf-fu utility methods for creating archives and getting users paths.
     */
    include_once (plugin_dir_path(__FILE__) . 'class-wtf-fu-archiver.php');
    //   include_once (plugin_dir_path( __FILE__ ) . '../plugins/work-the-flow-file-upload/includes/wtf-fu-common-utils.php');
    // append auto to the generated file name.
    $zip = new Wtf_Fu_Archiver();

    // Gets paths to the user upload area.
    $paths = wtf_fu_get_user_upload_paths();

    // create and open the archive (false means to exclude files that are archives)
    $zip->create_archive($paths['basedir'] . '/' . $zipname, false);

    // Add the users upload directory and files.
    $zip->add_file_or_dir($paths['basedir']);

    // close the archive.
    $zip->close();

    // link to the new archive to include in the email.
    $zip_url = $paths['baseurl'] . '/' . $zipname;

    return $zip_url;
}

/**
 * Create a filename suitable for use as an archive file name, from a user id and
 * a path.
 * If add_date is true (default) then the current timestamp will be included to 
 * make sure you have a unique filename each time.
 * 
 * @param type $user_id
 * @param type $subpath
 * @param type $ext
 * @param type $add_date
 * @return type
 */
function wtf_fu_create_archive_name($user_id, $subpath = '', $ext = '.zip', $add_date = true) {

    $name = getUserInfo('user_login', $user_id);

    if (!empty($subpath)) {
        $name .= $subpath;
    }

    $name = str_replace('/', '__', $name);

    if ($add_date == true) {
        $date = new DateTime();
        $date_str = date_format($date, '[Y-m-d_H-i-s]');
        $name .= '_' . $date_str;
    }

    $name .= $ext;
    return $name;
}

function log_me($message) {
    if (WP_DEBUG === true) {
        if (is_array($message) || is_object($message)) {
            error_log(print_r($message, true));
        } else {
            error_log($message);
        }
    }
}

function wtf_fu_powered_by_link() {
    $plugin_options = Wtf_Fu_Options::get_plugin_options();
    $show_powered_by_link = wtf_fu_get_value($plugin_options, 'show_powered_by_link');

    if ($show_powered_by_link == true) {
        return '[<a href="http://wtf-fu.com/download/">powered by wtf-fu</a>, a wordpress workflow and html5 file upload plugin.]';
    } else {
        return '';
    }
}

function wtf_fu_text_only($id, $name, $value, $size = 80, $label = null) {
    $html = '';
    if ($label) {
        $html .= '<label for="' . $id . '">' . $label . '</label><br/>';
    }
    $html .= "<input type=\"text\" id=\"$id\" name=\"$name\" value=\"$value\" size=\"$size\" readonly/>";
    return $html;
}

function wtf_fu_text_input($id, $name, $value, $size = 80, $label = null) {
    $html = '';
    if ($label) {
        $html .= '<label for="' . $id . '">' . $label . '</label><br/>';
    }
    $html .= "<input type=\"text\" id=\"$id\" name=\"$name\" value=\"$value\" size=\"$size\"/>";
    return $html;
}

/**
 * 
 * @param type $id
 * @param type $name
 * @param type $val
 * @param type $label
 * @param type $rows
 * @param type $cols
 * @param type $extra_attrs
 * @return type
 */
function wtf_fu_textarea($id, $name, $val, $label = null, $rows = 5, $cols = 80, $extra_attrs = '') {
    $html = '';
    if ($label) {
        $html .= '<label for="' . $name . '">' . $label . '</label><br/>';
    }
    $html .= "<textarea rows='$rows' cols='$cols' id=\"$id\" name=\"$name\"$extra_attrs>$val</textarea>";
    return $html;
}

/**
 * generate checkbox code.
 */
function wtf_fu_checkbox($id, $option_name, $val, $label) {

    // Convert to select list of true/false as unchecked checkboxes do not get submitted eith the form.
    $values = array(array('name' => "No (0)", 'value' => '0'), array('name' => "Yes (1)", 'value' => '1'));
    return wtf_fu_list_box($id, $option_name, $val, $label, $values);

//    $html = '<input type="checkbox" id="' . $id . '" name="' . $option_name . '" value="1"' . checked(1, $val, false) . '/>';
//    $html .= '&nbsp;';
//    $html .= '<label for="' . $id . '">' . $label . '</label>';
//    return $html;
}

/**
 * return a drop ldown list box from the $values array
 * @param type $id  select box id
 * @param type $option_name  select box name
 * @param type $val selected value
 * @param type $label help text label
 * @param type $values array of name value pairs 
 *              eg array(array (name => 'name' value => 0))
 * @return string  html for the select box.
 */
function wtf_fu_list_box($id, $option_name, $val, $label, $values) {

    $html = "<select id=\"$id\" name=\"$option_name\">";
    foreach ($values as $v) {
        $html .= "<option ";
        if ($v['value'] == $val) {
            $html .= "selected=\"selected\" ";
        }
        $html .= "value=\"{$v['value']}\">{$v['name']}</option>";
    }
    $html .= '</select>&nbsp;';
    $html .= '<label for="' . $id . '">' . $label . '</label>';

    return $html;
}

/**
 * Returns a table of all the available template fields.
 */
function wtf_fu_get_template_fields_table($ignore = false) {
    $table = "<table><tr><th>TEMPLATE SHORTCUT</th><th>ACTION</th></tr>";

    $arr = Wtf_Fu_Option_Definitions::get_instance()->get_page_option_fields_default_labels(wtf_fu_DEFAULTS_TEMPLATE_FIELDS_KEY);

    foreach ($arr as $k => $v) {
        $table .= "<tr><td>$k</td><td>$v</td></tr>";
    }

    $table .= '</table>';
    return $table;
}

/**
 * Returns a fully expanded shortcode example with all options set to 
 * factory defaults.
 *  
 * @param type $shortcode
 * @param type $factory if true uses the factory defaults otherwise the current defaults from the database.
 * Only applicable for wtf_fu_DEFAULTS_UPLOAD_KEY a.t.m.
 */
function wtf_fu_get_shortcode_with_default_attributes($code, $factory = true) {

    switch ($code) {
        case 'wtf_fu_upload' :
            $data_key = wtf_fu_DEFAULTS_UPLOAD_KEY;
            break;
        case 'wtf_fu_show_files' :
            $data_key = wtf_fu_DEFAULTS_SHORTCODE_SHOWFILES_KEY;
            break;
        case 'wtf_fu' :
            $attr = array('id' => '<workflow id>');
            break;
        default :
    }

    if (!isset($attr)) {

        if (!$factory && $code === 'wtf_fu_upload') {   // Only applicable for wtf_fu_DEFAULTS_UPLOAD_KEY a.t.m
            // Get current default settings from the database.
            $attr = Wtf_Fu_Options::get_upload_options();
        } else {

            // Get the factory defined options values.
            $attr = Wtf_Fu_Option_Definitions::get_instance()->get_page_option_fields_default_values($data_key);
        }
    }

    return wtf_fu_get_example_short_code_attrs($code, $attr);
}

function get_shortcode_info_table($shortcode) {
    $table = "<table class='table'><tr><th>SHORTCODE ATTRIBUTE</th><th>DEFAULT VALUE</th><th>DESCRIPTION</th></tr>";

    switch ($shortcode) {
        case 'wtf_fu_upload' :

            $attr_defs = Wtf_Fu_Option_Definitions::get_instance()->get_page_option_fields_default_values(wtf_fu_DEFAULTS_UPLOAD_KEY);

            foreach ($attr_defs as $k => $v) {
                $label = Wtf_Fu_Option_Definitions::get_instance()->get_page_option_field_label_value(wtf_fu_DEFAULTS_UPLOAD_KEY, $k);
                $table .= "<tr><td>{$k}</td><td>$v</td><td>$label</td></tr>";
            }

            break;
        case 'wtf_fu_show_files' :
            $attr_defs = Wtf_Fu_Option_Definitions::get_instance()->get_page_option_fields_default_values(wtf_fu_DEFAULTS_SHORTCODE_SHOWFILES_KEY);

            foreach ($attr_defs as $k => $v) {
                $label = Wtf_Fu_Option_Definitions::get_instance()->get_page_option_field_label_value(wtf_fu_DEFAULTS_SHORTCODE_SHOWFILES_KEY, $k);
                $table .= "<tr><td>{$k}</td><td>$v</td><td>$label</td>";
            }
            break;
        case 'wtf_fu' :
            break;
        default :
    }
    $table .= '</table>';

    return $table;
}

function wtf_fu_get_example_short_code_attrs($code, $attr) {
    $ret = "[<strong>$code</strong>";
    foreach ($attr as $k => $v) {
        $ret .= " $k=\"$v\"";
    }
    $ret .= ']';
    return $ret;
}

/**
 * return all available shortcut keys.
 * @return type
 */
function wtf_fu_get_shortcut_keys() {
    return array_keys(Wtf_Fu_Option_Definitions::get_instance()->get_page_option_fields_default_labels(wtf_fu_DEFAULTS_TEMPLATE_FIELDS_KEY));
}

/**
 * Inspects request vars to work out what page we are on.
 * @return string a page identifier.
 */
function wtf_fu_get_page_identifier_from_request() {

    $tab = wtf_fu_get_value($_REQUEST, 'tab');
    $wftab = wtf_fu_get_value($_REQUEST, 'wftab');
    $wtf_action = wtf_fu_get_value($_REQUEST, 'wtf_action');
       
    $page_id = sprintf('%s%s%s', 
        $tab ? "{$tab}" : '',
        $wftab ? "-{$wftab}" : '',
        $wtf_action ? "-{$wtf_action}" : '');                    
    
    return $page_id;
}

/**
 * Populates shortcut replacement values.
 * 
 * @param array $fields
 * @return array the fields with shortcut vaules replaced. 
 */
function wtf_fu_replace_shortcut_values($fields, $workflow_id = null, $stage_id = null) {


    // Build up required replacement values from the shortcuts needed by the fields content.
    $replace = array();

    $shortcuts = wtf_fu_get_shortcut_keys();

    $field_values = array_values($fields);

    $shortcuts_required = array();

    foreach ($shortcuts as $shortcut) {
        foreach ($field_values as $value) {
            if (strstr($value, $shortcut)) {
                $shortcuts_required[] = $shortcut;
                break; // once a match is found beak to next shortcut               
            }
        }
    }
    
    //log_me(array('shortcuts required' => $shortcuts_required ));

    foreach ($shortcuts_required as $shortcut) {

        switch ($shortcut) {

            case '%%USER_NAME%%' :
                $wp_user = wp_get_current_user();
                $replace[$shortcut] = $wp_user->display_name;
                break;

            case '%%USER_EMAIL%%' :
                $wp_user = wp_get_current_user();
                $replace[$shortcut] = $wp_user->user_email;
                break;

            case '%%ADMIN_NAME%%' :
                $wp_admin = new Wp_User(1);
                $replace[$shortcut] = $wp_admin->display_name;
                break;
            
            case '%%ADMIN_EMAIL%%' :               
                $replace[$shortcut] = get_option('admin_email');
                break;        

            case '%%SITE_URL%%' :
                $replace[$shortcut] = site_url();
                break;

            case '%%SITE_NAME%%' :
                $replace[$shortcut] = get_bloginfo('name');
                break;

            case '%%WTF_FU_POWERED_BY_LINK%%' :
                $replace[$shortcut] = wtf_fu_powered_by_link();
                break;

            case '%%ALL_WORKFLOW_USERS_EMAILS%%' :
                $replace[$shortcut] = Wtf_Fu_Options::get_all_user_emails($workflow_id);
                break;

            case '%%ALL_SITE_USERS_EMAILS%%' :
                $replace[$shortcut] = Wtf_Fu_Options::get_all_user_emails();
                break;

            case '%%WORKFLOW_NAME%%' :
                $wf_options = Wtf_Fu_Options::get_workflow_options($workflow_id);
                $replace[$shortcut] = wtf_fu_get_value($wf_options, 'name');
                break;

            case '%%WORKFLOW_STAGE_TITLE%%' :
                $stage_options = Wtf_Fu_Options::get_workflow_stage_options($workflow_id, $stage_id);
                $replace[$shortcut] = wtf_fu_get_value($stage_options, 'stage_title');
                break;

            case '%%WORKFLOW_STAGE_HEADER%%' :
                $stage_options = Wtf_Fu_Options::get_workflow_stage_options($workflow_id, $stage_id);
                $replace[$shortcut] = wtf_fu_get_value($stage_options, 'header');
                break;

            case '%%WORKFLOW_STAGE_NUMBER%%' :
                $stage_options = Wtf_Fu_Options::get_workflow_stage_options($workflow_id, $stage_id);
                $replace[$shortcut] = $stage_id;
                break;

            case '%%WORKFLOW_STAGE_CONTENT%%' :
                $stage_options = Wtf_Fu_Options::get_workflow_stage_options($workflow_id, $stage_id);
                $replace[$shortcut] = wtf_fu_get_value($stage_options, 'content_area');
                break;

            case '%%WORKFLOW_STAGE_FOOTER%%' :
                $stage_options = Wtf_Fu_Options::get_workflow_stage_options($workflow_id, $stage_id);
                $replace[$shortcut] = wtf_fu_get_value($stage_options, 'footer');
                break;

            default :
                //log_me("Shortcut replacement key not found for $shortcut");
        }
    }

    $fields = str_replace(array_keys($replace), array_values($replace), $fields);

    return $fields;
}

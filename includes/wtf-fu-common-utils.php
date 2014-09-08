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

   /**
     * Write an htaccess file out to the wp upload directory,
     * only if file does not already exist.
     */
function wtf_fu_write_htaccess_file() {

    $dir = wp_upload_dir();

    if (false !== $dir['error']) { 
        return $dir['error'];
    }

    $filename = $dir['basedir'] . "/.htaccess";

    $text = 
"# BEGIN wtf-fu modifications
<Files *>
    SetHandler none
    SetHandler default-handler
    Options -ExecCGI -Indexes
    php_flag engine off
    RemoveHandler .cgi .php .php3 .php4 .php5 .phtml .pl .py .pyc .pyo .asp .aspx 
</Files>
# END wtf-fu modifications";
                     
    if ( !file_exists($filename)) {
        wtf_fu_write_file($filename, $text); 
        return "To better secure file uploads the file : $filename has been created.";
    } else {
        return "$filename not required to be created as it already exists.";
    }
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
 *
 * 
 * @param type $dir
 * @param type $type
 * @return type
 */

/**
 * Returns an drop dwon html select box of all workflow example json files.
 * If suffix is supplied match file suffixes will be stripped from the list labels.
 * 
 * @param type $option_name
 * @param type $pattern
 * @param type $suffix
 * @param type $label
 * @return type
 */
function wtf_fu_get_files_list_box($option_name, $pattern, $suffix = '', $label) {
   
   $files = glob($pattern);
   $values = array( array('name' => 'New Empty Workflow', 'value' => ''));
   
   foreach ($files as $f) {
       $values[] = array('name' => basename($f, $suffix), 'value' => basename($f));
   }
   
   $dropbox = wtf_fu_list_box($option_name, $option_name, '' , $label, $values); 
   return $dropbox; 
}

function wtf_fu_multiple_list_box($id, $option_name, $val, $label, $values) {
    
    $option_name .= '[]'; // append array so options.php will know to store as multiple values.
    $size = count($values);
    $html = "<select id=\"$id\" name=\"$option_name\" multiple size=\"$size\">";
    
    // if value is not set or legacy string then force to an array.
    if (!is_array($val)) {
        $val = array($val);
    }
    foreach ($values as $v) {
        $html .= "<option ";
        if (in_array($v['value'], $val)) {
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
function wtf_fu_get_shortcuts_table($ignore = false) {
    $table = "<table class='table'  border=1 style='text-align:left;'><tr><th colspan=2 style='text-align:center;';'>Available Shortcut Placeholder codes</th></tr>"
            . "<tr><th>Shortcut</th><th>Expands to</th></tr>";

    $arr = Wtf_Fu_Option_Definitions::get_instance()->get_page_option_fields_default_labels(wtf_fu_DEFAULTS_SHORTCUTS_KEY);

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
            $attr = array('id' => "x");
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


function wtf_fu_get_general_info($type) {
    
    switch ($type) {
        
        case 'Quick Start Guide' :
            return "<p><ol><li>Go to the Work The Flow / File Upload Administration page and select the Workflows tab.</li>
                <li>Select <strong>Simple File Upload</strong> from the drop down list box and click <strong>Add</strong></li>
                <li>You should see a new copy of this sample workflow in the list, take note of the ID number.</li>
                <li>Create a new Wordpress page or post and type in <strong>[wtf_fu id=1]</strong> (replace 1 with the workflow ID)</li>
                <li>Browse to the page or post and try out the sample workflow. Upload some files to get a feel for how it works.</li>
                <li>Go back to the <strong>Workflows</strong> tab and click the <strong>Simple File Upload</strong> link in the name column to go to the edit screen</li>
                <li>If you have the PRO version, follow the instructions for adding an email template in the workflow notes.</li>
                <li>Play around with editing the content, editing the copy will not affect the template so go wild !</li>
                <li>Click on the stage tabs to edit a particular stage, make sure you save any changes before leaving the stage.</li>
                <li>Take note of the embedded <strong>[wtf_fu_upload]</strong> and <strong>[wtf_fu_show_files]</strong> shortcodes, try changing or adding extra attributes ( see the shortcodes section in the <strong>Documentation Tab</strong></li>
                <li>After uploading some files go to the <strong>Manage Users</strong> tab. Here you can alter a users current stage in a workflow</li>
                <li>Click on your user name to see the files you uploaded. You will see a list of the files and can archive, download or delete them.</li>
                <li>Context sensitive help is available on all admin pages by clicking the <strong>Help</strong> box in the top right corner of the screens.</li>
                <li>Full documentation is available in the <strong>Documentation</strong> tab</li>
                <li>If you cant find what you are after then please ask a question on the <a href=\"http://wordpress.org/support/plugin/work-the-flow-file-upload\" target=\"_blank\">WordPress support forum</a>
                and I'll try my hardest to help.</li></ol></p>";
        
        case 'File Uploads' :
            return "<p>File Upload is the main core functionality of this plugin. "
            . "This plugin wraps the open source javascript library <a href=\"https://github.com/blueimp/jQuery-File-Upload\">jQuery-File-Upload</a> from blueimp."
                . "and provides an interface to store and pass parameters to the javascript code.</p>"
                . "<p>To create a file upload you use the shortcode <code>[wtf_fu_upload]</code>"
                . "This may either be embedded in a page or post directly, or inside of a workflow to combine fileuploads with a workflow process.</p>"
                . "<p>Uploads are uploaded to the users upload directory to a subdirectory locatiion that can be specified as an attribute to the <code>[wtf_fu_upload]</code> shortcode.</p>";
            
        case 'Workflows' :
            return "<p>A Workflow allows users to pass through a sequence of stages.</p>"
            . "<p>Workflow stages can be added and edited in the admin Workflows tab. </p>"
                . "<p>Once defined a workflow can then be added to any page or post by embedding the shortcode <code>[wtf_fu id='x']</code> "
                . "where x is the numeric workflow ID of the workflow.</p>"
                . "<p>When a registered user enters a page with an embedded workflow, the plugin tracks the users progress "
                . "through the workflow and will return them to the same stage where they left off last time.</p>"
                . "<p>Movement through the workflow can be configured to allow or deny forward or backward "
                . "movement through the workflow stages.</p>"
                . "<p>For example, once a user has submitted some files he may be restricted from returning to previous stages.</p>"
                . "<p>User stages may also be set from the admin inteface for each user so that an administrator can manually reset a users stage.</p>"
                . "<p>For example, after a user summits some files they are restricted from moving forward until some inhouse processing has been done, "
                . "after which an administrator manually sets the users stage to the next stage in process so that the user can continue.</p>"
                . "";            
        
        case 'showfiles' :
            return "<p>Users uploaded files can be displayed with the <code>[wtf_fu_show_files]</code></p>."
            . "Specifying attributes with the shortcode allows you a variety of presentation effects including an "
                . "option to allow users to re-order the files via a drag and drop display.</p>";
            
            
        case 'Shortcodes' :
            //<ol style='list-style-type: lower-roman'>
            $str = "<p>Below are the plugins shortcodes that can be used by embedded them into pages and posts and workflow content."
                . "Default attribute values are set in the admin pages and may be overriden by supplying attribute values when you embed the shortcode.</p>"
                . "<ol style='list-style-type: lower-roman'><div id='subaccordion1'>";
                            
            foreach (array('wtf_fu', 'wtf_fu_upload', 'wtf_fu_show_files') as $shortcode) {
                $str .= "<li><h5><a href='#'>[{$shortcode}]</a></h5><div>" . wtf_fu_get_general_info($shortcode) . "</div></li>"; // warning recursive.
            }
            $str .= "</div></ol>";          
            return $str;
            break;
            
        case 'wtf_fu':
            return "<p>Use this shortcode to embed a workflow in a page or a post.</p>
                <p>The following rules apply :<br/>
                <ol><li>The shortcode cannot be nested inside other workflow stages, you can only use it inside normal pages or posts.</li>
                <li>The attribute <code>'id'</code> is required which specifies which workflow to embed in your page or post.</li>
                <li>You can only embedd one workflow per page</li></ol></p>
                
                <p>To use a workflow just include <code> "
                . wtf_fu_get_shortcode_with_default_attributes('wtf_fu')
                . "</code> in your page or post, where <strong>\"x\"</strong> represents the numeric workflow id.</p>
                    <p><small>NOTE: Prior to version 1.3.0 other attributes were available to return miscellaneous workflow information, such as the current username or workflow name.
                    These are now deprecated in favour of the newer shortcut <code>%%XXXX%%</code> fields that can be more directly used inside your workflow stage content. 
                    If your code uses any other attributes than 'id=x' then please see the shortcuts documentation and use a suitable shortcut placeholder instead.</small></p>";
  
       case 'wtf_fu_upload':
            return "<p>Use this shortcode to embed a file upload interface. It may be embedded either in a page or post, or inside a workflow stage.<br/>
                        Default attributes can be set on the Admin File Upload tab which will be used unless overridden by including attributes when you use the shortcode.</p>
                    <p>A shortcode example with the full list of factory set default attributes is below :</p>
                    <p><code>"
                   . wtf_fu_get_shortcode_with_default_attributes('wtf_fu_upload')
                   . "</code></p>
                    <p>Taking into account the current global settings on your File Upload options page
                        , the short code representing the current default behaviour would be :</p> 
                    <code>"
                    . wtf_fu_get_shortcode_with_default_attributes('wtf_fu_upload', false)
                    . "</code><br/> So this is currently how a shortcode with no attributes specified will behave by default. i.e. if a bare <code>[wtf_fu_upload]</code> is embedded in a page.</p>  
                    <p>The attributes are detailed with their factory default values in the table below.</p>"
                    . get_shortcode_info_table('wtf_fu_upload');
           
        case 'wtf_fu_show_files' :
            return "<p>The <strong>[wtf_fu_show_files]</strong> shortcode is used to present a users uploaded files on a page.<br/>
                The following rules apply :<br/>
                <ol><li>The shortcode can be used in pages, posts, workflow content and email templates.</li>
                <li>There is currently no admin interface to set the default attributes. To override the defaults you need to specify the attribute you want to override when you embedd the code.</li>
                </ol>
                <p> The default attributes for the shortcut that will be applied if not overriden (ie if you just use <strong>[wtf_fu_show_files]</strong> with no attributes) is equivilent to </p>
                    <code>" 
                . wtf_fu_get_shortcode_with_default_attributes('wtf_fu_show_files') 
                . "</code>                 
                    <p>The available attributes and there default values are listed below </p>"
                . get_shortcode_info_table('wtf_fu_show_files');  
            
        case 'Shortcuts' :
            return "<p>Shortcuts are place holders for information that can be expanded at runtime and make it easy to insert workflow details, workflow stage information, email lists, user names and other information.<p>
                <p>The following rules apply :<br/>
                <ol><li>Shortcuts can be used in the workflow stage content fields including title name header content and footer</li>
                <li>Shortcuts can be used in workflow layout templates to position the buttons header footer and other content.</li>
                <li>Shortcuts can be used in email template including the TO FROM CC BCC and content fields.</li>
                <li>You cannot use a shortcut where it represents the field you are trying to place it into.<br/>
                For example it would make no sense to put <strong>%%WORKFLOW_STAGE_TITLE%%</strong> inside a workflow <strong>stage_title</strong> field. 
                You can however use <strong>%%WORKFLOW_STAGE_NUMBER%%</strong> in the <strong>stage_title</strong> field to automatically number your stages.</li> 
                </ol></p>
                <p>Below is the full table of available shortcuts :</p>" . wtf_fu_get_shortcuts_table();
            
        case 'Templates' :
            $str = "<p>Templates are a PRO feature that allow you to define different layouts for workflows and emails.</p>
                <p> There are two types of templates :</p>"
                . "<ol style='list-style-type: lower-roman'><div id='subaccordion1'>";
                
                            
            foreach (array('Workflow Layout Templates', 'Email Layout Templates') as $name) {
               $str .= "<li><h5><a href='#'>{$name}</a></h5><div>" . wtf_fu_get_general_info($name) . "</div></li>"; // warning recursive.
            }
            $str .= "</div></ol>";  
            
            $str .= "<p>With the PRO extension installed and enabled the templates for workflow page layouts and for automated emails can be created, 
                edited and cloned from the <code>Templates</code> tab. </p>
            <p>An additional workflow option field <code>page_template</code> can be used for setting the workflow layout template.<br/>
            An additional workflow stage field <code>send_email</code> can be used to attach one or more email templates to any workflow stage.</p>
            <p>Templates can also include field shortcuts to allow embedding of workflow and user details.</p>";
            
            return $str;
            
        case 'Workflow Layout Templates' :
            return "<p>Workflow templates are used to layout the workflow presentation.</p>
            <p>You can customise layout templates for different workflows with your own images and html from the editing interface.</p>
            <p>Shortcuts are used to include the workflow header, title, footer, buttons. These should all normally be retained in your template but you can move them around 
            as desired and insert your own custom html. You may remove any workflow content shortcodes that you wish to exclude from the layout.</p>
            <p>You can use your own framework css classes to wrap the workflow content if you wish.</p>
            <p>The default workflow layout template is shown below :</p>"
            . "<p><blockquote><pre>"
            . htmlentities(wtf_fu_DEFAULT_WORKFLOW_TEMPLATE)
            . "</pre></blockquote></p>"
            ;
            
        case 'Email Layout Templates': 
            
            return "<p>PRO users can use Email Templates to define layouts for Emails to be sent when a certain workflow stage is passed through by a user.</p>
                <p>These templates can then be attached to any workflow stage <code>send_email</code> field (PRO only)</p>
                <p>You can add your own layout and image html to the templates.</p>
                <p>You can use Shortcuts in the <code>to: from: cc: bcc: and message </code>fields, to automatically fill in user email addresses and other workflow details at run time.</p>
                <p>You can use the <code><strong>[wtf_fu_show_files email_format='1']</strong></code> shortcode to include a show_files display inside an email template.</p>           
                <p>The default email template is shown below :</p>"
             . "<p><blockquote><pre>"
             . htmlentities(wtf_fu_DEFAULT_EMAIL_TEMPLATE)
             . "</pre></blockquote></p>"
            ;
                 
            
        case 'pro_details' :
            return "<p>With the work-the-flow-file-upload PRO extension installed additional admin page features are made available including :</p>
                <ul style='list-style-type: square'>
                <li>Email layout templates.</li>
                <li>Workflow layout templates.</li>
                <li>PHP code evaluation inside workflow content by wrapping PHP code inside <code>[wtf_eval] .. [/wtf_eval]</code> blocks.</li>
                <li>The PRO package can be purchased and downloaded from <a href='http://wtf-fu.com' target = '_blank'>wtf-fu.com</a>.</li>
                </ul>";
        default :
             return "$type not implemented yet.";
    }    
    
}

function wtf_fu_get_admininterface_info($type = 'all') {
    
    switch ($type) {
        
        case 'all' :
            $str = "<p>The Admin interface consists of the following TABS.</p><ul><div id='subaccordion1'>";
            $tabs = Wtf_Fu_Option_Definitions::get_instance()->get_menu_page_values();                
            
            foreach ($tabs as $tab) {
                $str .= "<li><h5><a href='#'>{$tab['title']}</a></h5><div>" . wtf_fu_get_admininterface_info($tab['title']) . "</div></li>"; // warning, this is recursive, dont send in 'intro'.
            }
            $str .= "</div></ul>";          
            return $str;
            

        case 'System Options' : 
            return "<p>Plugin Options page. These are system wide plugin settings. They define plugin behaviours for uninstalling, stylesheet useage, and licensing.</p>
                <ul><li>remove_all_data_on_uninstall<br/>
                If this is set to <strong>Yes</strong> then when the plugin is uninstalled from Wordpress all workflows, custom configuration settings and user workflow tracking data will be deleted from the database.<br/>
                If you want to delete all of your data when removing the plugin, then set this to <strong>Yes</strong> before uninstalling. <br>
                If you just want to uninstall and reinstall the plugin for some reason without losing your existing workflows and settings then make sure that this is set to <strong>No</strong>.</li>
                <li>include_plugin_style<br/>
                The plugin includes its own css style sheet for presenting the workflow pages, if this is conflicting with your other website styles you can turn off loading this stylesheet so that the workflow pages will inherit your websites styles.<br/>
                A simpler solution for minor modifications would be to override the offending css in your template style.css file.</li>
                <li>show_powered_by_link<br/>
                Turning this on causes the %%WTF_FU_POWERED_BY_LINK%% shortcut that is included in the default workflow template to be active and helps to support this plugin by displaying a link to wtf-fu.com on your pages.</li>
                <li>license_key<br/>
                This field is visible for PRO users so they can add their license key. Adding the license key activates automated updates for the PRO extension. When purchasing the PRO version a license key is emailed to you or can be retrieved by logging in to the wtf-fu.com members page.</li>
                </ul>";
            break;
            
        case 'File Upload' :
            return "<p>These settings allow you to set the default values for all the <code>[<strong>wtf_fu_upload</strong>]</code> shortcode attributes.</p>"
            . "This enables you to change the default values for attributes that are NOT supplied with the embedded shortcode in your pages, posts, or inside of your workflows.</p>"
                . "<p>The shortcode default values are displayed at the top of the File Upload settings page, this indicates how the <code>[<strong>wtf_fu_upload</strong>]</code> shortcode "
                . "without any attributes will behave with the current default settings.</p>"
                . "<p>This can be useful if you use a large number of shortcodes with many attributes that are different from the factory default settings.</p>"
                . "<p>You don't need to worry too much about this, it is just a convenience method for overriding the default attribute values, in most cases it is probably clearer and easier to just "
                . "specify the required attributes with the embedded shortcode itself, and leave the defaults as they are. The embedded attribute values will always take precedence over "
                . "whatever the default are set to. The defaults only apply for attributes not specified when using the shortcode.</p>"
                . "<p>In 2.4.0 the attribute <code>[<strong>deny_file_types</strong>]</code> was added to provide file type extensions that should never be uploaded for security purposes. This attribute is system wide for all "
                . "upload instances and (unlike all the other attributes) this cannot be overriden in embedded shortcodes.<p>"
                . "<p>For additional security a .htaccess file is auto generated (if one does not already exist) in the wordpress uploads directory. Provided your webhost runs an apache webserver configured to allow .htaccess rules, "
                . "this file will prevent apache webservers from executing ptoentially malicious scripts uploaded under this directory.</p>";          
            
        case 'Workflows' :
            return "<p>This page lists all the Workflows that are currently in your database. >br/>"
            . "Any of these workflows can be used in a page or post by embedding the <code>[<strong>wtf_fu id='x'</strong>]</code> shortcode, where x = the workflows ID.</p>"
            . "<p>On this page you can : <ul>
                <li>Click on a Workflow Name to edit the workflow settings.</li>
                <li>Add a new workflow by selecting the empty or an example workflow from the drop down list, then clicking <strong>Add</strong>.</li>
                <li>Import a workflow from a local file on your hard disk by browsing to the file then clicking <strong>Import</strong>. (PRO only)</li>
                <li>Click the 'clone' link under a workflow name to create a duplicate copy of a workflow.</li>
                <li>Click the 'delete' link under a workflow name to permanently delete a workflow from the database.</li>
                <li>Click the 'export' link under a workflow name to save a local copy of a workflow on your pc that can be imported into another site (pro only feature).</li>
                <li>Delete, clone, or export multiple workflows at once using the checkboxes and the bulk actions menu.</li>
                </ul></p>
                <p>The <code>Users [Stage]</code> column details any users that are currently progressing 
                through the workflow, and the current stge they are up to. You can manually alter a users current stage from the <code>Manage Users</code> tab.</p>
                
                <p><small>Notes:<br><ol>
                <li>To return to this list at any time click the 'workflows' tab.</li>
                <li>Workflows ID's are created with the first available number starting at 1. If a workflow is deleted its id will be reused by the next added workflow.<br>
        Any existing embedded workflow shortcodes that were using this workflow id will then reference the new workflow.</li></ol>
        </small></p>";
        
        case 'Workflow Options' :
            return "<p>This page allows you to customise options for a particular workflow. You can :<p>
                <ul>
                <li>Set the name for this workflow. This value will be expanded into %%WORKFLOW_NAME%% shortcuts if used in the workflows content stages.</li>
                <li>Add a description for a workflow. This is for admin purposes to help identify what a workflow does.</li>
                <li>Add default labels for the back and next buttons. These can be overridded in each of the workflow stages if desired.</li>
                <li>Turn testing mode on or off. In testing mode the buttons will always be shown to allow testing for every stage even if a stage has 'next_active' or 'back active' turned off.</li>
                <li>PRO users may also select a workflow layout template to use. ( These can be created and edited in the templates tab )</li>
                </ul>";
    
        case 'Workflow Stage Options' :
            return "<p>This page is where you can edit your content for each workflow stage. You can :</p>
                <ul><li>Add content for the Title, Header, Body and Footer sections of your page. This content may include shortcuts to expand values such as the stage number or title, the workflow name or other values.<br>
                <small>( please see the table of %%XXX%% shortcuts that are available on the Documentation tab. )</small></li>
                <li>Set the stages next and back button labels text. If not set these values will be taken from the default options for the workflow.</li>
                <li>Set the allowed user movements forward or backward to the next and previous stages.</li>
                <li>Attach simple javascript to the buttons (eg to caution a user before important actions).</li>
                <li>Add your own custom pre-hook and post-hook functions, these are executed before or after a stage is entered or exited whenever a user is in forward motion.<br/>
                Hooked user functions must not contain errors or the workflow will silently fail causing users to remain stuck at the same stage.<br/>
                To do this just create your custom function in your functions.php file (or in any file in the mu-plugins dir) and add the function name (without parenthesis) in the pre- or post- hook field.<br/>              
                See the file /wp-content/plugins/wtf-fu/examples/wtf-fu_hooks_example.php for an example of how to do this to generate confirmation emails, or do other post processing tasks like archiving.
                <li>For PRO users email templates can be attached to automatically send emails when a user passes through a stage. ( This is much more versatile and easier than using your own custom hook functions )</li>
                <li>PRO users can also embed PHP code inside the content by using [wtf_eval] ... [/wtf_eval] blocks <br/> e.g. <code>[wtf_eval]echo 'phpinfo output ->'; phpinfo();[/wtf_eval]</code></li>
                </ul>
                ";
            
            
        case 'Manage Users' :
            return "<p>This page displays a list of all users with currently active Workflows.<br/>Each line in the table lists a user and a workflow and the current stage that the user is at in the workflow.<br/>You can :</p>
                <ul><li>Click a users name to manage that users uploaded files, including archiving and deleting.</li>
                <li>Click the <strong>edit</strong> link ( hover on the users name to see ) to go to the Wordpress User settings page for that user.</li>
                <li>Modify users current stage in a workflow by : 
                    <ol>
                    <li>Change the users current stage number in the right column.</li>
                    <li>Check the checkbox for the users you want to update the current stage numbers for in the left column.</li>
                    <li>Select 'Update' from the bulk actions menu.</li>
                    <li>Click <strong>Apply</strong> to apply the changes.</li>
                    </ol>
                    This is a useful way to manually change a users stage in a workflow. <br/>
                    You can use this feature in conjunction with with a workflows stage <strong>next_active</strong> or <strong>back_active</strong> settings to pause a user at a certain stage in your workflow until you are ready to manually progress them or return them to another stage.
                </li>
                </ul>";
            
        case 'User Options' :
            return "<p>The User files page lists all the files for a user, you can get to this page by clicking a username from the list under the <strong>Manage Users</strong> tab.</p>
                <p>On this page you can : <br/>
                <ul><li>Select a different user who's files you want to manage from the drop down user list.</li>
                <li>Drill down into directories by clicking the directory name in the File Name column.</li>
                <li>Download a users file by clicking on the filename.</li>
                <li>Return to the users root directory location by clicking the <strong>root</strong> link.</li>
                <li>Perform bulk archive or delete actions on the users files</li>
                </ul></p>
                <p>Notes on archiving files :<br/>
                <ol><li>Archive files are automatically named in the format <pre>username_[YYYY-MM-DD_HH-MM-SS].zip</pre></li>
                <li>Archives will be created in the users root directory<br/>
                you can use the <strong>root link</strong> if you are performing the archive from a subdirectory to return to the root directory to see the newly created archive.</li>
                <li>PRO users can also achieve automated archiving for users by embedding the <code>%%ARCHIVE_USERS_FILES%%</code> shortcut inside of an email template.<br/>
                This will automatically create an archive and include a link to the archive inside of the generated email.<br/>
                <span style=\"color: red\">Warning!</span><ol><li> Auto achiving should be used with caution as <span style=\"color: red\">the front end will block</span> whilst the archive is being created.</li>
                <li>Using the <code>%%ARCHIVE_USERS_FILES%%</code> shortcut inside of workflow content or workflow template layout will cause archives to be created everytime the content is displayed 
                when a user access the page, this is probably not what you want.</li>
                <li>Archives should be deleted when no longer required or they will gradually consume the disk space on your server.</li>
                </ol></li>
                </ul></p>";
                       
        case 'Templates' :
            return "<p>This page lists the currently available email and workflow templates.<br/>
                Workflow templates can be attached to a workflow in the <strong>workflow options</strong> tab.<br/>
                Email templates can be attached to a workflow stage in the <strong>workflow options</strong> stage tabs.<br/></p>
                <p>Workflow templates are used to define the layout of a workflow page, and allow you to add your own images and html.<br/>
                You can also re-arrange where the content and buttons are placed and add shortcut fields directly into the workflow page template.</p>
                <p>Email templates are used to automatically send an email when a stage is completed by attaching them to workflow stages.</p>
                <p>On this page you can :</p>
                <ul>
                <li>Click on a templates name to edit the template.</li>
                <li>Click the 'clone' link under a template name to create a new duplicate template.</li>
                <li>Click the 'delete' link under a template name to permanently delete it.</li>
                <li>Delete or clone multiple templates using the checkboxes and the bulk actions menu.</li>
                <li>Add a new copy of the default email or default workflow templates using the <strong>Add Default ... Template</strong> buttons.</li>
                </ul>";
            
        case 'Workflow Templates' :
            return "<p>On this page you can edit a workflow template layout to use with your workflows.<br/>
                Workflow templates are used to define the layout of a workflow page, and allow you to add your own images and html.</p>
                <p>Workflow templates can be attached to a workflow by selecting the template from a drop down list for the <strong>page_template</strong>
                field in the <strong>workflow options</strong> tab on a Workflows edit page.</p>
                <p>Workflow Template Settings. Here you can :<br/>
                <ol><li>Edit a workflow template to include your own html and image files.</li>
                <li>Rearrange the workflow related Shortcut placeholders in the <strong>template</strong> field to place the prev/next buttons, header, 
                content and footer fields within your layout.</li>
                <li>Add additional Shotcuts as desired to include other information at runtime.</li>
                </ol><p>
                <small>Notes:<ol>
                    <li>At least one <strong>%%WORKFLOW_BUTTON_BAR%%</strong> shortcut <strong>must</strong> be included.</li>
                    <li>The plugin stylesheets use a copy of the bootstrap 3.0 css classes like <strong>panel panel-default ...</strong> 
                    with the additional class <strong>tbs</strong> appended so as to avoid any possible conflict with other bootstrap css that may be 
                    present in your templates or other installed plugins on your site.<br/>
                    You may wish to alter the template css class names to use your own custom style sheets. 
                    In this case you can safely disable plugins <strong>system options</strong> setting <strong>include_plugin_style</strong> 
                    and any workflows that use the template <strong>workflow option</strong> settings for <strong>include_plugin_style_default_overrides</strong>.</li>  
                    </ol></small>
                    </p>";
                             
        case 'Email Templates' :
            return "<p>On this page you can edit an email template layout to use to add automated emails to your workflow stages.<br/>
                Email templates can be attached to a workflow stage by selecting the template from a drop down list for the <strong>send_email</strong>
                field one (or more) of the <strong>workflow stage</strong> tabs on a Workflows edit page.<br/>
                Automatic emails are then sent whenever a user passes through the stage in forward motion.</p>
                <p>Email Template Settings. Here you can :<br/>
                <ol><li>Edit an email template to include your own html and image files.</li>
                <li>Use Shortcut placeholders in the <strong>subject, to, from, cc, bcc and message</strong> fields, making it easy to address 
                and copy emails to the current user and administrator emails.</li>
                <li>Embed <strong>[wtf_fu_show_files email_format=1]</strong> shortcodes inside the <strong>message</strong> field
                    to include thumbnail images of the users files inside the email.<br/>
                    <small>Notes:<ol>
                    <li>The attribute <strong>email_format=1</strong> is required to inline the css for use in email content.</li>
                    <li>You should also add the <strong>wtf_upload_dir</strong> and <strong>wtf_upload_subdir</strong> attributes to match
                    the required directory locations that were used in the <strong>[wtf_fu_upload]</strong> shortcode when the files were uploaded.</li>
                    </ol></small></p>";            
            
        case 'Documentation' :
            return "This page collates all available documentation for the other Admin pages.";

        default:
            return "wtf_fu_get_admininterface_info('$type') not implemented yet.";
    }

}


function get_shortcode_info_table($shortcode) {
    
    $table = "<table class='table'  border=1 style='text-align:left;'><tr><th>Shortcode Attribute</th><th>Default Value</th><th>Behaviour</th></tr>";

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
    return array_keys(Wtf_Fu_Option_Definitions::get_instance()->get_page_option_fields_default_labels(wtf_fu_DEFAULTS_SHORTCUTS_KEY));
}

/**
 * Inspects request vars to work out what page we are on.
 * @return string a page identifier.
 */
function wtf_fu_get_page_identifier_from_request() {

    $tab = wtf_fu_get_value($_REQUEST, 'tab');
    $wftab = wtf_fu_get_value($_REQUEST, 'wftab');
    $wtf_action = wtf_fu_get_value($_REQUEST, 'wtf-fu-action');
    $template_type = wtf_fu_get_value($_REQUEST, 'template-type');
       
    $page_id = sprintf('%s%s%s%s', 
        $tab ? "{$tab}" : '',
        $wftab ? "-{$wftab}" : '',
        $wtf_action ? "-{$wtf_action}" : '',
        $template_type ? "-{$template_type}" : '');                    
    
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
        if ($shortcut === '%%USER_GROUP_XXXX_EMAILS%%') {
            continue; //handled separately
        }
        foreach ($field_values as $value) {
            if (strstr($value, $shortcut)) {
                $shortcuts_required[] = $shortcut;
                break; // once a match is found beak to next shortcut               
            }
        }
    }
    
    foreach ($shortcuts_required as $shortcut) {

        switch ($shortcut) {

            case '%%USER_NAME%%' :
                $wp_user = wp_get_current_user();
                $replace[$shortcut] = $wp_user->display_name;
                break;
            
            case '%%USER_ID%%' :
                $wp_user = wp_get_current_user();
                $replace[$shortcut] = $wp_user->ID;
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
            
            case '%%ARCHIVE_USERS_FILES%%' :
                $wp_user = wp_get_current_user();
                $zipname = 'auto_' . wtf_fu_create_archive_name($wp_user->ID, '', '.zip', false);
                $replace[$shortcut] = wtf_fu_do_archive_user_files($wp_user->ID, $zipname); 
                break;
            
            default :
                //log_me("Shortcut replacement key not found for $shortcut");
        }
    }
    
    
    // Handle USER_GROUP_XXXX_EMAILS
    $pattern = '/%%USER_GROUP_([^%]*)_EMAILS%%/';
    foreach ($field_values as $value) {
        $matches = array();
        $num = preg_match_all($pattern, $value, $matches);
        if ( $num >= 1 ) {
            //log_me($matches);
            for ( $i=0; $i < $num; $i++) {
                $replace[$matches[0][$i]] 
                    = Wtf_Fu_Options::get_all_group_user_emails($matches[1][$i]);
            }
        }
    }   

    $fields = str_replace(array_keys($replace), array_values($replace), $fields);

    return $fields;
}
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
    
    $time =  $now - $seconds_offset;
    
    foreach ($files as $file) {       
        wtf_fu_update_timestamp($dir . '/' . $file, $time++);
    }
    
    log_me("$now should == $time");
}

function wtf_fu_update_timestamp($filename, $time) {
    
    if (file_exists($filename)) {      
        if (touch($filename, $time) === true) {
            log_me("SUCCESS : timestamp update for $filename");
        } else {
            log_me("FAILURE : timestamp update for $filename");            
        }      
    }
}

/**
 * replace bad filename character with _
 * 
 * @param type $filename
 * @return type
 */
function wtf_fu_clean_filename($filename) {
    $pattern = '/[\\\\\\/]/';
    $clean_name = preg_replace( $pattern, '__', $filename);
    log_me("wtf_fu_clean_filename $filename => $clean_name");
    return $clean_name;
}


function wtf_fu_write_file($filename, $text) { 
    $fh = fopen($filename, 'w') or die("can't open file");
    fwrite($fh, $text);
    fclose($fh);
    log_me("wrote file $filename");

}

function wtf_fu_get_javascript_form_vars($name, $php_array) {
    
    $js_array = json_encode($php_array);
    
    $js  = <<<EOS
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
function wtf_fu_get_user_upload_paths( $upload_dir = '', $upload_subdir = '', 
        $user_id = 0) {

    $user_id = getUserInfo('ID', $user_id);
    if (!isset($user_id) || $user_id == '') {
        $user_id = 'public';
    }
    
    $upload_array = wp_upload_dir();
    
    $path = '/' . $user_id;
    
     if (!empty($upload_dir)) {
        $path .= "/$upload_dir";
    }   
    if (!empty($upload_subdir)) {
        $path .= "/$upload_subdir";
    }
    
    return array(
        'basedir' => $upload_array['basedir'] . '/' . $user_id,
        'baseurl' => $upload_array['baseurl'] . '/' . $user_id,
        'upload_dir' => $upload_array['basedir'] . $path,
        'upload_url' => $upload_array['baseurl'] . $path
    );
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
    
    if (!empty($subpath)){
        $name .= $subpath;
    }
        
    $name = str_replace( '/', '__', $name);
    
    if($add_date == true) {
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
    return '<small>[<a href="http://wtf-fu.com/download/">powered by wtf-fu</a>, a wordpress workflow and html5 file upload plugin.]</small>';
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
    $html = '<input type="checkbox" id="' . $id . '" name="' . $option_name . '" value="1"' . checked(1, $val, false) . '/>';
    $html .= '&nbsp;';
    $html .= '<label for="' . $id . '">' . $label . '</label>';
    return $html;
}

function wtf_fu_get_example_short_code_attrs($code, $attr) {
    $ret = "[<string>$code</string>";
    foreach ($attr as $k => $v) {
        $ret .= " $k=\"$v\"";
    }
    $ret .= ']';
    return $ret;
}

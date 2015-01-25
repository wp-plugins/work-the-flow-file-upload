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
require_once( plugin_dir_path(__FILE__) . '../../includes/wtf-fu-common-utils.php' );
require_once( plugin_dir_path(__FILE__) . 'wtf-fu-templates.php' );

/**
 * class-wtf-fu-workflow-shortcode.php
 * Wtf_Fu_Fileupload_Shortcode
 * 
 * This class handles the File Upload capability.
 * 
 * @author user
 */
class Wtf_Fu_Fileupload_Shortcode {

    protected $plugin_slug = 'work-the-flow-file-upload';
    protected static $instance = null;
    protected $options;

    public function __construct($attr) {
        $this->options = $attr;
        // log_me(array('Wtf_Fu_Fileupload_Shortcode constructor()' => $attr));
    }


    public static function wtf_fu_load_ajax_function() {
        
//        log_me(array("ajax handler REQUEST:" => $_REQUEST));        
//        check_ajax_referer( 'wtf_fu_upload_nonce', 'security' );
        
        ob_start();
        
        // Get the option defaults.
        $db_options = Wtf_Fu_Options::get_upload_options();
        if ((wtf_fu_get_value($db_options, 'deny_public_uploads') == true) && !is_user_logged_in()) {
            ob_end_clean();
            die("<div class=\"alert\">Public upload access is not allowed. Please log in and try again.</div>");
        }   
           
        $options = $db_options;
        
        // Overwrite defaults with options set by the request.
        foreach (array_keys($options) as $k) {
            if (isset($_REQUEST[$k])) {
                $options[$k] = $_REQUEST[$k];
            }
        }

        // put in a fornat suitable for the UploadHandler.
        $options = self::massageUploadHandlerOptions($options);
        
        // Add in deny options from database AFTER we have processed form field options.
        $options['deny_file_types'] = '/\.('. $db_options['deny_file_types'] . ')$/i';   

        // Include the upload handler.
        require_once('UploadHandler.php');

        error_reporting(E_ALL | E_STRICT);
        
        ob_end_clean(); // Discard any warnings output.
                  
        $upload_handler = new UploadHandler($options);

        die(); // always exit after an ajax call.
    }

    /**
     * Massage captured options into a format consistent with the JQuery 
     * uploadhandler class.
     * 
     * This is mainly because some values are set as regexp expression that
     * are not easy fo users to enter and also because thumbnail values are in 
     * a nested array. 
     * 
     * Only the ones that need changing are massaged. The rest from the request 
     * are passed on as is.
     * 
     * @param type $options
     * @return array
     */
    static function massageUploadHandlerOptions($raw_options) {

        /*
         * 'wtf_upload_dir' MUST be present or the upload directory
         * will default to the wp_admin root !
         */
        if (!array_key_exists('wtf_upload_dir', $raw_options) || empty($raw_options['wtf_upload_dir'])) {
            // die("Option 'wtf_upload_dir' was not found in the request.");
            // force to use public dir if not defined,
            // $raw_options['wtf_upload_dir'] = 'public';
        }

        /*
         * user_id 0 will get paths for current user.
         */
        $paths = wtf_fu_get_user_upload_paths(
                $raw_options['wtf_upload_dir'], $raw_options['wtf_upload_subdir'], 0, $raw_options['use_public_dir']);
        
        $options = array();
        $options['script_url'] = admin_url('admin-ajax.php');
        $options['upload_dir'] = $paths['upload_dir'] . '/';
        $options['upload_url'] = $paths['upload_url'] . '/';

        /*
         * Set up the default array of 'image_versions' options duplicating the 
         * UploadHandler.php defaults values.
         * 
         * Required because the UploadHandler class uses the array '+' operator
         * to merge these options with its default options array. 
         * 
         * Because of this a nested array will always replace the entire 
         * equivilent array in the Handler. 
         * 
         * We avoid this by duplicating the Handlers defaults values here before
         * modifying any attributes passed in $raw_options.
         */

        $options['image_versions'] = array(
            '' => array('auto_orient' => true),
            'thumbnail' => array('crop' => true, 'max_width' => 80, 'max_height' => 80)
        );

        if (array_key_exists('create_medium_images', $raw_options) && $raw_options['create_medium_images'] == true) {
            $options['image_versions']['medium'] = array('max_width' => 800, 'max_height' => 600);
        }

        /* Process the options we know need some massaging. */
        foreach ($raw_options as $k => $v) {
            switch ($k) {
                case 'accept_file_types' :
                case 'inline_file_types' :
                case 'image_file_types' :
                    if (!empty($v)) {
                        $options[$k] = '/\.(' . $v . ')$/i';
                    }
                    break;
                case 'max_number_of_files' :
                    $options[$k] = (int) $v; // TODO is cast needed ?
                    break;
                case 'auto_orient' :
                    if (!empty($v)) {
                        $options['image_versions'][''] = array($k => (bool) $v);
                    }
                    break;
                case 'thumbnail_width' :
                    if (!empty($v)) {
                        $options['image_versions']['thumbnail']['max_width'] = (int) $v;
                    }
                    break;
                case 'thumbnail_height' :
                    if (!empty($v)) {
                        $options['image_versions']['thumbnail']['max_height'] = (int) $v;
                    }
                    break;
                case 'thumbnail_crop' :
                    if (!empty($v)) {
                        $options['image_versions']['thumbnail']['crop'] = (bool) $v;
                    }
                    break;
                case 'medium_width' :
                    if (array_key_exists('medium', $options['image_versions'])) {
                        if (!empty($v)) {
                            $options['image_versions']['medium']['max_width'] = (int) $v;
                        }
                    }
                    break;
                case 'medium_height' :
                    if (array_key_exists('medium', $options['image_versions'])) {
                        if (!empty($v)) {
                            $options['image_versions']['medium']['max_height'] = (int) $v;
                        }
                    }
                    break;
                case 'create_medium_images' :
                    break; // dont add these ones in.

                default :
                    $options[$k] = $v;  // add any others as is unmodified.
            }
        }
        
        return $options;
    }

    /**
     * Renders the File upload form and sets up the options for the UploadHandler.
     * @param array $options
     * @return type
     */
    function uploadFilesHtml($options) {

        $form_vars = '';

        // Add the ajax handler action for jQuery to our options.
        $options['action'] = 'load_ajax_function';

        
        // 
        // Put unmassaged options into POST vars for subsequent posts of 
        // the form. These are then read by the ajax handler load_ajax_function.
        // and then passed as options to the UploadHandler class. 
        // 
        foreach ($options as $k => $v) {
            $form_vars = $form_vars . '<input type="hidden" name="' . $k . '" value="' . $v . '" />';
        };
        
        
        
        // log_me(array("form created"=>$form_vars));

        // The form action MUST be the wp admin hander which will then delegate
        // to our ajax hook load_ajax_function. 
        $action_href = admin_url() . 'admin-ajax.php';

        $html = get_file_upload_form($action_href, $form_vars)
                . getGalleryWidgetTemplate()
                . getUploadJSTemplate()
                . getDownloadJSTemplate();

        return ($html);
    }

    public function generate_content() {

        // set the defaults and allowed options to those stored in the database.
        $defaults = Wtf_Fu_Options::get_upload_options();

        if ((wtf_fu_get_value($defaults, 'deny_public_uploads') == true) && !is_user_logged_in()) {
            return("<div class=\"alert\">Public upload access is denied. Please log in and try again.</div>");
        }

        // override with any short code attributes.
        $options = shortcode_atts($defaults, $this->options);

        // modify this max_file_size from Mb to bytes.
        // done here b/c the html form will validate file size against 
        // this value when it is posted as a hidden field.
        $options['max_file_size'] = (int) $options['max_file_size'] * 1048576;
        return $this->uploadFilesHtml($options);
    }

}

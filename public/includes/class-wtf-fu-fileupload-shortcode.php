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

    protected $plugin_slug = 'wtf-fu';
    protected static $instance = null;
    protected $options;

    public function __construct($attr) {

        $this->options = $attr;


        // Load workflow style sheet and JavaScript.
        // add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        // add_action( 'wp_head', array( $this, 'enqueue_scripts' ) );

        /*
         * Too late too add this to wp_enqueue scripts as recommended.
         * 
         * But this http://codex.wordpress.org/Function_Reference/wp_enqueue_script#Notes
         * seems to say its ok to call mid page after version 3.3
         * 
         * Otherwise we have to enqueue in the main plugin file which means it is included
         * in EVERY page which causes problems when the localized injected vars 
         * are not available.
         * 
         */
        $this->enqueue_scripts();
        $this->enqueue_styles();
    }

    /**
     * Register and enqueue style sheet.
     * @since    1.0.0
     */
    public function enqueue_styles() {
        
    }

    /**
     * Register and enqueues JavaScript files.
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        
        /*
         *  Add the local ajax handler script to the footer.
         *  This will be localized with options before use later.
         * 
         *  This is our modified replacement for js/main.js in the bluimp demo.
         */
        wp_enqueue_script($this->plugin_slug . '-file-upload', 
                plugin_dir_url(__FILE__) . '../assets/js/wtf-fu-file-upload.js', 
                array('jquery'), Wtf_Fu::VERSION, true);
    }

    public static function wtf_fu_load_ajax_function() {

        log_me("wtf_fu_load_ajax_function");
        // Get the option defaults.
        $options = Wtf_Fu_Options::get_upload_options();

        // Overwrite any options set in the request.
        foreach (array_keys($options) as $k) {
            if (isset($_REQUEST[$k])) {
                $options[$k] = $_REQUEST[$k];
            }
        }

        // put in a fornat suitable for the UploadHandler.
        $options = self::massageUploadHandlerOptions($options);

        /* Include the upload handler */
        require_once(wtf_fu_JQUERY_FILE_UPLOAD_HANDLER_FILE);

        error_reporting(E_ALL | E_STRICT);
        $upload_handler = new UploadHandler($options);
        
        /*
         * Intentional, must always die or exit after an ajax call.
         */
        die(); 
        
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
        if (!array_key_exists('wtf_upload_dir', $raw_options) 
                || empty($raw_options['wtf_upload_dir'])) {
            
            die("Option 'wtf_upload_dir' was not found in the request.");
        }

        /*
         * user_id 0 will get paths for current user.
         */
        $paths = wtf_fu_get_user_upload_paths(
                $raw_options['wtf_upload_dir'], $raw_options['wtf_upload_subdir']);

        $options = array();
        $options['script_url'] = admin_url('admin-ajax.php');
        $options['upload_dir'] = $paths['upload_dir'] . '/';
        $options['upload_url'] = $paths['upload_url'] . '/';


        /*
         * Set up the default array of 'image_versions' options duplicating the 
         * UploadHandler.php defaults values.
         * 
         * Required because the UploadHandler class uses the array '+' operator
         * to merge these options with its default optoins array. 
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

        if ($raw_options['create_medium_images'] == true) {
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

        // we also add the handler action for jQuery to our options.
        // This is not really an option but required for wp ajax to load the 
        // correct handler fucntion. It is inserted in the same way as the options
        // so easiest to just include it in the array and process the lot together.
        $options['action'] = 'load_ajax_function';

        // 
        // Put unmassaged options into POST vars for subsequent posts of 
        // the form. These are then read by the ajax handler load_ajax_function.
        // and then passed as options to the UploadHandler class. 
        // 
        foreach ($options as $k => $v) {
            $form_vars = $form_vars . '<input type="hidden" name="' . $k . '" value="' . $v . '" />';
        };

        /*
         * Also Inject the options as data into the jQuery script so they will 
         * get passed into the ajax callback. These will appear as REQUEST vars
         * when the callback handler is invoked. This may be POST or GET 
         * depending on whether user is logged in.
         * 
         * This is required in addition to the hidden fields, because the first
         * call to the ajax handler is done before the form has been posted !
         * 
         * This happens when the js fires the event to display the files 
         * available for download.
         * 
         * So we need to inject the data as json data into a global JS variable
         * that will be localised into our jQuery script. They are then accessable 
         * via $_REQUEST vars by our load_ajax_function method and can be massaged
         * and passed onto the JQuery Html5 File Upload UploadHandler class.
         */
        $ret = wp_localize_script($this->plugin_slug . '-file-upload', 'WtfFileUploadVars', array('url' => admin_url('admin-ajax.php'),
            'absoluteurl' => wtf_fu_JQUERY_FILE_UPLOAD_URL . 'cors/result.html?%s',
            'data' => $options
                )
        );

        error_log("wp_localize_script ret= " . print_r($ret, true));


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
        
        if ( (wtf_fu_get_value($defaults, 'deny_public_uploads') == true)  && !is_user_logged_in()) {
            return("You need to be logged in to access file upload features. Please log on and try again.");
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

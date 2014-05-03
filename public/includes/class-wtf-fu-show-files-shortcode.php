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

define('WTF_FU_BAD_IMAGE_URL', plugins_url('public/assets/img/error.svg'));

/**
 * class-wtf-fu-filereorder-shortcode.php
 * Wtf_Fu_Filereorder_Shortcode
 * 
 * This class manages display and reordering of images.
 * 
 * @author user
 */
class Wtf_Fu_Show_Files_Shortcode {

    protected $plugin_slug = 'work-the-flow-file-upload';
    protected static $instance = null;
    protected $options;
    protected $files;
    protected $paths;

    /**
     * Set up the class options from the passed array of atributes.
     * This may be via an initial shortcode request or via request vars after an ajax form sumbit
     * during re-ordering processing.
     * 
     * @param type $attr
     */
    public function set_options($attr) {

        log_me(array("set_options passed in " => $attr));

        /*
         * retrieve the default upload options to act as defaults.
         */
        $default_upload_settings = Wtf_Fu_Options::get_upload_options();


        /*
         * Process shortcode attributes passed by user.
         * We are only interested in the directory names for this shortcode.
         */
        $this->options = shortcode_atts(
                array(
            'wtf_upload_dir' => $default_upload_settings['wtf_upload_dir'],
            'wtf_upload_subdir' => $default_upload_settings['wtf_upload_subdir'],
            'reorder' => false,
            'gallery' => false,
            'file_type' => "image",
            'email_format' => false,
            'show_numbers' => true,
                ), $attr);

        /*
         * Current User upload directory paths.
         */
        $this->paths = wtf_fu_get_user_upload_paths($this->options['wtf_upload_dir'], $this->options['wtf_upload_subdir']);

        /*
         * Glob the files.
         */
        $this->files = array();

        /*
         * Sort the files by timestamp with the oldest files first.
         */
        $filearray = glob($this->paths['upload_dir'] . '/*');

        array_multisort(array_map('filemtime', $filearray), SORT_NUMERIC, SORT_ASC, $filearray);

        foreach ($filearray as $filename) {

            if (!is_dir($filename)) {
                $info = new stdClass();

                $info->filename = $filename;
                $info->basename = basename($filename);
                $info->fileurl = $this->paths['upload_url'] . '/' . $info->basename;

                $info->thumb = $this->paths['upload_dir']
                        . '/thumbnail/' . $info->basename;

                $info->thumburl = $this->paths['upload_url']
                        . '/thumbnail/' . $info->basename;

                if (!file_exists($info->thumb)) {
                    $info->thumb = 'thumnail image not found';
                    $info->thumburl = WTF_FU_BAD_IMAGE_URL;
                }

                $this->files[] = $info;
            }
        }
    }

    /**
     * Constructor for the class Wtf_Fu_Filereorder_Shortcode
     * 
     * It is constructed from shortcode attributes for the files location
     * under the user upload directory. 
     */
    public function __construct() {
        log_me('__construct  Wtf_Fu_Filereorder_Shortcode ');
    }

    /**
     * Return a singleton instance of this class.
     * @return    object    A single instance of this class.
     */
    public static function get_instance() {

        // If the single instance hasn't been set, set it now.
        if (null == self::$instance) {
            self::$instance = new self;
            log_me("new " . __CLASS__ . " instance created.");
            return self::$instance;
        }

        log_me("existing  " . __CLASS__ . " instance returned.");
        return self::$instance;
    }

    /**
     * Callback from ajax javascript.
     * 
     * This method will update the file timestamps in the order that the 
     * form divs have been sorted into by the user.
     * 
     * Note that this static method is called via the main plugin class as 
     * WP Ajax seems to require that the handlers be registered in the main
     * plugin file.
     * 
     * To get around that these is a wrapper method in the main class Wtf_fu 
     * which then delegates to this static method here. 
     */
    public static function wtf_fu_ajax_show_files_function() {
        
        // Buffer output during ajax calls.
        ob_start();

        //log_me(array("wtf_fu_ajax_reorder_function" => $_REQUEST));

        switch ($_REQUEST['fn']) {

            case 'show_files' :

                $paths = wtf_fu_get_user_upload_paths(
                        $_REQUEST['wtf_upload_dir'], $_REQUEST['wtf_upload_subdir']);

                /*
                 * Loop over the files and update the timestamps
                 * 
                 * This should mean that the resulting files when sorted in 
                 * date-time order will be in the order as arranged by the user.
                 */
                wtf_fu_set_files_order_times($paths['upload_dir'], $_REQUEST['files']);


                /*
                 * Also write a file with the list of files order to the user 
                 * root Directory.
                 */
                $filename = wtf_fu_clean_filename(
                        "file_order_{$_REQUEST['wtf_upload_dir']}/{$_REQUEST['wtf_upload_subdir']}.txt");

                $file_order_txt = 'User File ordering selections : ' . PHP_EOL;
                $i = 1;
                foreach ($_REQUEST['files'] as $file) {
                    $file_order_txt .= sprintf("%3s\t%s%s", $i, $file, PHP_EOL);
                    $i++;
                }

                wtf_fu_write_file(
                        $paths['basedir'] . '/' . $filename, $file_order_txt);

                break;

            default:
                break;
        }

        /**
         * Instantiate an instance of the class and initialize from the REQUEST 
         * form vars.
         */
        $instance = self::get_instance();
        $instance->set_options($_REQUEST);

        $content = $instance->generate_files_div();

        $response = array(
            'what' => 'stuff',
            'action' => 'wtf_fu_show_files',
            'id' => '1', //new WP_Error('oops','I had an accident.'),
            'data' => $content
        );

        $xmlResponse = new WP_Ajax_Response($response);

        log_me(array('xmlresponse' => $xmlResponse));
        
        
        $xmlResponse->send();
        
        ob_end_clean();
        
        /*
         * Intentional, must always die or exit after an ajax call.
         */
        exit;
    }

    /**
     * The Reorder page.
     * 
     * TODO this need reworking to better assemble the list css.
     * need to better separate image / music / reorder styles.
     */
    function generate_content() {

        $html = '';

        /*
         * If we are reordering then we need a submit button and to store all the options,
         * so we can re-generate the content after submission of the new order.
         */
        if ($this->options['reorder'] == true) {

            $action_href = admin_url('admin-ajax.php');

            $form_vars = '';
            foreach ($this->options as $k => $v) {
                $form_vars = $form_vars . '<input type="hidden" name="' . $k . '" value="' . $v . '" />';
            };

            $html .= "<form id='wtf_show_files_form' action='$action_href' method='post'>"
                    . "<input type='hidden' name='action' value='wtf_fu_show_files' />"
                    . "<input type='hidden' name='fn' value='show_files' />";

            $html .= $form_vars;
            $html .= '<div><button id="reorder_submit_button" class="btn btn-success" type="submit">
                <i class="glyphicon glyphicon-retweet"></i><span>&nbsp;&nbsp;&nbsp;Save new file order</span></button></div>';

            $html .= '<div id="reorder_response"><p>&nbsp;<p></div></form>';
        }

        $html .= '<div id="links" class="links">' . $this->generate_files_div() . '</div>';

        if ($this->options['gallery'] == true) {
            $script = <<<GALLERYJSTEMPLATE
   <!-- The blueimp Gallery widget -->
<div id="blueimp-gallery" class="blueimp-gallery blueimp-gallery-controls" data-filter="">
    <div class="slides"></div>
    <h3 class="title"></h3>
    <a class="prev">‹</a>
    <a class="next">›</a>
    <a class="close">×</a>
    <a class="play-pause"></a>
    <ol class="indicator"></ol>
</div>
GALLERYJSTEMPLATE;


            $html .= $script; // getGalleryWidgetTemplate();
        }

        /*
         * If this is for inclusion in an email then inline the css into style tags.
         */
        if ($this->options['email_format'] == true) {   
            // namespaced classes only work with php >= 5.3.0
            if (version_compare(phpversion(), '5.3.0', '>')) {             
                require_once(plugin_dir_path(__FILE__) . '../assets/tools/wtf_fu_php_53_only.php');
                
                // inline the required css for email html display.
                $css = inline_css_style_file(plugin_dir_path(__FILE__) . '../assets/css/bootstrap.css');
                $css .= inline_css_style_file(plugin_dir_path(__FILE__) . '../assets/css/wtf-fu-show-files.css');

                $html = wtf_fu_53_do_inline_style_conversion($html, $css);
            } else {
                log_me('WARNING : Could not inline CSS for email_format : '
                        . 'PHP version needs to be >= 5.3.0 but only php version ' . 
                        phpversion() . ' was detected');
            }
        }
        return $html;
    }

    public function generate_files_div() {

        $container_id = 'files_container';
        $ul_id = 'files_list';

        if ($this->options['reorder'] == true) {
            $container_id = 'sort_container';
            $ul_id = 'reorder_sortable';
        }

        $html = "<div id='$container_id'>";
        $html .= "<ul id='$ul_id'>";

        $i = 0;
        foreach ($this->files as $file) {
            $i++;
            switch ($this->options['file_type']) {

                case 'image' :
                    $file_link = sprintf(
                            '<a href="%s" title="%s" data-gallery><img src="%s" alt="%s"></a>', $file->fileurl, $file->basename, $file->thumburl, $file->basename);
                    $number_div = '';
                    if ($this->options['show_numbers'] == true) {
                        $number_div = sprintf('<p class="reorder-number">%s</p>', $i);
                    }
                    $html .= sprintf(
                            '<li class="list" title="%s">%s%s</li>', $file->basename, $number_div, $file_link);
                    break;

                case 'music' :

                    $file_link = sprintf(
                            '<p>%s&nbsp;&nbsp;&nbsp;<a href="%s">%s</a></p><p><audio src="%s" controls="controls"></audio></p>', $i, $file->fileurl, $file->basename, $file->fileurl
                    );
                    $html .= sprintf(
                            '<li title="%s">%s</li>', $file->basename, $file_link);

                    break;

                default:
                    $file_link = sprintf(
                            '<a href="%s">%s</a>', $file->fileurl, $file->basename
                    );
                    break;
            }
        }

        $html .= '</ul></div>';
        return $html;
    }

}

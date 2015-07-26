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

//define('WTF_FU_BAD_IMAGE_URL', plugins_url('public/assets/img/error.svg'));

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

        //log_me(array("set_options passed in " => $attr));

        /*
         * retrieve the default upload options to act as defaults.
         */
        $default_upload_settings = Wtf_Fu_Options::get_upload_options();


        /*
         * Process shortcode attributes passed by user.
         * We are only interested in the directory names for this shortcode.
         */
        $this->options = shortcode_atts(
                Wtf_Fu_Option_Definitions::get_instance()->
                        get_page_option_fields_default_values(wtf_fu_DEFAULTS_SHORTCODE_SHOWFILES_KEY)
                , $attr);

        /*
         * Current User upload directory paths.
         */
        $this->paths = wtf_fu_get_user_upload_paths($this->options['wtf_upload_dir'], $this->options['wtf_upload_subdir'], 0, $this->options['use_public_dir']);

        //log_me(array("showfiles paths="=>$this->paths));
        //log_me("use_public_dir = {$this->options['use_public_dir']}");

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
                $this->files[] = wtf_getFileInfo($filename, $this->paths);
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
        // log_me('__construct  Wtf_Fu_Filereorder_Shortcode ');
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

        $fn = wtf_fu_get_value($_REQUEST, 'fn');

        switch ($fn) {
            case 'show_files' :

                $files = wtf_fu_get_value($_REQUEST, 'files');

                $files = wtf_fu_stripslashes_deep($files);

                if ($files !== false) {
                    $upload_dir = wtf_fu_get_value($_REQUEST, 'wtf_upload_dir');
                    $upload_subdir = wtf_fu_get_value($_REQUEST, 'wtf_upload_subdir');
                    $use_public_dir = wtf_fu_get_value($_REQUEST, 'use_public_dir');

                    $paths = wtf_fu_get_user_upload_paths($upload_dir, $upload_subdir, 0, $use_public_dir);

                    // Update all the files last modified timestamps
                    wtf_fu_set_files_order_times($paths['upload_dir'], $files);

                    // Write a file with the list of the files ordering
                    $filename = wtf_fu_clean_filename("file_order_{$upload_dir}/{$upload_subdir}.txt");

                    $file_order_txt = 'User File ordering selections : ' . PHP_EOL;
                    $i = 1;
                    foreach ($files as $file) {
                        $file_order_txt .= sprintf("%3s\t%s%s", $i, $file, PHP_EOL);
                        $i++;
                    }
                    wtf_fu_write_file($paths['basedir'] . '/' . $filename, $file_order_txt);
                }
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
        //log_me(array('xmlresponse' => $xmlResponse));

        ob_end_clean();

        $xmlResponse->send();

        /*
         * Intentional, must always die or exit after an ajax call.
         */
        exit;
    }

    function generate_content() {

        // for emails just want the files.
        if ($this->options['email_format'] == true) {
            // namespaced classes only work with php >= 5.3.0
            $html = $this->generate_files_div();

            if (version_compare(phpversion(), '5.3.0', '>=')) {

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
            return $html;
        }

        $html = '';

        $html .= '<div id="wtf_fu_show_files_output">' . $this->generate_inner_content() . '</div>';

        // Add in the gallery controls if required.
        if ($this->options['gallery'] == true) {

            $blueimp_gallery_conrols = '<div id="blueimp-gallery" class="blueimp-gallery blueimp-gallery-controls" data-filter="">
                <div class="slides"></div>
                <h3 class="title"></h3>
                <a class="prev">‹</a>
                <a class="next">›</a>
                <a class="close">×</a>
                <a class="play-pause"></a>
                <ol class="indicator"></ol>
            </div>';
            $html .= $blueimp_gallery_conrols;
        }

        return $html;
    }

    /**
     * The Reorder page.
     * 
     * TODO this need reworking to better assemble the list css.
     * need to better separate image / music / reorder styles.
     */
    function generate_inner_content() {

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

            $html .= "<form id='wtf_show_files_form' name='wtf_fu_show_files_form' action='$action_href' method='post'>
                    <input type='hidden' name='action' value='wtf_fu_show_files' />
                    <input type='hidden' name='fn' value='show_files' />
                    $form_vars
                <div id='reorder_button_container'>
                <button id='reorder_submit_button' class='btn btn-success' type='submit' disabled='disabled'>
                <i class='glyphicon glyphicon-retweet'></i>              
                <span>Update Order</span>
                </button>
                <div id='reorder_message'>Drag and drop to change the order.</div>
                <span class=reorder-process></span>
                </div>
                </form>";
        }

        $html .= $this->generate_files_div();

        return $html;
    }

    public function generate_files_div() {

        $html = "<div id='wtf_fu_show_files_response'>";

        $container_id = 'files_container';
        $ul_id = 'files_list';

        if ($this->options['reorder'] == true) {
            $container_id = 'sort_container';
            $ul_id = 'reorder_sortable';
        }

        $vertical_class = '';
        if ($this->options['vertical'] == true) {
            $vertical_class = 'vertical';
        }

        $html .= "<div id='$container_id'>";
        $html .= "<ul id='$ul_id'  class='$vertical_class'>";

        $i = 0;
        foreach ($this->files as $file) {
            $i++;
            $html .= $this->render_file_li($i, $file);
        }

        $html .= '</ul></div></div>';
        return $html;
    }

    function render_file_li($number, $file) {
               
        $number_div = '';
        $gallery_att = '';
        $audio_controls = '';
        $file_title_class = '';
        $vertical_span = '';
        $image_src = '';

        if ($this->options['show_images'] == true && $file->filetype == 'image') {
            $image_src = sprintf('<img src="%s" alt="%s">', $file->thumburl, $file->basename);
        }

        if ($this->options['gallery'] == true) {
            $gallery_att = 'data-gallery';
        }
        if ($this->options['vertical'] == true) {
            $vertical_span = sprintf("<span>%s</span>", $file->basename);
        }
        if ($this->options['show_numbers'] == true) {
            $file_title_class = 'pad_top_20px';
            $number_div = "<span class='reorder-number'>$number</span>";
        }
        if ($this->options['audio_controls'] == true) {
            $audio_controls = ' controls="controls"';
        }

        $download = false;
        if ($this->options['download_links'] == true) {
            $download = true;
        }

        switch ($file->filetype) {
            case 'image' :
                $file_link = sprintf(
                        '<a %s href="%s" title="%s">%s%s</a>', $gallery_att, $file->fileurl, $file->basename, $vertical_span, $image_src);
                break;
            case 'audio' :
                if ($download) {
                    $file_link = sprintf(
                            '<a class="%s" href="%s">%s%s</a><audio src="%s"%s></audio>', $file_title_class, $file->fileurl, $file->basename, $image_src, $file->fileurl, $audio_controls);
                } else {
                    $file_link = sprintf(
                            '%s<span class="%s" title="%s">%s</span><audio src="%s"%s></audio>', $image_src, $file_title_class, $file->basename, $file->basename, $file->fileurl, $audio_controls);
                }
                break;
            case 'text' :
            default: // default to text if type not found.

                if ($download) {
                    $file_link = sprintf('<a class="%s" href="%s">%s%s</a>', $file_title_class, $file->fileurl, $file->basename, $image_src);
                } else {
                    $file_link = sprintf('<span class="%s" title="%s">%s%s</span>', $file_title_class, $file->basename, $file->basename, $image_src);
                }
                break;
        }
        $line = sprintf('<li class="list" title="%s">%s%s</li>', $file->basename, $number_div, $file_link);
        return $line;
    }

}

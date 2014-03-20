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

define ('WTF_FU_BAD_IMAGE_URL' , plugins_url('public/assets/img/error.svg'));

/**
 * class-wtf-fu-filereorder-shortcode.php
 * Wtf_Fu_Filereorder_Shortcode
 * 
 * This class manages display and reordering of images.
 * 
 * @author user
 */
class Wtf_Fu_Show_Files_Shortcode {

    protected $plugin_slug = 'wtf-fu';
    protected static $instance = null;
    protected $options;
    protected $files;
    protected $paths;
    
    

    /**
     * Constructor for the class Wtf_Fu_Filereorder_Shortcode
     * 
     * It is constructed from shortcode attributes for the files location
     * under the user upload directory. 
     */
    public function __construct($attr) {

        
        log_me('__construct  Wtf_Fu_Filereorder_Shortcode ');
        
        $this->enqueue_styles();


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
                ), $attr);

         if( $this->options['reorder'] == true) {
             $this->enqueue_scripts();
         }
                
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
        
        array_multisort(array_map('filemtime', $filearray), 
                SORT_NUMERIC, SORT_ASC, $filearray); 
        
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

        log_me(array("wtf_fu_ajax_reorder_function" => $_REQUEST));

        switch ($_REQUEST['fn']) {

            case 'show_files' :
                
                $paths = wtf_fu_get_user_upload_paths(
                        $_REQUEST['wtf_upload_dir'], 
                        $_REQUEST['wtf_upload_subdir']);

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
                    $file_order_txt .= sprintf("%3s\t%s%s" ,
                            $i, $file, PHP_EOL);
                    $i++;             
                }
                
                wtf_fu_write_file( 
                    $paths['basedir'] . '/' . $filename, $file_order_txt);
                
                break;

            default:
                break;
        }

        $response = array(
           'what'=>'stuff',
           'action'=> 'wtf_fu_show_files',
           'id'=> '1', //new WP_Error('oops','I had an accident.'),
           'data'=>'The new file order has been successfully updated.'
       //    'supplemental'
        );
        
        $xmlResponse = new WP_Ajax_Response($response);
        $xmlResponse->send();
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
        $container_id = 'files_container';
        $ul_id = 'reorder_sortable';
       // $file_id = 'files_list';
        
        if( $this->options['reorder'] == true) {
        
            $ret = wp_localize_script($this->plugin_slug . '-show-files-js', 
                'show_files_js_vars', 
                array (
                    'action' => 'wtf_fu_show_files',
                    'url' => admin_url('admin-ajax.php'),
                    'fn' => 'show_files',
                    'wtf_upload_dir' => $this->options['wtf_upload_dir'], 
                    'wtf_upload_subdir' => $this->options['wtf_upload_subdir']
                ));

           // log_me("reorder generate_content()  wp_localize_script returned [$ret]");
            $container_id = 'sort_container';
            $ul_id = 'reorder_sortable';

            $html = '<div><button id="reorder_submit_button" class="btn btn-success">
                <i class="glyphicon glyphicon-retweet"></i><span>&nbsp;&nbsp;&nbsp;Save new file order</span></button></div>';

            $html .= '<div id="reorder_response"><p>&nbsp;<p></div>';
        }
        
        
        switch ( $this->options['file_type'] ) {
            case 'image' :
                $ul_id = 'reorder_sortable';
               // $file_id = 'files_list'; 
                break;
            case 'music' :
                $ul_id = 'files_list';   
                break;
        }

        $html .= '<div id="links" class="links">';
        $html .= "<div id='$container_id'>";
        $html .= "<ul id='$ul_id'>";

        $i = 0;
        foreach ($this->files as $file) {
            $i++;
            switch ( $this->options['file_type'] ) {
                
                case 'image' :
                    $file_link = sprintf(                    
                        '<a href="%s" title="%s" data-gallery><img src="%s" alt="%s"></a>', 
                        $file->fileurl, 
                        $file->basename, 
                        $file->thumburl, 
                        $file->basename);
                    
                        $html .= sprintf(
                            '<li title="%s"><span class="ui-icon ui-icon-arrowthick-2-n-s">%s</span>%s</li>', 
                                $file->basename, $i, $file_link);  
                        
                    break;
                
                case 'music' :
                    
                    $file_link = sprintf( 
                        '<p>%s&nbsp;&nbsp;&nbsp;<a href="%s">%s</a></p><p><audio src="%s" controls="controls"></audio></p>', 
                        $i,
                        $file->fileurl,
                        $file->basename,
                        $file->fileurl                         
                    ); 
                        $html .= sprintf(
                            '<li title="%s">%s</li>', 
                                $file->basename, $file_link);  
                        
//                        $html .= sprintf(
//                            "<li title='%s'><span>%s&nbsp;%s</span></li>", 
//                                $file->basename, $i, $file_link); 
                        
                    break;
                
                default:
                    $file_link = sprintf (
                        '<a href="%s">%s</a>',
                        $file->fileurl, 
                        $file->basename
                    );                             
                            
                    break;      
            }                                 

        }

        $html .= '</ul></div></div>';
        
        if( $this->options['gallery'] == true) {
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
        
        return $html;
    }

    /**
     * Register and enqueue class related style sheet.
     * NOTE : this is NOT called on the wp_enqueue hook as that is called before this
     * class is contructed.
     * 
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_slug . '-show-files-css', plugins_url('../assets/css/wtf-fu-show-files.css', __FILE__), array(), Wtf_Fu::VERSION);
    }

    /**
     * Register and enqueues JavaScript files specific to this shortcode.
     * 
     * NOTE : this is NOT called on the wp_enqueue hook as that is called before this
     * class is contructed.
     */
    public function enqueue_scripts() {
        /*
         * Add the ajax handler javascript.
         */

        wp_enqueue_script(
                'wp-ajax-response', 
                site_url('/wp-includes/js/wp-ajax-response.js'), 
                array( 'jquery' ), true );
        
        $script_handle = $this->plugin_slug . '-show-files-js';
        wp_enqueue_script(
                $script_handle, 
                plugin_dir_url(__FILE__) . '../assets/js/wtf-fu-show-files.js', 
                array('jquery', 'wp-ajax-response'), Wtf_Fu::VERSION, true);        
    }
    
}

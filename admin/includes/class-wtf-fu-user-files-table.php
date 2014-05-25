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

/* 
 * The WP_List_Table class isn't automatically available to plugins, so we need
 * to check if it's available and load it if necessary.
 */
if (!class_exists('WP_List_Table')) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

require_once plugin_dir_path(__FILE__) . 'class-wtf-fu-options-admin.php';
require_once plugin_dir_path(__FILE__) . '../../includes/class-wtf-fu-option-definitions.php';

class Wtf_Fu_User_Files_Table extends WP_List_Table {

    private $filearray, $subpath, $user_id, $user_root, $files_root, $files_url;

    /**
     * the data to populate the rows of the list Table.
     * @return type
     */
    function get_data() {
        $data = array();
                
        $this->filearray = glob($this->files_root . '/*');


        

        foreach ($this->filearray as $filename) {

            $data[] = array (
                'file_name' => basename($filename),
                'file_time' => strftime('%c', filemtime($filename)),
                'file_size' => filesize($filename),
                'file_time_stamp' => filemtime($filename), 
                'file_type' => is_dir($filename) ? 'directory' : 'file'
                );
        }
        
        //log_me(array('data=' => $data));
        return $data;
    }

    /**
     * REQUIRED. Set up a constructor that references the parent constructor. We 
     * use the parent reference to set some default configs.
     */
    function __construct($user_id, $user_root, $files_root, $files_url, $subpath) {
        global $status, $page;

                //Set parent defaults
        parent::__construct(array(
            'singular' => 'file', //singular name of the listed records
            'plural' => 'files', //plural name of the listed records
            'ajax' => false        //does this table support ajax?
        ));     
        
        $this->subpath = $subpath;
        $this->user_id = $user_id;
        $this->user_root = $user_root;
        $this->files_root = $files_root;
        $this->files_url = $files_url;
                
//        log_me(array ( "Wtf_Fu_User_Files_Table __construct" => array(
//            'filearray' => $this->filearray, 
//            'subpath' => $this->subpath,
//            'user_id' => $this->user_id,
//            'files_root' => $this->files_root,
//            'files_url' => $this->files_url)));
    }

    /**
     * Recommended. This method is called when the parent class can't find a method
     * specifically build for a given column. Generally, it's recommended to include
     * one method for each column you want to render, keeping your package class
     * neat and organized. For example, if the class needs to process a column
     * named 'name', it would first see if a method named $this->column_name() 
     * exists - if it does, that method will be used. If it doesn't, this one will
     * be used. Generally, you should try to use custom column methods as much as 
     * possible. 
     * 
     * Since we have defined a column_name() method later on, this method doesn't
     * need to concern itself with any column with a name of 'name'. Instead, it
     * needs to handle everything else.
     * 
     * For more detailed insight into how columns are handled, take a look at 
     * WP_List_Table::single_row_columns()
     * 
     * @param array $item A singular item (one full row's worth of data)
     * @param array $column_name The name/slug of the column to be processed
     * @return string Text or HTML to be placed inside the column <td>
     */
    function column_default($item, $column_name) {
        switch ($column_name) {
            default:
                return $item[$column_name];
            // return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    /**
     * Recommended. This is a custom column method and is responsible for what
     * is rendered in any column with a name/slug of 'name'. Every time the class
     * needs to render a column, it first looks for a method named 
     * column_{$column_name} - if it exists, that method is run. If it doesn't
     * exist, column_default() is called instead.
     * 
     * This example also illustrates how to implement rollover actions. Actions
     * should be an associative array formatted as 'slug'=>'link html' - and you
     * will need to generate the URLs yourself. You could even ensure the links
     * 
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (workflow name only)
     */
    function column_file_name($item) {

        if ($item['file_type'] === 'directory') {
            $link = sprintf('<a href="?page=%s&tab=%s&wtf-fu-action=%s&user=%s&subpath=%s">%s</a>', 
                $_REQUEST['page'], 
                wtf_fu_PAGE_USERS_KEY, 
                'user', 
                $this->user_id, 
                $this->subpath . '/' . $item['file_name'], 
                $item['file_name']);
        } else {
            $link = sprintf('<a href="%s/%s" target=\"_blank\">%s</a>',  
                $this->files_url,
                $item['file_name'],
                $item['file_name']
                );
        }

        return $link;
    }

    /**
     * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
     * is given special treatment when columns are processed. It ALWAYS needs to
     * have it's own method.
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (workflow name only)
     */
    function column_cb($item) {
        return sprintf(
                '<input type="checkbox" name="%1$s[]" value="%2$s" />',
                /* $1%s */ $this->_args['singular'], //Let's simply repurpose the table's singular label ("workflow")
                /* $2%s */ $item['file_name']                //The value of the checkbox should be the record's id
        );
    }

    /**
     * REQUIRED! This method dictates the table's columns and names. This should
     * return an array where the key is the column slug (and class) and the value 
     * is the column's name text. If you need a checkbox for bulk actions, refer
     * to the $columns array below.
     * 
     * The 'cb' column is treated differently than the rest. If including a checkbox
     * column in your table you must create a column_cb() method. If you don't need
     * bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
     * 
     * @see WP_List_Table::::single_row_columns()
     * @return array An associative array containing column information: 'slugs'=>'Visible Names'
     */
    function get_columns() {
        $columns = array(
            'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
            'file_name' => 'File Name',
            'file_type' => 'Type',
            'file_size' => 'Size (bytes)',
            'file_time' => 'Last Modified',
        );
        return $columns;
    }

    /**
     * Optional. If you want one or more columns to be sortable (ASC/DESC toggle), 
     * you will need to register it here. This should return an array where the 
     * key is the column that needs to be sortable, and the value is db column to 
     * sort by. Often, the key and value will be the same, but this is not always
     * the case (as the value is a column name from the database, not the list table).
     * 
     * This method merely defines which columns should be sortable and makes them
     * clickable - it does not handle the actual sorting. You still need to detect
     * the ORDERBY and ORDER querystring variables within prepare_items() and sort
     * your data accordingly (usually by modifying your query).
     * 
     * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
     */
    function get_sortable_columns() {
        $sortable_columns = array(
            'file_name' => array('file_name', false),
            'file_type' => array('file_type', false),
            'file_size' => array('file_size', false),
            'file_time' => array('file_time', false)
        );
        return $sortable_columns;
    }

    /**
     * Optional. If you need to include bulk actions in your list table, this is
     * the place to define them. Bulk actions are an associative array in the format
     * 'slug'=>'Visible Name'
     * 
     * If this method returns an empty value, no bulk action will be rendered. If
     * you specify any bulk actions, the bulk actions box will be rendered with
     * the table automatically on display().
     * 
     * Also note that list tables are not automatically wrapped in <form> elements,
     * so you will need to create those manually in order for bulk actions to function.
     * 
     * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Names'
     */
    function get_bulk_actions() {
        $actions = array(
            'archive' => 'Archive',
            'delete' => 'Delete'
        );
        return $actions;
    }

    /**
     * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
     * For this example package, we will handle it in the class to keep things
     * clean and organized.
     * 
     * @see $this->prepare_items()
     */
    function process_bulk_action() {
        
        //log_me( array('user_flies_table : process_bulk_action $_REQUEST' => $_REQUEST));

        if ( isset( $_REQUEST['file'] ) ) {

            switch ($this->current_action()) {
                
                case 'delete' :
                    
                    $this->do_delete_files($_REQUEST['file']);
                    break;
                
                case 'archive' : 
                    $zip_name = wtf_fu_create_archive_name($this->user_id, $this->subpath);
                    if ( $ret = $this->do_archive_files($zip_name, $_REQUEST['file']) !== true) {
                        echo $ret;
                    }
                    break;
                    
                default :
            }
        }
    }
    
    function do_delete_files($files) {
        
        foreach ($files as $file) {
            
            $file_to_delete = $this->files_root . '/' . $file;
            
            if (! Wtf_Fu_Admin_Utils::safe_delete_files($file_to_delete, true)) {
                die("unable to delete file $file_to_delete");
            }
        }       
    }
    
    
    /**
     * archives array of select files.
     * 
     * @param type $zip_name
     * @param type $files
     * @param type $inc_archives
     * @return type
     */
    function do_archive_files($zip_name, $files, $inc_archives = false) {
        
        $archive_name = $this->user_root . "/$zip_name";
        
        $zip = new Wtf_Fu_Archiver();
        
        $ret = $zip->create_archive($archive_name, $inc_archives);
        
        if ($ret === true) {
        
            foreach ($files as $file) {
                $ret = $zip->add_file_or_dir($this->files_root . '/' . $file); 
            }
            
        } else {           
            echo 'Archive creation for ' . $archive_name . ' failure ' . $ret;
        }
        
        $ret = $zip->close();
        if ($ret === true) {
            echo 'Archive ' . $zip_name . ' has been created in the users root directory. Click the [root] link to view the file in the user root directory.';          
        }
        
        return $ret;
    }


    /**
     * REQUIRED! This is where you prepare your data for display. This method will
     * usually be used to query the database, sort and filter the data, and generally
     * get it ready to be displayed. At a minimum, we should set $this->items and
     * $this->set_pagination_args(), although the following properties and methods
     * are frequently interacted with here...
     * 
     * @global WPDB $wpdb
     * @uses $this->_column_headers
     * @uses $this->items
     * @uses $this->get_columns()
     * @uses $this->get_sortable_columns()
     * @uses $this->get_pagenum()
     * @uses $this->set_pagination_args()
     */
    function prepare_items() {

        /**
         * First, lets decide how many records per page to show
         */
        $per_page = 10;


        /**
         * REQUIRED. Now we need to define our column headers. This includes a complete
         * array of columns to be displayed (slugs & names), a list of columns
         * to keep hidden, and a list of columns that are sortable. Each of these
         * can be defined in another method (as we've done here) before being
         * used to build the value for our _column_headers property.
         */
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();


        /**
         * REQUIRED. Finally, we build an array to be used by the class for column 
         * headers. The $this->_column_headers property takes an array which contains
         * 3 other arrays. One for all columns, one for hidden columns, and one
         * for sortable columns.
         */
        $this->_column_headers = array($columns, $hidden, $sortable);


        /**
         * Optional. You can handle your bulk actions however you see fit. In this
         * case, we'll handle them within our package just to keep things clean.
         */
        $this->process_bulk_action();


        /**
         * Retrieves the data from our $filearray.
         */
        $data = $this->get_data();

        /**
         * This checks for sorting input and sorts the data in our array accordingly.
         */
        function usort_reorder($a, $b) {
            /* If no sort, default to file_time_stamp */
            $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'file_time_stamp';

            /* If no order, default to asc */
            $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc';

            /* Determine sort order */
            $result = strcmp($a[$orderby], $b[$orderby]);

            /* Send final sort direction to usort */
            return ($order === 'asc') ? $result : -$result;
        }

        usort($data, 'usort_reorder');


        /**
         * REQUIRED for pagination. Let's figure out what page the user is currently 
         * looking at. We'll need this later, so you should always include it in 
         * your own package classes.
         */
        $current_page = $this->get_pagenum();

        /**
         * REQUIRED for pagination. Let's check how many items are in our data array. 
         * In real-world use, this would be the total number of items in your database, 
         * without filtering. We'll need this later, so you should always include it 
         * in your own package classes.
         */
        $total_items = count($data);


        /**
         * The WP_List_Table class does not handle pagination for us, so we need
         * to ensure that the data is trimmed to only the current page. We can use
         * array_slice() to 
         */
        $data = array_slice($data, (($current_page - 1) * $per_page), $per_page);



        /**
         * REQUIRED. Now we can add our *sorted* data to the items property, where 
         * it can be used by the rest of the class.
         */
        $this->items = $data;


        /**
         * REQUIRED. We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args(array(
            'total_items' => $total_items, //WE have to calculate the total number of items
            'per_page' => $per_page, //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items / $per_page)   //WE have to calculate the total number of pages
        ));
    }

}

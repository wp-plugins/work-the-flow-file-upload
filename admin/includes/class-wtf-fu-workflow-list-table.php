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



/*
 * Workflow list class.
 * Displays list of workflows with links for editing individual items.
 * 
 * Actions include delete and clone.
 * 
 */

class Wtf_Fu_Workflow_List_Table extends WP_List_Table {

    function get_workflows_data() {
        $data = array();

        // retrieve all workflow keys with options (keys_only= false)
        $workflows = Wtf_Fu_Options::get_all_workflows(false);

        foreach ($workflows as $option_key => $workflow) {
            $users = Wtf_Fu_Options::get_workflow_users($workflow['key_id']);
            $user_details = ''; //$first = true;
            foreach ($users as $user) {
                //if (!$first) {$user_details .= ", ";} else {$first = false;}
                $user_details .= sprintf("%s [%s]&nbsp; ", $user['user']->display_name, $user['workflow_settings']['stage']);
            }
            $options = $workflow['options'];
            // sanity check
            if ($options['id'] != $workflow['key_id']) {
                log_me("WARNING! mismatching id keys found for workflow key_id = {$workflow['key_id']} id = {$options['id']}");
            }
            $data[] = array(
                'id' => $options['id'],
                'name' => $options['name'],
                'description' => wtf_fu_get_value($options, 'description'),
                'notes' => wtf_fu_get_value($options, 'notes'),
                //'number_of_users' => count($users),
                'user_details' => $user_details
            );
        }
        return $data;
    }

    /**
     * REQUIRED. Set up a constructor that references the parent constructor. We 
     * use the parent reference to set some default configs.
     */
    function __construct() {
        global $status, $page;
        
        //Set parent defaults
        parent::__construct(array(
            'singular' => 'workflow', //singular name of the listed records
            'plural' => 'workflows', //plural name of the listed records
            'ajax' => false        //does this table support ajax?
        ));
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
            case 'id':
            case 'name':
            case 'description':
            case 'notes' :
           // case 'number_of_users' :
            case 'user_details' :
                return $item[$column_name];
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
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
    function column_name($item) {

        $edit_link = sprintf('<a href="?page=%s&tab=%s&wftab=%s&wtf-fu-action=%s&wf_id=%s">%s</a>', $_REQUEST['page'], wtf_fu_PAGE_WORKFLOWS_KEY, wtf_fu_PAGE_WORKFLOW_OPTION_KEY, 'edit', $item['id'], $item['name']
        );

        $actions = array(
            'clone' => sprintf('<a href="?page=%s&tab=%s&wtf-fu-action=%s&wf_id=%s">Clone</a>', $_REQUEST['page'], wtf_fu_PAGE_WORKFLOWS_KEY, 'clone', $item['id']
            ),
            'delete' => sprintf('<a href="?page=%s&tab=%s&wtf-fu-action=%s&wf_id=%s" onClick="return confirm(\'WARNING! You are about to premanently delete this Workflow ? Are you sure about this ?\');">Delete</a>', $_REQUEST['page'], wtf_fu_PAGE_WORKFLOWS_KEY, 'delete', $item['id']
            ),
            // 'export' => sprintf('<a href="?page=%s&tab=%s&wtf-fu-action=%s&wf_id=%s">Export</a>', $_REQUEST['page'], wtf_fu_PAGE_WORKFLOWS_KEY, 'export', $item['id']
            'export' => sprintf('<a href="?wtf-fu-export=%s&id=%s">Export</a>', 'workflow', $item['id']
            )
        );

        //Return the name contents
        return sprintf('%1$s %3$s',
                /* $1%s */ $edit_link,
                /* $2%s */ $item['id'],
                /* $3%s */ $this->row_actions($actions)
        );
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
                /* $2%s */ $item['id']                //The value of the checkbox should be the record's id
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
            'name' => 'Name',
            'id' => 'ID',           
            'description' => 'Description',
            'notes' => 'Notes',
            'user_details' => 'Users [Stage]'
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
            'name' => array('name', false), //true means it's already sorted
            'id' => array('id', false),
            //'number_of_users' => array('number_of_users', false)
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
            'delete' => 'Delete',
            'clone' => 'Clone',
                //   'export' => 'Export'
        );
        return $actions;
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
        global $wpdb; //This is used only if making any database queries

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
        //$this->process_bulk_action();


        /**
         * Fetch the workflow list data
         */
        $data = $this->get_workflows_data();

        /**
         * This checks for sorting input and sorts the data in our array accordingly.
         */
        function usort_reorder($a, $b) {
            $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'name'; //If no sort, default to name
            $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; //If no order, default to asc
            $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
            return ($order === 'asc') ? $result : -$result; //Send final sort direction to usort
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

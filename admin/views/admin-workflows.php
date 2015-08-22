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
 * Handle any bulk actions first then redirect to remove the actions from the url.
 */

require_once plugin_dir_path(__FILE__)
        . '../../admin/includes/class-wtf-fu-workflow-list-table.php';

$add_section = "";
$add_section .= "<button name='add_new_workflow' id='wtf_fu_operation_button' value='1'><span>Add</span></button>";

$add_section .= wtf_fu_get_files_list_box('add_workflow_name', dirname( dirname(plugin_dir_path( __FILE__ ))) . '/examples/workflows/*.json', '.json', ''); 
     
if (class_exists('Wtf_fu_Pro')) {
    $import_nonce = wp_nonce_field('wtf_fu_import_nonce', 'wtf_fu_import_nonce', true);
    //$import_submit_button = '<input id="submit" class="button" type="submit" value="Import" name="submit">';// get_submit_button(__('Import'), 'secondary', 'submit', true);
    $import_submit_button = '<button type="submit" value="Import" name="submit"><span>Import</span></button>';// get_submit_button(__('Import'), 'secondary', 'submit', true);
    $import_section = "<form method='post' enctype='multipart/form-data'>{$add_section}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"
    . "{$import_submit_button}<input type='file' name='import_file' label='select file to import.'/><input type='hidden' name='wtf_fu_import_action' value='workflow' />{$import_nonce}</form>";
    $add_section = $import_section;
} 
/*
 * Add new workflow link.
 */

//Create an instance of our package class...
$workflowListTable = new Wtf_Fu_Workflow_List_Table();
//Fetch, prepare, sort, and filter our data...
$workflowListTable->prepare_items();
?>

<style type="text/css">
.wp-list-table .column-name { width: 25%; }
.wp-list-table .column-id { width: 5%; }
.wp-list-table .column-notes { width: 30%; }
.wp-list-table .column-description { width: 20%; }
.wp-list-table .column-user_details { width: 20%; } 
</style>  

<div class="wrap">
    <div id="icon-users" class="icon32"><br/></div>
    <div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
        <p><?php echo $add_section; ?></p>     
    </div> 
    <div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
    <!-- Forms are NOT created automatically, wrap the table in one to use features like bulk actions -->
    <form id="workflows-filter" method="get">
        <!-- For plugins, we also need to ensure that the form posts back to our current page -->
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
        <input type="hidden" name="tab" value="<?php echo $_REQUEST['tab'] ?>" />
        <input type="hidden" name="wtf-fu-action" value="<?php echo wtf_fu_get_value($_REQUEST, 'wtf-fu-action'); ?>" />
        <!-- render the workflows list table -->
        <?php $workflowListTable->display() ?>
    </form>
    </div>
</div>
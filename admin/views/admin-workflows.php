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

require_once plugin_dir_path( __FILE__ )
        . '../../admin/includes/class-wtf-fu-workflow-list-table.php';


/*
 * Add new workflow link.
 */
//$add_new_link = sprintf('<a href="?page=%s&tab=%s&addnew=true"><span>[ %s ]</span></a>', 
//             $_REQUEST['page'], 
//             $_REQUEST['tab'],    
//             'Add New Empty Workflow');
//
//$add_demo_link = sprintf('<a href="?page=%s&tab=%s&adddemo=true"><span>[ %s ]</span></a>', 
//             $_REQUEST['page'], 
//             $_REQUEST['tab'],     
//             'Add New cloned Demo Workflow');
//
//$import_link = sprintf('<a href="?page=%s&tab=%s&import=true"><span>[ %s ]</span></a>', 
//             $_REQUEST['page'], 
//             $_REQUEST['tab'],     
//             'Import saved workflow from disk');

//Create an instance of our package class...
$workflowListTable = new Wtf_Fu_Workflow_List_Table();
//Fetch, prepare, sort, and filter our data...
$workflowListTable->prepare_items();
?>
<div class="wrap">
    <div id="icon-users" class="icon32"><br/></div>
    <h2>Workflows</h2>
    <div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
        <ul>
        <li>Click on a <strong>Workflow Name</strong> to edit the workflow.</li>
        <li>Click the <strong>'clone'</strong> link under a workflow name to create a new duplicate workflow.</li>
        <li>Click the <strong>'delete'</strong> link under a workflow name to permanently remove a workflow.</li>
        <li>Click the <strong>'export'</strong> link under a workflow name to save a local copy of a workflow as a json backup file that can be imported into another site (pro only).</li>
        <li>Delete, clone, or export multiple workflows using the checkboxes and the bulk actions menu.</li>
        <li>Add a new blank workflow or a clone of the demo workflow using the links below.<li/>
        <li>To return to this list from the edit screen subpage's click the <strong>'workflows' tab </strong> above.</li>
        <small><strong>notes: </strong>
            <ol><li>Workflows are always created with an id equal to the first available number starting at 1. <br/> If a workflow is deleted then its number will be reused for the next added workflow. <br/>
                    Any embedded workflow shortcodes that were using this workflow id will then reference the new workflow.</li>
                <li>Importing of any exported json files is not yet implemented.<br/></li>
            </ol>
        </small>    
    </div>
    <p>
    <button name='add_new_empty_workflow' id='wtf_fu_operation_button' value="1"><span>Add New Empty Workflow</span></button>
    <button name='add_new_demo_workflow' id='wtf_fu_operation_button' value="1"><span>Add New Cloned Demo Workflow</span></button>
    </p>
    <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
    <form id="workflows-filter" method="get">
        <!-- For plugins, we also need to ensure that the form posts back to our current page -->
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
        <input type="hidden" name="tab" value="<?php echo $_REQUEST['tab'] ?>" />
        <input type="hidden" name="wtf-fu-action" value="<?php echo wtf_fu_get_value($_REQUEST, 'wtf-fu-action'); ?>" />
        <!-- Now we can render the completed list table -->
        <?php $workflowListTable->display() ?>
    </form>
</div>
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
        . '../../admin/includes/class-wtf-fu-users-list-table.php';


//Create an instance of our package class...
$table = new Wtf_Fu_Users_List_Table();
//Fetch, prepare, sort, and filter our data...
$table->prepare_items();
?>
<div class="wrap">
    <div id="icon-users" class="icon32"><br/></div>
    <h2>Workflows</h2>
    <div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
        <p>These users have currently active Workflows.</p>
        <p>Click a users name to manage that users uploaded files</p>
        <p>Click edit (under the users name) to go to the Wordpress User settings page for that user.</p> 
        <p>To modify a user(s) stage in a workflow, change the users stage number, click the checkbox in the left column and select 'Update' from the bulk actions menu. Then Apply</p>
    </div>
    <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
    <form id="workflows-filter" method="get">
        <!-- For plugins, we also need to ensure that the form posts back to our current page -->
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
        <input type="hidden" name="tab" value="<?php echo $_REQUEST['tab'] ?>" />
        <!-- Now we can render the completed list table -->
        <?php $table->display() ?>
    </form>
</div>
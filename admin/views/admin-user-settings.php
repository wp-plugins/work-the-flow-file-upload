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

require_once plugin_dir_path(__FILE__) . '../includes/wtf-fu-admin-utils.php';
require_once plugin_dir_path(__FILE__) . '../../includes/class-wtf-fu-archiver.php';
require_once plugin_dir_path( __FILE__ )
        . '../../admin/includes/class-wtf-fu-user-files-table.php';

/*
 * Generated a wp_dropdown_users select box and add in some JS to auto 
 * submit the form onChange. All users are included as there may be file uploads 
 * that are NOT from workflow users.
 */
$user_id = wtf_fu_get_value($_REQUEST, 'user');

$drop_down_users = preg_replace(
        '/<select /', 
        '<select onchange="this.form.submit()" ', 
        wp_dropdown_users(array(
            'orderby' => 'display_name',
            'order' => 'ASC',
            'selected' => $user_id,
            'echo' => false)));

/*
 * Get the base directory for all of this users files.
 */
$user_paths = wtf_fu_get_user_upload_paths('', '', $user_id);

$files_root = $user_paths['upload_dir'];
$files_url = $user_paths['upload_url'];

$user_root = $files_root;

$inc_archives = wtf_fu_get_value($_REQUEST, 'include_archive_files');

/*
 * If user has drilled down into a subdir then this will be set.
 */
$subpath = wtf_fu_get_value($_REQUEST, 'subpath');
if (!empty($subpath)) {
    
    $files_root .= $subpath;
    $files_url .= $subpath;
    
    /* 
     * work out the '..' directory 
     */
    $up_subpath = implode('/', array_slice(explode('/', $subpath), 0, -1));
}



/*
 * The users home upload directory link.
 */
$top_link = sprintf('<a href="?page=%s&tab=%s&wtf-fu-action=%s&user=%s"><span>[ %s ]</span></a>', 
             $_REQUEST['page'], 
             wtf_fu_PAGE_USERS_KEY, 
             'user', 
             $user_id,    
             'root');

/*
 * The up one directory link if we are in a subdir.
 */
$up_link = '';
if (isset($up_subpath)) {
    
    $up_link = sprintf('<a href="?page=%s&tab=%s&wtf-fu-action=%s&user=%s%s"><span>[ %s ]</span></a>', 
             $_REQUEST['page'], 
             wtf_fu_PAGE_USERS_KEY, 
             'user', 
             $user_id, 
             empty($up_subpath) ? '' : "&subpath=$up_subpath",    
             '..');
}

/*
 * create the list table.
 */
$table = new Wtf_Fu_User_Files_Table($user_id, $user_root, $files_root, $files_url, $subpath);
$table->prepare_items();
?>

<div class="wrap">
    <div id="icon-users" class="icon32"><br/></div>
    <div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
        <p>User uploaded files. You may download, delete or archive these files here. Click a directory to drill down into sub-directories.</p> 
        <p>Displaying files for user <strong> <?php echo getUserInfo('user_login', $user_id); ?></strong></p>
        <p>Location &nbsp;:&nbsp; <?php echo !empty($subpath) ? $subpath : '/'; ?> </p>
        
    </div>
    <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
    <form id="user-files" method="post">
        <?php 
        
        echo $drop_down_users; 
        echo "&nbsp;&nbsp;&nbsp;$top_link &nbsp;&nbsp;&nbsp;$up_link &nbsp;&nbsp; $files_root";
        
        ?>
        <!-- 
            Add  vars needed \sto that the form will pots back to the current page 
            note that 'user' will be added in $drop_down_users select option so 
            extra hidden form element not required.
        -->
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
        <input type="hidden" name="tab" value="<?php echo $_REQUEST['tab'] ?>" />
        <input type="hidden" name="wtf-fu-action" value="<?php echo $_REQUEST['wtf-fu-action'] ?>" />
        <input type="hidden" name="subpath" value="<?php echo $subpath ?>" />

        <!-- Now we can render the completed list table -->
        <?php $table->display() ?>
    </form>
</div>




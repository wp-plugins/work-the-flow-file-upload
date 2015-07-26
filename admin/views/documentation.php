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

?>

<div class="wrap">
    <div id="icon-users" class="icon32"><br/></div>
    <div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
        <ol><div id="wtf_fu_accord">
            <li><h4><a href='#'>Quick Start Guide</a></h4><div><?php echo wtf_fu_get_general_info('Quick Start Guide');?></div></li>
            <li><h4><a href='#'>Workflows</a></h4><div><?php echo wtf_fu_get_general_info('Workflows');?></div></li>
            <li><h4><a href='#'>File Uploads</a></h4><div><?php echo wtf_fu_get_general_info('File Uploads')?></div></li>
            <li><h4><a href='#'>Displaying Files</a></h4><div><?php echo wtf_fu_get_general_info('showfiles');?></div></li>
            <li><h4><a href='#'>Admin Interface</a></h4><div><?php echo wtf_fu_get_admininterface_info('all')?></div></li>
            <li><h4><a href='#'>PRO Features</a></h4><div><?php echo wtf_fu_get_general_info('pro_details'); ?></div></li>
            <li><h4><a href='#'>Short Codes</a></h4><div><?php echo wtf_fu_get_general_info('Shortcodes'); ?></div></li>
            <li><h4><a href='#'>Shortcuts</a></h4><div><?php echo wtf_fu_get_general_info('Shortcuts'); ?></div></li>
            <li><h4><a href='#'>Templates</a></h4><div><?php echo wtf_fu_get_general_info('Templates'); ?></div></li>           
        </ol>       
    </div>
</div>




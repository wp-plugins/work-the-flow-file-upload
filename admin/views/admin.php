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

if (isset($_GET['tab'])) {
    $active_tab = $_GET['tab'];
} else {
    $active_tab = wtf_fu_PAGE_PLUGIN_KEY;
} 
// log_me(array("active_tab="=>$active_tab));

// the list of tabs
$menu_items = Wtf_Fu_Option_Definitions::get_instance()->get_menu_page_values();
$page_slug = $this->plugin_slug;
?>

<div class="wrap">
    <h2><?php echo esc_html(get_admin_page_title()); ?></h2>                       
        <div id="icon-themes" class="icon32"></div>
        <h2 class="nav-tab-wrapper">
            <?php 
            foreach ($menu_items as $k => $v) {          
                $subclass = $active_tab == $k ? 'nav-tab-active' : '';
                echo "<a href=\"?page={$page_slug}&tab=$k\" class=\"nav-tab {$subclass}\">";
                _e($v['title'], $page_slug);
                echo "</a>";
            }
            ?>
        </h2>
</div>

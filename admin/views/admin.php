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
    <h3><?php echo esc_html(get_admin_page_title());?>&nbsp;&nbsp;<small>[&nbsp;<?php echo wtf_fu_get_version_info(); ?>&nbsp]</small></h3>
<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">    
    <small>To help support the free plugin, please consider rating us 
        <a title="***** Fantastic!" href="http://wordpress.org/support/view/plugin-reviews/work-the-flow-file-upload?rate=5#postform" target="_blank"><span>*****</span></a> on WordPress.org.<br/>
        For support and extended features you can purchase the <a href="http://wtf-fu.com/download/" title="PRO Package" target="_blank">Work the Flow File Upload PRO</a> package from wtf-fu.com.
    </small>
</div>
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
    <?php settings_errors(); ?>
</div>

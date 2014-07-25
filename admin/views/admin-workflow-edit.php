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

/**
 * Workflow edit page.
 */

require_once plugin_dir_path( __FILE__ ) . '../includes/class-wtf-fu-options-admin.php';
require_once plugin_dir_path( __FILE__ ) . '../../includes/class-wtf-fu-option-definitions.php';

$page_slug = $this->plugin_slug;
if (isset($_GET['wf_id'])) {
    $wfid = $_GET['wf_id'];
}

if (isset($_GET['stage_id'])) {
    $stage_id = $_GET['stage_id'];
}

if (isset($_GET['wftab'])) {
    $active_tab = $_GET['wftab'];
    $stages = Wtf_Fu_Options::get_stage_ids_in_order($wfid);
}

// settings_errors();
?>

<div class = "wrap">
<div id = "icon-themes" class = "icon32"></div>    
<h2 class="nav-tab-wrapper">
<?php
    echo sprintf("<a href=\"?page=%s&tab=%s&wftab=%s&wf_id=%s\" class=\"nav-tab %s\">%s</a>" ,
        $page_slug,
        wtf_fu_PAGE_WORKFLOWS_KEY,
        wtf_fu_PAGE_WORKFLOW_OPTION_KEY,
        $wfid,
        $active_tab === wtf_fu_PAGE_WORKFLOW_OPTION_KEY ? 'nav-tab-active' : '',
        "Workflow $wfid Options"
    );           

    $stage = -1;  // -1 so that a new stage of 0 will be used if no stages were found.
    foreach ($stages as $stage) {
        echo sprintf("<a href=\"?page=%s&tab=%s&wftab=%s&wf_id=%s&stage_id=%s\" class=\"nav-tab %s\">%s</a>" ,
            $page_slug,
            wtf_fu_PAGE_WORKFLOWS_KEY,
            wtf_fu_PAGE_WORKFLOW_STAGE_OPTION_KEY,
            $wfid,
            $stage,
            $active_tab === wtf_fu_PAGE_WORKFLOW_STAGE_OPTION_KEY && 
                isset($stage_id) 
                && $stage == $stage_id ? 'nav-tab-active' : '',
            "Stage $stage"
        );           
    }
    
    /*
     * extra tab for + to add a new stage.
     */
    echo sprintf("<a href=\"?page=%s&tab=%s&wftab=%s&wf_id=%s&stage_id=%s\" class=\"nav-tab\" title='Add new Stage [%s]'>%s</a>" ,
        $page_slug,
        wtf_fu_PAGE_WORKFLOWS_KEY,
        wtf_fu_PAGE_WORKFLOW_STAGE_OPTION_KEY,
        $wfid,
        $stage + 1, // add one to the last stage that was found.
        $stage + 1, // add one to the last stage that was found.
        "+"
    );  
    
    /*
     * extra tab for - to delete currently selected stage. 
     * then return to the workflow option tab
     */
    if ( isset( $_REQUEST['stage_id']) ) {
        echo sprintf("<a href=\"?page=%s&tab=%s&wftab=%s&wf_id=%s&delete_stage=%s\" class=\"nav-tab\" title='Delete Current Stage [%s]'"
                . "onClick=\"return confirm('Confirm to DELETE STAGE %s. "
                . "This cannot be undone! Remaining stages will be re-numbered in consecutive order.');\">%s</a>" ,
            $page_slug,
            wtf_fu_PAGE_WORKFLOWS_KEY,
            wtf_fu_PAGE_WORKFLOW_OPTION_KEY,
            $wfid,
            $stage_id,
            $stage_id,
            $stage_id,
            "-"
        );
    }
?> 
</h2>
</div>


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
$pics = array(
    array(
        'title' => 'The PRO template list screen listing all available templates.',
        'image' => 'screenshot_pro_templates_list_page.png'
    ), array(
        'title' => 'The PRO edit screen for email templates.',
        'image' => 'screenshot_pro_templates_email_edit_page.png'
    ), array(
        'title' => 'The PRO edit screen for workflow templates (top).',
        'image' => 'screenshot_pro_templates_workflow_edit_page_top.png'
    ), array(
        'title' => 'The PRO edit screen for workflow templates (bottom).',
        'image' => 'screenshot_pro_templates_workflow_edit_page_bottom.png'
    ), array(
        'title' => 'The PRO edit screen for workflow templates in visual edit mode.',
        'image' => 'screenshot_pro_templates_workflow_edit_page_bottom_visual.png'
    ), array(
        'title' => 'The PRO Workflow options field for setting the workflow template to use.',
        'image' => 'screenshot_pro_workflow_options_page_template_field.png'
    ), array(
        'title' => 'The PRO Workflow Stage options tab for selecting an auto email template to send when the stage is completed.',
        'image' => 'screenshot_pro_workflow_stage_options_email_template_field.png'
    )
);

$pics_list = "<ol id='wtf_admin_templates'>";

$asset_loc =  plugins_url('../assets/screenshots/', __FILE__) ;

foreach ($pics as $pic) {
    $pics_list .= sprintf(
    "<li>%s<a title='%s' href='%s' target ='_blank'><img type='image/png' src='%s'></a></li>",
    $pic['title'],
    $pic['image'],
    $asset_loc . $pic['image'],
    $asset_loc . $pic['image']
    );
}
$pics_list .= '</ol>';


?>
<div class="wrap">
    <div id="icon-users" class="icon32"><br/></div>
    <h2>Templates</h2>
    <div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
        <p>Templates are a feature provided with the <a href="http://wtf-fu.com/download/" title="Get the PRO Package now" target="_blank">Work the Flow File Upload PRO</a> extension.</p>
        <p>Templates allow you the ability to create your own layouts for emails and workflow pages</p>
        <p>You can also copy and edit the default templates to easily create your own branded email and workflow page templates.</p>
        <p>With the PRO package is installed you can attach email templates to Workflow stages from a dropdown listbox. <br/>
            This will then automatically generate and send an email whenever a user passes through the stage that the email is attached to.<br/>
            Similarly you can attach Workflow page templates to a Workflow on the Workflow option tab, in this way you can create different layouts for different workflows.</p>
        <p>Templates also support embedding of <strong>shortcut fields</strong> and the <code>[wtf_fu_show_files]</code> shortcode. See the documentation tab for more details on available template shortcuts.</p>
        <p>You can see some screenshots of the PRO version of the relevant page tabs below.</p>       
    </div>
    <?php echo $pics_list; ?>

</div>
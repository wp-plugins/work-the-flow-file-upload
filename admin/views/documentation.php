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
        <p>Documentation</p>
        <ol>
            <li>Shortcodes</li>
            <p>Below are the shortcodes with their full list of default attributes. You may specify any attribute for which you want to override the default values.</p>
            <ol style='list-style-type: lower-roman'>
                <li><strong>[wtf_fu]</strong>
                    <p>This is the workflow shortcode that embeds a workflow into a page or a post. These cannot be nested inside other workflow stages.<br/>
                    The only required attribute is the <code>'id'</code> attribute which specifies which workflow to embed.</p>
                    <p>To use a workflow just include the following in your page or post :</p>
                    <p><code><?php echo wtf_fu_get_shortcode_with_default_attributes('wtf_fu');?></code> where 1 here represents the workflow id number.</p>
                    <p>Prior to version 1.3.0 other attributes were available to return miscellaneous workflow information, such as the current username or workflow name.</p>
                    <p>These are now deprecated in favour of the new template shortcut <code>%%XXX%%</code> fields. These can now be directly embedded in your stage page options. (see Templates section below)</p>
                </li>
                <li><strong>[wtf_fu_upload]</strong>
                    <p>This is the upload shortcode that causes the file upload interface to be embedded. It may be embedded either in a page or post, or inside a workflow stage.<br/>
                        Default attributes as set on the File Upload tab will be used unless overriden in a short-code instance. </p>
                    <p>A shortcode example with the full list of factory default attributes is below :</p>
                    <p><code><?php echo wtf_fu_get_shortcode_with_default_attributes('wtf_fu_upload'); ?></code></p>
                    <p>These upload default values can also be overridden globally for all uploads in the File Upload options page. Taking into account the current global settings on your File Upload options page
                        , the short code representing the current default behaviour would be :</p> 
                    <p><code><?php echo wtf_fu_get_shortcode_with_default_attributes('wtf_fu_upload', false); ?></code><br/> So this is currently how a shortcode with no attributes specified will behave by default. i.e. if a bare <code>[wtf_fu_upload]</code> is embedded in a page.</p>  
                    <p>The attributes are detailed in the table below.</p>
                    <p><?php echo get_shortcode_info_table('wtf_fu_upload'); ?></p>
                </li>
                <li><strong>[wtf_fu_show_files]</strong>
                    <p><code><?php echo wtf_fu_get_shortcode_with_default_attributes('wtf_fu_show_files'); ?></code></p>
                    <p><?php echo get_shortcode_info_table('wtf_fu_show_files'); ?></p>
                </li>                 
            </ol>
            <li><strong>Templates (PRO) Feature only </strong>         
            <p>With the PRO extension installed templates for workflow pages and automated emails are available under the templates tab. </p>
            Once installed and activated you will see an additional workflow option for setting the workflow template and an
                additional workflow stage option for attaching automatic email templates to stages.</p>
            
            <p>Templates can include the following field shortcuts to allow embedding of workflow and user details into the templates.<br/> These shortcuts can also be used inside of the workflow stage content fields.</p>
            <p><?php echo wtf_fu_get_template_fields_table(); ?></p>   
            <p>Email Templates may also contain the <code><strong>[wtf_fu_show_files email_format='1']</strong></code> shortcode if desired to include a show_files display inside an email.<br/>
            The email_format option is used here to cause the html output to inline the css for use inside an email.</p>
            <p>The default email template is below, this can be edited or cloned as desired for your own emails in the <code>Templates tab</code> You can add your own html to the message as well as 
                modify the TO: CC: BCC: and FROM: fields.</p>
            <p>After that you will be able to select the template to be emailed from a drop down list in the workflow stage options and the email will be automatically sent once the stage is completed by a user.</p>
            <p><blockquote><?php echo wtf_fu_DEFAULT_EMAIL_TEMPLATE?></blockquote></p>
            </li>
        </ol>
        
    </div>
</div>




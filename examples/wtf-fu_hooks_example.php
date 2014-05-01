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
 * This is an example file to illustrate how to perform user defined functions
 * triggered by the Wtf_Fu plugin workflow stages pre-hook and post-hook fields.
 * 
 * Either copy this file to your mu-plugins directory or paste required functions into
 * your themes functions.php file.
 * 
 * Then add the function name to the pre-hook or post-hook option in the workflow
 * stage settings. 
 * 
 * Notes :
 * 
 * 1. The work-the-flow-file-upload PRO extension provides an admin email interface 
 * so that email templates can be edited and added to your workflow stages.
 * 
 * The templates in the PRO package allow embedding of %%xxx%% type shortcuts and
 * shortcodes to set email to from cc bcc and message fields and to include workflow 
 * and user details and file displays in emails. PRO users dont need to hand code hooked 
 * email functions here.
 * 
 * 2. If the hook user code causes an error then the workflow will not 
 * be able to complete and progress on to the next stage. 
 * 
 * 3. As output is buffered during hook processing, you may not see any error
 * messages if there is a problem with your code, so it is best to make and test 
 * small changes at a time.
 * 
 * 4. Over time the code base may change and require updating of the internal 
 * function calls in this code. If your hook code breaks with a neww release, 
 * check the latest version in the example directory and update your code as 
 * necessary.
 * 
 * 5. Displaying html and css in email content can be tricky to get right, 
 * the show_files shortcode used attempts to inline the css to display the images 
 * your milage may however vary with depending on the users email client.
 */

/**
 * This example will create an archive of the users files
 * and send Emails to the User and to the Administrator with links to the 
 * File Archive.
 * 
 * Simply add the function name 'wtf_fu_sendActivationMail' without quotes or parenthesis 
 * to the pre- or post hook workflow stage option to have this function fired
 * when a user enters or leaves the workflow stage.
 * 
 * Note that for production sites archiving may take some time during which 
 * the hook will block and cause delays in the user progressing to the next
 * stage. Archiving is presented here as an example but may not be practical
 * where users are expected to be uploading large numbers of files. 
 * 
 * Because archiving can be resource intensive it is left commented out below.
 */
function wtf_fu_sendActivationMail() {
    
    // Site administrators details. Admin user has user id = 1.
    $wp_admin = new Wp_User(1);

    $admin_name = $wp_admin->display_name;
    $admin_email = get_option('admin_email');

    // Administrator site url.
    $admin_url = site_url('wp-admin');

    // The currently logged in user
    $wp_user = wp_get_current_user();

    $user = $wp_user->display_name;
    $user_email = $wp_user->user_email;
    
    /*
     * Caution this may be resource intensive.
     * Uncomment to add auto archiving user files. 
     */
    //include_once (plugin_dir_path( __FILE__ ) . '../plugins/work-the-flow-file-upload/includes/wtf-fu-common-utils.php');
    //$zipname = 'auto_' . wtf_fu_create_archive_name($user_id, '', '.zip', false);
    //$zip_url = wtf_fu_do_archive_user_files($wp_user->ID, $zipname);  
        
    // link to the wtf-fu admin user options tab.
    $user_options_page_url = site_url('wp-admin/admin.php?page=work-the-flow-file-upload&tab=user-options');

    // link to this web site.
    $site_url = site_url();
    
    // Manually call the show_files short code directly to display thumbnails of 
    // users uploaded image files.
    $shortcode_instance = Wtf_Fu_Show_Files_Shortcode::get_instance(); 
    $shortcode_instance->set_options(array(
        'wtf_upload_dir' => "demofiles",
        'file_type' => "image",
        'email_format' => true
    ));   
    $files_content = $shortcode_instance->generate_content();    

    $subject = "{$user}'s files have just been submitted and are now ready to be processed.";
    
    $message = "Hi $admin_name, <br /><br /> "
            . "{$user} just submitted the following files : <br /><br />"
            . '<br style="clear:all;"/>'
            . $files_content
            . '<br style="clear:all;"/>'
            . "An email has been sent to $user_email and also cc'd to you<br /><br />"
            . "You can view or re-archive or download {$user} 's files "
            . "<a href={$user_options_page_url}>on this page</a>.<br />"
            . "<small>[note: you will need to be logged in as an administrator]</small><br /><br />"
       //     . "You can also directly download the automatically archived zip file "
       //     . "<a href={$zip_url}>" . basename($zipname) . "</a>.<br /><br />"                  
            . "Alternatively just login as administrator to "
            . "<a href=\"{$admin_url}\">the back end</a>.<br/><br/><br/><br/>"
            . "<small>This is an automated response from {$site_url}.</small><br/><hr/>";

    $headers = array();
    $headers[] = "Content-type: text/html" . PHP_EOL;

    wp_mail($admin_email, $subject, $message, $headers);

    /*
     *  User Email
     */
    $subject = "Your files have been submitted for processing.";
        
    $message = "Hi {$wp_user->display_name},<br/><br/>
        <p>This is a demonstration email generated by the example wtf-fu hook function wtf_fu_sendActivationMail.</p>
        <br/><br/>
        The site administrator has been notified and also sent a copy of this email.<br/>
        regards<br/>
        <br/>
            <br style='clear:all;'/>                  
        <p>The following files we submitted :</p>
               <p> $files_content </p>
            <br style='clear:all;'/>
            <p></p>
            <hr/>
        {$wp_admin->display_name}<br/>
        <small>$admin_email</small><br/>
        <small>This has been an automated email response from {$site_url}.</small> ";

    $bcc = "BCC: {$admin_email}" . PHP_EOL;         // bcc to admin 

    $headers = array(); // reset headers array.
    $headers[] = "Content-type: text/html" . PHP_EOL;   // allow html in email.
    $headers[] = $bcc;
    
    wp_mail($user_email, $subject, $message, $headers);
}

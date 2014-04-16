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
 * 
 * Note that for production sites archiving may take some time during which 
 * the hook will block and cause delays in the user progressing to the next
 * stage. Archiving is presented here as an example but may not be practical
 * where users are expected to be uploading large numbers of files. 
 */
function wtf_fu_sendActivationMail() {

    // Site administrators details. Admin user has user id = 1.
    $wp_admin = new Wp_User(1);

    $admin_name = $wp_admin->user_firstname;
    $admin_email = get_option('admin_email');

    // Administrator site url.
    $admin_url = site_url('wp-admin');

    // The currently logged in user
    $wp_user = wp_get_current_user();

    $user = $wp_user->display_name;
    $user_email = $wp_user->user_email;

    /*
     *  Creates an archive of the users files.
     * 
     *  Note : This can be time consuming.
     * 
     *  Front end will block waiting for this to complete.
     *  Archives can be manually created as needed from the Wtf-Fu admin pages.
     * 
     *  Uncomment the code block below to add auto arhive of the users uploaded files.
     */

//        // Include some existing wtf-fu utility methods for creating archives and getting users paths.
//        
//        include_once (plugin_dir_path( __FILE__ ) . '../plugins/work-the-flow-file-upload/includes/class-wtf-fu-archiver.php'); 
//        include_once (plugin_dir_path( __FILE__ ) . '../plugins/work-the-flow-file-upload/includes/wtf-fu-common-utils.php');
//               
//        // append auto to the generated file name.
//        $zipname = 'auto_' . wtf_fu_create_archive_name($id, '', '.zip', false);
//        $zip = new Wtf_Fu_Archiver();
//
//        // Gets paths to the user upload area.
//        $paths = wtf_fu_get_user_upload_paths();
//          
//        // create and open the archive (false means to exclude files that are archives)
//        $zip->create_archive($paths['basedir'] . '/' . $zipname, false); 
//        
//        // Add the users upload directory and files.
//        $zip->add_file_or_dir($paths['basedir']);
//        
//        // close the archive.
//        $zip->close();
//            
//        // link to the new archive to include in the email.
//        $zip_url = $paths['baseurl'] . '/' . $zipname;
//        
    // link to the wtf-fu admin user options tab.
    $user_options_page_url = site_url('wp-admin/admin.php?page=work-the-flow-file-upload&tab=user-options');

    // link to this web site.
    $site_url = site_url();

    $subject = "User {$user}'s files have just been submitted and are now ready to be processed.";
    $message = "Hi $admin_name, <br /><br /> "
            . "{$user} wants you to do some work now ;-).<br /><br />"
            . "An email has been sent to $user_email and also cc'd to you<br /><br />"
            . "You can view or re-archive or download {$user} 's files "
            . "<a href={$user_options_page_url}>on this page</a>.<br />"
            . "<small>[note: you will need to be logged in as an administrator]</small><br /><br />"
//                . "You can also directly download the automatically archived zip file "
//                . "<a href={$zip_url}>" . basename($zipname) . "</a>.<br /><br />"
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
        
    // Call the show_files short code directly to generate thumbnails of 
    // users uploaded files.
    $shortcode_instance = new Wtf_Fu_Show_Files_Shortcode(array(
        'wtf_upload_dir' => "demofiles",
        'file_type' => "image",
        'gallery' => false,
        'reorder' => false,
        'inline_css' => true
    ));    
    
    $content = $shortcode_instance->generate_content();
    
    $message = "Hi {$wp_user->display_name},<br/><br/>
        <p>This is a demonstration email generated by the example wtf-fu hook function wtf_fu_sendActivationMail.</p>
        <p>Your workflow files have now been submitted to the website and the site administrator has been notified.</p>
                $content
        <br/><br/>
        regards<br/>
        <br/>

        {$wp_admin->display_name}<br/>
        <small>$admin_email</small><br/>
        <small>This has been an automated email response from {$site_url}.</small> ";

    $bcc = "BCC: {$admin_email}" . PHP_EOL;         // bcc to admin 

    $headers = array(); // reset headers array.
    $headers[] = "Content-type: text/html" . PHP_EOL;   // allow html in email.
    $headers[] = $bcc;
    
    wp_mail($user_email, $subject, $message, $headers);

    // Make sure NOT to exit when finished, so the workflow will continue.
    // exit;
}

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
        
        /*
         * Use existing wtf-fu methods to help out.
         * 
         * These methods may change in future releases, so where possible
         * use more generic WordPress methods directly.
         * 
         * That said this works fine for now, and this example will be updated soon
         * in a future release to use only native WordPress methods.
         */
        require_once (plugin_dir_path( __FILE__ ) . '../plugins/wtf-fu/includes/wtf-fu-common-utils.php');
        require_once (plugin_dir_path( __FILE__ ) . '../plugins/wtf-fu/includes/class-wtf-fu-archiver.php');        

        // admin name
        $admin_name = getUserInfo('user_firstname', 1);
        
        // Admin Email.
        $admin_email = get_option('admin_email');
        
        // Administrator site url.
        $admin_url = site_url('wp-admin');
        
        
        // current user details
        $user = getUserInfo('display_name');
        $id = getUserInfo(); 
        $user_email = getUserInfo('user_email');

        // Create an archive name from the userid.
        // append auto so we know this is the one created when they submitted the form,
        $zipname = 'auto_' . wtf_fu_create_archive_name($id, '', '.zip', false);
        $zip = new Wtf_Fu_Archiver();

        // Get paths to the user upload area.
        $paths = wtf_fu_get_user_upload_paths();
          
        // create and open the archive (false means to exclude files that are archives)
        $zip->create_archive($paths['basedir'] . '/' . $zipname, false); 
        
        // Add the users directory.
        $zip->add_file_or_dir($paths['basedir']);
        
        // close the archive.
        $zip->close();
            
        // link to the new archive to include in the email.
        $zip_url = $paths['baseurl'] . '/' . $zipname;
        
        // get user names and assemble
        $fullUserName = getUserNameDetails($id, false);

        $url_settings = site_url('wp-admin/admin.php?page=wtf-fu&tab=user-options');

        $subject = "User {$fullUserName}'s files have just been submitted and are now ready to be processed.";
        $message = "Hi $admin_name, <br /><br /> "
                . "It looks like {$fullUserName} wants you to do some work now ;-).<br /><br />"
                . "An email has been sent to $user_email and also cc'd to you<br /><br />"
                . "You can view or re-archive or download {$user} 's files "
                . "<a href={$url_settings}>on this page</a>.<br />"
                . "<small>[note: you will need to be logged in as an administrator]</small><br /><br />"
                . "You can also directly download the automatically archived zip file "
                . "<a href={$zip_url}>" . basename($zipname) . "</a>.<br /><br />"
                . "Alternatively just login as administrator to "
                . "<a href=\"{$admin_url}\">the back end</a>.<br/><br/><br/><br/>"
                . "Hope business is booming, <br/>"
                . "cheers from cyberspace,<br />"
                . "<br />"
                . "<small>This is an automated response.</small><br/><hr/>";

        
        $headers = array();
        $headers[] = "Content-type: text/html" . PHP_EOL;    
        
        if (wp_mail($admin_email, $subject, $message, $headers)) {
            error_log("sendActivationMail to $admin_email success");
        } else {
            error_log("sendActivationMail to $admin_email failure");
        }

        /*
         *  User Email
         */
        $subject = "Your files have been submitted for processing.";
        // EOM below must not have any spaces following EOM !
        $message = <<<EOM
		
Hi $fullUserName,<br />
<br />
Your files have successfully been submitted to the website.<br />
<br /><br />
You will recieve another email once work is complete with details on delivery. <br /><br />
<br />
<br /><br />
If you did not mean to submit your files yet then don't worry, just let us know by email 
asap before we begin work on your project.<br /><br />
If you have any other questions or concerns please just reply to this email or fill out a contact form <br />
on the website.<br />
<br /><br /><br />
regards<br />
<br />
The Owner<br />
The Site<br />
$admin_email<br />
<small>This is an automated email response.</small>                
		
EOM;
// EOM; IMPORTANT the line above must not have any spaces following the ';' !
        
        // reset headers
        $headers = array();
        $headers[] = "Content-type: text/html" . PHP_EOL;   // allow html in email.
        $headers[] = "CC: $admin_email" . PHP_EOL;          // cc to admin         

        if (wp_mail($user_email, $subject, $message, $headers)) {
            error_log("sendActivationMail to $user_email success");
        } else {
            error_log("sendActivationMail to $user_email failure");
        }
    }

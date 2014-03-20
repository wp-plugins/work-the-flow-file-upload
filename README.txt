=== Work The Flow File Upload ===
Contributors: lynton_reed
Donate link: http://wtf-fu.com/
Tags: file upload, upload, workflow, ajax, jquery, html5, image, gallery 
Requires at least: 3.5.1
Tested up to: 3.8.1
Stable tag: 1.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Work the Flow - File Upload. 

This plugin may be used to facilitate simple workflow and html5 front end upload on a wordpress site.

By embedding shortcodes into your pages you can provide users with file upload capability and / or workflow steps, configurable 
from an admin interface by an administrator.

Files are uploaded to a configurable subdirectory location under the wordpress user upload directory.
You may also specify allowed file types, number of upload files allowed and other parameters.

Uploaded files can be managed via the backend allowing for archiving as zip files and deletion.

Workflows allow simple stepwise processing of each stage. Users can be allowed to move forward or backward through 
the workflow as desired. File upload short codes can also be embedded inside workflow stages to provide 
upload capabiliy to a given workflow stage.
 

Workflow hooks to user defined functions may also be added to allow post and pre processing as desired as a user
moves from one stage to the next. For example to archive files or send and email once a user
reaches a certain point in the workflow.

File uploads are html5 based allowing multiple concurrent file uploads to be processed. 
Simply drag and drop a collection of files to your page to start an upload.

A demo of this plugin can be found at 
http://wtf-fu.com/demo

You will need to register (free) to view the demo.

A pro version is also available from the download page. http://wtf-fu.com/download

This plugin utilizes the blueimp / jQuery-File-Upload javascript libraries from github 
https://github.com/blueimp/jQuery-File-Upload of which a demo can be seen at 
http://blueimp.github.io/jQuery-File-Upload/

Please be aware that using the file upload features of this plugin will enable front end users to upload files to your website.
Care should be taken to restrict access to pages containing the [wtf_fu_upload] shortcode to authorized users only.

== Installation ==

Use the normal wordpress installer or extract the archive into the plugins directory into a folder named work-the-flow-file-upload.


= Using The WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'work-the-flow-file-upload'
3. Click 'Install Now'
4. Activate the work-the-flow-file-upload plugin on the Plugin dashboard

= Uploading in WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `work-the-flow-file-upload.zip` from your computer
4. Click 'Install Now'
5. Activate the work-the-flow-file-upload plugin in the Plugin dashboard

= Using FTP =

1. Download `work-the-flow-file-upload.zip`
2. Extract the `work-the-flow-file-upload` directory to your computer
3. Upload the `work-the-flow-file-upload` directory to the `/wp-content/plugins/` directory
4. Activate the work-the-flow-file-upload plugin in the Plugin dashboard


== Frequently Asked Questions ==

= How can I restrict access to the file upload feature to authorized users only ? =

Because this plugin can be used to allow users to upload files to your website, 
it is important that you restrict access only to users that you trust.

The File Upload settings includes the option 'deny_public_uploads' which is 
turned on by default. 

If you decide to allow public access then this permits uploads by NON logged in 
users! If you allow this then you are leaving your site open to POTENTIALLY 
MALICIOUS ABUSE OR SPAM FROM UNAUTHORIZED USERS.

Additionally you can further restrict access by use of 3rd party membership 
plugins (e.g. S2Member - free), or by wrapping the wtf_fu_upload plugin shortcode 
in php code that checks that the capabilities of the user are sufficient to 
access the code.

For this you will need a plugin such as 
<a href="http://www.websharks-inc.com/product/ezphp/">EZPhp</a> (free)
that allows you to embed php code in your pages.

A simple php example that restricts access based on user capability :

[php] if(current_user_can('upload_files')){ [/php]
[wtf_fu_upload wtf_upload_dir='files']
[php] } else { [/php]
<p>You do not have sufficient privileges to upload files.</p>
[php] } [/php]


= How do I include my workflow or file upload in a page ? =

The plugin works using wordpress shortcodes.
There are 3 main ones
[wtf_fu] ,  [wtf_fu_upload], and [wtf_fu_show_files]

Use [wtf_fu]  for workflows
e.g. [wtf_fu id="2"]  to embed workflow 2 in your page.

Use [wtf_fu_upload] for file uploads
e.g. 
[wtf_fu_upload 
accept_file_types="jpg|jpeg|gif|png" 
max_file_size="30" 
max_number_of_files="30" 
wtf_upload_dir="main_dir" 
wtf_upload_subdir="images"]

Use [wtf_fu_show_files] to display already upload files
e.g.
[wtf_fu_show_files 
file_type="image" 
wtf_upload_dir="main_dir" 
wtf_upload_subdir="images" 
reorder="true" 
gallery="true"]


note that the reorder attribute can be used to allow users to change the order of their 
uploaded files. The order will then be included in a text file in the users base upload 
directory.

the gallery attribute will display images in a lightbox slideshow when an image
is clicked.

= What other short codes can I use inside the workflows ? =

Other adhoc workflow details may be retrieved using the type='get' attribute.
These may be also embedded inside the workflow stage content.
e.g.
Welcome [wtf_fu type="get" value="display_name"], // display the current users name,

e.g.
[wtf_fu type="get" value="workflow" id="1" key="name"] // display the title name for workflow 1.


= Do I need to create a workflow just to enable file upload features ? =

No, the workflow and file upload capabilities are completely separate entities. 
You can add file uploads to a page by embedding the [wtf_fu_wtf_fu_upload] 
shortcode directly to a page. 
There is no need to use a workflow at all if this is all you need.

However it is often useful to embed this short code inside a workflow stage so that
uploading is then part of a defined workflow.

= Will the css styles used by the plugin conflict with my themes css ? =

By default the plugin workflow template includes a namespaced copy of the twitter 
bootstrap css version 3.0.3. So it should be safe from clashes with other plugin or theme css files,
even if they also use the bootstrap css.

To modify the plugins namespaced bootstrap styles without affecting other bootstrap css, 
just be sure to include the class selector 'tbs' when overriding the bootstrap 
styles in your themes css.

e.g. to change the workflow header background to blue use :

.tbs .panel-heading {background-color: #428bca;}

in your child theme style.css file overrides.

In the PRO version you can also edit your workflow templates to use whatever 
html and css classes that you require.

= How do I restrict the number of files that can be uploaded ? =

Uploads are configurable via short code attributes 

Use the 'max_number_of_files' attribute to limit the number of files users may upload.
[wtf_fu_upload max_number_of_files="10"]

See the work-the-flow-file-upload 'File Upload' settings page for the full list 
of configurable upload options. On this page you may also set default values 
that will be used by default if they are not specified in the embedded 
short code attributes.

Here is an example specifying the full list of attributes set with their factory set 
default values. 

[wtf_fu_upload 
deny_public_uploads="1"     // Do NOT allow public uploads i.e. user must be logged in.
wtf_upload_dir="wtf-fu_files" 
wtf_upload_subdir="default" 
accept_file_types="jpg|jpeg|mpg|mp3|png|gif|wav|ogg" 
inline_file_types="jpg|jpeg|mpg|mp3|png|gif|wav|ogg" 
image_file_types="gif|jpg|jpeg|png" 
max_file_size="5" 
max_number_of_files="30"    // max size for individual files in Mb
auto_orient="1"             // auto orient images that include orientation info.
create_medium_images="0"    // set to 1 to create additional medium sized images.
medium_width="800" 
medium_height="600" 
thumbnail_crop="1"          // crop to exact thumbnail dimensions.
thumbnail_width="80" 
thumbnail_height="80"]

You may override as many or as few of these as you require.

= Can a user change the order of their uploaded files ? =

File uploads are asynchronously delivered so the order in which they arrive cannot
be determined.

However this is catered for with a plugin shortcode that will allow users to reorder 
their already uploaded files.

Add a shortcode to your workflow stage or page like the following :

[wtf_fu_show_files 
file_type="image" 
wtf_upload_dir="mypackagename" 
wtf_upload_subdir="images" 
reorder="true"]

Users can then drag and drop thumbnail images of their files into their desired 
ordering. When they are finished and submit the form, the files will be timestamped
with times one second apart in the order requested and a text file will be produced
in the users base upload directory with the file ordering specified.


= Can I configure the plugin to use different upload directories per shortcode instance ? =

Yes. Use the 'wtf_upload_dir' and  'wtf_upload_subdir' attributes
(see previous faq)

For security reasons file uploads are *always* directed to the users wordpress 
upload directory. You may use these two attributes to define a base upload sub 
directory and an optional secondary subdir using relative paths like 
'workflow_A_files' and 'some/arbitrary/subpath/here' respectively.

= How can I provide hook functions to send an email at a point in a workflow ? =

Each workflow stage provides the option of a pre-hook and post-hook function that
will be called before a user enters a stage or after a user leaves a stage.

Simply create your user defined function in your function.php file (or into a 
file in your mu-plugins directory) and then put the function name 
(without the parenthesis) into the workflow stage pre-hook or post-hook option.

An example hook function that archives a users files and sends emails to the site 
admin and to the user when the hook is fired is included in the examples directory.
see :
.../wp-content/plugins/work-the-flow-file-upload/examples/wtf-fu_hooks_example.php

Copy and use this as a starting point for your own pre- and post-hook 
functions.

= What support is offered for this product ? =

There is no support for the free LITE version. 

That said, bug reports are welcomed and will be promptly addressed where there is 
something that is broken with the free version.

For support please upgrade to the paid pro version at http://wtf-fu.com/ , 
which allows template editing as well as support and automatic updates.


= Where can I get more information about how to use this plugin ? =

Go to http://wtf-fu.com which is the web site where this product is officially maintained.

Install the demo workflow from the admin workflows tab, which provides a demo workflow detailing 
how to use the plugin shortcodes.

= Can you build me a customized version of this plugin ? =

If you have special requirements for a custom built plugin or even just want a 
website configured for you using this one then please contact me at 
lynton@wtf-fu.com detailing your requirements.


== Screenshots ==

1.  Client File Upload screen shot A. 
2.  Client File Upload screen shot B. 
3.  Client File Upload screen shot C.
4.  Client File Upload screen shot D.
5.  Admin Workflow Stage Settings Screen shot A.  
6.  Admin Workflow Stage Settings Screen shot B. 
7.  Admin Workflow Stage Settings Screen shot C. 
8.  Admin Manage Users Screen Shot. 
9.  Admin Workflow Options Screen Shot A.
10. Admin Workflow Options Screen Shot B.
11. Admin Workflows List Screen Shot.
12. Admin Plugin System Options Screen Shot.
13. Admin File Upload Default Options Screen Shot A.
14. Admin File Upload Default Options Screen Shot B.

== Changelog ==

= 1.1.1 =
* Minor update related to release tagging.

= 1.1.0 =
* Initial work-the-flow-file-upload release to WordPress.org repository.
* Modified directory names to suit generated repository directory.
* Minor updates to README to reflect new directory location.
* Minor updates to the workflow demo.

= 0.1.6 =
* Removed automatic updater, this is now handled by the wordpress.org updater.
* Added File Upload option to restrict access to logged in users only.
* Added additional warnings about public access to the File Upload settings page.
* Updated FAQ with info about the new 'deny_public_uploads' option.

= 0.1.5 =
* Added plugin option to include powered by link to wtf-fu.com (off by default)
* Updated FAQ to explain how to restrict public access for uploads.

= 0.1.4 =
* Changed workflow controller hook mechanism to be more efficient.
* Removed redundant global plugin testing option (use the workflow testing option instead).
* Updated demo workflow.

= 0.1.3 =
* Added extra help into the admin screens.
* Added single clone and delete functionalities to workflows list screen.
* Changes to simplify the default workflow page template.
* Some other minor changes to some default option values.

= 0.1.2 =
* Added Automatic updates.

= 0.1.1 =
* Initial wtf-fu release.
 
== Upgrade Notice ==
= 1.1.1 =
* Upgrade to fix release tagging issue with 1.1.0.

= 1.1.0 =
* Upgrade to initial WordPress.org release.

= 0.1.6 =
* Upgrade to include an 'allow_public_uploads' option on the File Upload settings page.

= 0.1.5 =
* Upgrade to include an option to add a powered by wtf-fu link to workflows.

= 0.1.4 =
* Upgrade to receive performance improvements in workflow shortcode processing.

= 0.1.3 =
* Upgrade to receive extra documentation on the admin screens
* and ability to clone or delete single workflows (previously only bulk actions could be used for this).

= 0.1.2 =
* Upgrade to receive automatic updates in the WordPress plugins dashboard.

= 0.1.1 =
* This is the initial release, so upgrade information is not applicable for this release.

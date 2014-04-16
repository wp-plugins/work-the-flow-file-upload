=== Work The Flow File Upload ===
Contributors: lynton_reed
Donate link: http://wtf-fu.com/
Tags: file upload, upload, workflow, html5, image, gallery
Requires at least: 3.5.1
Tested up to: 3.8.2
Stable tag: 1.1.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Embed Html5 User File Uploads and Workflows into pages and posts. Multiple file Drag and Drop upload, Image Gallery display, Reordering and Archiving

== Description ==

*Work the Flow File Upload.* 
Embed Html5 User File Uploads and Workflows into pages and posts. Multiple file Drag and Drop upload, Image Gallery display, Reordering and Archiving.

This two in one plugin provides shortcodes to embed front end user file upload capability and / or step by step workflow.

Three separate short-codes are made available to page and post content.

Use :
`[wtf_fu]` to embed a workflow, 
`[wtf_fu_upload]` to embed a file upload form.
and 
`[wtf_fu_show_files]` to embed a display of uploaded files with gallery and file re-ordering options.

Note that the [wtf_fu_upload] and [wtf_fu_show_files] shortcodes may also be embedded inside workflow stage contents.
This allows for separate upload instances in different workflow stages, 
for example to upload 10 image only files in stage 3 and 2 music only files in stage 5.

Please read the FAQ for more on the available attributes for these shortcodes.

Workflow configurations, File Upload capabilities and user files can be managed from the admin interface by an administrator.

Default configurations can be overridden in embedded short code attributes so, for example, you can define varying locations, file types, file sizes, max number of files, thumbnail size, and other parameters for uploads.

Files are uploaded to a configurable subdirectory location under the wordpress user upload directory.

Uploaded files from each user can be managed via the backend allowing for archiving as zip files and deletion.

Workflows allow simple stepwise processing of each stage. Users can be allowed to move forward or backward through 
the workflow as desired. File upload short codes can also be embedded inside workflow stages to provide 
upload capabiliy inside a given workflow stage.
 
Workflow pre and post functions may also be added to allow post and pre processing as desired as a user moves from one stage to the next. For example to archive files or send and email once a user
reaches a certain point in the workflow.

File uploads are html5 based allowing multiple concurrent file uploads to be processed. 
Simply drag and drop a collection of files to your page to start an upload.

A demo of this plugin can be found at 
http://wtf-fu.com/demo

You will need to register (free) to view the demo.

A pro version is also available from the download page. http://wtf-fu.com/download

By default only registered site users are allowed to access the file upload capabilities.
You can allow public access by un-checking the 'deny_public_uploads' on the File Upload options page. 
(please see the FAQ entry on restricting upload access).

This plugin includes the jQuery-File-Upload libraries from github 
https://github.com/blueimp/jQuery-File-Upload 


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

`[php] if(current_user_can('upload_files')){ [/php]
[wtf_fu_upload wtf_upload_dir='files']
[php] } else { [/php]
<p>You do not have sufficient privileges to upload files.</p>
[php] } [/php]`


= How do I include my workflow or file upload in a page or post ? =

Embed one or more of `[wtf_fu]`, `[wtf_fu_upload]` and `[wtf_fu_show_files]` short-codes into your pages or posts.

Use `[wtf_fu]`  for workflows
e.g. `[wtf_fu id="2"]`  to embed the workflow with id 2 into a page or post.

Use `[wtf_fu_upload]` for file uploads
e.g. 
`[wtf_fu_upload accept_file_types="jpg|jpeg|gif|png" max_file_size="30" max_number_of_files="30" wtf_upload_dir="main_dir" wtf_upload_subdir="images"]`

Use `[wtf_fu_show_files]` to display already upload files
e.g.
`[wtf_fu_show_files file_type="image" wtf_upload_dir="main_dir" wtf_upload_subdir="images" reorder="true" gallery="true"]`

The `reorder=true` attribute here allows users to change the order of their uploaded image files by dragging and dropping thumbnail images. 
When submitted the final order will then be written to in a text file along with the other files in the users upload directory.

The `gallery` attribute will display the full sized images in a lightbox slideshow when an image file thumbnail is clicked.

= What other short codes can I use inside the workflows ? =

Other workflow details can be retrieved using the `type='get' value='value_name'` attribute these are useful for 
embedding the workflow name or stage title inside your workflow stages content.

e.g.
Welcome `[wtf_fu type="get" value="display_name"], // display the current users name`,

`[wtf_fu type="get" value="workflow" id="1" key="name"] // display the title name for workflow 1.`

= Do I need to create a workflow just to enable file upload features ? =

No, the workflow and file upload capabilities are completely separate entities. 
You can add file uploads to a page by embedding the `[wtf_fu_wtf_fu_upload]` shortcode directly to a page. 
There is no need to use a workflow at all if standalone file uploads are all you need.

However it is often useful to embed this short code inside a workflow stage so that uploading is then part of a defined workflow.

= Will the css styles used by the plugin conflict with my themes css ? =

By default the plugin workflow template includes a namespaced copy of the twitter 
bootstrap css version 3.0.3. So it should be safe from clashes with other plugin or theme css files,
even if they also use the bootstrap css.

If you want to remove this css completely then uncheck the `include_plugin_style` option on 
the `system options` page. 

You can also remove default overrides for the css by unchecking the `include_plugin_style_default_overrides`
checkbox in individual workflow settings.

To manually modify the plugins namespaced bootstrap styles without affecting other bootstrap css, include class selector `'tbs'` when overriding the bootstrap 
styles in your themes css.

e.g. to change the workflow header background to blue use :

`.tbs .panel-heading {background-color: #428bca;}` in your child theme style.css file overrides.

You may also wish to consider the paid PRO extension for this plugin which provides additional options for template editing.
http://wtf-fu.com/download

= How do I restrict the number of files that can be uploaded ? =

Uploads are configurable via short code attributes 

Use the `max_number_of_files` attribute to limit the number of files users may upload.
`[wtf_fu_upload max_number_of_files="10"]`

See the work-the-flow-file-upload 'File Upload' settings page for the full list 
of configurable upload options. On this page you may also set default values 
that will be used by default if they are not specified in the embedded 
short code attributes.

Here is an example specifying the full list of attributes set with their factory set 
default values. 

`[wtf_fu_upload 
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
thumbnail_height="80"]`

You may override as many or as few of these as you require.

= Can a user change the order of their uploaded files ? =

File uploads are handled asynchronously via ajax, so the initial order in which they arrive cannot be determined.

However you can use the `[wtf_fu_show_files reorder="true"]` short-code to give users a list of their uploaded image thumbnails that 
they can drag into their desired order. When the reorder is submitted file modified timestamps are adjusted to reflect the new order and a text file detailing the desired order
is added to the users upload directory.

e.g. `[wtf_fu_show_files file_type="image" wtf_upload_dir="mypackagename" wtf_upload_subdir="images" reorder="true"]`

= Can I configure the plugin to use different upload directories per shortcode instance ? =

Yes. Use the `wtf_upload_dir` and  `wtf_upload_subdir` attributes
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
Please see the plugin file `examples/wtf-fu_hooks_example.php`.

You may copy and use this as a starting point for your own pre- and post-hook functions.

= What support is offered for this product ? =

Normal WordPress plugin support is available. 

Bug reports and feedback are very welcome and will be promptly addressed.

For VIP support and access to the PRO support forums please upgrade to the paid pro extension 
http://wtf-fu.com/ which extends the plugin with template editing capability as well as support and updates.


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
= 1.1.5 =
* Updated Readme file.

= 1.1.4 =
* Simplified and replaced plugin functions in the examples\wtf-fu_hooks_example.php file. 
with core WordPress code. 
* FAQ, README, and description updates.

= 1.1.3 =
* Added checkboxes to include or exclude default styling.
* Fix for incorrect directory locations in the hooks example code.

= 1.1.2 =
* Added default style overrides for workflows.

= 1.1.1 =
* Initial work-the-flow-file-upload release to WordPress.org repository.
* Modified directory names to suit generated repository directory.
* Minor updates to README to reflect new directory location.
* Minor updates to the workflow demo.

== Upgrade Notice ==
= 1.1.5 =
* Readme Updates Only. No functional changes.

= 1.1.4 =
* Updates to the examples\wtf-fu_hooks_example.php file, 
FAQ, README and description updates.

= 1.1.3 =
* Added options include / exclude the default workflow styling css.

= 1.1.2 =
* Added default css styling of workflow pages.

= 1.1.1 =
* Initial WordPress.org release.
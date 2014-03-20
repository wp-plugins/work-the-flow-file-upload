jQuery(function () {
    'use strict';
    
    // Initialize the jQuery File Upload widget:
    jQuery('#fileupload').fileupload({
        // Uncomment the following to send cross-domain cookies:
        //xhrFields: {withCredentials: true},
        url: WtfFileUploadVars.url,
    });

    // Enable iframe cross-domain access via redirect option:
    jQuery('#fileupload').fileupload(
        'option',
        'redirect',
        WtfFileUploadVars.absoluteurl
    );

    // Load existing files:
    jQuery('#fileupload').addClass('fileupload-processing');
    
    jQuery.ajax({
       url: jQuery('#fileupload').fileupload('option', 'url'),
       data: WtfFileUploadVars.data,
       dataType: 'json',
       context: jQuery('#fileupload')[0]
    }).always(function () {
        jQuery(this).removeClass('fileupload-processing');
    }).done(function (result) {
        jQuery(this).fileupload('option', 'done')
            .call(this, jQuery.Event('done'), {result: result});
    });     
});



function wtf_file_upload_init($) {
    
    
    if ($("#fileupload").length === 0) {
        return; // nothing to do.
    } 

    console.log('wtf_init activation.');
       
    // Capture form data fields to pass on to ajax request as POST vars.
    var WtfFuUploadFormData = $("#fileupload").serializeArray();
    
    // add in the nonce to the request data.
    // WtfFuUploadFormData.push({name : "security", value : WtfFuAjaxVars.security}); 
    
    // console.log(WtfFuUploadFormData);
    // Initialize the jQuery File Upload widget:
    $('#fileupload').fileupload({
        // Uncomment the following to send cross-domain cookies:
        //xhrFields: {withCredentials: true},
        url: WtfFuAjaxVars.url
    });

    // Enable iframe cross-domain access via redirect option:
    $('#fileupload').fileupload(
        'option',
        'redirect',
        WtfFuAjaxVars.absoluteurl
    );

    // Load spinners.
    $('#fileupload').addClass('fileupload-processing');
 
    $.ajax({
       url: WtfFuAjaxVars.url, 
       data: WtfFuUploadFormData, 
       dataType: 'json',
       context: $('#fileupload')[0]
    }).always(function () {
        $(this).removeClass('fileupload-processing');
    }).done(function (result) {
        $(this).fileupload('option', 'done')
            .call(this, $.Event('done'), {result: result});
    });    
    
} //end wtf_init


(function ($) {
    'use strict';

// call at load time.
wtf_file_upload_init($);

})(jQuery);



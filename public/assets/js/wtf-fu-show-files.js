// Global function.
function wtf_show_files_init() {

    if ($("#reorder_sortable").length === 0) {
        return; // nothing to do.
    }

    console.log('wtf_show_files_init activation.');
    init_sortable();

    $('#wtf_show_files_form').on('submit', function(event) {
    //$(document).on('submit', '#wtf_show_files_form', function(event) {
        
        //var myForm = $('#wtf_show_files_form');
        
        
        console.log('showfiles js submit called');
        
        //console.log(myForm);
        
        // Capture form data fields.
        var WtfFuShowFilesFormData = $('#wtf_show_files_form').serializeArray();
        
        console.log(WtfFuShowFilesFormData);        
        
        // disable submit buttons while processing.
        //$("#wtf_show_files_form:input[type='submit']").attr("disabled", true);
        //$("#wtf_show_files_form > input[type="hidden"]:nth-child(1)
        $('#reorder_submit_button').attr("disabled", true);
        $('#reorder_message').html('Saving changes ..');

        var files = $("#reorder_sortable").sortable("toArray", {attribute: "title"});

        var data = {
//            action: this.action.value,
//            fn: this.fn.value,
            //wtf_upload_dir: this.wtf_upload_dir.value,
            //wtf_upload_subdir: this.wtf_upload_subdir.value,
            files: files
        };

        // Append the form vars to the data 
        $.each(WtfFuShowFilesFormData, function(key, input) {
            data[input.name] = input.value;
        });
        
        $('#wtf_fu_show_files_output').addClass('reorder-processing');

        $.ajax({
            url: showfiles_js_vars.url,
            data: data,
            type: "POST",
            dataType: 'xml',
        }).always(function() {
            $('#wtf_fu_show_files_output').removeClass('reorder-processing');
            //$('#reorder_submit_button').attr("disabled", false);
        }).success(function(data, code, xhr) {
            var res = wpAjax.parseAjaxResponse(data, 'response');
            $.each(res.responses, function() {
                switch (this.what) {
                    case "stuff" :
                        $('#wtf_fu_show_files_response').html(this.data);
                        $('#reorder_message').html('Order Updated');
                    break;
                }
            });
            init_sortable();// reinitialize the new reorder_sortable div.
        });

        event.preventDefault();
    });

}

function init_sortable() {
    // Add the sortable function to div.
    $(function() {
        $('#reorder_sortable').sortable({
            opacity: 0.4,
            cursor: 'move',
            scrollSensitivity: 40,
            tolerance: 'pointer',
            containment: '#sort_container',
            update: function() {
                $('#reorder_submit_button').attr("disabled", false);
                $('#reorder_message').html('Click save to apply your changes.');
            }
        });
        $('#reorder_sortable').disableSelection();
    });
}

(function($) {
    'use strict';

// call at load time.
    wtf_show_files_init();

})(jQuery);

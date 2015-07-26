// Global function.
function wtf_show_files_init($) {

    if ($("#reorder_sortable").length === 0) {
        return; // nothing to do.
    }

    // console.log('wtf_show_files_init activation.');
    init_sortable($);

    $('#wtf_show_files_form').on('submit', function(event) {
       
        // Capture form data fields.
        var WtfFuShowFilesFormData = $('#wtf_show_files_form').serializeArray();
                
        $('#reorder_submit_button').attr("disabled", true);
        $('#reorder_message').html('Updating Order .....');

        var files = $("#reorder_sortable").sortable("toArray", {attribute: "title"});

        var data = { files: files };

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
            init_sortable($);// reinitialize the new reorder_sortable div.
        });
        event.preventDefault();
    });

}

function init_sortable($) {
    // Add the sortable function to div.
    $(function() {
        $('#reorder_sortable').sortable({
            opacity: 0.4,
            cursor: 'move',
            scrollSensitivity: 40,
            tolerance: 'pointer',
            containment: 'parent',
            update: function() {
                $('#reorder_submit_button').attr("disabled", false);
                $('#reorder_message').html('Click update to apply your changes.');
            }
        });
        $('#reorder_sortable').disableSelection();
    });
}

(function($) {
    'use strict';

// call at load time.
    wtf_show_files_init($);

})(jQuery);

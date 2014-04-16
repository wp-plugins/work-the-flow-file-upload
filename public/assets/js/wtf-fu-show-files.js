// Global function.
function wtf_show_files_init() {

    if ($("#reorder_sortable").length === 0) {
        return; // nothing to do.
    }

    console.log('wtf_show_files_init activation.');
    init_sortable();

    $('#wtf_show_files_form').on('submit', function(event) {

        $('#reorder_response').html('Saving changes ..');

        var files = $("#reorder_sortable").sortable("toArray", {attribute: "title"});

        // Capture form data fields to pass on to ajax request as POST vars.
        var WtfFuShowFilesFormData = $("#wtf_show_files_form").serializeArray();

        var data = {
            action: this.action.value,
            fn: this.fn.value,
            //wtf_upload_dir: this.wtf_upload_dir.value,
            //wtf_upload_subdir: this.wtf_upload_subdir.value,
            files: files
        };

        // Append the form vars to the data 
        $.each(WtfFuShowFilesFormData, function(key, input) {
            data[input.name] = input.value;
        });

        $.post(showfiles_js_vars.url, data, function(data, code, xhr) {

            var res = wpAjax.parseAjaxResponse(data, 'response');
            $.each(res.responses, function() {
                switch (this.what) {
                    case "stuff":
                        $('#links').html(this.data);
                        $('#reorder_response').html('<p>File Order Updated</p>');
                        init_sortable();
                        break;
                }
            });
        }, 'xml');

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
                $('#reorder_response').html('<p>File order has been changed. Click save to apply your changes.</p>');
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

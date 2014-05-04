(function($) {
    'use strict';

    $('#workflow_response').unbind("DOMNodeInserted").bind("DOMNodeInserted", function(ev) {
        if (ev.originalEvent.relatedNode && ev.originalEvent.relatedNode.id === 'workflow_response') {
            wtf_file_upload_init();
            wtf_show_files_init();
        }
    });

    //$('#wtf_workflow_form').on('submit', function(event) {

    $(document).on('submit', '#wtf_workflow_form', function(event) {
        
        // The selected button that submitted the form.
        var btn = $(":input[type=submit]:focus");
        
        $(":input[type='submit']").attr("disabled", true);
                      
        var data = {
            action: this.action.value,
            fn: this.fn.value,
            workflow_id: this.workflow_id.value,
            stage: this.stage.value,
            button_name: btn.attr('name'),
            button_value: btn.val()
        };

        // Add processing spinner
        $('#workflow_response').addClass('workflow-processing');

        $.ajax({
            url: workflow_js_vars.url,
            data: data,
            type: "POST",
            dataType: 'xml',
        }).always(function() {
            $('#workflow_response').removeClass('workflow-processing');
            $(":input[type='submit']").attr("disabled", false);
        }).success(function(data, code, xhr) {
            var res = wpAjax.parseAjaxResponse(data, 'response');
            $.each(res.responses, function() {
                switch (this.what) {
                    case "stuff":
                        $('#workflow_response').html(this.data);
                        break;
                }
            });
        });

        event.preventDefault();
    });


})(jQuery);

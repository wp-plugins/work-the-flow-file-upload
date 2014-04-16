(function($) {
    'use strict';
    
    $('#workflow_response').unbind("DOMNodeInserted").bind("DOMNodeInserted", function(ev) {
        if (ev.originalEvent.relatedNode && ev.originalEvent.relatedNode.id === 'workflow_response') {
            wtf_file_upload_init();
            wtf_show_files_init();       
        }       
    }); 

    // begin changes.
    $(document).on('submit', '#wtf_workflow_form', function(event) {
          
        var btn = $( ":input[type=submit]:focus" );
  
        var data = {
            action: this.action.value,
            fn: this.fn.value,
            workflow_id : this.workflow_id.value,
            stage :  this.stage.value,
            button_name : btn.attr('name'),
            button_value : btn.val()           
        };

        $.post(workflow_js_vars.url,
            data, function(data, code, xhr) {                   
                var res = wpAjax.parseAjaxResponse(data, 'response');
                $.each(res.responses, function() {
                    switch (this.what) {
                        case "stuff":
                            $('#workflow_response').html(this.data);
                            break;
                    }
                });
            }, 'xml'
        );
        
        event.preventDefault();
    });

})(jQuery);

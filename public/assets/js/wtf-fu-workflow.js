(function($) {
    'use strict';

// select the target node
    var target = document.querySelector('#workflow_response');

// create an observer instance
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            //console.log(mutation.type);
            var myNodeList = mutation.addedNodes;
            for (var i = 0; i < myNodeList.length; ++i) {
                var item = myNodeList[i];
                if (item.querySelector('#reorder_sortable') !== null) {
                    wtf_show_files_init($);
                }
                if (item.querySelector('#fileupload') !== null) {
                    wtf_file_upload_init($);
                }
                if (item.querySelector('#accordion') !== null) {
                    wtf_accordion_init($);
                }                
                //console.log(item);
            }
        });
    });

// configuration of the observer:
    var config = {attributes: false, childList: true, characterData: false};

// pass in the target node, as well as the observer options
    observer.observe(target, config);

    var clicked_name;
    var clicked_value;

    $(document).on('click' , '#workflow_submit_button',  function(event) {
            clicked_name = $(this).attr('name');
            clicked_value = $(this).val();
            console.log("clicked called");
    });

    $(document).on('submit', '#wtf_workflow_form', function(event) {

        $(":input[type='submit']").attr("disabled", true);

        var data = {
            action: this.action.value,
            fn: this.fn.value,
            workflow_id: this.workflow_id.value,
            stage: this.stage.value,
            button_name: clicked_name,
            button_value: clicked_value
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
        }).done(function() {
           // console.log('done');           
        });

        event.preventDefault();
    });
    
    // Initialize any accordion links.
    wtf_accordion_init($);
    
// later, you can stop observing
// observer.disconnect();
})(jQuery);

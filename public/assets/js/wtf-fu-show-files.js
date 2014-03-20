$(function() {
    'use strict';

    /*
     * add the sorting function to the #sortable id. 
     */
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
    })

    $('#reorder_submit_button').click(function(event) {

        var files = $("#reorder_sortable").sortable("toArray", {attribute: "title"});

        $('#reorder_response').html('Saving changes ..');

        var data = {
            action: show_files_js_vars.action,
            fn: show_files_js_vars.fn,
            wtf_upload_dir: show_files_js_vars.wtf_upload_dir,
            wtf_upload_subdir: show_files_js_vars.wtf_upload_subdir,
            files: files
        }

        //    console.log(data);

        // $.ajax(show_files_js_vars.url, data);


        $.post(show_files_js_vars.url,
                data, function(data, code, xhr) {
                    // var restaurantInfo,
                    console.log(data);                    
                    var res = wpAjax.parseAjaxResponse(data, 'response');
                    console.log(res);

                    $.each(res.responses, function() {
                        switch (this.what) {
                            case "stuff":
                                console.log(this.data);
                                $('#reorder_response').html('<p>' + this.data + '</p>');
                                // restaurantInfo = this.supplemental;
                                //display the menus…
                                break;
                        }
                    })
                }, 'xml'
                );

        event.preventDefault();
    });

});

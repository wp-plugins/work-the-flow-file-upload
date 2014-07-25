(function($) {
    "use strict";

    $(function() {


    $(document).on('click', '#wtf_fu_operation_button', function(event) {
        
        //console.log('this is ', this);

        var data = {
            action: 'wtf_fu_admin_operations',
            operation: this.name,
            value: this.value
        };
        
        if (data.operation == 'add_new_workflow') {
            data['add_workflow_name'] = document.getElementById('add_workflow_name').value;
        }
        
        //console.log(data);

        $.ajax({
            url: ajaxurl,
            data: data,
            type: "POST",
            dataType: 'xml',
        }).always(function() {
           // this.attr("disabled", false);
        }).success(function(data, code, xhr) {
            var res = wpAjax.parseAjaxResponse(data, 'response');
            $.each(res.responses, function() {
                switch (this.what) {
                    case "stuff":
                        alert(this.data);
                        break;
                }
            });
        }).done(function() {
             window.location.reload();
           // console.log('done');           
        });

        event.preventDefault();
    });

    });

}(jQuery));
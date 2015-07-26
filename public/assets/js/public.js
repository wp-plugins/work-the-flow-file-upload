
function wtf_accordion_init($) {
    $("#wtf_fu_accord,#wtf_fu_subaccord1,#wtf_fu_subaccord2,#wtf_fu_subaccord3").accordion({
        collapsible: true,
        heightStyle: "content",
        active: false
    });
}

(function($) {
    'use strict';
    wtf_accordion_init($);
})(jQuery);
(function($) {
    'use strict';
    wtf_accordion_init();
})(jQuery);

function wtf_accordion_init() {
    $("#accordion,#subaccordion1,#subaccordion2,#subaccordion3").accordion({
        collapsible: true,
        heightStyle: "content",
        active: false
    });
}
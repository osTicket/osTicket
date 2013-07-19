/*
   osticket.js
   Copyright (c) osTicket.com
 */

$(document).ready(function(){

    $("input:not(.dp):visible:enabled:first").focus();
    $('table.list tbody tr:odd').addClass('odd');

    //Overlay
    $('#overlay').css({
        opacity : 0.3,
        top     : 0,
        left    : 0
     });

    /* loading ... */
    $("#loading").css({
        top  : ($(window).height() / 3),
        left : ($(window).width() / 2 - 160)
     });

    $("form :input").change(function() {
        var fObj = $(this).closest('form');
        if(!fObj.data('changed')){
            fObj.data('changed', true);
            $('input[type=submit]', fObj).css('color', 'red');
            $(window).bind('beforeunload', function(e) {
                return "Are you sure you want to leave? Any changes or info you've entered will be discarded!";
             });
        }
       });

    $("form :input[type=reset]").click(function() {
        var fObj = $(this).closest('form');
        if(fObj.data('changed')){
            $('input[type=submit]', fObj).removeAttr('style');
            $('label', fObj).removeAttr('style');
            $('label', fObj).removeClass('strike');
            fObj.data('changed', false);
            $(window).unbind('beforeunload');
        }
       });

    $('form').submit(function() {
        $(window).unbind('beforeunload');
        $('#overlay, #loading').show();
        return true;
       });

    jQuery.fn.exists = function() { return this.length>0; };

    var getConfig = (function() {
        var dfd = $.Deferred();
        return function() {
            if (!dfd.isResolved())
                $.ajax({
                    url: "ajax.php/config/client",
                    dataType: 'json',
                    success: function (json_config) {
                        dfd.resolve(json_config);
                    }
                });
            return dfd;
        }
    })();

    /* Multifile uploads */
    var elems = $('.multifile');
    if (elems.exists()) {
        /* Get config settings from the backend */
        getConfig().then(function(c) {
            elems.multifile({
                container:   '.uploads',
                max_uploads: c.max_file_uploads || 1,
                file_types:  c.file_types || ".*",
                max_file_size: c.max_file_size || 0
            });
        });
    }
});

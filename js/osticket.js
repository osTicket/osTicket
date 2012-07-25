/* 
   osticket.js
   Copyright (c) osTicket.com
 */

$(document).ready(function(){

    $("input:not(.dp):visible:enabled:first").focus();
    $('table.list tbody tr:odd').addClass('odd');

    $("form#save :input").change(function() {
        var fObj = $(this).closest('form');
        if(!fObj.data('changed')){
            fObj.data('changed', true);
            $('input[type=submit]', fObj).css('color', 'red');
            $(window).bind('beforeunload', function(e) {
                return 'Are you sure you want to leave? Any changes or info you\'ve entered will be discarded!';
             });
        }
       });

    $("form#save :input[type=reset]").click(function() {
        var fObj = $(this).closest('form');
        if(fObj.data('changed')){
            $('input[type=submit]', fObj).removeAttr('style');
            $('label', fObj).removeAttr('style');
            $('label', fObj).removeClass('strike');
            fObj.data('changed', false);
            $(window).unbind('beforeunload');
        }
       });

    $('form#save').submit(function() {
        $(window).unbind('beforeunload');
        return true;
       });

    /* Get config settings from the backend */
    var $config = null;
    $.ajax({
        url: "ajax.php/config/client",
        dataType: 'json',
        async: false,
        success: function (config) {
            $config = config;
            }
        });
     
    /* Multifile uploads */
     $('.multifile').multifile({
        container:   '.uploads',
        max_uploads: ($config && $config.max_file_uploads)?$config.max_file_uploads:1,
        file_types:  ($config && $config.file_types)?$config.file_types:".*"
       });
});

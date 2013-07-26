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

    //Add CSRF token to the ajax requests.
    // Many thanks to https://docs.djangoproject.com/en/dev/ref/contrib/csrf/ + jared.
    $(document).ajaxSend(function(event, xhr, settings) {

        function sameOrigin(url) {
            // url could be relative or scheme relative or absolute
            var host = document.location.host; // host + port
            var protocol = document.location.protocol;
            var sr_origin = '//' + host;
            var origin = protocol + sr_origin;
            // Allow absolute or scheme relative URLs to same origin
            return (url == origin || url.slice(0, origin.length + 1) == origin + '/') ||
                (url == sr_origin || url.slice(0, sr_origin.length + 1) == sr_origin + '/') ||
                // or any other URL that isn't scheme relative or absolute i.e
                // relative.
                !(/^(\/\/|http:|https:).*/.test(url));
        }

        function safeMethod(method) {
            return (/^(GET|HEAD|OPTIONS|TRACE)$/.test(method));
        }
        if (!safeMethod(settings.type) && sameOrigin(settings.url)) {
            xhr.setRequestHeader("X-CSRFToken", $("meta[name=csrf_token]").attr("content"));
        }

    });

    getConfig = (function() {
        var dfd = $.Deferred();
        return function() {
            if (dfd.state() != 'resolved')
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

showImagesInline = function(urls, thread_id) {
    var selector = (thread_id == undefined)
        ? '.thread-body img[src^=cid]'
        : '.thread-body#thread-id-'+thread_id+' img[src^=cid]';
    $(selector).each(function(i, el) {
        var hash = $(el).attr('src').slice(4),
            info = urls[hash],
            e = $(el);
        if (info && e.attr('src') == 'cid:' + hash) {
            e.attr('src', info.url);
            // Add a hover effect with the filename
            var caption = $('<div class="image-hover">')
                .hover(
                    function() { $(this).find('.caption').slideDown(250); },
                    function() { $(this).find('.caption').slideUp(250); }
                ).append($('<div class="caption">')
                    .append('<span class="filename">'+info.filename+'</span>')
                    .append('<a href="'+info.download_url+'" class="action-button"><i class="icon-download-alt"></i> Download</a>')
                )
            caption.appendTo(e.parent())
            e.appendTo(caption);
        }
    });
}

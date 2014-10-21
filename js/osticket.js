/*
   osticket.js
   Copyright (c) osTicket.com
 */

$(document).ready(function(){

    $('input:not(.dp):visible:enabled:input[value=""]:first').focus();
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
                return __("Are you sure you want to leave? Any changes or info you've entered will be discarded!");
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
        var dfd = $.Deferred(),
            requested = false;
        return function() {
            if (dfd.state() != 'resolved' && !requested)
                requested = $.ajax({
                    url: "ajax.php/config/client",
                    dataType: 'json',
                    success: function (json_config) {
                        dfd.resolve(json_config);
                    }
                });
            return dfd;
        }
    })();

    $.translate_format = function(str) {
        var translation = {
            'd':'dd',
            'j':'d',
            'z':'o',
            'm':'mm',
            'F':'MM',
            'n':'m',
            'Y':'yy'
        };
        // Change PHP formats to datepicker ones
        $.each(translation, function(php, jqdp) {
            str = str.replace(php, jqdp);
        });
        return str;
    };

    var showNonLocalImage = function(div) {
        var $div = $(div),
            $img = $div.append($('<img>')
              .attr('src', $div.data('src'))
              .attr('alt', $div.attr('alt'))
              .attr('title', $div.attr('title'))
              .attr('style', $div.data('style'))
            );
        if ($div.attr('width'))
            $img.width($div.attr('width'));
        if ($div.attr('height'))
            $img.height($div.attr('height'));
    };

    // Optionally show external images
    $('.thread-entry').each(function(i, te) {
        var extra = $(te).find('.textra'),
            imgs = $(te).find('.non-local-image[data-src]');
        if (!extra) return;
        if (!imgs.length) return;
        extra.append($('<a>')
          .addClass("action-button show-images pull-right")
          .css({'font-weight':'normal'})
          .text(' ' + __('Show Images'))
          .click(function(ev) {
            imgs.each(function(i, img) {
              showNonLocalImage(img);
              $(img).removeClass('non-local-image')
                // Remove placeholder sizing
                .css({'display':'inline-block'})
                .width('auto')
                .height('auto')
                .removeAttr('width')
                .removeAttr('height');
              extra.find('.show-images').hide();
            });
          })
          .prepend($('<i>')
            .addClass('icon-picture')
          )
        );
        imgs.each(function(i, img) {
            var $img = $(img);
            // Save a copy of the original styling
            $img.data('style', $img.attr('style'));
            $img.removeAttr('style');
            // If the image has a 'height' attribute, use it, otherwise, use
            // 40px
            $img.height(($img.attr('height') || '40') + 'px');
            // Ensure the image placeholder is visible width-wise
            if (!$img.width())
                $img.width(($img.attr('width') || '80') + 'px');
            // TODO: Add a hover-button to show just one image
        });
    });
});

showImagesInline = function(urls, thread_id) {
    var selector = (thread_id == undefined)
        ? '.thread-body img[data-cid]'
        : '.thread-body#thread-id-'+thread_id+' img[data-cid]';
    $(selector).each(function(i, el) {
        var cid = $(el).data('cid').toLowerCase(),
            info = urls[cid],
            e = $(el);
        if (info && !e.data('wrapped')) {
            // Add a hover effect with the filename
            var timeout, caption = $('<div class="image-hover">')
                .css({'float':e.css('float')});
            e.wrap(caption).parent()
                .hover(
                    function() {
                        var self = this;
                        timeout = setTimeout(
                            function() { $(self).find('.caption').slideDown(250); },
                            500);
                    },
                    function() {
                        clearTimeout(timeout);
                        $(this).find('.caption').slideUp(250);
                    }
                ).append($('<div class="caption">')
                    .append('<span class="filename">'+info.filename+'</span>')
                    .append('<a href="'+info.download_url+'" class="action-button pull-right"><i class="icon-download-alt"></i> ' + __('Download') + '</a>')
                );
            e.data('wrapped', true);
        }
    });
}

$.sysAlert = function (title, msg, cb) {
    var $dialog =  $('.dialog#alert');
    if ($dialog.length) {
        $('#title', $dialog).html(title);
        $('#body', $dialog).html(msg);
        $dialog.show();
    } else {
        msg = msg.replace(/<br\s*\/?>/, "\n").replace(/<\/?\w+[^>]*>/g, '');
        alert(title+':\n'+msg);
    }
};

function __(s) {
  if ($.oststrings && $.oststrings[s])
    return $.oststrings[s];
  return s;
}

$.clientPortal = true;

$(document).on('submit', 'form', function() {
    // Reformat dates
    $('.dp', $(this)).each(function(i, e) {
        var $e = $(e),
            d = $e.datepicker('getDate');
        if (!d) return;
        var day = ('0'+d.getDate()).substr(-2),
            month = ('0'+(d.getMonth()+1)).substr(-2),
            year = d.getFullYear();
        $e.val(year+'-'+month+'-'+day);
    });
});

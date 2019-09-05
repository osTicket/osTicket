/*********************************************************************
    thread.js

    Thread JS untils
    Copyright (c) 2014 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

var thread = {

    options: {
        autoScroll: true,
        showimages: false
    },

    scrollTo: function (entry) {

       if (!entry) return;

       var frame = 0;
       $('html, body').animate({
            scrollTop: entry.offset().top - 50,
       }, {
            duration: 400,
            step: function(now, fx) {
                // Recalc end target every few frames
                if (++frame % 6 == 0)
                    fx.end = entry.offset().top - 50;
            }
        });
    },

    showExternalImage: function(div) {
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
    },

    externalImages: function()  {

        // Optionally show external images
        $('.thread-entry', this.options.container).each(function(i, te) {
            var extra = $(te).find('.textra'),
                imgs = $(te).find('.non-local-image[data-src]');

            if (!extra || !imgs.length)
                return;

            // Add Show Images buttons
            extra.append($('<a>')
              .addClass("white button action-button show-images")
              .css({'font-weight':'normal'})
              .text(' ' + __('Show Images'))
              .click(function(ev) {
                imgs.each(function(i, img) {
                  thread.showExternalImage(img);
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

            // Show placeholders
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
    },

    inlineImages: function (entry_id) {
        // TODO: use entry selector or object instead of ID
        var selector = (entry_id == undefined)
            ? '.thread-body img[data-cid]'
            : '.thread-body#thread-id-'+entry_id+' img[data-cid]';

        // Get urls
        if (!(urls=this.options.container.data('imageUrls')))
            return;

        $(selector, this.options.container).each(function(i, el) {
            var e = $(el),
                cid = e.data('cid').toLowerCase(),
                info = urls[cid];
            if (info && !e.data('wrapped')) {
                // Add a hover effect with the filename
                var timeout, caption = $('<div class="image-hover">')
                    .css({'float':e.css('float')});
                e.wrap(caption).parent()
                    .append($('<div class="caption">')
                        .append($('<a href="'+info.download_url+'" class="dark button pull-right no-pjax"><i class="icon-download-alt"></i></a>')
                          .attr('download', info.filename)
                          .attr('title', __('Download'))
                          .tooltip()
                        )
                    );
                e.data('wrapped', true);
            }
        });
    },

    prepImages: function() {

        // TODO: Check config options
        this.externalImages();
        this.inlineImages();
    },

    onLoad: function (container, options) {

        // See if thread container is valid
        $container = $('#'+container);
        if (!$container || !$container.length)
            return;

        // set options
        this.options.container = $container;
        $.extend(this.options, options);

        // Prep images
        this.prepImages();

        // Auto scroll to the last entry if autoScroll is enabled.
        if (this.options.autoScroll === true) {
            // Find the last entry to scroll to.
            var e = $('.thread-entry', $container).filter(':visible').last();
            if (e.length)
                this.scrollTo(e);
        }

        // Open thread body links in a new tab/window
        // unless referring to thread entry on current page
        $('div.thread-body a', $container).each(function() {
          var str = this.toString();
          if (str.indexOf('#entry-') == -1)
            $(this).attr('target', '_blank');
        });

        // Open first response option tab if not already active
        if (!document.location.hash)
            $('.actions .tabs li:visible:first:not(.active) a', $container.parent()).trigger('click');
    }
};

// Set thread as JQuery object
$.thread = thread;


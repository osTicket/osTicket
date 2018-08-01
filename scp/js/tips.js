jQuery(function() {
    var showtip = function (url, elem,xoffset) {

            // If element is no longer visible
            if (!elem.is(':visible'))
                return;

            var pos = elem.offset();
            var y_pos = pos.top - 12;
            var x_pos = pos.left + (xoffset || (elem.width() + 16));

            var tip_arrow = $('<img>').attr('src', './images/tip_arrow.png').addClass('tip_arrow');
            var tip_box = $('<div>').addClass('tip_box');
            var tip_shadow = $('<div>').addClass('tip_shadow');
            var tip_content = $('<div>').addClass('tip_content').load(url, function() {
                if (elem.is(':visible')) {
                    tip_content.prepend('<a href="#" class="tip_close"><i class="icon-remove-circle"></i></a>').append(tip_arrow);
                    var width = $(window).width(),
                    rtl = $('html').hasClass('rtl'),
                    size = tip_content.outerWidth(),
                    left = the_tip.position().left,
                    left_room = left - size,
                    right_room = width - size - left,
                    flip = rtl
                        ? (left_room > 0 && left_room > right_room)
                        : (right_room < 0 && left_room > right_room);
                    if (flip) {
                        the_tip.css({'left':x_pos-tip_content.outerWidth()-elem.width()-32+'px'});
                        tip_box.addClass('right');
                        tip_arrow.addClass('flip-x');
                    }
                } else {
                    // Self close  if the element is gone
                    $('.tip_box').remove();
                }
            });

            var the_tip = tip_box.append(tip_content).prepend(tip_shadow);
            the_tip.css({
                "top":y_pos + "px",
                "left":x_pos + "px"
            }).addClass(elem.data('id'));

            // Close any open tips
            $('.tip_box').remove();
            // Only show the tip if the element is still visible.
            if (elem.is(':visible')) {
                $('body').append(the_tip.hide().fadeIn());
                $('.' + elem.data('id') + ' .tip_shadow').css({
                    "height":$('.' + elem.data('id')).height() + 5
                });
            }
    },
    getHelpTips = (function() {
        var dfd, cache = {};
        return function(elem) {
            var namespace =
                   $(elem).closest('[data-tip-namespace]').data('tipNamespace')
                || $('#content').data('tipNamespace')
                || $('meta[name=tip-namespace]').attr('content');
            if (!namespace)
                return $.Deferred().resolve().promise();
            else if (!cache[namespace])
                cache[namespace] = {
                  dfd: dfd = $.Deferred(),
                  ajax: $.ajax({
                    url: "ajax.php/help/tips/" + namespace,
                    dataType: 'json',
                    success: $.proxy(function (json_config) {
                        this.resolve(json_config);
                    }, dfd)
                  })
                }
            return cache[namespace].dfd;
        };
    })();

    var tip_id = 1;
    //Generic tip.
    $(document)
    .on('click mouseover', '.tip', function(e) {
        e.preventDefault();
        if (!this.rel)
            this.rel = 'tip-' + (tip_id++);
        var id = this.rel,
            elem = $(this);

        elem.data('id',id);
        elem.data('timer',0);
        if ($('.' + id).length == 0) {
            if (e.type=='mouseover') {
                // wait about 1 sec - before showing the tip - mouseout kills
                // the timeout
                elem.data('timer',setTimeout(function() {
                    showtip('ajax.php/content/'+elem.attr('href').substr(1),elem);
                },750));
            } else {
                showtip('ajax.php/content/'+elem.attr('href').substr(1),elem);
            }
        }
    })
    .on('mouseout', '.tip', function(e) {
        clearTimeout($(this).data('timer'));
    });

    $(document)
    .on('mouseover click', '.help-tip', function(e) {
        e.preventDefault();

        var elem = $(this),
            pos = elem.offset(),
            y_pos = pos.top - 8,
            x_pos = pos.left + elem.width() + 16,
            tip_arrow = $('<img>')
                .attr('src', './images/tip_arrow.png')
                .addClass('tip_arrow'),
            tip_box = $('<div>')
                .addClass('tip_box'),
            tip_content = $('<div>')
                .append('<a href="#" class="tip_close"><i class="icon-remove-circle"></i></a>')
                .addClass('tip_content'),
            the_tip = tip_box
                .append(tip_content.append(tip_arrow))
                .css({
                    "top":y_pos + "px",
                    "left":x_pos + "px"
                }),
            tip_timer = setTimeout(function() {
                $('.tip_box').remove();
                $('body').append(the_tip.hide().fadeIn());
                var width = $(window).width(),
                    rtl = $('html').hasClass('rtl'),
                    size = tip_content.outerWidth(),
                    left = the_tip.position().left,
                    left_room = left - size,
                    right_room = width - size - left,
                    flip = rtl
                        ? (left_room > 0 && left_room > right_room)
                        : (right_room < 0 && left_room > right_room);
                if (flip) {
                    the_tip.css({'left':x_pos-tip_content.outerWidth()-40+'px'});
                    tip_box.addClass('right');
                    tip_arrow.addClass('flip-x');
                }
            }, 500);

        elem.on('mouseout', function() {
            clearTimeout(tip_timer);
        });

        getHelpTips(elem).then(function(tips) {
            var href = elem.attr('href');
            if (href) {
                section = tips[elem.attr('href').substr(1)];
            }
            else if (elem.data('content')) {
                section = {title: elem.data('title'), content: elem.data('content')};
            }
            else {
                elem.remove();
                clearTimeout(tip_timer);
                return;
            }
            if (!section)
                return;
            tip_content.append(
                $('<h1>')
                    .append('<i class="icon-info-sign faded"> ')
                    .append(section.title)
                ).append(section.content);
            if (section.links) {
                var links = $('<div class="links">');
                $.each(section.links, function(i,l) {
                    var icon = l.href.match(/^http/)
                        ? 'icon-external-link' : 'icon-share-alt';
                    links.append($('<div>')
                        .append($('<a>')
                            .html(l.title)
                            .prepend('<i class="'+icon+'"></i> ')
                            .attr('href', l.href).attr('target','_blank'))
                    );
                });
                tip_content.append(links);
            }
        });
        $('.tip_shadow', the_tip).css({
            "height":the_tip.height() + 5
        });
    });

    //faq preview tip
    $(document)
    .on('mouseover', '.previewfaq', function(e) {
        e.preventDefault();
        var elem = $(this);

        var vars = elem.attr('href').split('=');
        var url = 'ajax.php/kb/faq/'+vars[1];
        var id='faq'+vars[1];
        var xoffset = 100;

        elem.data('id',id);
        elem.data('timer',0);
        if($('.' + id).length == 0) {
            if(e.type=='mouseover') {
                 /* wait about 1 sec - before showing the tip - mouseout kills the timeout*/
                 elem.data('timer',setTimeout(function() { showtip(url,elem,xoffset);},750))
            }else{
                showtip(url,elem,xoffset);
            }
        }
    }).on('mouseout', '.previewfaq', function(e) {
        clearTimeout($(this).data('timer'));
    });


    $(document).on('mouseover', 'a.collaborators.preview', function(e) {
        e.preventDefault();
        var elem = $(this);

        var url = 'ajax.php/'+elem.attr('href').substr(1)+'/preview';
        var xoffset = 100;
        elem.data('timer', 0);

        if (e.type=='mouseover') {
            elem.data('timer',setTimeout(function() { showtip(url, elem, xoffset);},750))
        } else {
            showtip(url,elem,xoffset);
        }
    }).on('mouseout', 'a.collaborators.preview', function(e) {
        clearTimeout($(this).data('timer'));
    }).on('click', 'a.collaborators.preview', function(e) {
        clearTimeout($(this).data('timer'));
        $('.tip_box').remove();
    });


    // Tooltip preview
    $(document).on('mouseover', '.preview', function(e) {
        var elem = $(this);
        if (!elem.attr('href'))
            return;
        var vars = elem.attr('href').split('=');
        if (!elem.data('preview'))
            return;
        e.preventDefault();
        var url = 'ajax.php/'+elem.data('preview').substr(1);
        // TODO - hash url to integer and use it as id.
        var id= url.match(/\d/g).join("");
        var xoffset = 80;
        elem.data('timer', 0);
        if(!elem.data('id')) {
            elem.data('id', id);
            if(e.type=='mouseover') {
                 /* wait about 1 sec - before showing the tip - mouseout kills the timeout*/
                 elem.data('timer',setTimeout(function() { showtip(url,elem,xoffset);},750))
            }else{
                clearTimeout(elem.data('timer'));
                showtip(url,elem,xoffset);
            }
        }
    }).on('mouseout', '.preview', function(e) {
        $(this).data('id', 0);
        clearTimeout($(this).data('timer'));
    });

    $('body')
    .delegate('.tip_close', 'click', function(e) {
        e.preventDefault();
        $(this).parent().parent().remove();
    });

    $(document).on('mouseup', function (e) {
        var container = $('.tip_box');
        if (!container.is(e.target)
            && container.has(e.target).length === 0) {
            container.remove();
        }
    });
});

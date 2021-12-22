jQuery(function($) {
    getHelpTips = (function() {
        var dfd = $.Deferred();
        return function() {
            if (dfd.state() != 'resolved')
                $.ajax({
                    url: "ajax.php/help/tips/install",
                    dataType: 'json',
                    success: function (json_config) {
                        dfd.resolve(json_config);
                    }
                });
            return dfd;
        }
    })();

    $('.tip')
    .each(function(i, e) {
        e.rel = 'tip-' + i;
    })
    .on('mouseover click', function(e) {
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
                var rtl = $('html.rtl').length > 0;
                $('.tip_box').remove();
                $('body').append(the_tip.hide().fadeIn());
                if ((rtl && ($(window).width() > tip_content.outerWidth() + the_tip.position().left))
                        || (!rtl && ($(window).width() < tip_content.outerWidth() + the_tip.position().left))) {
                    the_tip.css({'left':x_pos-tip_content.outerWidth()-40+'px'});
                    tip_box.addClass('right');
                    tip_arrow.addClass('flip-x');
                }
            }, 500);

        elem.on('mouseout', function() {
            clearTimeout(tip_timer);
        });

        getHelpTips().then(function(tips) {
            var section = tips[elem.attr('href').substr(1)];
            if (!section) {
                elem.remove();
                clearTimeout(tip_timer);
                return;
            }
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

    $('body')
    .delegate('.tip_close', 'click', function(e) {
        e.preventDefault();
        $(this).parent().parent().remove();
    });
});

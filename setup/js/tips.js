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
    .live('mouseover click', function(e) {
        e.preventDefault();
        var tip_num = this.rel;

        if($('.' + tip_num).length == 0) {

            var elem = $(this),
                pos = elem.offset(),
                y_pos = pos.top - 12,
                x_pos = pos.left + elem.width() + 20,
                tip_arrow = $('<img>')
                    .attr('src', './images/tip_arrow.png')
                    .addClass('tip_arrow'),
                tip_box = $('<div>')
                    .addClass('tip_box'),
                tip_content = $('<div>')
                    .append('<a href="#" class="tip_close">x</a>')
                    .addClass('tip_content'),
                the_tip = tip_box
                    .append(tip_arrow)
                    .append(tip_content)
                    .css({
                        "top":y_pos + "px",
                        "left":x_pos + "px"
                    })
                    .addClass(tip_num),
                tip_timer = setTimeout(function() {
                    $('.tip_box').remove();
                    $('body').append(the_tip.hide().fadeIn());
                }, 500);

            elem.live('mouseout', function() {
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
                    $('<b>').append(section.title))
                    .append(section.content);
            });
            $('.' + tip_num + ' .tip_shadow').css({
                "height":$('.' + tip_num).height() + 5
            });
        }
    });

    $('body')
    .delegate('.tip_close', 'click', function(e) {
        e.preventDefault();
        $(this).parent().parent().remove();
    });
});

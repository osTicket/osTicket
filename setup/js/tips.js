jQuery(function($) {
    var tips = $('.tip');
    for(i=0;i<tips.length;i++) {
        tips[i].rel = 'tip-' + i;
    }

    $('.tip').live('mouseover click', function(e) {
        e.preventDefault();
        var tip_num = this.rel;

        if($('.' + tip_num).length == 0) {

            var elem = $(this);
            var pos = elem.offset();

            var y_pos = pos.top - 12;
            var x_pos = pos.left + elem.width() + 20;

            var tip_arrow = $('<img>').attr('src', './images/tip_arrow.png').addClass('tip_arrow');
            var tip_box = $('<div>').addClass('tip_box');
            var tip_content = $('<div>').addClass('tip_content').load('tips.html '+elem.attr('href'), function() {
                tip_content.prepend('<a href="#" class="tip_close">x</a>');
            });

            var the_tip = tip_box.append(tip_arrow).append(tip_content);
            the_tip.css({
                "top":y_pos + "px",
                "left":x_pos + "px"
            }).addClass(tip_num);

            tip_timer = setTimeout(function() {
                $('.tip_box').remove();
                $('body').append(the_tip.hide().fadeIn());
            }, 500);

            $('.' + tip_num + ' .tip_shadow').css({
                "height":$('.' + tip_num).height() + 5
            });
        }
    }).live('mouseout', function() {
        clearTimeout(tip_timer);
    });
    $('body').delegate('.tip_close', 'click', function(e) {
        e.preventDefault();
        $(this).parent().parent().remove();
    });
});

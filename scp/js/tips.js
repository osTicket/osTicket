jQuery(function($) {
    var tip_timer = 0;
    var tips = $('.tip');
    for(i=0;i<tips.length;i++) {
        tips[i].rel = 'tip-' + i;
    }

    var showtip = function (url, elem,xoffset) {

            var pos = elem.offset();
            var y_pos = pos.top - 12;
            var x_pos = pos.left + (xoffset || ((elem.width()/2) + 20));

            var tip_arrow = $('<img>').attr('src', './images/tip_arrow.png').addClass('tip_arrow');
            var tip_box = $('<div>').addClass('tip_box');
            var tip_shadow = $('<div>').addClass('tip_shadow');
            var tip_content = $('<div>').addClass('tip_content').load(url, function() {
                tip_content.prepend('<a href="#" class="tip_close">x</a>');
            });

            var the_tip = tip_box.append(tip_arrow).append(tip_content).prepend(tip_shadow);
            the_tip.css({
                "top":y_pos + "px",
                "left":x_pos + "px"
            }).addClass(elem.data('id'));
            $('.tip_box').remove();
            $('body').append(the_tip.hide().fadeIn());
            $('.' + elem.data('id') + ' .tip_shadow').css({
                "height":$('.' + elem.data('id')).height() + 5
            });
    };

    //Generic tip.
    $('.tip').live('click mouseover', function(e) {
        e.preventDefault();
        var id = this.rel;
        var elem = $(this);
    
        elem.data('id',id);
        elem.data('timer',0);
        if($('.' + id).length == 0) {
            if(e.type=='mouseover') {
                 /* wait about 1 sec - before showing the tip - mouseout kills the timeout*/
                 elem.data('timer',setTimeout(function() { showtip('ajax.php/content/'+elem.attr('href'),elem);},750))
            }else{
                showtip('ajax.php/content/'+elem.attr('href'),elem);
            }
        }
    }).live('mouseout', function(e) {
        clearTimeout($(this).data('timer'));
    });

    //faq preview tip
    $('.previewfaq').live('mouseover', function(e) {
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
    }).live('mouseout', function(e) {
        clearTimeout($(this).data('timer'));
    });

    //Ticket preview
    $('.ticketPreview').live('mouseover', function(e) {
        e.preventDefault();
        var elem = $(this);

        var vars = elem.attr('href').split('=');
        var url = 'ajax.php/tickets/'+vars[1]+'/preview';
        var id='t'+vars[1];
        var xoffset = 80;
        

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
    }).live('mouseout', function(e) {
        clearTimeout($(this).data('timer'));
    });



    $('body').delegate('.tip_close', 'click', function(e) {
        e.preventDefault();
        $(this).parent().parent().remove();
    });
});

jQuery(function($) {
            
    $("#overlay").css({
        opacity : 0.3,
        top     : 0,
        left    : 0,
        width   : $(window).width(),
        height  : $(window).height()
        });

    $("#loading").css({
        top  : ($(window).height() / 3),
        left : ($(window).width() / 2 - 160)
        });
        
    $('form#install').submit(function(e) {
        $('input[type=submit]', this).attr('disabled', 'disabled');
        $('#overlay, #loading').show();
        return true;
        });
});

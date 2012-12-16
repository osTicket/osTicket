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
        
    $('form#upgrade').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        $('input[type=submit]', this).attr('disabled', 'disabled');
        $('#overlay, #upgrading').show();
        doTasks('upgrade.php',form.serialize());

        return false;
        });

    function doTasks(url, data) {
        function _lp(count) {
            $.ajax({
                type: 'POST',
                url: 'ajax.php/upgrader',
                async: true,
                cache: false,
                data: data,
                dataType: 'text',
                success: function(res) {
                    if (res) { 
                        $('#loading #msg').html(res);
                    }
                },
                statusCode: {
                    200: function() {
                        setTimeout(function() { _lp(count+1); }, 2);
                    },

                    201: function() {
                        $('#loading #msg').html("We're done... cleaning up!");
                        setTimeout(function() { location.href =url+'?c='+count+'&r='+Math.floor((Math.random()*100)+1); }, 3000);
                    }
                },
                error: function() {
                    $('#loading #msg').html("Something went wrong");
                    setTimeout(function() { location.href =url+'?c='+count+'&r='+Math.floor((Math.random()*100)+1); }, 1000);
                }
            });
        };
        _lp(0);
    }
});

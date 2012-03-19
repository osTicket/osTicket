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
        
    $('form#install, form#upgrade, form#attachments').submit(function(e) {
        $('input[type=submit]', this).attr('disabled', 'disabled');
        $('#overlay, #loading').show();
        return true;
        });

    $('form#cleanup').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        $('input[type=submit]', this).attr('disabled', 'disabled');
        $('#overlay, #loading').show();
        doCleanup('upgrade',form.attr('action'));
        return false;
        });


    function doCleanup(type,url) {
        function _lp(count) {
            $.ajax({
                type: 'GET',
                url: 'cleanup.php',
                async: true,
                cache: false,
                data: {c:count,type:type},
                dataType: 'text',
                success: function(res) {
                    if (res) { 
                        $('#loading #msg').html(res);
                    }
                },
                statusCode: {
                    200: function() {
                        setTimeout(function() { _lp(count+1); },2);
                    },

                    304: function() {
                        $('#loading #msg').html("We're done... ");
                        setTimeout(function() { location.href =url;},1000);
                    }
                },
                error: function(){
                    alert("Something went wrong");
                }
            });
        };
        _lp(0);
    }
});

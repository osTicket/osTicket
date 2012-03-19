jQuery(function($) {
    var max_uploads = 5;
    var current_reply_uploads = 0;
    var current_note_uploads = 0;

    function parse_upload(elem) {
        var new_input = elem.clone();
        var filename = elem.val();
        if(filename != '') {
            var container = elem.parent().parent();
            var form_type = container.attr('id');
            elem.blur().hide();
            $('.uploads', container).append('<div><label><input type="checkbox" name="uploads[]" value="' + filename + '" checked="checked"> ' + filename.replace('C:\\', '').replace('fakepath\\', '') + '</label></div>');
            if(form_type=='reply_form_attachments') {
                current_reply_uploads++;
                if(current_reply_uploads < max_uploads) {
                    elem.after(new_input.val('').blur());
                } 
            } else {
                current_note_uploads++;
                if(current_note_uploads < max_uploads) {
                    elem.after(new_input.val('').blur());
                } 
            }
        }
    }

    if($.browser.msie) {
        $('.attachments').delegate('input[type=file]', 'click', function() {
            var elem = $(this);
            setTimeout(function() {
                parse_upload(elem);
                elem.blur();
            }, 0);
        });
    } else {
        $('.attachments').delegate('input[type=file]', 'change', function() {
            var elem = $(this);
            parse_upload(elem);
        });
    }

    $('.uploads').delegate('.uploads input', 'click', function(e) {
        e.preventDefault();
        var elem = $(this);
        elem.attr('checked', 'checked');
        if(confirm("Are you sure you want to delete this attachment?")==true) {
            var container = elem.parent().parent();
            var cparent = container.parent().parent();
            var form_type = cparent.attr('id');
            var filename = elem.val();
            $('input[type=file]', cparent).each(function() {
                if($(this).val() == filename) {
                    $(this).remove();
                }
            });
            container.remove();
            var new_input = $('input[type=file]:last', cparent).clone();
            var last_elem = $('input[type=file]:last', cparent);
            if(form_type=='reply_form_attachments') {
                current_reply_uploads--;
                if(current_reply_uploads < max_uploads) {
                    if(last_elem.css('display')=='none') {
                        last_elem.after(new_input.val('').show());
                    }
                } 
            } else {
                current_note_uploads--;
                if(current_note_uploads < max_uploads) {
                    if(last_elem.css('display')=='none') {
                        last_elem.after(new_input.val('').show());
                    }
                } 
            }
        } else {
            e.preventDefault();
        }
    });    
});
/*
   scp.js

   osTicket SCP
   Copyright (c) osTicket.com

 */

function checkbox_checker(formObj, min, max) {

    var max = max || 0;
    var min = min || 1;
    var checked=$('input:checkbox:checked', formObj).length;
    var action= action?action:"process";
    if (max>0 && checked > max ){
        msg="You're limited to only " + max + " selections.\n"
        msg=msg + "You have made " + checked + " selections.\n"
        msg=msg + "Please remove " + (checked-max) + " selection(s)."
        alert(msg)
        return (false);
    }

    if (checked< min ){
        alert("Please make at least " + min + " selections. " + checked + " checked so far.")
        return (false);
    }

    return (true);
}


$(document).ready(function(){

    $("input:not(.dp):visible:enabled:first").focus();
    $('table.list tbody tr:odd').addClass('odd');
    $('table.list input:checkbox').bind('click, change', function() {
        $(this)
            .parents("tr:first")
            .toggleClass("highlight", this.checked);
     });

    $('table.list input:checkbox:checked').trigger('change');

    $('#selectAll').click(function(e) {
        e.preventDefault();
        var target = $(this).attr('href').substr(1, $(this).attr('href').length);
        $(this).closest('form')
            .find('input:enabled:checkbox.'+target)
            .prop('checked', true)
            .trigger('change');

        return false;
     });


    $('#selectNone').click(function(e) {
        e.preventDefault();
        var target = $(this).attr('href').substr(1, $(this).attr('href').length);
        $(this).closest('form')
            .find('input:enabled:checkbox.'+target)
            .prop('checked', false)
            .trigger('change');
        return false;
     });

    $('#selectToggle').click(function(e) {
        e.preventDefault();
        var target = $(this).attr('href').substr(1, $(this).attr('href').length);
        $(this).closest('form')
            .find('input:enabled:checkbox.'+target)
            .each(function() {
                $(this)
                    .prop('checked', !$(this).is(':checked'))
                    .trigger('change');
             });
        return false;
     });

    $('#actions input:submit.button').bind('click', function(e) {

        var formObj = $(this).closest('form');
        e.preventDefault();
        if($('.dialog#confirm-action p#'+this.name+'-confirm').length == 0) {
            alert('Unknown action '+this.name+' - get technical help.');
        } else if(checkbox_checker(formObj, 1)) {
            var action = this.name;
            $('.dialog#confirm-action').undelegate('.confirm');
            $('.dialog#confirm-action').delegate('input.confirm', 'click.confirm', function(e) {
                e.preventDefault();
                $('.dialog#confirm-action').hide();
                $('#overlay').hide();
                $('input#action', formObj).val(action);
                formObj.submit();
                return false;
             });
            $('#overlay').show();
            $('.dialog#confirm-action .confirm-action').hide();
            $('.dialog#confirm-action p#'+this.name+'-confirm')
            .show()
            .parent('div').show().trigger('click');
        }

        return false;
     });

    if($.browser.msie) {
        $('.inactive').mouseenter(function() {
            var elem = $(this);
            var ie_shadow = $('<div>').addClass('ieshadow').css({
                height:$('ul', elem).height()
            });
            elem.append(ie_shadow);
        }).mouseleave(function() {
            $('.ieshadow').remove();
        });
    }

    var warnOnLeave = function (el) {
        var fObj = el.closest('form');
        if(!fObj.data('changed')){
            fObj.data('changed', true);
            $('input[type=submit]', fObj).css('color', 'red');
            $(window).bind('beforeunload', function(e) {
                return 'Are you sure you want to leave? Any changes or info you\'ve entered will be discarded!';
             });
        }
    };

    $("form#save :input").change(function() {
        warnOnLeave($(this));
    });

    $("form#save :input[type=reset]").click(function() {
        var fObj = $(this).closest('form');
        if(fObj.data('changed')){
            $('input[type=submit]', fObj).removeAttr('style');
            $('label', fObj).removeAttr('style');
            $('label', fObj).removeClass('strike');
            fObj.data('changed', false);
            $(window).unbind('beforeunload');
        }
    });

    $('form#save, form:has(table.list)').submit(function() {
        $(window).unbind('beforeunload');
        $('#overlay, #loading').show();
        return true;
     });

    $('select#tpl_options').change(function() {
        var $this = $(this), form = $this.closest('form');
        if ($this.val() % 1 !== 0) {
            $('[name="a"]', form).val('implement');
            $this.attr('name', 'code_name');
        }
        form.submit();
     });

    $(".clearrule").live('click',function() {
        $(this).closest("tr").find(":input").val('');
        return false;
     });


    //Canned attachments.
    $('.canned_attachments, .faq_attachments').delegate('input:checkbox', 'click', function(e) {
        var elem = $(this);
        if(!$(this).is(':checked') && confirm("Are you sure you want to remove this attachment?")==true) {
            elem.parent().addClass('strike');
        } else {
            elem.attr('checked', 'checked');
            elem.parent().removeClass('strike');
        }
     });

    $('form select#cannedResp').change(function() {

        var fObj=$(this).closest('form');
        var cannedId = $(this).val();
        var ticketId = $(':input[name=id]',fObj).val();

        $(this).find('option:first').attr('selected', 'selected').parent('select');

        $.ajax({
                type: "GET",
                url: 'ajax.php/kb/canned-response/'+cannedId+'.json',
                data: 'tid='+ticketId,
                dataType: 'json',
                cache: false,
                success: function(canned){
                    //Canned response.
                    var box = $('#response',fObj),
                        redactor = box.data('redactor');
                    if(canned.response) {
                        if($('#append',fObj).is(':checked') &&  $('#response',fObj).val()) {
                            if (redactor)
                                redactor.insertHtml(canned.response);
                            else
                                box.val(box.val() + canned.response);
                        }
                        else {
                            if (redactor)
                                redactor.set(canned.response);
                            else
                                box.val(canned.response);
                        }
                        if (redactor)
                            redactor.observeStart();
                    }
                    //Canned attachments.
                    if(canned.files && $('.canned_attachments',fObj).length) {
                        $.each(canned.files,function(i, j) {
                            if(!$('.canned_attachments #f'+j.id,fObj).length) {
                                var file='<span><label><input type="checkbox" name="cannedattachments[]" value="' + j.id+'" id="f'+j.id+'" checked="checked">';
                                    file+= ' '+ j.name + '</label>';
                                    file+= ' (<a href="file.php?h=' + j.key + j.hash + '">view</a>) </span>';
                                $('.canned_attachments', fObj).append(file);
                            }

                         });
                    }
                }
            })
            .done(function() { })
            .fail(function() { });
     });




    /************ global inits *****************/

    //Add CSRF token to the ajax requests.
    // Many thanks to https://docs.djangoproject.com/en/dev/ref/contrib/csrf/ + jared.
    $(document).ajaxSend(function(event, xhr, settings) {

        function sameOrigin(url) {
            // url could be relative or scheme relative or absolute
            var host = document.location.host; // host + port
            var protocol = document.location.protocol;
            var sr_origin = '//' + host;
            var origin = protocol + sr_origin;
            // Allow absolute or scheme relative URLs to same origin
            return (url == origin || url.slice(0, origin.length + 1) == origin + '/') ||
                (url == sr_origin || url.slice(0, sr_origin.length + 1) == sr_origin + '/') ||
                // or any other URL that isn't scheme relative or absolute i.e
                // relative.
                !(/^(\/\/|http:|https:).*/.test(url));
        }

        function safeMethod(method) {
            return (/^(GET|HEAD|OPTIONS|TRACE)$/.test(method));
        }
        if (!safeMethod(settings.type) && sameOrigin(settings.url)) {
            xhr.setRequestHeader("X-CSRFToken", $("meta[name=csrf_token]").attr("content"));
        }

       });

    /* Get config settings from the backend */
    jQuery.fn.exists = function() { return this.length>0; };

    /* Multifile uploads */
    var elems = $('.multifile');
    if (elems.exists()) {
        /* Get config settings from the backend */
        getConfig().then(function(c) {
            elems.multifile({
                container:   '.uploads',
                max_uploads: c.max_file_uploads || 1,
                file_types:  c.file_types || ".*",
                max_file_size: c.max_file_size || 0
            });
        });
    }

    $.translate_format = function(str) {
        var translation = {
            'd':'dd',
            'j':'d',
            'z':'o',
            'm':'mm',
            'F':'MM',
            'n':'m',
            'Y':'yy'
        };
        // Change PHP formats to datepicker ones
        $.each(translation, function(php, jqdp) {
            str = str.replace(php, jqdp);
        });
        return str;
    };

    /* Datepicker */
    getConfig().then(function(c) {
        $('.dp').datepicker({
            numberOfMonths: 2,
            showButtonPanel: true,
            buttonImage: './images/cal.png',
            showOn:'both',
            dateFormat: $.translate_format(c.date_format||'m/d/Y')
        });
        $(document).on('submit', 'form', function() {
            $('.dp', $(this)).each(function(i, e) {
                var $e = $(e),
                    d = $e.datepicker('getDate'),
                    day = ('0'+d.getDate()).substr(-2),
                    month = ('0'+(d.getMonth()+1)).substr(-2),
                    year = d.getFullYear();
                $e.val(year+'-'+month+'-'+day);
            });
        });
    });

    /* Typeahead tickets lookup */
    var last_req;
    $('#basic-ticket-search').typeahead({
        source: function (typeahead, query) {
            if (last_req) last_req.abort();
            last_req = $.ajax({
                url: "ajax.php/tickets/lookup?q="+query,
                dataType: 'json',
                success: function (data) {
                    typeahead.process(data);
                }
            });
        },
        onselect: function (obj) {
            $('#basic-ticket-search').val(obj.value);
            $('#basic-ticket-search').closest('form').submit();
        },
        property: "matches"
    });

    /* Typeahead user lookup */
    $('.email.typeahead').typeahead({
        source: function (typeahead, query) {
            if(query.length > 2) {
                if (last_req) last_req.abort();
                last_req = $.ajax({
                    url: "ajax.php/users?q="+query,
                    dataType: 'json',
                    success: function (data) {
                        typeahead.process(data);
                    }
                });
            }
        },
        onselect: function (obj) {
            var fObj=$('.email.typeahead').closest('form');
            if(obj.name)
                $('.auto.name', fObj).val(obj.name);
        },
        property: "email"
    });
    $('.staff-username.typeahead').typeahead({
        source: function (typeahead, query) {
            if(query.length > 2) {
                if (last_req) last_req.abort();
                last_req = $.ajax({
                    url: "ajax.php/users/staff?q="+query,
                    dataType: 'json',
                    success: function (data) {
                        typeahead.process(data);
                    }
                });
            }
        },
        onselect: function (obj) {
            var fObj=$('.staff-username.typeahead').closest('form');
            $.each(['first','last','email','phone','mobile'], function(i,k) {
                if (obj[k]) $('.auto.'+k, fObj).val(obj[k]);
            });
        },
        property: "username"
    });

    //Overlay
    $('#overlay').css({
        opacity : 0.3,
        top     : 0,
        left    : 0
    });

    //Dialog
    $('.dialog').each(function() {
        var w = $(window), $this=$(this);
        $this.css({
            top : (w.innerHeight() / 7),
            left : (w.width() - $this.outerWidth()) / 2
        });
        $this.hasClass('draggable') && $this.draggable({handle:'h3'});
    });

    $('.dialog').delegate('input.close, a.close', 'click', function(e) {
        e.preventDefault();
        $(this).parents('div.dialog').hide()
        $('#overlay').hide();

        return false;
    });

    $(document).keydown(function(e) {

        if (e.keyCode == 27 && !$('#overlay').is(':hidden')) {
            $('div.dialog').hide();
            $('#overlay').hide();

            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    });

    /* advanced search */
    $('.dialog#advanced-search').css({
        top  : ($(window).height() / 6),
        left : ($(window).width() / 2 - 300)
    });

    /* loading ... */
    $("#loading").css({
        top  : ($(window).height() / 3),
        left : ($(window).width() - $("#loading").outerWidth()) / 2
    });

    $('#go-advanced').click(function(e) {
        e.preventDefault();
        $('#result-count').html('');
        $('#overlay').show();
        $('#advanced-search').show();
    });

    $.dialog = function (url, code, cb, options) {
        options = options||{};

        $('.dialog#popup .body').load(url, function () {
            $('#overlay').show();
            $('.dialog#popup').show({
                duration: 0,
                complete: function() { if (options.onshow) options.onshow(); }
            });
            $(document).off('.dialog');
            $(document).on('submit.dialog', '.dialog#popup form', function(e) {
                e.preventDefault();
                var $form = $(this);
                var $dialog = $form.closest('.dialog');
                $.ajax({
                    type:  $form.attr('method'),
                    url: 'ajax.php/'+$form.attr('action').substr(1),
                    data: $form.serialize(),
                    cache: false,
                    success: function(resp, status, xhr) {
                        if (xhr && xhr.status == code) {
                            $('div.body', $dialog).empty();
                            $dialog.hide();
                            $('#overlay').hide();
                            if(cb) cb(xhr.responseText);
                        } else {
                            $('div.body', $dialog).html(resp);
                            $('#msg_notice, #msg_error', $dialog).delay(5000).slideUp();
                        }
                    }
                })
                .done(function() { })
                .fail(function() { });
                return false;
            });
         });
        if (options.onload) { options.onload(); }
     };

    $.userLookup = function (url, cb) {
        $.dialog(url, 201, function (resp) {
            var user = $.parseJSON(resp);
            if(cb) cb(user);
        }, {
            onshow: function() { $('#user-search').focus(); }
        });
    };

    $('#advanced-search').delegate('#status', 'change', function() {
        switch($(this).val()) {
            case 'closed':
                $('select#assignee').find('option:first').attr('selected', 'selected').parent('select');
                $('select#assignee').attr('disabled','disabled');
                $('select#staffId').removeAttr('disabled');
                break;
            case 'open':
            case 'overdue':
            case 'answered':
                $('select#staffId').find('option:first').attr('selected', 'selected').parent('select');
                $('select#staffId').attr('disabled','disabled');
                $('select#assignee').removeAttr('disabled');
                break;
            default:
                $('select#staffId').removeAttr('disabled');
                $('select#assignee').removeAttr('disabled');
        }
    });

    $('#advanced-search form#search').submit(function(e) {
        e.preventDefault();
        var fObj = $(this);
        var elem = $('#advanced-search');
        $('#result-count').html('');
        $.ajax({
                url: "ajax.php/tickets/search",
                data: fObj.serialize(),
                dataType: 'json',
                beforeSend: function ( xhr ) {
                   $('.buttons', elem).hide();
                   $('.spinner', elem).show();
                   return true;
                },
                success: function (resp) {

                    if(resp.success) {
                        $('#result-count').html('<div class="success">' + resp.success +'</div>');
                    } else if (resp.fail) {
                        $('#result-count').html('<div class="fail">' + resp.fail +'</div>');
                    } else {
                        $('#result-count').html('<div class="fail">Unknown error</div>');
                    }
                }
            })
            .done( function () {
             })
            .fail( function () {
                $('#result-count').html('<div class="fail">Advanced search failed - try again!</div>');
            })
            .always( function () {
                $('.spinner', elem).hide();
                $('.buttons', elem).show();
             });
    });

   // Return a helper with preserved width of cells
   var fixHelper = function(e, ui) {
      ui.children().each(function() {
          $(this).width($(this).width());
      });
      return ui;
   };
   // Sortable tables for dynamic forms objects
   $('.sortable-rows').sortable({
       'helper': fixHelper,
       'stop': function(e, ui) {
           var attr = ui.item.parent('tbody').data('sort');
           warnOnLeave(ui.item);
           $('input[name^='+attr+']', ui.item.parent('tbody')).each(function(i, el) {
               $(el).val(i+1);
           });
       }
   });

   //Tabs
   $(document).on('click.tab', 'ul.tabs li a', function(e) {
        e.preventDefault();
        if ($('.tab_content'+$(this).attr('href')).length) {
            $('ul.tabs li a').removeClass('active');
            $(this).addClass('active');
            $('.tab_content').hide();
            $('.tab_content'+$(this).attr('href')).show();
        }
    });

    //Collaborators
    $(document).on('click', 'a.collaborator, a.collaborators', function(e) {
        e.preventDefault();
        var url = 'ajax.php/'+$(this).attr('href').substr(1);
        $.dialog(url, 201, function (resp) {
           $('input#emailcollab').show();
           $('#recipients').text(resp);
           $('.tip_box').remove();
        }, {
            onshow: function() { $('#user-search').focus(); }
        });
        return false;
     });
});

// NOTE: getConfig should be global
getConfig = (function() {
    var dfd = $.Deferred(),
        requested = null;
    return function() {
        if (dfd.state() != 'resolved' && !requested)
            requested = $.ajax({
                url: "ajax.php/config/scp",
                dataType: 'json',
                success: function (json_config) {
                    dfd.resolve(json_config);
                }
            });
        return dfd;
    }
})();

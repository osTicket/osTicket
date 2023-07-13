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
        msg=__("You're limited to only {0} selections.\n") .replace('{0}', max);
        msg=msg + __("You have made {0} selections.\n").replace('{0}', checked);
        msg=msg + __("Please remove {0} selection(s).").replace('{0}', checked-max);
        $.sysAlert(__('Alert'), msg);

        return (false);
    }

    if (checked< min ){
        $.sysAlert( __('Alert'),
                __("Please make at least {0} selections. {1} checked so far.")
                .replace('{0}', min)
                .replace('{1}', checked)
                );

        return (false);
    }

    return checked;
}


var scp_prep = function() {

    $("input[autofocus]:visible:enabled:first").each(function() {
      if ($(this).val())
        $(this).blur();
    });
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

    $('#actions:submit, #actions :submit.button:not(.no-confirm), #actions .confirm').bind('click', function(e) {

        var formObj,
            name = this.name || $(this).data('name');
        if ($(this).data('formId'))
            formObj = $('#' + $(this).data('formId'));
        else
            formObj = $(this).closest('form');
        if($('.dialog#confirm-action p#'+name+'-confirm').length === 0) {
            alert('Unknown action '+name+' - Get technical help!');
        } else if(checkbox_checker(formObj, 1)) {
            var action = name;
            $('.dialog#confirm-action').undelegate('.confirm');
            $('.dialog#confirm-action').delegate('input.confirm', 'click.confirm', function(e) {
                e.preventDefault();
                $('.dialog#confirm-action').hide();
                $.toggleOverlay(false);
                $('input#action', formObj).val(action);
                formObj.submit();
                return false;
             });
            $.toggleOverlay(true);
            $('.dialog#confirm-action .confirm-action').hide();
            $('.dialog#confirm-action p#'+name+'-confirm')
            .show()
            .parent('div').show().trigger('click');
        }
        e.preventDefault();
        return false;
     });

    $('a.confirm-action').click(function(e) {
        $dialog = $('.dialog#confirm-action');
        if ($($(this).attr('href')+'-confirm', $dialog).length) {
            e.preventDefault();
            var action = $(this).attr('href').substr(1, $(this).attr('href').length);

            $('input#action', $dialog).val(action);
            $.toggleOverlay(true);
            $('.confirm-action', $dialog).hide();
            $('p'+$(this).attr('href')+'-confirm', $dialog)
            .show()
            .parent('div').show().trigger('click');

            return false;
        }
     });

    var warnOnLeave = function (el) {
        var fObj = el.closest('form');
        if(!fObj.data('changed')){
            fObj.data('changed', true);
            $('input[type=submit], button[type=submit]', fObj).addClass('save pending');
            $(window).bind('beforeunload', function(e) {
                return __('Are you sure you want to leave? Any changes or info you\'ve entered will be discarded!');
            });
            $(document).on('pjax:beforeSend.changed', function(e) {
                return confirm(__('Are you sure you want to leave? Any changes or info you\'ve entered will be discarded!'));
            });
        }
    };

    $("form.save").on('change', ':input[name], :button[name]', function() {
        if (!$(this).is('.nowarn')) warnOnLeave($(this));
    });

    $("form.save").on('click', ':input[type=reset], :button[type=reset]', function() {
        var fObj = $(this).closest('form');
        if(fObj.data('changed')){
            $('input[type=submit], button[type=submit]', fObj).removeClass('save pending');
            $('label', fObj).removeAttr('style');
            $('label', fObj).removeClass('strike');
            fObj.data('changed', false);
            $(window).unbind('beforeunload');
        }
    });

    $('form.save, form:has(table.list)').submit(function() {
        $(window).unbind('beforeunload');
        $.toggleOverlay(true);
        // Disable staff-side Post Reply/Open buttons to help prevent
        // duplicate POST
        var form = $(this);
        $(this).find('input[type="submit"]').each(function (index) {
            // Clone original input
            $(this).clone(false).removeAttr('id').prop('disabled', true).insertBefore($(this));

            // Hide original input and add it to top of form
            $(this).hide();
            form.prepend($(this));
        });
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

    $(document).on('click', ".clearrule",function() {
        $(this).closest("tr").find(":input").val('');
        return false;
     });


    //Canned attachments.
    $('.canned_attachments, .faq_attachments').delegate('input:checkbox', 'click', function(e) {
        var elem = $(this);
        if(!$(this).is(':checked') && confirm(__("Are you sure you want to remove this attachment?"))==true) {
            elem.parent().addClass('strike');
        } else {
            elem.attr('checked', 'checked');
            elem.parent().removeClass('strike');
        }
     });

    $('form select#cannedResp').select2({width: '350px'});
    $('form select#cannedResp').on('select2:opening', function (e) {
        var redactor = $('.richtext', $(this).closest('form')).data('redactor');
        if (redactor)
            redactor.api('selection.save');
    });

    $('form select#cannedResp').change(function() {

        var fObj = $(this).closest('form');
        var cid = $(this).val();
        var tid = $(':input[name=id]',fObj).val();
        $(this).find('option:first').attr('selected', 'selected').parent('select');

        var $url = 'ajax.php/kb/canned-response/'+cid+'.json';
        if (tid)
            $url =  'ajax.php/tickets/'+tid+'/canned-resp/'+cid+'.json';

        $.ajax({
                type: "GET",
                url: $url,
                dataType: 'json',
                cache: false,
                success: function(canned){
                    //Canned response.
                    var box = $('#response', fObj),
                        redactor = $R('#response.richtext');
                    if (canned.response) {
                        if (redactor) {
                            redactor.api('selection.restore');
                            redactor.insertion.insertHtml(canned.response);
                        } else
                            box.val(box.val() + canned.response);
                    }
                    //Canned attachments.
                    var ca = $('.attachments', fObj);
                    if(canned.files && ca.length) {
                        var fdb = ca.find('.dropzone').data('dropbox');
                        $.each(canned.files,function(i, j) {
                          fdb.addNode(j);
                        });
                    }
                }
            })
            .done(function() { })
            .fail(function() { });
    });


    /* Get config settings from the backend */
    getConfig().then(function(c) {
        // Datepicker
        $('.dp').datepicker({
            numberOfMonths: 2,
            showButtonPanel: true,
            buttonImage: './images/cal.png',
            showOn:'both',
            dateFormat: c.date_format || 'm/d/Y'
        });

    });

    /* Typeahead tickets lookup */
    var last_req;
    $('input.basic-search').typeahead({
        source: function (typeahead, query) {
            if (last_req) last_req.abort();
            var $el = this.$element;
            var url = $el.data('url')+'?q='+encodeURIComponent(query);
            last_req = $.ajax({
                url: url,
                dataType: 'json',
                success: function (data) {
                    typeahead.process(data);
                }
            });
        },
        onselect: function (obj) {
            var $el = this.$element;
            var form = $el.closest('form');
            form.find('input[name=search-type]').val('typeahead');
            $el.val(obj.value);
            if (obj.id) {
                form.append($('<input type="hidden" name="number">').val(obj.id))
            }
            form.submit();
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

    //Dialog
    $('.dialog').resize(function() {
        var w = $(window), $this=$(this);
        $this.css({
            top : (w.innerHeight() / 7),
            left : (w.width() - $this.outerWidth()) / 2
        });
        $this.hasClass('draggable') && $this.draggable({handle:'.drag-handle'});
    });


    $('.dialog').each(function() {
        $this=$(this);
        $this.resize();
        $this.hasClass('draggable') && $this.draggable({handle:'.drag-handle'});
    });

    $('.dialog').delegate('input.close, a.close', 'click', function(e) {
        e.preventDefault();
        var $dialog = $(this).parents('div.dialog');
        $dialog.off('blur.redactor');
        $dialog
        .hide()
        .removeAttr('style');
        $.toggleOverlay(false);

        return false;
    });

    /* loading ... */
    $("#loading").css({
        top  : ($(window).height() / 3),
        left : ($(window).width() - $("#loading").outerWidth()) / 2
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
       'cursor': 'move',
       'stop': function(e, ui) {
           var attr = ui.item.parent('tbody').data('sort'),
               offset = parseInt($('#sort-offset').val(), 10) || 0;
           warnOnLeave(ui.item);
           $('input[name^='+attr+']', ui.item.parent('tbody')).each(function(i, el) {
               $(el).val(i + 1 + offset);
           });
       },
       'cancel': ':input,button,div[contenteditable=true]'
   });

    // Scroll to a stop or top on scroll-up click
     $(document).off('click.scroll-up');
     $(document).on('click.scroll-up', 'a.scroll-up', function() {
        $stop = $(this).data('stop');
        $('html, body').animate({scrollTop: ($stop ? $stop : 0)}, 'fast');
        return false;
      });


   // Make translatable fields translatable
   $('input[data-translate-tag]').translatable();

   if (window.location.hash) {
     $('ul.tabs li a[href="' + window.location.hash + '"]').trigger('click');
   }

   // Make sticky bars float on scroll
   // Thanks, https://stackoverflow.com/a/17166225/1025836
   $('div.sticky.bar:not(.stop)').each(function() {
     var $that = $(this),
         placeholder = $('<div class="sticky placeholder">').insertBefore($that),
         offset = $that.offset(),
         top = offset.top - parseFloat($that.css('marginTop').replace(/auto/, 100)),
         stop = $('div.sticky.bar.stop').filter(':visible'),
         stopAt,
         visible = false;

     // Append scroll-up icon and set stop point for this sticky
     $('.content', $that)
     .append($('<a class="only sticky scroll-up" href="#" data-stop='
             + (placeholder.offset().top-75) +' ><i class="icon-chevron-up icon-large"></i></a>'));

     if (stop.length) {
       var onmove = function() {
         // Recalc when pictures pop in
         stopAt = stop.offset().top;
       };
       $('#ticket_thread .thread-body img').each(function() {
         this.onload = onmove;
       });
       onmove();
     }

     // Drop the sticky bar on PJAX navigation
     $(document).on('pjax:start', function() {
         placeholder.removeAttr('style');
         $that.stop().removeClass('fixed');
         $(window).off('.sticky');
     });

     $that.find('.content').width($that.width());
     $(window).on('scroll.sticky', function (event) {
       // what the y position of the scroll is
       var y = $(this).scrollTop();

       // whether that's below the form
       if (y >= top && (!stopAt || stopAt > y)) {
         // if so, add the fixed class
         if (!visible) {
           visible = true;
           setTimeout(function() {
             $that.addClass('fixed').css('top', '-'+$that.height()+'px')
                .animate({top:0}, {easing: 'swing', duration:'fast'});
             placeholder.height($that.height());
             $that.find('[data-dropdown]').dropdown('hide');
           }, 1);
         }
       } else {
         // otherwise remove it
         if (visible) {
           visible = false;
           setTimeout(function() {
             placeholder.removeAttr('style');
             $that.find('[data-dropdown]').dropdown('hide');
             $that.stop().removeClass('fixed');
           }, 1);
         }
       }
    });
  });

  $('div.tab_content[id] div.error:not(:empty), div.tab_content[id] font.error:not(:empty)').each(function() {
    var div = $(this).closest('.tab_content');
    $('a[href^="#'+div.attr('id')+'"]').parent().addClass('error');
    $('a#'+div.attr('id')+'_tab').parent().addClass('error');
  });

  $('[data-toggle="tooltip"]').tooltip()

  $('[data-toggle="tooltip"]').on('click', function() {
        $(this).tooltip('hide');
  });

  $('.attached.input input[autofocus]').parent().addClass('focus')
  $('.attached.input input')
    .on('focus', function() { $(this).parent().addClass('focus'); })
    .on('blur', function() { $(this).parent().removeClass('focus'); })

  $(function() {
    // whenever we hover over a menu item that has a submenu
    $('.subQ').on('mouseover', function() {
      var $menuItem = $(this),
          $submenuWrapper = $('> .subMenuQ', $menuItem);

      // grab the menu item's position relative to its positioned parent
      var menuItemPos = $menuItem.position();

      // place the submenu in the correct position relevant to the menu item
      $submenuWrapper.css({
        top: menuItemPos.top - 1,
        left: menuItemPos.left + Math.round($menuItem.outerWidth())
      });
    });
    // Ensure the "new ticket" link is never in the drop-down menu
    $('#new-ticket').parent('li').addClass('primary-only');
    $('#customQ_nav').overflowmenu({
      guessHeight: false,
      // items: 'li.top-queue',
      change: function( e, ui ) {
        var handle = ui.container.find('.jb-overflowmenu-menu-secondary-handle');
        handle.toggle( ui.secondary.children().length > 0 );
      }
    });
  });

  // Auto fetch queue counts
  $(function() {
    var fired = false;
    $('#customQ_nav li.item').hover(function() {
      if (fired) return;
      fired = true;
      $.ajax({
        url: 'ajax.php/queue/counts',
        dataType: 'json',
        success: function(json) {
          $('li span.queue-count').each(function(i, e) {
            var $e = $(e);
            $e.text(json['q' + $e.data('queueId')]);
            $(e).parents().find('#queue-count-bucket').show();
          });
        }
      });
    });
  });
};

$(document).ready(scp_prep);
$(document).on('pjax:end', scp_prep);
var fixupDatePickers = function() {
    // Reformat dates
    $('.dp', $(this)).each(function(i, e) {
        var $e = $(e),
            d = $e.datepicker('getDate');
        if (!d || $e.data('fixed')) return;
        $e.val(d.toISOString());
        $e.data('fixed', true);
        $e.on('change', function() { $(this).data('fixed', false); });
    });
};
$(document).on('submit', 'form', fixupDatePickers);

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

$.pjax.defaults.timeout = 30000;
$(document).keydown(function(e) {

    if (e.keyCode == 27 && !$('#overlay').is(':hidden')) {
        $('div.dialog').hide();
        $.toggleOverlay(false);

        e.preventDefault();
        e.stopPropagation();
        return false;
    }
});


$(document).on('focus', 'form.spellcheck textarea, form.spellcheck input[type=text]', function() {
  var $this = $(this);
  if ($this.attr('lang') !== undefined)
    return;
  var lang = $(this).closest('[lang]').attr('lang');
  if (lang)
    $(this).attr({'spellcheck':'true', 'lang': lang});
});

$(document).on('click', '.thread-entry-group a', function() {
    var inner = $(this).parent().find('.thread-entry-group-inner');
    if (inner.is(':visible'))
      inner.slideUp();
    else
      inner.slideDown();
    return false;
});

$.toggleOverlay = function (show) {
  if (typeof(show) === 'undefined') {
    return $.toggleOverlay(!$('#overlay').is(':visible'));
  }
  if (show) {
    $('#overlay').stop().hide().fadeIn();
    $('body').css('overflow', 'hidden');
  }
  else {
    $('#overlay').stop().fadeOut();
    $('body').css('overflow', 'auto');
  }
};
//modal---------//
$.dialog = function (url, codes, cb, options) {
    options = options||{};

    if (codes && !$.isArray(codes))
        codes = [codes];

    var $popup = $('.dialog#popup');

    $popup.attr('class',
        function(pos, classes) {
            return classes.replace(/\bsize-\S+/g, '');
    });

    $popup.addClass(options.size ? ('size-'+options.size) : 'size-normal');

    $.toggleOverlay(true);
    $('div.body', $popup).empty().hide();
    $('div#popup-loading', $popup).show()
        .find('h1').css({'margin-top':function() { return $popup.height()/3-$(this).height()/3}});
    $popup.resize().show();
    $('div.body', $popup).load(url, options.data, function () {
        $('div#popup-loading', $popup).hide();
        $('div.body', $popup).slideDown({
            duration: 300,
            queue: false,
            complete: function() {
                if (options.onshow) options.onshow();
                $(this).removeAttr('style');
            }
        });
        $("input[autofocus]:visible:enabled:first", $popup).focus();
        var submit_button = null;
        $(document).off('.dialog');
        $(document).on('click.dialog',
            '#popup input[type=submit], #popup button[type=submit]',
            function(e) { submit_button = $(this); });
        $(document).on('submit.dialog', '.dialog#popup form', function(e) {
            e.preventDefault();
            var $form = $(this),
                data = $form.serialize();
            if (submit_button) {
                data += '&' + escape(submit_button.attr('name')) + '='
                    + escape(submit_button.attr('value'));
            }
            $('div#popup-loading', $popup).show()
                .find('h1').css({'margin-top':function() { return $popup.height()/3-$(this).height()/3}});
            $.ajax({
                type:  $form.attr('method'),
                url: 'ajax.php/'+$form.attr('action').substr(1),
                data: data,
                cache: false,
                success: function(resp, status, xhr) {
                    if (xhr && xhr.status && codes
                        && $.inArray(xhr.status, codes) != -1) {
                        $.toggleOverlay(false);
                        $popup.hide();
                        $('div.body', $popup).empty();
                        if (cb && (false === cb(xhr, resp)))
                            // Don't fire event if callback returns false
                            return;
                        var done = $.Event('dialog:close');
                        $popup.trigger(done, [resp, status, xhr]);
                    } else {
                        try {
                            var json = $.parseJSON(resp);
                            if (json.redirect) return window.location.href = json.redirect;
                        }
                        catch (e) { }
                        $('div.body', $popup).html(resp);
                        if ($('#msg_error, .error-banner', $popup).length) {
                            $popup.effect('shake');
                        }
                        $('#msg_notice, #msg_error', $popup).delay(5000).slideUp();
                        $('div.tab_content[id] div.error:not(:empty)', $popup).each(function() {
                          var div = $(this).closest('.tab_content');
                          $('a[href^="#'+div.attr('id')+'"]').parent().addClass('error');
                        });
                    }
                }
            })
            .done(function() {
                $('div#popup-loading', $popup).hide();
            })
            .fail(function() { });
            return false;
        });
     });
    if (options.onload) { options.onload(); }
 };
$(document).on('click', 'a[data-dialog]', function(event) {
    event.preventDefault();
    event.stopImmediatePropagation();
    var link = $(this);
    $.dialog($(this).data('dialog'), 201, function(xhr, json) {
      try {
        json = JSON.parse(json);
      } catch (e) {}
      if (link.attr('href').length > 1) {
        // Replace {xx} expressions with data from JSON
        if (typeof json === 'object')
            link.attr('href',
              link.attr('href').replace(/\{([^}]+)\}/, function($0, $1) { return json[$1]; }));
        $.pjax.click(event, '#pjax-container');
      }
      else $.pjax.reload('#pjax-container');
    });
    return false;
});

$.sysAlert = function (title, msg, cb) {
    var $dialog =  $('.dialog#alert');
    if ($dialog.length) {
        $.toggleOverlay(true);
        $('#title', $dialog).html(title);
        $('#body', $dialog).html(msg);
        $dialog.resize().show();
        if (cb)
            $dialog.find('input.ok.close').click(cb);
    } else {
        alert(msg);
    }
};

$.confirm = function(message, title, options) {
    title = title || __('Please Confirm');
    options = options || {};
    var D = $.Deferred(),
      $popup = $('.dialog#popup'),
      hide = function() {
          $.toggleOverlay(false);
          $popup.hide();
      };
      $('div#popup-loading', $popup).hide();
      var body = $('div.body', $popup).empty()
        .append($('<h3></h3>').text(title))
        .append($('<a class="close" href="#"><i class="icon-remove-circle"></i></a>'))
        .append($('<hr/>'))
        .append($('<p class="confirm-action"></p>')
            .text(message)
        ).append($('<div></div>')
            .append($('<b>').text(__('Please confirm to continue.')))
        );

      if (Object.keys(options).length)
          body.append('<hr>');
      $.each(options, function(k, v) {
        body.append($('<div>')
          .html('&nbsp;'+v)
          .prepend($('<input type="checkbox">')
            .attr('name', k)
          )
        );
      });

      body.append($('<hr style="margin-top:1em"/>'))
        .append($('<p class="full-width"></p>')
            .append($('<span class="buttons pull-left"></span>')
                .append($('<input type="button" class="close"/>')
                    .attr('value', __('Cancel'))
                    .click(function() { hide();  D.resolve(false); })
            )).append($('<span class="buttons pull-right"></span>')
                .append($('<input type="button"/>')
                    .attr('value', __('OK'))
                    .click(function() {  hide(); D.resolve(body.find('input').serializeArray()); })
        ))).append($('<div class="clear"></div>'));
    $.toggleOverlay(true);
    $popup.resize().show();
    return D.promise();
};


$.confirmAction = function(action, form, confirmed) {
    var ids = [];
    $(':checkbox.mass:checked', form).each(function() {
        ids.push($(this).val());
    });
    if (ids.length) {
      var submit = function(data) {
        form.find('#action').val(action);
        $.each(ids, function() { form.append($('<input type="hidden" name="ids[]">').val(this)); });
        if (data)
          $.each(data, function() { form.append($('<input type="hidden">').attr('name', this.name).val(this.value)); });
        form.find('#selected-count').val(ids.length);
        form.submit();
      };
      var options = {};
      if (!confirmed)
          $.confirm(__('You sure?'), undefined, options).then(function(data) {
            if (data === false)
              return false;
            submit(data);
          });
      else
          submit();
    } else {
        $.sysAlert(__('Oops'),
            __('You need to select at least one item'));
    }
};



$.userLookup = function (url, cb) {
    $.dialog(url, 201, function (xhr, user) {
        if ($.type(user) == 'string')
            user = $.parseJSON(user);
        if (cb) return cb(user);
    }, {
        onshow: function() { $('#user-search').focus(); }
    });
};

$.orgLookup = function (url, cb) {
    $.dialog(url, 201, function (xhr, org) {
        if ($.type(org) == 'string')
            org = $.parseJSON(user);
        if (cb) cb(org);
    }, {
        onshow: function() { $('#org-search').focus(); }
    });
};

$.objectifyForm = function(formArray) { //serialize data function
    var returnArray = {};
    for (var i = 0; i < formArray.length; i++) {
        returnArray[formArray[i]['name']] = formArray[i]['value'];
    }
    return returnArray;
};

$.uid = 1;

+function($) {
  var MessageBar = function() {
    this.defaults = {
      avatar: 'oscar-boy',
      bar: '<div class="message bar"></div>',
      button: '<button type="button" class="inline button"></button>',
      buttonClass: '',
      buttonText: __('OK'),
      classes: '',
      dismissible: true,
      html: false,
      onok: null,
      position: 'top',
    };

    this.show = function(title, message, options) {
      this.hide();
      options = $.extend({}, this.defaults, options);
      var bar = this.bar = $(options.bar).addClass(options.classes)
        .append($('<div class="title"></div>').html(title))
        .append($('<div class="body"></div>').html(message))
        .addClass(options.position);
      if (options.avatar)
        bar.prepend($('<div class="avatar pull-left" title="Oscar"></div>')
            .addClass(options.avatar));

      if (options.onok || options.dismissible) {
        bar
          .prepend($('<div><div class="valign-helper"></div></div>')
            // FIXME: This is not compatible with .rtl
            .css({position: 'absolute', top: 0, bottom: 0, right: 0, margin: '0 15px'})
            .append($(options.button)
              .text(options.buttonText)
              .click(this.dismiss.bind(this))
              .addClass(options.buttonClass)
            )
          );
      }
      this.visible = true;
      this.options = options;

      $('body').append(bar);
      this.height = bar.height();

      // Slight slide in
      if (options.position == 'bottom') {
        bar.css('bottom', -this.height/2).animate({'bottom': 0});
      }
      // Otherwise assume TOP positioning
      else {
        var hovering = false,
            y = $(window).scrollTop(),
            targetY = (y < this.height) ? -this.height - 10 + y : 0;
        bar.css('top', -this.height/2).animate({'top': targetY});

        // Plop out on mouse hover
        bar.hover(function() {
          if (!hovering && this.visible && bar.css('top') != '0') {
            bar.stop().animate({'margin-top': -parseInt(bar.css('top'), 10)}, 400, 'easeOutBounce');
            hovering = true;
          }
        }.bind(this), function() {
          if (this.visible && hovering) {
            bar.stop().animate({'margin-top': 0});
            hovering = false;
          }
        }.bind(this));
      }

      return bar;
    };

    this.scroll = function(event) {
      // Shade on scroll to top
      if (!this.visible || this.options.position != 'top')
        return;
      var y = $(window).scrollTop();
      if (y < this.height) {
        this.bar.css({top: -this.height -10 + y});
        this.shading = true;
      }
      else if (this.bar.css('top') != '0') {
        if (this.shading) {
          this.bar.stop().animate({top: 0});
          this.shading = false;
        }
      }
    };

    this.dismiss = function(event) {
      if (this.options.onok) {
        this.bar.find('button').replaceWith(
          $('<i class="icon-spinner icon-spin icon-large"></i>')
        );
        if (this.options.onok(event) === false)
          return;
      }
      this.hide();
    };

    this.hide = function() {
      if (!this.bar || !this.visible)
        return;
      var bar = this.bar.removeAttr('style');
      var dir = this.options.position == 'bottom' ? 'down' : 'up';
      // NOTE: destroy() is not called here because a new bar might be
      //       created before the animation finishes
      bar.hide("slide", { direction: dir }, 400, function() { bar.remove(); });
      this.visible = false;
    };

    this.destroy = function() {
      if (!this.bar || !this.visible)
        return;
      this.bar.remove();
      this.visible = false;
    };

    // Destroy on away navigation
    $(document).on('pjax:start.messageBar', this.destroy.bind(this));
    $(window).on('scroll.messageBar', this.scroll.bind(this));
  };

  $.messageBar = new MessageBar();
}(window.jQuery);

// Tabs
$(document).on('click.tab', 'ul.tabs > li > a', function(e) {
    e.preventDefault();
    var $this = $(this),
        $ul = $(this).closest('ul'),
        $container = $('#'+$ul.attr('id')+'_container');
    if (!$container.length)
        $container = $ul.parent();

    var $tab = $($this.attr('href'), $container);
    if (!$tab.length && $(this).data('url').length > 1) {
        var url = $this.data('url');
        if (url.charAt(0) == '#')
            url = 'ajax.php/' + url.substr(1);
        $tab = $('<div>')
            .addClass('tab_content')
            .attr('id', $this.attr('href').substr(1)).hide();
        $container.append(
            $tab.load(url, function () {
                // TODO: Add / hide loading spinner
            })
         );
        $this.removeData('url');
    }
    else {
        $tab.addClass('tab_content');
        // Don't change the URL hash for nested tabs or in dialogs
        if ($(this).closest('.tab_content, .dialog').length == 0)
            $.changeHash($(this).attr('href'), true);
    }

    if ($tab.length) {
        $ul.children('li.active').removeClass('active');
        $(this).closest('li').addClass('active');
        $container.children('.tab_content').hide();
        $tab.fadeIn('fast').show();
        return false;
    }

});
$.changeHash = function(hash, quiet) {
  if (quiet) {
    hash = hash.replace( /^#/, '' );
    var fx, node = $( '#' + hash );
    if ( node.length ) {
      node.attr( 'id', '' );
      fx = $( '<div></div>' )
              .css({
                  position:'absolute',
                  visibility:'hidden',
                  top: $(document).scrollTop() + 'px'
              })
              .attr( 'id', hash )
              .appendTo( document.body );
    }
    document.location.hash = hash;
    if ( node.length ) {
      fx.remove();
      node.attr( 'id', hash );
    }
  }
  else {
    document.location.hash = hash;
  }
};

// Exports
$(document).on('click', 'a.export', function(e) {
    e.preventDefault();
    var url = 'ajax.php/'+$(this).attr('href').substr(1)
    $.dialog(url, 201, function (xhr) {
        var resp = $.parseJSON(xhr.responseText);
        var checker = 'ajax.php/export/'+resp.eid+'/check';
        $.dialog(checker, 201, function (xhr) { });
        return false;
     });
    return false;
});

$(document).on('click', 'a.nomodalexport', function(e) {
    e.preventDefault();
    var url = 'ajax.php/'+$(this).attr('href').substr(1);

     $.ajax({
          type: "GET",
          url: url,
          dataType: 'json',
          error:function(XMLHttpRequest, textStatus, errorThrown) {
          },
          success: function(resp) {
              var checker = 'ajax.php/export/'+resp.eid+'/check';
              $.dialog(checker, 201, function (xhr) { });
              return false;
          }
    });
    return false;
});

// Forms — submit, stay on same tab
$(document).on('submit', 'form', function() {
    if (!!$(this).attr('action') && $(this).attr('action').indexOf('#') == -1)
        $(this).attr('action', $(this).attr('action') + window.location.hash);
});

//Collaborators
$(document).on('click', 'a.collaborator, a.collaborators:not(.noclick)', function(e) {
    e.preventDefault();
    var url = 'ajax.php/'+$(this).attr('href').substr(1);
    $.dialog(url, 201, function (xhr) {
       var resp = $.parseJSON(xhr.responseText);
       $('#t'+resp.id+'-recipients').text(resp.text);
       $('.tip_box').remove();
    }, {
        onshow: function() { $('#user-search').focus(); }
    });
    return false;
 });

 //Merge
 $(document).on('click', 'a.merge, a.merge:not(.noclick)', function(e) {
     e.preventDefault();
     var url = 'ajax.php/'+$(this).attr('href').substr(1);
     $.dialog(url, 201, function (xhr) {
        var resp = $.parseJSON(xhr.responseText);
        $('#t'+resp.id+'-recipients').text(resp.text);
        $('.tip_box').remove();
     }, {
         onshow: function() { $('#user-search').focus(); }
     });
     return false;
  });

// NOTE: getConfig should be global
getConfig = (function() {
    var dfd = $.Deferred(),
        requested = false;
    return function() {
        return dfd;
    };
})();

$(document).on('pjax:click', function(options) {
    // Stop all animations
    $(document).stop(false, true);

    // Remove tips and clear any pending timer
    $('.tip, .help-tips, .previewfaq, .preview').each(function() {
        if ($(this).data('timer'))
            clearTimeout($(this).data('timer'));
    });
    $('.tip_box, .typeahead.dropdown-menu').remove();
});

$(document).on('pjax:start', function() {
    // Cancel save-changes warning banner
    $(document).unbind('pjax:beforeSend.changed');
    $(window).unbind('beforeunload');
    $.toggleOverlay(false);
    // Close tooltips
    $('.tip_box').remove();
});

$(document).on('pjax:send', function(event) {

    if ($('#loadingbar').length !== 0) {
        $('#loadingbar').remove();
    }

    $("body").append("<div id='loadingbar'></div>");
    $("#loadingbar").addClass("waiting").append($("<dt/><dd/>"));

    // right
    $('#loadingbar').stop(false, true).width((50 + Math.random() * 30) + "%");
    $('#overlay').css('background-color','white');
    $.toggleOverlay(true);
});

$(document).on('pjax:complete', function() {
    // right
    $("#loadingbar").width("101%").delay(200).fadeOut(400, function() {
        $(this).remove();
    });
    $.toggleOverlay(false);
    $('#overlay').removeAttr('style');
});

// Enable PJAX for the staff interface
if ($.support.pjax) {
  $(document).on('click', 'a', function(event) {
    var $this = $(this);
    var href = $this.attr('href');
    if (!$this.hasClass('no-pjax')
        && !$this.closest('.no-pjax').length
        && href && href.charAt(0) != '#')
      $.pjax.click(event, {container: $this.data('pjaxContainer') || '#pjax-container', timeout: 30000});
  })
}

$(document).on('click', '.link:not(a):not(.button)', function(event) {
  var $e = $(event.currentTarget);
  $('<a>').attr({href: $e.attr('href'), 'class': $e.attr('class')})
    .hide()
    .insertBefore($e)
    .get(0).click(event);
});

// Quick-Add dialogs
$(document).on('change', 'select[data-quick-add]', function() {
    var $select = $(this),
        selected = $select.find('option:selected'),
        type = selected.parent().closest('[data-quick-add]').data('quickAdd');
    if (!type || (selected.data('quickAdd') === undefined && selected.val() !== ':new:'))
        return;
    $.dialog('ajax.php/admin/quick-add/' + type, 201,
    function(xhr, data) {
        data = JSON.parse(data);
        if (data && data.id && data.name) {
          var id = data.id;
          if (selected.data('idPrefix'))
            id = selected.data('idPrefix') + id;
          $('<option>')
            .attr('value', id)
            .text(data.name)
            .insertBefore(selected)
          $select.val(id);
        }
    });
});

// Quick note interface
$(document).on('click.note', '.quicknote .action.edit-note', function(e) {
    // Prevent Auto-Scroll to top of page
    e.preventDefault();
    var note = $(this).closest('.quicknote'),
        body = note.find('.body'),
        T = $('<textarea>').text(body.html());
    if (note.closest('.dialog, .tip_box').length)
        T.addClass('no-bar small');
    body.replaceWith(T);
    T.redactor({ focusEnd: true });
    note.find('.action.edit-note').hide();
    note.find('.action.save-note').show();
    note.find('.action.cancel-edit').show();
    $('#new-note-box').hide();
    return false;
});
$(document).on('click.note', '.quicknote .action.cancel-edit', function() {
    var note = $(this).closest('.quicknote'),
        T = note.find('textarea'),
        body = $('<div class="body">');
    body.load('ajax.php/note/' + note.data('id'), function() {
      try { T.redactor('stop'); } catch (e) {}
      T.replaceWith(body);
      note.find('.action.save-note').hide();
      note.find('.action.cancel-edit').hide();
      note.find('.action.edit-note').show();
      $('#new-note-box').show();
    });
    return false;
});
$(document).on('click.note', '.quicknote .action.save-note', function() {
    var note = $(this).closest('.quicknote'),
        T = note.find('textarea');
    $.post('ajax.php/note/' + note.data('id'),
      { note: T.redactor('source.getCode') },
      function(html) {
        var body = $('<div class="body">').html(html);
        try { T.redactor('stop'); } catch (e) {}
        T.replaceWith(body);
        note.find('.action.save-note').hide();
        note.find('.action.cancel-edit').hide();
        note.find('.action.edit-note').show();
        $('#new-note-box').show();
      },
      'html'
    );
    return false;
});
$(document).on('click.note', '.quicknote .delete', function() {
  if (!window.confirm(__('Confirm Deletion')))
    return;
  var that = $(this),
      id = $(this).closest('.quicknote').data('id');
  $.ajax('ajax.php/note/' + id, {
    type: 'delete',
    success: function() {
      that.closest('.quicknote').animate(
        {height: 0, opacity: 0}, 'slow', function() {
          $(this).remove();
      });
    }
  });
  return false;
});
$(document).on('click', '#new-note', function() {
  var note = $(this).closest('.quicknote'),
    T = $('<textarea>'),
    button = $('<input type="button">').val(__('Create'));
    button.click(function() {
      $.post('ajax.php/' + note.data('url'),
        { note: T.redactor('source.getCode'), no_options: note.hasClass('no-options') },
        function(response) {
          T.redactor('stop');
          T.replaceWith(note);
          $(response).show('highlight').insertBefore(note.parent());
          $('.submit', note.parent()).remove();
        },
        'html'
      );
    });
    if (note.closest('.dialog, .tip_box').length)
        T.addClass('no-bar small');
    note.replaceWith(T);
    $('<p>').addClass('submit').css('text-align', 'center')
        .append(button).appendTo(T.parent());
    T.redactor({ focusEnd: true });
    return false;
});

function __(s) {
  if ($.oststrings && $.oststrings[s])
    return $.oststrings[s];
  return s;
}

// Thanks, http://stackoverflow.com/a/487049
function addSearchParam(data) {
    var kvp = document.location.search.substr(1).replace('+', ' ').split('&');
    var i=kvp.length, x, params = {};
    while (i--) {
        x = kvp[i].split('=');
        params[decodeURIComponent(x[0])] = decodeURIComponent(x[1]);
    }

    //this will reload the page, it's likely better to store this until finished
    return $.param($.extend(params, data));
}

// Periodically adjust relative times
window.relativeAdjust = setInterval(function() {
  // Thanks, http://stackoverflow.com/a/7641822/1025836
  var prettyDate = function(time) {
    var date = new Date((time || "").replace(/-/g, "/").replace(/[TZ]/g, " ")),
        diff = (((new Date()).getTime() - date.getTime()) / 1000),
        day_diff = Math.floor(diff / 86400);

    if (isNaN(day_diff) || day_diff < 0 || day_diff >= 31) return;

    return day_diff == 0 && (
         diff < 60 && __("just now")
      || diff < 120 && __("about a minute ago")
      || diff < 3600 && __("%d minutes ago").replace('%d', Math.floor(diff/60))
      || diff < 7200 && __("about an hour ago")
      || diff < 86400 &&  __("%d hours ago").replace('%d', Math.floor(diff/3600))
    )
    || day_diff == 1 && __("yesterday")
    || day_diff < 7 && __("%d days ago").replace('%d', day_diff);
    // Longer dates don't need to change dynamically
  };
  $('time.relative[datetime]').each(function() {
    var rel = prettyDate($(this).attr('datetime'));
    if (rel) $(this).text(rel);
  });
}, 20000);

// Add 'afterShow' event to jQuery elements,
// thanks http://stackoverflow.com/a/1225238/1025836
jQuery(function($) {
    var _oldShow = $.fn.show;

    // This should work with jQuery 3 with or without jQuery UI
    $.fn.show = function() {
        var argsArray = Array.prototype.slice.call(arguments),
            arg = argsArray[0],
            options = argsArray[1] || {duration: 0};
        if (typeof(arg) === 'number')
            options.duration = arg;
        else
            options.effect = arg;
        return this.each(function () {
            var obj = $(this);
            _oldShow.call(obj, $.extend(options, {
                complete: function() {
                    obj.trigger('afterShow');
                }
            }));
        });
    }
});

$(document).off('.inline-edit');
$(document).on('click.inline-edit', 'a.inline-edit', function(e) {
        e.preventDefault();
        var url = 'ajax.php/'
        +$(this).attr('href').substr(1)
        +'?_uid='+new Date().getTime();
        var $options = $(this).data('dialog');
        $.dialog(url, [201], function (xhr) {
            var obj = $.parseJSON(xhr.responseText);
            if (obj.id && obj.value) {
                $('#field_'+obj.id).html(obj.value);
                if (obj.value.includes('Empty'))
                    $('#field_'+obj.id).addClass('faded');
                else
                    $('#field_'+obj.id).removeClass('faded');
                $('#msg-txt').text(obj.msg);
                $('div#msg_notice').show();
            }
            // If Help Topic was set and statuses are returned 
            if (obj.statuses) {
                var reply = $('select[name=reply_status_id]');
                var note = $('select[name=note_status_id]');
                // Foreach status see if exists, if not appned to options
                $.each(obj.statuses, function(key, value) {
                    var option = $('<option></option>').attr('value', key).text(value);
                    if (reply)
                        if (reply.find('option[value='+key+']').length == 0)
                            reply.append(option);
                    if (note)
                        if (note.find('option[value='+key+']').length == 0)
                            note.append(option.clone());
                });
                // Hide warning banner
                reply.closest('td').find('.warning-banner').hide();
            }
        }, $options);

        return false;
    });

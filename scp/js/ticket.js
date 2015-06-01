/*********************************************************************
    ticket.js

    Ticket utility loaded on ticket view!

    Useful for UI config settings, ticket locking ...etc

    Peter Rotich <peter@osticket.com>
    Copyright (c) 2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
var autoLock = {

    // Defaults
    lockId: 0,
    lockCode: '',
    timerId: 0,
    lasteventTime: 0,
    lastcheckTime: 0,
    lastattemptTime: 0,
    acquireTime: 0,
    renewTime: 0,
    renewFreq: 10000, //renewal frequency in seconds...based on returned lock time.
    time: 0,
    lockAttempts: 0, //Consecutive lock attempt errors
    maxattempts: 2, //Maximum failed lock attempts before giving up.
    warn: true,
    retry: true,

    addEvent: function(elm, evType, fn, useCapture) {
        if(elm.addEventListener) {
            elm.addEventListener(evType, fn, useCapture);
            return true;
        }else if(elm.attachEvent) {
            return elm.attachEvent('on' + evType, fn);
        }else{
            elm['on' + evType] = fn;
        }
    },

    removeEvent: function(elm, evType, fn, useCapture) {
        if(elm.removeEventListener) {
            elm.removeEventListener(evType, fn, useCapture);
            return true;
        }else if(elm.detachEvent) {
            return elm.detachEvent('on' + evType, fn);
        }else {
            elm['on' + evType] = null;
        }
    },

    //Incoming event...
    handleEvent: function(e) {

        if(autoLock.lockId && !autoLock.lasteventTime) { //I hate nav away warnings..but
            $(document).on('pjax:beforeSend.changed', function(e) {
                return confirm(__("Any changes or info you've entered will be discarded!"));
            });
            $(window).bind('beforeunload', function(e) {
                return __("Any changes or info you've entered will be discarded!");
             });
        }
        // Handle events only every few seconds
        var now = new Date().getTime(),
            renewFreq = autoLock.renewFreq;

        if (autoLock.lasteventTime && now - autoLock.lasteventTime < renewFreq)
            return;

        autoLock.lasteventTime = now;

        if (!autoLock.lockId) {
            // Retry every 5 seconds??
            if (autoLock.retry)
                autoLock.acquireLock(e,autoLock.warn);
        } else {
            autoLock.renewLock(e);
        }

    },

    //Watch activity on individual form.
    watchForm: function(fObj,fn) {
        if(!fObj || !fObj.length)
            return;

        //Watch onSubmit event on the form.
        autoLock.addEvent(fObj,'submit',autoLock.onSubmit,true);
        //Watch activity on text + textareas + select fields.
        for (var i=0; i<fObj.length; i++) {
            switch(fObj[i].type) {
                case 'textarea':
                case 'text':
                    autoLock.addEvent(fObj[i],'keyup',autoLock.handleEvent,true);
                    break;
                case 'select-one':
                case 'select-multiple':
                    if(fObj.name!='reply') //Bug on double ajax call since select make it's own ajax call. TODO: fix it
                        autoLock.addEvent(fObj[i],'change',autoLock.handleEvent,true);
                    break;
                default:
            }
        }
    },

    //Watch all the forms on the document.
    watchDocument: function() {

        //Watch forms of interest only.
        for (var i=0; i<document.forms.length; i++) {
            if(!document.forms[i].id.value || parseInt(document.forms[i].id.value)!=autoLock.tid)
                continue;
            autoLock.watchForm(document.forms[i],autoLock.checkLock);
        }
    },

    Init: function(config) {

        //make sure we are on ticket view page & locking is enabled!
        var fObj=$('form#note');
        if(!fObj
                || !$(':input[name=id]',fObj).length
                || !$(':input[name=locktime]',fObj).length
                || $(':input[name=locktime]',fObj).val()==0) {
            return;
        }

        void(autoLock.tid=parseInt($(':input[name=id]',fObj).val()));
        void(autoLock.lockTime=parseInt($(':input[name=locktime]',fObj).val()));

        autoLock.watchDocument();
        autoLock.addEvent(window,'unload',autoLock.releaseLock,true); //Release lock regardless of any activity.
    },


    onSubmit: function(e) {
        if(e.type=='submit') { //Submit. double check!
            //remove nav away warning if any.
            $(window).unbind('beforeunload');
            //Only warn if we had a failed lock attempt.
            if(autoLock.warn && !autoLock.lockId && autoLock.lasteventTime) {
                var answer=confirm(__('Unable to acquire a lock on the ticket. Someone else could be working on the same ticket.  Please confirm if you wish to continue anyways.'));
                if(!answer) {
                    e.returnValue=false;
                    e.cancelBubble=true;
                    if(e.preventDefault) {
                        e.preventDefault();
                    }
                    return false;
                }
            }
        }
        return true;
    },

    acquireLock: function(e,warn) {

        if(!autoLock.tid) { return false; }

        var warn = warn || false;

        if(autoLock.lockId) {
            autoLock.renewLock(e);
        } else {
            $.ajax({
                type: "POST",
                url: 'ajax.php/tickets/'+autoLock.tid+'/lock',
                dataType: 'json',
                cache: false,
                success: function(lock){
                    autoLock.setLock(lock,'acquire',warn);
                }
            });
        }

        return autoLock.lockId;
    },

    //Renewal only happens on form activity..
    renewLock: function(e) {

        if (!autoLock.lockId)
            return false;

        var now = new Date().getTime(),
            renewFreq = autoLock.renewFreq;

        if (autoLock.lastcheckTime && now - autoLock.lastcheckTime < renewFreq)
            return;

        autoLock.lastcheckTime = now;
        $.ajax({
            type: 'POST',
            url: 'ajax.php/tickets/'+autoLock.tid+'/lock/'+autoLock.lockId+'/renew',
            dataType: 'json',
            cache: false,
            success: function(lock){
                autoLock.setLock(lock,'renew',autoLock.warn);
            }
        });
    },

    releaseLock: function(e) {
        if (!autoLock.tid || !autoLock.lockId) { return false; }

        $.ajax({
            type: 'POST',
            url: 'ajax.php/tickets/'+autoLock.tid+'/lock/'+autoLock.lockId+'/release',
            data: 'delete',
            async: false,
            cache: false,
            success: function() {
                autoLock.lockId = 0;
            }
        });
    },

    setLock: function(lock, action, warn) {
        var warn = warn || false;

        if (!lock)
            return false;

        autoLock.lockId=lock.id; //override the lockid.

        if (lock.code) {
            autoLock.lockCode = lock.code;
            // Update the lock code for the upcoming POST
            var el = $('input[name=lockCode]').val(lock.code);
        }

        switch(action){
            case 'renew':
                if(!lock.id && lock.retry) {
                    autoLock.lockAttempts=1; //reset retries.
                    autoLock.acquireLock(e,false); //We lost the lock?? ..try to re acquire now.
                }
                break;
            case 'acquire':
                if(!lock.id) {
                    autoLock.lockAttempts++;
                    if(warn && (!lock.retry || autoLock.lockAttempts>=autoLock.maxattempts)) {
                        autoLock.retry=false;
                        alert(__('Unable to lock the ticket. Someone else could be working on the same ticket.'));
                    }
                }
                break;
        }

        if (lock.id && lock.time) {
            autoLock.resetTimer((lock.time - 10) * 1000);
        }
    },

    discardWarning: function(e) {
        e.returnValue=__("Any changes or info you've entered will be discarded!");
    },

    //TODO: Monitor events and elapsed time and warn user when the lock is about to expire.
    monitorEvents: function() {
        $.sysAlert(
            __('Your lock is expiring soon'),
            __('The lock you hold on this ticket will expire soon. Would you like to renew the lock?'),
            function() {
                autoLock.renewLock();
            }
        );
    },

    clearTimer: function() {
        clearTimeout(autoLock.timerId);
    },

    resetTimer: function(time) {
        clearTimeout(autoLock.timerId);
        autoLock.timerId = setTimeout(
          function () { autoLock.monitorEvents(); },
          time || 30000
        );
    }
};
$.autoLock = autoLock;

/*
   UI & form events
*/
$.showNonLocalImage = function(div) {
    var $div = $(div),
        $img = $div.append($('<img>')
          .attr('src', $div.data('src'))
          .attr('alt', $div.attr('alt'))
          .attr('title', $div.attr('title'))
          .attr('style', $div.data('style'))
        );
    if ($div.attr('width'))
        $img.width($div.attr('width'));
    if ($div.attr('height'))
        $img.height($div.attr('height'));
};

$.showImagesInline = function(urls, thread_id) {
    var selector = (thread_id == undefined)
        ? '.thread-body img[data-cid]'
        : '.thread-body#thread-entry-'+thread_id+' img[data-cid]';
    $(selector).each(function(i, el) {
        var e = $(el),
            cid = e.data('cid').toLowerCase(),
            info = urls[cid];
        if (info && !e.data('wrapped')) {
            // Add a hover effect with the filename
            var timeout, caption = $('<div class="image-hover">')
                .css({'float':e.css('float')});
            e.wrap(caption).parent()
                .hover(
                    function() {
                        var self = this;
                        timeout = setTimeout(
                            function() { $(self).find('.caption').slideDown(250); },
                            500);
                    },
                    function() {
                        clearTimeout(timeout);
                        $(this).find('.caption').slideUp(250);
                    }
                ).append($('<div class="caption">')
                    .append('<span class="filename">'+info.filename+'</span>')
                    .append($('<a href="'+info.download_url+'" class="action-button pull-right no-pjax"><i class="icon-download-alt"></i> '+__('Download')+'</a>')
                      .attr('download', info.filename)
                    )
                );
            e.data('wrapped', true);
        }
    });
};

$.refreshTicketView = function(interval) {
    var refresh =
    window.ticket_refresh = setInterval(function() {
      if ($('table.list input.ckb[name=tids\\[\\]]:checked').length)
        // Skip the refresh b/c items are checked
        return;
      else if (0 < $('.dialog:visible').length)
        // Dialog open — skip refresh
        return;

      clearInterval(refresh);
      $.pjax({url: document.location.href, container:'#pjax-container'});
    }, interval);
}

var ticket_onload = function($) {
    if (0 === $('#ticket_thread').length)
        return;

    //Start watching the form for activity.
    autoLock.Init();

    /*** Ticket Actions **/
    //print options TODO: move to backend
    $('a#ticket-print').click(function(e) {
        e.preventDefault();
        $('#overlay').show();
        $('.dialog#print-options').show();
        return false;
    });


    $(document).off('.ticket-action');
    $(document).on('click.ticket-action', 'a.ticket-action', function(e) {
        e.preventDefault();
        var url = 'ajax.php/'
        +$(this).attr('href').substr(1)
        +'?_uid='+new Date().getTime();
        var $redirect = $(this).data('href');
        var $options = $(this).data('dialog');
        $.dialog(url, [201], function (xhr) {
            window.location.href = $redirect ? $redirect : window.location.href;
        }, $options);

        return false;
    });

    $(document).on('change', 'form[name=reply] select#emailreply', function(e) {
         var $cc = $('form[name=reply] tbody#cc_sec');
        if($(this).val() == 0)
            $cc.hide();
        else
            $cc.show();
     });

    // Optionally show external images
    $('.thread-entry').each(function(i, te) {
        var extra = $(te).find('.textra'),
            imgs = $(te).find('.non-local-image[data-src]');
        if (!extra) return;
        if (!imgs.length) return;
        extra.append($('<a>')
          .addClass("action-button pull-right show-images")
          .css({'font-weight':'normal'})
          .text(' ' + __('Show Images'))
          .click(function(ev) {
            imgs.each(function(i, img) {
              $.showNonLocalImage(img);
              $(img).removeClass('non-local-image')
                // Remove placeholder sizing
                .css({'display':'inline-block'})
                .width('auto')
                .height('auto')
                .removeAttr('width')
                .removeAttr('height');
              extra.find('.show-images').hide();
            });
          })
          .prepend($('<i>')
            .addClass('icon-picture')
          )
        );
        imgs.each(function(i, img) {
            var $img = $(img);
            // Save a copy of the original styling
            $img.data('style', $img.attr('style'));
            $img.removeAttr('style');
            // If the image has a 'height' attribute, use it, otherwise, use
            // 40px
            $img.height(($img.attr('height') || '40') + 'px');
            // Ensure the image placeholder is visible width-wise
            if (!$img.width())
                $img.width(($img.attr('width') || '80') + 'px');
            // TODO: Add a hover-button to show just one image
        });
    });

    $.showImagesInline($('#ticket_thread').data('imageUrls'));

    var last_entry = $('#ticket_thread .thread-entry').last(),
        frame = 0;
    $('html, body').delay(500).animate({
        scrollTop: last_entry.offset().top - 50,
    }, {
        duration: 750,
        step: function(now, fx) {
            // Recalc end target every few frames
            if (++frame % 6 == 0)
                fx.end = last_entry.offset().top - 50;
        }
    });

    $('div.thread-body a').each(function() {
        $(this).attr('target', '_blank');
    });
};
$(ticket_onload);
$(document).on('pjax:success', function() { ticket_onload(jQuery); });

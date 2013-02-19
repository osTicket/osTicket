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
        if(!autoLock.lockId) {
            var now = new Date().getTime();
            //Retry every 5 seconds??
            if(autoLock.retry && (!autoLock.lastattemptTime || (now-autoLock.lastattemptTime)>5000)) {
                autoLock.acquireLock(e,autoLock.warn);
                autoLock.lastattemptTime=new Date().getTime();
            }
        }else{
            autoLock.renewLock(e);
        }

        if(!autoLock.lasteventTime) { //I hate nav away warnings..but
            $(window).bind('beforeunload', function(e) {
                return "Any changes or info you've entered will be discarded!";
             });
        }
        
        autoLock.lasteventTime=new Date().getTime();
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

        autoLock.lockId=0;
        autoLock.timerId=0;
        autoLock.lasteventTime=0;
        autoLock.lastattemptTime=0;
        autoLock.acquireTime=0;
        autoLock.renewTime=0;
        autoLock.renewFreq=0; //renewal frequency in seconds...based on returned lock time.
        autoLock.time=0;
        autoLock.lockAttempts=0; //Consecutive lock attempt errors
        autoLock.maxattempts=2; //Maximum failed lock attempts before giving up.
        autoLock.warn=true;
        autoLock.retry=true;
        autoLock.watchDocument();
        autoLock.resetTimer();
        autoLock.addEvent(window,'unload',autoLock.releaseLock,true); //Release lock regardless of any activity.
    },
          

    onSubmit: function(e) {
        if(e.type=='submit') { //Submit. double check!
            //remove nav away warning if any.
            $(window).unbind('beforeunload');
            //Only warn if we had a failed lock attempt.
            if(autoLock.warn && !autoLock.lockId && autoLock.lasteventTime) {
                var answer=confirm('Unable to acquire a lock on the ticket. Someone else could be working on the same ticket. \
                    Please confirm if you wish to continue anyways.');
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
            })
            .done(function() { })
            .fail(function() { });
        }
   
        return autoLock.lockId;
    },

    //Renewal only happens on form activity.. 
    renewLock: function(e) {
        
        if(!autoLock.lockId) { return false; }
        
        var now= new Date().getTime(); 
        if(!autoLock.lastcheckTime || (now-autoLock.lastcheckTime)>=(autoLock.renewFreq*1000)){
            $.ajax({
                type: 'POST',
                url: 'ajax.php/tickets/'+autoLock.tid+'/lock/'+autoLock.lockId+'/renew',
                dataType: 'json',
                cache: false,
                success: function(lock){
                    autoLock.setLock(lock,'renew',autoLock.warn);
                }
            })
            .done(function() {  })
            .fail(function() { });
        }
    }, 
     
    releaseLock: function(e) {
        if(!autoLock.tid) { return false; }

        $.ajax({
            type: 'POST',
            url: 'ajax.php/tickets/'+autoLock.tid+'/lock/'+autoLock.lockId+'/release',
            data: 'delete',
            cache: false,
            success: function(){
               
            }
        })
        .done(function() { })
        .fail(function() { });
    },

    setLock: function(lock, action, warn) {
        var warn = warn || false;
            
        if(!lock) return false;

        if(lock.id) {
            autoLock.renewFreq=lock.time?(lock.time/2):30;
            autoLock.lastcheckTime=new Date().getTime();
        }
        autoLock.lockId=lock.id; //overwrite the lockid.
        
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
                        alert('Unable to lock the ticket. Someone else could be working on the same ticket.'); 
                    }
                }   
                break;
        }
    },
    
    discardWarning: function(e) {
        e.returnValue="Any changes or info you've entered will be discarded!";
    },

    //TODO: Monitor events and elapsed time and warn user when the lock is about to expire. 
    monitorEvents: function() {
       // warn user when lock is about to expire??;
        //autoLock.resetTimer();
    },

    clearTimer: function() {
        clearTimeout(autoLock.timerId);
    },
    
    resetTimer: function() {
        clearTimeout(autoLock.timerId);
        autoLock.timerId=setTimeout(function () { autoLock.monitorEvents() },30000);
    }
}

/*
   UI & form events
*/

jQuery(function($) {
    $('#response_options form').hide();
    $('#ticket_notes').hide();
    if(location.hash != "" && $('#response_options '+location.hash).length) {
        $('#response_options '+location.hash+'_tab').addClass('active');
        $('#response_options '+location.hash).show();
    } else if(location.hash == "#notes" && $('#ticket_notes').length) {
        $('#response_options #note_tab').addClass('active');
        $('#response_options form').hide();
        $('#response_options #note').show();
        $('#ticket_thread').hide();
        $('#ticket_notes').show();
        $('#toggle_ticket_thread').removeClass('active');
        $('#toggle_notes').addClass('active');
    } else {
        $('#response_options ul li:first a').addClass('active');
        $('#response_options '+$('#response_options ul li:first a').attr('href')).show();
    }

    $('#reply_tab').click(function() {
       $(this).removeClass('tell'); 
     });

    $('#note_tab').click(function() {
        if($('#response').val() != '') {
            $('#reply_tab').addClass('tell');
        }
     });

    $('#response_options ul li a').click(function(e) {
        e.preventDefault();
        $('#response_options ul li a').removeClass('active');
        $(this).addClass('active');
        $('#response_options form').hide();
        //window.location.hash = this.hash;
        $('#response_options '+$(this).attr('href')).show();
        $("#msg_error, #msg_notice, #msg_warning").fadeOut();
     });

    $('#toggle_ticket_thread, #toggle_notes, .show_notes').click(function(e) {
        e.preventDefault();
        $('#threads a').removeClass('active');

        if($(this).attr('id') == 'toggle_ticket_thread') {
            $('#ticket_notes').hide();
            $('#ticket_thread').show();
            $('#toggle_ticket_thread').addClass('active');
            $('#reply_tab').removeClass('tell').click();
        } else {
            $('#ticket_thread').hide();
            $('#ticket_notes').show();
            $('#toggle_notes').addClass('active');
            $('#note_tab').click();
            if($('#response').val() != '') {
                $('#reply_tab').addClass('tell');
            }
        }
     });

    //Start watching the form for activity.
    autoLock.Init();

    /*** Ticket Actions **/
    //print options
    $('a#ticket-print').click(function(e) {
        e.preventDefault();
        $('#overlay').show();
        $('.dialog#print-options').show();
        return false;
    });

    //ticket status (close & reopen)
    $('a#ticket-close, a#ticket-reopen').click(function(e) {
        e.preventDefault();
        $('#overlay').show();
        $('.dialog#ticket-status').show();
        return false;
    });
       
    //ticket actions confirmation - Delete + more
    $('a#ticket-delete, a#ticket-claim, #action-dropdown-more li a').click(function(e) {
        e.preventDefault();
        if($('.dialog#confirm-action '+$(this).attr('href')+'-confirm').length) {
            var action = $(this).attr('href').substr(1, $(this).attr('href').length);
            $('.dialog#confirm-action #action').val(action);
            $('#overlay').show();
            $('.dialog#confirm-action .confirm-action').hide();
            $('.dialog#confirm-action p'+$(this).attr('href')+'-confirm')
            .show()
            .parent('div').show().trigger('click');

        } else {
            alert('Unknown action '+$(this).attr('href')+'- get technical help.');
        }

        return false;
    });

});

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
+function( $ ) {
  var Lock = function(element, options) {
    this.$element = $(element);
    this.options = $.extend({}, $.fn.exclusive.defaults, options);
    if (!this.$element.data('lockObjectId'))
      return;
    this.objectId = this.$element.data('lockObjectId');
    this.fails = 0;
    this.disabled = false;
    getConfig().then(function(c) {
      if (c.lock_time)
        this.setup(options.lockId || this.$element.data('lockId') || undefined);
    }.bind(this));
  }

  Lock.prototype = {
    constructor: Lock,
    registry: [],

    setup: function(lockId) {
      // When something inside changes or is clicked which requires a lock,
      // attempt to fetch one (lazily)
      $(':input', this.$element).on('keyup, change', this.acquire.bind(this));
      $(':submit', this.$element).click(this.ensureLocked.bind(this));

      // If lock already held, assume full time of lock remains, but warn
      // user about pending expiration
      if (lockId) {
        getConfig().then(function(c) {
          this.update({id: lockId, time: c.lock_time - 10});
        }.bind(this));
      }
    },

    acquire: function() {
      if (this.lockId)
        return this.renew();
      if (this.nextRenew && new Date().getTime() < this.nextRenew)
        return this.locked;
      if (this.disabled || this.ajaxActive)
        return this.locked;

      this.ajaxActive = $.ajax({
        type: "POST",
        url: 'ajax.php/lock/'+this.objectId,
        dataType: 'json',
        cache: false,
        success: $.proxy(this.update, this),
        error: $.proxy(this.retry, this, this.acquire),
        complete: $.proxy(function() { this.ajaxActive = false; }, this)
      });
      return this.locked = $.Deferred();
    },

    renew: function() {
      if (!this.lockId)
        return;
      if (this.nextRenew && new Date().getTime() < this.nextRenew)
        return this.locked;
      if (this.disabled || this.ajaxActive)
        return this.locked;

      this.ajaxActive = $.ajax({
        type: "POST",
        url: 'ajax.php/lock/{0}/{1}/renew'.replace('{0}',this.lockId).replace('{1}',this.objectId),
        dataType: 'json',
        cache: false,
        success: $.proxy(this.update, this),
        error: $.proxy(this.retry, this, this.renew),
        complete: $.proxy(function() { this.ajaxActive = false; }, this)
      });
      return this.locked = $.Deferred();
    },

    wakeup: function(e) {
      // Click handler from message bar. Bar will be manually hidden when
      // lock is re-acquired
      this.renew();
      return false;
    },

    retry: function(func, xhr, textStatus, response) {
      var json = xhr ? xhr.responseJSON : response;

      if (xhr.status == 418) {
          this.disabled = true;
          return this.destroy();
      }

      if ((typeof json == 'object' && !json.retry) || !this.options.retry)
        return this.fail(json.msg);
      if (typeof json == 'object' && json.retry == 'acquire') {
        // Lock no longer exists server-side
        this.destroy();
        setTimeout(this.acquire.bind(this), 2);
      }
      if (++this.fails > this.options.maxRetries)
        // Attempt to acquire a new lock ?
        return this.fail(json ? json.msg : null);
      this.retryTimer = setTimeout($.proxy(func, this), this.options.retryInterval * 1000);
    },

    release: function() {
      if (!this.lockId)
        return false;
      if (this.ajaxActive)
        this.ajaxActive.abort();

      $.ajax({
        type: 'POST',
        url: 'ajax.php/lock/{0}/release'.replace('{0}', this.lockId),
        data: 'delete',
        cache: false,
        success: this.clearAll.bind(this),
        complete: this.destroy.bind(this)
      });
    },

    clearAll: function() {
      // Clear all other current locks with the same ID as this
      $.each(Lock.prototype.registry, function(i, l) {
        if (l.lockId && l.lockId == this.lockId)
          l.shutdown();
      }.bind(this));
    },

    shutdown: function() {
      clearTimeout(this.warning);
      clearTimeout(this.retryTimer);
      $(document).off('.exclusive');
    },

    destroy: function() {
      this.shutdown();
      delete this.lockId;
      $(this.options.lockInput, this.$element).val('');
      if (this.locked)
        this.locked.reject();
    },

    update: function(lock) {
      if (typeof lock != 'object' || lock.retry === true) {
        // Non-json response, or retry requested server-side
        return this.retry(this.renew, this.activeAjax, false, lock);
      }
      if (!lock.id) {
        // Response did not include a lock id number
        return this.fail(lock.msg);
      }
      if (!this.lockId) {
        // Set up release on away navigation
        $(document).off('.exclusive');
        $(document).on('pjax:click.exclusive', $.proxy(this.release, this));
        Lock.prototype.registry.push(this);
      }

      this.lockId = lock.id;
      this.fails = 0;
      $.messageBar.hide();
      this.errorBar = false;

      // If there is an input with the name 'lockCode', then set the value
      // to the lock.code retrieved (if any)
      if (lock.code)
        $(this.options.lockInput, this.$element).val(lock.code);

      // Deadband renew to every 30 seconds
      this.nextRenew = new Date().getTime() + 30000;

      // Warn 10 seconds before expiration
      this.lockTimeout(lock.time - 10);

      if (this.locked)
        this.locked.resolve(lock);
    },

    lockTimeout: function(time) {
      if (this.warning)
        clearTimeout(this.warning);
      this.warning = setTimeout(this.warn.bind(this), time * 1000);
    },

    ensureLocked: function(e) {
      // Make sure a lock code has been fetched first
      if (!$(this.options.lockInput, this.$element).val()) {
        var $target = $(e.target),
            text = $target.text() || $target.val();
        $target.prop('disabled', true).text(__('Acquiring Lock')).val(__('Acquiring Lock'));
        this.acquire().always(function(lock) {
          $target.text(text).val(text).prop('disabled', false);
          if (typeof lock == 'object' && lock.code)
            $target.trigger(e.type, e);
        }.bind(this));
        return false;
      }
    },

    warn: function() {
      $.messageBar.show(
        __('Your lock is expiring soon.'),
        __('The lock you hold on this ticket will expire soon. Would you like to renew the lock?'),
        {onok: this.wakeup.bind(this), buttonText: __("Renew")}
      ).addClass('warning');
    },

    fail: function(msg) {
      // Don't retry for 5 seconds
      this.nextRenew = new Date().getTime() + 5000;
      // Resolve anything awaiting
      if (this.locked)
        this.locked.rejectWith(msg);
      // No longer locked
      this.destroy();
      // Flash the error bar if it's already on the screen
      if (this.errorBar && $.messageBar.visible)
          return this.errorBar.effect('highlight');
      // Add the error bar to the screen
      this.errorBar = $.messageBar.show(
        msg || __('Unable to lock the ticket.'),
        __('Someone else could be working on the same ticket.'),
        {avatar: 'oscar-borg', buttonClass: 'red', dismissible: true}
      ).addClass('danger');
    }
  };

  $.fn.exclusive = function ( option ) {
    return this.each(function () {
      var $this = $(this),
        data = $this.data('exclusive'),
        options = typeof option == 'object' && option;
      if (!data) $this.data('exclusive', (data = new Lock(this, options)));
      if (typeof option == 'string') data[option]();
    });
  };

  $.fn.exclusive.defaults = {
    lockInput: 'input[name=lockCode]',
    maxRetries: 2,
    retry: true,
    retryInterval: 2
  };

  $.fn.exclusive.Constructor = Lock;

}(window.jQuery);

/*
   UI & form events
*/
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
    var refresh = setInterval(function() {
      if ($('table.list input.ckb[name=tids\\[\\]]:checked').length)
        // Skip the refresh b/c items are checked
        return;
      else if (0 < $('.dialog:visible').length)
        // Dialog open — skip refresh
        return;

      clearInterval(refresh);
      $.pjax({url: document.location.href, container:'#pjax-container'});
    }, interval);
    $(document).on('pjax:start', function() {
        clearInterval(refresh);
    });
};

var ticket_onload = function($) {
    if (0 === $('#ticketThread').length)
        return;

    $(function(){$('.exclusive[data-lock-object-id]').exclusive();});

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
        var $redirect = $(this).data('redirect');
        var $options = $(this).data('dialog');
        $.dialog(url, [201], function (xhr) {
            if (!!$redirect)
                window.location.href = $redirect;
            else
                $.pjax.reload('#pjax-container');
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

};
$(ticket_onload);
$(document).on('pjax:success', function() { ticket_onload(jQuery); });

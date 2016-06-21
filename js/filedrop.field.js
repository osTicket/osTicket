!function($) {
  "use strict";

  var FileDropbox = function(element, options) {
    this.$element = $(element);
    this.uploads = [];

    var events = {
      uploadStarted: $.proxy(this.uploadStarted, this),
      uploadFinished: $.proxy(this.uploadFinished, this),
      progressUpdated: $.proxy(this.progressUpdated, this),
      speedUpdated: $.proxy(this.speedUpdated, this),
      dragOver: $.proxy(this.dragOver, this),
      drop: $.proxy(this.drop, this),
      beforeSend: $.proxy(this.beforeSend, this),
      beforeEach: $.proxy(this.beforeEach, this),
      error: $.proxy(this.handleError, this),
      afterAll: $.proxy(this.afterAll, this)
    };

    this.options = $.extend({}, $.fn.filedropbox.defaults, events, options);
    this.$element.filedrop(this.options);
    if (this.options.shim) {
      $('input[type=file]', this.$element).attr('name', this.options.name)
          .addClass('shim').css('display', 'inline-block').show();
      $('a.manual', this.$element).hide();
    }
    (this.options.files || []).forEach($.proxy(this.addNode, this));
  };

  FileDropbox.prototype = {
    drop: function(e) {
        this.$element.removeAttr('style');
    },
    dragOver: function(box, e) {
        this.$element.css('background-color', 'rgba(0, 0, 0, 0.3)');
    },
    beforeEach: function (file) {
      if (this.options.maxfiles && this.uploads.length >= this.options.maxfiles) {
          // This file is not allowed to be added to the list. It's over the
          // limit
          this.handleError('TooManyFiles', file);
          return false;
      }
      var node = this.addNode(file).data('file', file);
      node.find('.progress').show();
      node.find('.progress-bar').width('100%').addClass('progress-bar-striped active');
      node.find('.trash').hide();
    },
    beforeSend: function (file, i, reader) {
      var URL = window.webkitURL || window.URL;
      this.uploads.some(function(e) {
        if (e.data('file') == file) {
          if (file.type.indexOf('image/') === 0 && file.size < 1e6) {
            var img = e.find('.preview')
              .tooltip({items:'img',
                tooltipClass: 'tooltip-preview',
                content:function(){ return $(this).clone().wrap('<div>'); }}
              )
              .get()[0];
              img.src = URL.createObjectURL(file);
          }
          return true;
        }
      });
    },
    speedUpdated: function(i, file, speed) {
      var that = this;
      this.uploads.some(function(e) {
        if (e.data('file') == file) {
          e.find('.upload-rate').text(that.fileSize(speed * 1024)+'/s');
          return true;
        }
      });
    },
    progressUpdated: function(i, file, value) {
      this.uploads.some(function(e) {
        if (e.data('file') == file) {
          e.find('.progress-bar')
            .attr({'aria-valuenow': value})
            .width(value + '%');
          if (value == 100)
            e.find('.progress-bar').addClass('progress-bar-striped active');
          return true;
        }
      });
    },
    uploadStarted: function(i, file, n, xhr) {
      var that = this;
      this.uploads.some(function(e) {
        if (e.data('file') == file) {
          e.data('xhr', xhr);
          e.find('.cancel').show();
          that.lockSubmit(1);
          that.progressUpdated(i, file, 0);
          setTimeout(function() {
            e.find('.progress-bar')
             .removeClass('progress-bar-striped active');
          }, 50);
          return true;
        }
      });
    },
    uploadFinished: function(i, file, json, time, xhr) {
      var that = this;
      this.uploads.some(function(e) {
        if (e.data('file') == file) {
          if (!json || !json.id)
            // Upload failed. TODO: Add a button to the UI to retry on
            // HTTP 500
            return e.remove();
          e.find('[name="'+that.options.name+'"]').val(''+json.id+','+file.name);
          e.data('fileId', json.id);
          e.find('.progress-bar')
            .width('100%')
            .attr({'aria-valuenow': 100});
          e.find('.trash').show();
          e.find('.upload-rate').hide();
          e.find('.cancel').hide();
          setTimeout(function() { e.find('.progress').hide(); }, 600);
          return true;
        }
      });
    },
    fileSize: function(size) {
      var sizes = ['k','M','G','T'],
          suffix = '';
      while (size > 900) {
        size /= 1024;
        suffix = sizes.shift();
      }
      return (suffix ? size.toPrecision(3) + suffix : size) + 'B';
    },
    findNode: function(file) {
      var nodes = $.grep(this.uploads, function(e) {
        return e.data('file') == file;
      });
      return nodes ? nodes[0] : null;
    },
    addNode: function(file) {
      // Check if the file is already in the list of files for this dropbox
      var already_added = false;
      this.uploads.some(function(e) {
        if (file.id && e.data('fileId') == file.id) {
          already_added = true;
          return true;
        }
      });
      if (already_added)
          return;

      var filenode = $('<div class="file"></div>');
      filenode
          .append($('<div class="filetype"></div>').addClass())
          .append($('<img class="preview" />'))
          .append($('<span class="filename ltr"></div>')
            .append($('<span class="filesize"></span>').text(
              this.fileSize(parseInt(file.size))
            ))
            .append($('<div class="pull-right cancel"></div>')
              .append($('<i class="icon-remove"></i>')
                .attr('title', __('Cancel'))
              )
              .click($.proxy(this.cancelUpload, this, filenode))
              .hide()
            )
            .append($('<div class="upload-rate pull-right"></div>'))
          ).append($('<div class="progress"></div>')
            .append($('<div class="progress-bar"></div>'))
            .attr({'aria-valuemin':0,'aria-valuemax':100})
            .hide())
          .append($('<input type="hidden"/>').attr('name', this.options.name)
            .val(file.id))
      if (this.options.deletable) {
        filenode.prepend($('<span><i class="icon-trash"></i></span>')
          .addClass('trash pull-right')
          .click($.proxy(this.deleteNode, this, filenode))
        );
      }
      if (file.id)
        filenode.data('fileId', file.id);
      if (file.download_url) {
        filenode.find('.filename').prepend(
          $('<a class="no-pjax" target="_blank"></a>').text(file.name)
            .attr('href', file.download_url)
        );
      }
      else
        filenode.find('.filename').prepend($('<span>').text(file.name));
      this.$element.parent().find('.files').append(filenode);
      this.uploads.push(filenode);
      return filenode;
    },
    deleteNode: function(filenode, e) {
      if (!e || confirm(__('You sure?'))) {
        var i = this.uploads.indexOf(filenode);
        if (i !== -1)
            this.uploads.splice(i,1);
        filenode.slideUp('fast', function() { $(this).remove(); });
      }
    },
    cancelUpload: function(node) {
      if (node.data('xhr')) {
        node.data('xhr').abort();
        var img = node.find('.preview').get()[0];
        if (img) (window.webkitURL || window.URL).revokeObjectURL(img.src);
      }
      return this.deleteNode(node, false);
    },
    handleError: function(err, file, i, status) {
      var message = $.fn.filedropbox.messages[err],
          filenode = this.findNode(file);
      if (file instanceof File) {
        message = '<b>' + file.name + '</b><br/>' + message + '<br/>' + status;
      }
      $.sysAlert(__('File Upload Error'), message);
      if (filenode) this.cancelUpload(filenode);
    },
    afterAll: function() {
      var submit = this.$element.closest('form').find('input[type=submit]'),
          $submit = $(submit);
      if ($submit.data('original')) {
        $submit.val($submit.data('original')).prop('disabled', false);
      }
    },
    lockSubmit: function() {
      var submit = this.$element.closest('form').find('input[type=submit]'),
          $submit = $(submit);
      if (!$submit.data('original')) {
        $submit.data('original', $submit.val());
      }
      $submit.val(__('Uploading ...')).prop('disabled', true);
    }
  };

  $.fn.filedropbox = function ( option ) {
    return this.each(function () {
      var $this = $(this),
        data = $this.data('dropbox'),
        options = typeof option == 'object' && option;
      if (!data) $this.data('dropbox', (data = new FileDropbox(this, options)));
      if (typeof option == 'string') data[option]();
    });
  };

  $.fn.filedropbox.defaults = {
    files: [],
    deletable: true,
    shim: !window.FileReader,
    queuefiles: 1
  };

  $.fn.filedropbox.messages = {
    'BrowserNotSupported': __('Your browser is not supported'),
    'TooManyFiles': __('You are trying to upload too many files'),
    'FileTooLarge': __('File is too large'),
    'FileTypeNotAllowed': __('This type of file is not allowed'),
    'FileExtensionNotAllowed': __('This type of file is not allowed'),
    'NotFound': __('Could not find or read this file'),
    'NotReadable': __('Could not find or read this file'),
    'AbortError': __('Could not find or read this file')
  };

  $.fn.filedropbox.Constructor = FileDropbox;

}(jQuery);

/*
 * Default text - jQuery plugin for html5 dragging files from desktop to browser
 *
 * Author: Weixi Yen
 *
 * Email: [Firstname][Lastname]@gmail.com
 *
 * Copyright (c) 2010 Resopollution
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   http://www.github.com/weixiyen/jquery-filedrop
 *
 * Version:  0.1.0
 *
 * Features:
 *      Allows sending of extra parameters with file.
 *      Works with Firefox 3.6+
 *      Future-compliant with HTML5 spec (will work with Webkit browsers and IE9)
 * Usage:
 *  See README at project homepage
 *
 */
;(function($) {

  jQuery.event.props.push("dataTransfer");

  var default_opts = {
      fallback_id: '',
      link: false,
      url: '',
      refresh: 1000,
      paramname: 'userfile',
      requestType: 'POST',    // just in case you want to use another HTTP verb
      allowedfileextensions:[],
      allowedfiletypes:[],
      maxfiles: 25,           // Ignored if queuefiles is set > 0
      maxfilesize: 1,         // MB file size limit
      queuefiles: 0,          // Max files before queueing (for large volume uploads)
      queuewait: 200,         // Queue wait time if full
      data: {},
      headers: {},
      drop: empty,
      dragStart: empty,
      dragEnter: empty,
      dragOver: empty,
      dragLeave: empty,
      docEnter: empty,
      docOver: empty,
      docLeave: empty,
      beforeEach: empty,
      afterAll: empty,
      rename: empty,
      error: function(err, file, i, status) {
        alert(err);
      },
      uploadStarted: empty,
      uploadFinished: empty,
      progressUpdated: empty,
      globalProgressUpdated: empty,
      speedUpdated: empty
      },
      errors = ["BrowserNotSupported", "TooManyFiles", "FileTooLarge", "FileTypeNotAllowed", "NotFound", "NotReadable", "AbortError", "ReadError", "FileExtensionNotAllowed"],
      Blob = window.WebKitBlob || window.MozBlob || window.Blob;

  $.fn.filedrop = function(options) {
    var opts = $.extend({}, default_opts, options),
        global_progress = [],
        doc_leave_timer, stop_loop = false,
        files_count = 0,
        files;

    if (window.FileReader)
      $('#' + opts.fallback_id).css({
        display: 'none',
        width: 0,
        height: 0
      });

    this.on('drop', drop).on('dragstart', opts.dragStart).on('dragenter', dragEnter).on('dragover', dragOver).on('dragleave', dragLeave);
    $(document).on('drop', docDrop).on('dragenter', docEnter).on('dragover', docOver).on('dragleave', docLeave);

    (opts.link || this).click(function(e) {
      $('#' + opts.fallback_id).trigger('click');
      return false;
    });

    $('#' + opts.fallback_id).change(function(e) {
      opts.drop(e);
      files = e.target.files;
      files_count = files.length;
      upload();
    });

    function drop(e) {
      if( opts.drop.call(this, e) === false ) return false;
      if(!e.dataTransfer)
        return;
      files = e.dataTransfer.files;
      if (files === null || files === undefined || files.length === 0) {
        opts.error(errors[0]);
        return false;
      }
      files_count = files.length;
      upload();
      e.preventDefault();
      return false;
    }

    function getBuilder(filename, filedata, mime, boundary) {
      var dashdash = '--',
          crlf = '\r\n',
          builder = [],
          paramname = opts.paramname;

      if (opts.data) {
        var params = $.param(opts.data).replace(/\+/g, '%20').split(/&/);

        $.each(params, function() {
          var pair = this.split("=", 2),
              name = decodeURIComponent(pair[0]),
              val  = decodeURIComponent(pair[1]);

          if (pair.length !== 2) {
              return;
          }

          builder.push(dashdash
              + boundary
              + crlf
              + 'Content-Disposition: form-data; name="' + name + '"'
              + crlf
              + crlf
              + val
              + crlf);
        });
      }

      if (jQuery.isFunction(paramname)){
        paramname = paramname(filename);
      }

      builder.push(dashdash
          + boundary
          + crlf
          + 'Content-Disposition: form-data; name="' + (paramname||"") + '"'
          + '; filename="' + encodeURIComponent(filename) + '"'
          + crlf

          + 'Content-Type: ' + mime
          + crlf
          + crlf);

      builder.push(filedata);
      builder.push(crlf
          + dashdash
          + boundary
          + dashdash
          + crlf);
      return new Blob(builder);
    }

    function progress(e) {
      if (e.lengthComputable) {
        var percentage = ((e.loaded * 100) / e.total).toFixed(1);
        if (this.currentProgress != percentage) {

          this.currentProgress = percentage;
          opts.progressUpdated(this.index, this.file, this.currentProgress);

          global_progress[this.global_progress_index] = this.currentProgress;
          globalProgress();

          var elapsed = new Date().getTime();
          var diffTime = elapsed - this.currentStart;
          if (diffTime >= opts.refresh) {
            var diffData = e.loaded - this.startData;
            var speed = diffData / diffTime; // KB per second
            opts.speedUpdated(this.index, this.file, speed);
            this.startData = e.loaded;
            this.currentStart = elapsed;
          }
        }
      }
    }

    function globalProgress() {
      if (global_progress.length === 0) {
        return;
      }

      var total = 0, index;
      for (index in global_progress) {
        if(global_progress.hasOwnProperty(index)) {
          total = total + global_progress[index];
        }
      }

      opts.globalProgressUpdated(Math.round(total / global_progress.length));
    }

    // Respond to an upload
    function upload() {
      stop_loop = false;

      if (!files) {
        opts.error(errors[0]);
        return false;
      }
      if (typeof Blob === "undefined") {
        opts.error(errors[0]);
        return false;
      }

      if (opts.allowedfiletypes.push && opts.allowedfiletypes.length) {
        for(var fileIndex = files.length;fileIndex--;) {
          if(!files[fileIndex].type || $.inArray(files[fileIndex].type, opts.allowedfiletypes) < 0) {
            opts.error(errors[3], files[fileIndex], fileIndex);
            return false;
          }
        }
      }

      if (opts.allowedfileextensions.push && opts.allowedfileextensions.length) {
        for(var fileIndex = files.length;fileIndex--;) {
          var allowedextension = false;
          for (i=0;i<opts.allowedfileextensions.length;i++){
            if (files[fileIndex].name.substr(files[fileIndex].name.length-opts.allowedfileextensions[i].length).toLowerCase()
                    == opts.allowedfileextensions[i].toLowerCase()
            ) {
              allowedextension = true;
            }
          }
          if (!allowedextension){
            opts.error(errors[8], files[fileIndex], fileIndex);
            return false;
          }
        }
      }

      var filesDone = 0,
          filesRejected = 0;

      if (files_count > opts.maxfiles && opts.queuefiles === 0) {
        opts.error(errors[1]);
        return false;
      }

      // Define queues to manage upload process
      var workQueue = [];
      var processingQueue = [];
      var doneQueue = [];

      // Add everything to the workQueue
      for (var i = 0; i < files_count; i++) {
        workQueue.push(i);
      }

      // Helper function to enable pause of processing to wait
      // for in process queue to complete
      var pause = function(timeout) {
        setTimeout(process, timeout);
        return;
      };

      // Process an upload, recursive
      var process = function() {

        var fileIndex;

        if (stop_loop) {
          return false;
        }

        // Check to see if are in queue mode
        if (opts.queuefiles > 0 && processingQueue.length >= opts.queuefiles) {
          return pause(opts.queuewait);
        } else {
          // Take first thing off work queue
          fileIndex = workQueue[0];
          workQueue.splice(0, 1);

          // Add to processing queue
          processingQueue.push(fileIndex);
        }

        try {
          if (beforeEach(files[fileIndex]) !== false) {
            if (fileIndex === files_count) {
              return;
            }
            var reader = new window.FileReader(),
                max_file_size = 1048576 * opts.maxfilesize;

            reader.index = fileIndex;
            if (files[fileIndex].size > max_file_size) {
              opts.error(errors[2], files[fileIndex], fileIndex);
              // Remove from queue
              processingQueue.forEach(function(value, key) {
                if (value === fileIndex) {
                  processingQueue.splice(key, 1);
                }
              });
              filesRejected++;
              return true;
            }

            reader.onerror = function(e) {
                switch(e.target.error.code) {
                    case e.target.error.NOT_FOUND_ERR:
                        opts.error(errors[4], files[fileIndex], fileIndex);
                        return false;
                    case e.target.error.NOT_READABLE_ERR:
                        opts.error(errors[5], files[fileIndex], fileIndex);
                        return false;
                    case e.target.error.ABORT_ERR:
                        opts.error(errors[6], files[fileIndex], fileIndex);
                        return false;
                    default:
                        opts.error(errors[7], files[fileIndex], fileIndex);
                        return false;
                };
            };

            reader.onloadend = function(e) {
              if (!opts.beforeSend
                  || false !== opts.beforeSend(files[fileIndex], fileIndex, e.target))
                return send(e);
            };

            reader.readAsArrayBuffer(files[fileIndex]);

          } else {
            filesRejected++;
          }
        } catch (err) {
          // Remove from queue
          processingQueue.forEach(function(value, key) {
            if (value === fileIndex) {
              processingQueue.splice(key, 1);
            }
          });
          opts.error(errors[0], files[fileIndex], fileIndex, err);
          return false;
        }

        // If we still have work to do,
        if (workQueue.length > 0) {
          process();
        }
      };

      var send = function(e) {

        var fileIndex = (e.srcElement || e.target).index;

        // Sometimes the index is not attached to the
        // event object. Find it by size. Hack for sure.
        if (e.target.index === undefined) {
          e.target.index = getIndexBySize(e.total);
        }

        var xhr = new XMLHttpRequest(),
            upload = xhr.upload,
            file = files[e.target.index],
            index = e.target.index,
            start_time = new Date().getTime(),
            boundary = '------multipartformboundary' + (new Date()).getTime(),
            global_progress_index = global_progress.length,
            builder,
            newName = rename(file.name),
            mime = file.type;

        if (opts.withCredentials) {
          xhr.withCredentials = opts.withCredentials;
        }

        var data = e.target.result;
        if (typeof newName === "string") {
          builder = getBuilder(newName, data, mime, boundary);
        } else {
          builder = getBuilder(file.name, data, mime, boundary);
        }

        upload.index = index;
        upload.file = file;
        upload.downloadStartTime = start_time;
        upload.currentStart = start_time;
        upload.currentProgress = 0;
        upload.global_progress_index = global_progress_index;
        upload.startData = 0;
        upload.addEventListener("progress", progress, false);

        // Allow url to be a method
        if (jQuery.isFunction(opts.url)) {
            xhr.open(opts.requestType, opts.url(), true);
        } else {
            xhr.open(opts.requestType, opts.url, true);
        }

        xhr.setRequestHeader('content-type', 'multipart/form-data; boundary=' + boundary);
        xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");

        // Add headers
        $.each(opts.headers, function(k, v) {
          xhr.setRequestHeader(k, v);
        });

        xhr.send(builder);

        global_progress[global_progress_index] = 0;
        globalProgress();

        opts.uploadStarted(index, file, files_count, xhr);

        var afterComplete = function(result) {
          filesDone++;

          // Remove from processing queue
          processingQueue.forEach(function(value, key) {
            if (value === fileIndex) {
              processingQueue.splice(key, 1);
            }
          });

          // Add to donequeue
          doneQueue.push(fileIndex);

          // Make sure the global progress is updated
          global_progress[global_progress_index] = 100;
          globalProgress();

          if (filesDone === (files_count - filesRejected)) {
            afterAll();
          }
          if (result === false) {
            stop_loop = true;
          }
        };

        xhr.onabort = afterComplete;
        xhr.onload = function() {
            var serverResponse = null;

            if (xhr.responseText) {
              try {
                serverResponse = jQuery.parseJSON(xhr.responseText);
              }
              catch (e) {
                serverResponse = xhr.responseText;
              }
            }

            var now = new Date().getTime(),
                timeDiff = now - start_time,
                result = opts.uploadFinished(index, file, serverResponse, timeDiff, xhr);

            afterComplete(result);

          // Pass any errors to the error option
          if (xhr.status < 200 || xhr.status > 299) {
            opts.error(xhr.statusText, file, fileIndex, xhr.status);
          }
        };
      };

      // Initiate the processing loop
      process();
    }

    function getIndexBySize(size) {
      for (var i = 0; i < files_count; i++) {
        if (files[i].size === size) {
          return i;
        }
      }

      return undefined;
    }

    function rename(name) {
      return opts.rename(name);
    }

    function beforeEach(file) {
      return opts.beforeEach(file);
    }

    function afterAll() {
      return opts.afterAll();
    }

    function dragEnter(e) {
      clearTimeout(doc_leave_timer);
      e.preventDefault();
      opts.dragEnter.call(this, e);
    }

    function dragOver(e) {
      clearTimeout(doc_leave_timer);
      e.preventDefault();
      opts.docOver.call(this, e);
      opts.dragOver.call(this, e);
    }

    function dragLeave(e) {
      clearTimeout(doc_leave_timer);
      opts.dragLeave.call(this, e);
      e.stopPropagation();
    }

    function docDrop(e) {
      e.preventDefault();
      opts.docLeave.call(this, e);
      return false;
    }

    function docEnter(e) {
      clearTimeout(doc_leave_timer);
      e.preventDefault();
      opts.docEnter.call(this, e);
      return false;
    }

    function docOver(e) {
      clearTimeout(doc_leave_timer);
      e.preventDefault();
      opts.docOver.call(this, e);
      return false;
    }

    function docLeave(e) {
      doc_leave_timer = setTimeout((function(_this) {
        return function() {
          opts.docLeave.call(_this, e);
        };
      })(this), 200);
    }

    return this;
  };

  function empty() {}

})(jQuery);

/* Generic draft support for osTicket. The plugins supports draft retrieval
 * automatically, along with draft autosave, and image uploading.
 *
 * Configuration:
 * draftNamespace: namespace for the draft retrieval
 * draftObjectId: extension to the namespace for draft retrieval
 *
 * Caveats:
 * Login (staff only currently) is required server-side for drafts and image
 * uploads. Furthermore, the id of the staff is considered for the drafts,
 * so one user will not retrieve drafts for another user.
 */
(function(R$) {
  // Monkey patch incorrect code in the inpection module
  var stockInspectorParser = $R[$R.env['class']]['inspector.parser'];
  R$.add('class', 'inspector.parser', $R.extend(stockInspectorParser.prototype, {
    _getClosestUpNode: function(selector)
    {
        var $el = this.$el.parents(selector, '.redactor-in-' + this.uuid).last();
        return ($el.length !== 0) ? $el.get() : false;
    },
    _getClosestNode: function(selector)
    {
        var $el = this.$el.closest(selector, '.redactor-in-' + this.uuid);
        return ($el.length !== 0) ? $el.get() : false;
    },
    _getClosestElement: function(selector)
    {
        var $el = this.$el.closest(selector, '.redactor-in-' + this.uuid);
        return ($el.length !== 0) ? $el : false;
    },
  }));

  R$.add('plugin', 'draft', {
    init: function (app) {
        this.app = app;
        this.$textarea = $(this.app.rootElement);
        this.toolbar = this.app.toolbar;
        this.opts = app.opts;
        this.lastUpdate = 0;
        this.statusbar = app.statusbar;
    },

    start: function() {
        if (!this.opts.draftNamespace)
            return;

        var autosave_url = 'ajax.php/draft/' + this.opts.draftNamespace;
        if (this.opts.draftObjectId)
            autosave_url += '.' + this.opts.draftObjectId;
        this.opts.autosave = this.autoCreateUrl = autosave_url;
        this.opts.autosaveDelay = 10000;
        if (this.opts.draftId) {
            this.statusbar.add('draft', __('all changes saved'));
            this._setup(this.opts.draftId);
        }
        else if (this.$textarea.hasClass('draft')) {
            // Just upload the file. A draft will be created automatically
            // and will be configured locally in the afterUpateDraft()
            this.opts.clipboardUpload =
            this.opts.imageUpload = this.autoCreateUrl + '/attach';
        }
        this.opts.autosaveData = {
            '__CSRFToken__': $("meta[name=csrf_token]").attr("content")
        };

        if (autosave_url)
            this.app.api('module.autosave.enable');

        if (this.app.source.getCode())
            this.app.broadcast('draft.recovered');
    },

    stop: function() {
        this.app.statusbar.remove('draft');
    },

    _setup: function (draft_id) {
        this.opts.draftId = draft_id;
        this.opts.autosave = 'ajax.php/draft/' + draft_id;
        this.opts.clipboardUpload =
        this.opts.imageUpload =
            'ajax.php/draft/' + draft_id + '/attach';

        // Add [Delete Draft] button to the toolbar
        if (this.opts.draftDelete) {
            this.opts.draftSave = true;
            var trash = this.deleteButton =
                this.toolbar.addButton('deletedraft', {
                    title: __('Delete Draft'),
                    api: 'plugin.draft.deleteDraft',
                    icon: 'icon-trash',
                });
            trash.addClass('pull-right icon-trash');

        }

        // Add [Save Draft] button to the toolbar
        if (this.opts.draftSave) {
            var save = this.saveButton =
                this.toolbar.addButton('savedraft', {
                    title: __('Save Draft'),
                    api: 'plugin.draft.saveDraft',
                    icon: 'icon-save',
                });
            save.addClass('pull-right icon-save');
        }
    },

    onautosave: function(name, _, data) {
        // If the draft was created, a draft_id will be sent back — update
        // the URL to send updates in the future
        if (!this.opts.draftId && data.draft_id) {
            this._setup(data.draft_id);
            $(this.app.rootElement).attr('data-draft-id', this.opts.draftId);
        }

        this.statusbar.add('draft', __('all changes saved'));
        this.app.broadcast('draft.saved');

    },

    onautosaveSend: function() {
        this.statusbar.add('draft', __('saving...'));
    },

    onautosaveError: function(error) {
        if (error.code == 422)
            // Unprocessable request (Empty message)
            return;

        this.displayError(error);
        // Cancel autosave
        this.app.api('module.autosave.disable');
        this.statusbar.add('draft', '<span style="color:red">{}</span>'.replace('{}', __('save error')));
        this.app.broadcast('draft.failed');
    },

    onimage: {
        uploaded: function(image, response) {
            this.onautosave(null, null, response);
        },
        uploadError: function (response) {
            this.displayError(response);
        }
    },

    displayError: function(json) {
        $.sysAlert(json.error,
            __('Unable to save draft.')
          + __('Refresh the current page to restore and continue your draft.'));
    },

    onchanged: function() {
        this.statusbar.add('draft', __('unsaved'));
    },

    showDraftSaved: function() {
        this.$draft_saved.show();
    },

    saveDraft: function() {
        if (!this.opts.draftId)
            return;

        response = $(".draft").val()
        if (response) {
            var data = {
                name: 'response',
                response: response,
            };

            var self = this;
            $.ajax('ajax.php/draft/'+this.opts.draftId, {
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function() {
                    self.draft_id = self.opts.draftId;
                    self.opts.autosave = self.autoCreateUrl;
                    self.app.statusbar.add('draft', __('all changes saved'));
                }
            });
        }
    },

    deleteDraft: function() {
        if (!this.opts.draftId)
            // Nothing to delete
            return;
        var self = this;
        $.ajax('ajax.php/draft/'+this.opts.draftId, {
            type: 'delete',
            success: function() {
                self.draft_id = self.opts.draftId = undefined;
                self.app.statusbar.remove('draft');
                self.app.source.setCode(self.opts.draftOriginal || '');
                self.opts.autosave = self.autoCreateUrl;
                self.opts.clipboardUpload =
                self.opts.imageUpload = self.autoCreateUrl + '/attach';
                self.deleteButton.hide();
                self.saveButton.hide();
                self.app.broadcast('draft.deleted');
            }
        });
    }
  });

  // Monkey patch the autosave module to include an `autosaveBefore` signal
  // and an delay option to limit calls to the backend.
  var stockAutosave = $R[$R.env['module']]['autosave'];
  R$.add('module', 'autosave', $R.extend(stockAutosave.prototype, {
    onsynced: function() {
        if (this.opts.autosave) {
            // Don't send to backend if empty
            if (!this.source.getCode())
                return;
            if (this.opts.autosaveDelay) {
                if (this.delayTimer)
                    clearInterval(this.delayTimer);
                this.delayTimer = setTimeout(this._sendDelayed.bind(this),
                    this.opts.autosaveDelay);
            }
            else {
                this._sendDelayed();
            }
        }
    },
    _sendDelayed: function() {
        this.app.broadcast('autosaveSend');
        this._send.call(this);
    },
  }));

  // Monkey patch the toolbar server to support adding buttons in an automatic
  // position based on the `buttons` setting
  var stockToolbar = $R[$R.env['service']]['toolbar'];
  R$.add('service', 'toolbar', $R.extend(stockToolbar.prototype, {
      addButtonAuto: function(name, btnObj) {
          var pos = this.opts.buttons.indexOf(name);

          if (pos === -1)
              return this.addButton(name, btnObj);
          if (pos === 0)
              return this.addButtonFirst(name, btnObj);
          return this.addButtonAfter(this.opts.buttons[pos - 1], name, btnObj);
      },
  }));

  R$.add('plugin', 'autolock', {
    init: function (app) {
        this.app = app;
    },
    start: function () {
        var root = $(this.app.rootElement),
            code = root.closest('form').find('[name=lockCode]');
        if (code.length)
            this.lock = root.closest('[data-lock-object-id]');
    },
    onchanged: function(e) {
        if (this.lock)
            this.lock.exclusive('acquire');
    }
  });

  R$.add('plugin', 'signature', {
    init: function (app) {
        this.app = app;
    },
    start: function() {
        var $el = $R.dom(this.app.rootElement),
            $box = this.app.editor.getElement(),
            inner = $R.dom('<div class="inner"></div>'),
            $form = $el.closest('form'),
            signatureField = $el.data('signature-field');
        if (signatureField) {
            this.$signatureBox = $R.dom('<div class="selected-signature"></div>')
                .append(inner);
            this.app.editor.getElement().parent().find('.redactor-statusbar').before(this.$signatureBox);
            if ($el.data('signature'))
                inner.html($el.data('signature'));
            else
                this.$signatureBox.hide();
            $R.dom('input[name='+signatureField+']', $form)
                .on('change', this.updateSignature.bind(this));
            // Expand on hover
            var outer = this.$signatureBox,
                inner = $('.inner', this.$signatureBox).get(0),
                originalHeight = outer.height(),
                hoverTimeout = undefined,
                originalShadow = this.$signatureBox.css('box-shadow');
            this.$signatureBox.on('hover', function() {
                hoverTimeout = setTimeout(function() {
                    originalHeight = Math.max(originalHeight, outer.height());
                    $(this).animate({
                        'height': inner.offsetHeight
                    }, 'fast');
                    $(this).css('box-shadow', 'none', 'important');
                }.bind(this), 250);
            }, function() {
                clearTimeout(hoverTimeout);
                $(this).stop().animate({
                    'height': Math.min(inner.offsetHeight, originalHeight)
                }, 'fast');
                $(this).css('box-shadow', originalShadow);
            });
            $el.find('.redactor-box').css('border-bottom-style', 'none', true);
        }
    },
    updateSignature: function(e) {
        var $el = $(this.app.rootElement),
            signatureField = $el.data('signature-field'),
            $form = $el.closest('form'),
            selected = $(':input:checked[name='+signatureField+']', $form).val(),
            type = $R.dom(e.target).val(),
            dept = $R.dom(':input[name='+$el.data('dept-field')+']', $form).val(),
            url = 'ajax.php/content/signature/',
            inner = $R.dom('.inner', this.$signatureBox);
        e.preventDefault && e.preventDefault();
        if (selected == 'dept' && $el.data('dept-id'))
            url += 'dept/' + $el.data('dept-id');
        else if (selected == 'dept' && $el.data('dept-field')) {
            if (dept)
                url += 'dept/' + dept;
            else
                return inner.empty().parent().hide();
        }
        else if (selected == 'theirs' && $el.data('poster-id')) {
            url += 'agent/' + $el.data('poster-id');
        }
        else if (type == 'none')
           return inner.empty().parent().hide();
        else
            url += selected;

        $R.ajax.get({
            url: url,
            success: function(html) {
                inner.html(html).parent().show();
            }
        });
    }
  });
})(Redactor);

/* Redactor richtext init */
$(function() {
    var captureImageSizes = function(html) {
        $('img', this.$box).each(function(i, img) {
            // TODO: Rewrite the entire <img> tag. Otherwise the @width
            // and @height attributes will begin to accumulate
            before = img.outerHTML;
            if (img.clientWidth && img.clientHeight)
                $(img).attr('width', img.clientWidth)
                      .attr('height',img.clientHeight);
            html = html.replace(before, img.outerHTML);
        });
        return html;
    },
    redact = $.fn.redact = function(el, options) {
        var el = $(el),
            sizes = {'small': '75px', 'medium': '150px', 'large': '225px'},
            selectedSize = sizes['medium'];
        $.each(sizes, function(k, v) {
            if (el.hasClass(k)) selectedSize = v;
        });
        var options = $.extend({
                'air': el.hasClass('no-bar'),
                'buttons': el.hasClass('no-bar')
                  ? ['format', '|', 'bold', 'italic', 'underline', 'deleted', 'lists', 'link', 'image']
                  : ['html', 'format', 'fontcolor', 'fontfamily', 'bold',
                    'italic', 'underline', 'deleted', 'lists', 'image', 'video',
                    'file', 'table', 'link', 'line', 'fullscreen'],
                'buttonSource': !el.hasClass('no-bar'),
                'autoresize': !el.hasClass('no-bar') && !el.closest('.dialog').length,
                'maxHeight': el.closest('.dialog').length ? selectedSize : false,
                'minHeight': selectedSize,
                'maxWidth': el.hasClass('fullscreen') ? '950px' : false,
                'focus': false,
                'plugins': el.hasClass('no-bar')
                  ? ['imagemanager','definedlinks']
                  : ['imagemanager','table','video','definedlinks','autolock', 'fontcolor', 'fontfamily'],
                'imageUpload': el.hasClass('draft'),
                'imageManagerJson': 'ajax.php/draft/images/browse',
                'imagePosition': true,
                'imageUploadData': {
                    '__CSRFToken__': $("meta[name=csrf_token]").attr("content")
                },
                'imageResizable': true,
                'syncBeforeCallback': captureImageSizes,
                'tabFocus': false,
                'toolbarFixed': true,
                'callbacks': {
                    'start': function() {
                        var $element = $R.dom(this.rootElement),
                            $editor = this.editor.$editor;
                        if ($element.data('width'))
                            $editor.width($element.data('width'));
                        $editor.addClass('no-pjax');
                        $editor.attr('spellcheck', 'true');
                        var lang = $element.closest('[lang]').attr('lang');
                        if (lang)
                            $editor.attr('lang', lang);
                        // Fixup class for
                        $element.parent().closest(':not(.redactor-box)').addClass('-redactor-container')
                    },
                },
                'linkSize': 100000,
                'definedlinks': 'ajax.php/config/links'
            }, options||{});
        if (el.data('redactor')) return;
        var reset = $('input[type=reset]', el.closest('form'));
        if (reset) {
            reset.click(function() {
                var file = $('.file', el.closest('form'));
                if (file)
                    file.remove();
                if (el.attr('data-draft-id')) {
                    el.redactor('plugin.draft.deleteDraft');
                    el.attr('data-draft-id', '');
                }
                else {
                    try {
                        el.redactor('source.setCode', '');
                    }
                    catch (error) {
                        el.redactor(); //reinitialize redactor
                        el.redactor('source.setCode', '');
                    }
                }
            });
        }
        if (!$.clientPortal) {
            options['plugins'].push('signature');
        }
        if (el.hasClass('draft')) {
            el.closest('form').append($('<input type="hidden" name="draft_id"/>'));
            options['plugins'].push('draft');
            options['plugins'].push('imageannotate');
            options.draftDelete = el.hasClass('draft-delete');
            options.draftSave = el.hasClass('draft-save');
        }
        if (true || 'scp') { // XXX: Add this to SCP only
            options['plugins'].push('contexttypeahead');
        }
        if (el.hasClass('fullscreen'))
            options['plugins'].push('fullscreen');
        if (el.data('translateTag'))
            options['plugins'].push('translatable');
        if ($('#thread-items[data-thread-id]').length)
            options['imageManagerJson'] += '?threadId=' + $('#thread-items').data('threadId');
        getConfig().then(function(c) {
            if (c.lang && c.lang.toLowerCase() != 'en_us' &&
                    Redactor.lang[c.short_lang])
                options['lang'] = c.short_lang;
            if (c.has_rtl)
                options['plugins'].push('textdirection');
            if (el.find('rtl').length)
                options['direction'] = 'rtl';
            el.data('redactor', el.redactor(options));
        });
    },
    findRichtextBoxes = function() {
        $('.richtext').each(function(i,el) {
            if ($(el).hasClass('ifhtml'))
                // Check if html_thread is enabled first
                getConfig().then(function(c) {
                    if (c.html_thread)
                        redact(el);
                });
            else
                // Make a rich text editor immediately
                redact(el);
        });
    },
    cleanupRedactorElements = function() {
        // Tear down redactor editors on this page
        $('.richtext').each(function() {
            var redactor = $(this).data('redactor');
            if (redactor)
                redactor.stop();
        });
    };
    findRichtextBoxes();
    $(document).ajaxStop(findRichtextBoxes);
    $(document).on('pjax:start', cleanupRedactorElements);
});

$(document).on('focusout.redactor', 'div.redactor_richtext', function (e) {
    alert('focusout.redactor');
    $(this).siblings('textarea').trigger('change');
});

$(document).ajaxError(function(event, request, settings) {
    if (settings.url.indexOf('ajax.php/draft') != -1
            && settings.type.toUpperCase() == 'POST') {
        $('.richtext').each(function() {
            var redactor = $(this).data('redactor');
            if (redactor) {
                redactor.autosave.disable();
            }
        });
        $.sysAlert(__('Unable to save draft.'),
            __('Refresh the current page to restore and continue your draft.'));
    }
});

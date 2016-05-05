if (typeof RedactorPlugins === 'undefined') var RedactorPlugins = {};

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
RedactorPlugins.draft = function() {
  return {
    init: function() {
        if (!this.opts.draftNamespace)
            return;

        this.opts.changeCallback = this.draft.hideDraftSaved;
        var autosave_url = 'ajax.php/draft/' + this.opts.draftNamespace;
        if (this.opts.draftObjectId)
            autosave_url += '.' + this.opts.draftObjectId;
        this.opts.autosave = this.opts.autoCreateUrl = autosave_url;
        this.opts.autosaveInterval = 30;
        this.opts.autosaveCallback = this.draft.afterUpdateDraft;
        this.opts.autosaveErrorCallback = this.draft.autosaveFailed;
        this.opts.imageUploadErrorCallback = this.draft.displayError;
        if (this.opts.draftId) {
            this.opts.autosave = 'ajax.php/draft/'+this.opts.draftId;
            this.opts.clipboardUploadUrl =
            this.opts.imageUpload =
                'ajax.php/draft/'+this.opts.draftId+'/attach';
        }
        else if (this.$textarea.hasClass('draft')) {
            // Just upload the file. A draft will be created automatically
            // and will be configured locally in the afterUpateDraft()
            this.opts.clipboardUploadUrl =
            this.opts.imageUpload = this.opts.autoCreateUrl + '/attach';
            this.opts.imageUploadCallback = this.afterUpdateDraft;
        }

        // FIXME: Monkey patch Redactor's autosave enable method to disable first
        var oldAse = this.autosave.enable;
        this.autosave.enable = function() {
            this.autosave.disable();
            oldAse.call(this);
        }.bind(this);

        if (autosave_url)
            this.autosave.enable();

        if (this.$textarea.hasClass('draft')) {
            this.$draft_saved = $('<span>')
                .addClass("pull-right draft-saved")
                .hide()
                .append($('<span>')
                    .text(__('Draft Saved')));
            // Float the [Draft Saved] box with the toolbar
            this.$toolbar.append(this.$draft_saved);

            // Add [Delete Draft] button to the toolbar
            if (this.opts.draftDelete) {
                var trash = this.draft.deleteButton =
                    this.button.add('deleteDraft', __('Delete Draft'))
                this.button.addCallback(trash, this.draft.deleteDraft);
                this.button.setAwesome('deleteDraft', 'icon-trash');
                trash.parent().addClass('pull-right');
                trash.addClass('delete-draft');
                if (!this.opts.draftId)
                    trash.hide();
            }
        }
        if (this.code.get())
            this.$box.trigger('draft:recovered');
    },
    afterUpdateDraft: function(name, data) {
        // If the draft was created, a draft_id will be sent back — update
        // the URL to send updates in the future
        if (!this.opts.draftId && data.draft_id) {
            this.opts.draftId = data.draft_id;
            this.opts.autosave = 'ajax.php/draft/' + data.draft_id;
            this.opts.clipboardUploadUrl =
            this.opts.imageUpload =
                'ajax.php/draft/'+this.opts.draftId+'/attach';
            if (!this.code.get())
                this.code.set(' ', false);
        }
        // Only show the [Draft Saved] notice if there is content in the
        // field that has been touched
        if (!this.draft.firstSave) {
            this.draft.firstSave = true;
            // No change yet — dont't show the button
            return;
        }
        if (data && this.code.get()) {
            this.$draft_saved.show().delay(5000).fadeOut();
        }
        // Show the button if there is a draft to delete
        if (this.opts.draftId && this.opts.draftDelete)
            this.draft.deleteButton.show();
        this.$box.trigger('draft:saved');
    },
    autosaveFailed: function(error) {
        if (error.code == 422)
            // Unprocessable request (Empty message)
            return;

        this.draft.displayError(error);
        // Cancel autosave
        this.autosave.disable();
        this.hideDraftSaved();
        this.$box.trigger('draft:failed');
    },

    displayError: function(json) {
        $.sysAlert(json.error,
            __('Unable to save draft.')
          + __('Refresh the current page to restore and continue your draft.'));
    },

    hideDraftSaved: function() {
        this.$draft_saved.hide();
    },

    deleteDraft: function() {
        if (!this.opts.draftId)
            // Nothing to delete
            return;
        var self = this;
        $.ajax('ajax.php/draft/'+this.opts.draftId, {
            type: 'delete',
            async: false,
            success: function() {
                self.draft_id = self.opts.draftId = undefined;
                self.draft.hideDraftSaved();
                self.code.set(self.opts.draftOriginal || '', false, false);
                self.opts.autosave = self.opts.autoCreateUrl;
                self.draft.deleteButton.hide();
                self.draft.firstSave = false;
                self.$box.trigger('draft:deleted');
            }
        });
    }
  };
};

RedactorPlugins.autolock = function() {
  return {
    init: function() {
      var code = this.$box.closest('form').find('[name=lockCode]'),
          self = this;
      if (code.length)
        this.opts.keydownCallback = function(e) {
          self.$box.closest('[data-lock-object-id]').exclusive('acquire');
        };
    }
  };
}

RedactorPlugins.signature = function() {
  return {
    init: function() {
        var $el = $(this.$element.get(0)),
            inner = $('<div class="inner"></div>');
        if ($el.data('signatureField')) {
            this.$signatureBox = $('<div class="selected-signature"></div>')
                .append(inner)
                .appendTo(this.$box);
            if ($el.data('signature'))
                inner.html($el.data('signature'));
            else
                this.$signatureBox.hide();
            $('input[name='+$el.data('signatureField')+']', $el.closest('form'))
                .on('change', false, false, $.proxy(this.signature.updateSignature, this));
            if ($el.data('deptField'))
                $(':input[name='+$el.data('deptField')+']', $el.closest('form'))
                    .on('change', false, false, $.proxy(this.signature.updateSignature, this));
            // Expand on hover
            var outer = this.$signatureBox,
                inner = $('.inner', this.$signatureBox).get(0),
                originalHeight = outer.height(),
                hoverTimeout = undefined,
                originalShadow = this.$signatureBox.css('box-shadow');
            this.$signatureBox.hover(function() {
                hoverTimeout = setTimeout($.proxy(function() {
                    originalHeight = Math.max(originalHeight, outer.height());
                    $(this).animate({
                        'height': inner.offsetHeight
                    }, 'fast');
                    $(this).css('box-shadow', 'none', 'important');
                }, this), 250);
            }, function() {
                clearTimeout(hoverTimeout);
                $(this).stop().animate({
                    'height': Math.min(inner.offsetHeight, originalHeight)
                }, 'fast');
                $(this).css('box-shadow', originalShadow);
            });
            this.$box.find('.redactor_editor').css('border-bottom-style', 'none', true);
        }
    },
    updateSignature: function(e) {
        var $el = $(this.$element.get(0));
            selected = $(':input:checked[name='+$el.data('signatureField')+']', $el.closest('form')).val(),
            type = $(e.target).val(),
            dept = $(':input[name='+$el.data('deptField')+']', $el.closest('form')).val(),
            url = 'ajax.php/content/signature/',
            inner = $('.inner', this.$signatureBox);
        e.preventDefault && e.preventDefault();
        if (selected == 'dept' && $el.data('deptId'))
            url += 'dept/' + $el.data('deptId');
        else if (selected == 'dept' && $el.data('deptField')) {
            if (dept)
                url += 'dept/' + dept;
            else
                return inner.empty().parent().hide();
        }
        else if (selected == 'theirs' && $el.data('posterId')) {
            url += 'agent/' + $el.data('posterId');
        }
        else if (type == 'none')
           return inner.empty().parent().hide();
        else
            url += selected;

        inner.load(url).parent().show();
    }
  }
};

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
    redact = $.redact = function(el, options) {
        var el = $(el),
            sizes = {'small': 75, 'medium': 150, 'large': 225},
            selectedSize = sizes['medium'];
        $.each(sizes, function(k, v) {
            if (el.hasClass(k)) selectedSize = v;
        });
        var options = $.extend({
                'air': el.hasClass('no-bar'),
                'buttons': el.hasClass('no-bar')
                  ? ['formatting', '|', 'bold', 'italic', 'underline', 'deleted', '|', 'unorderedlist', 'orderedlist', 'outdent', 'indent', '|', 'link', 'image']
                  : ['html', '|', 'formatting', '|', 'bold',
                    'italic', 'underline', 'deleted', '|', 'unorderedlist',
                    'orderedlist', 'outdent', 'indent', '|', 'image', 'video',
                    'file', 'table', 'link', '|', 'alignment', '|',
                    'horizontalrule'],
                'buttonSource': !el.hasClass('no-bar'),
                'autoresize': !el.hasClass('no-bar') && !el.closest('.dialog').length,
                'maxHeight': el.closest('.dialog').length ? selectedSize : false,
                'minHeight': selectedSize,
                'focus': false,
                'plugins': el.hasClass('no-bar')
                  ? ['imagemanager','definedlinks']
                  : ['imagemanager','imageannotate','table','video','definedlinks','autolock'],
                'imageUpload': el.hasClass('draft'),
                'imageManagerJson': 'ajax.php/draft/images/browse',
                'syncBeforeCallback': captureImageSizes,
                'linebreaks': true,
                'tabFocus': false,
                'toolbarFixedBox': true,
                'focusCallback': function() { this.$box.addClass('no-pjax'); },
                'initCallback': function() {
                    if (this.$element.data('width'))
                        this.$editor.width(this.$element.data('width'));
                    this.$editor.attr('spellcheck', 'true');
                    var lang = this.$editor.closest('[lang]').attr('lang');
                    if (lang)
                        this.$editor.attr('lang', lang);
                },
                'linkSize': 100000,
                'definedLinks': 'ajax.php/config/links'
            }, options||{});
        if (el.data('redactor')) return;
        var reset = $('input[type=reset]', el.closest('form'));
        if (reset) {
            reset.click(function() {
                if (el.hasClass('draft'))
                    el.redactor('deleteDraft');
                else
                    el.redactor('set', '', false, false);
            });
        }
        $('input[type=submit]', el.closest('form')).on('click', function() {
            // Some setups (IE v10 on Windows 7 at least) seem to have a bug
            // where Redactor does not sync properly after adding an image.
            // Therefore, the ::get() call will not include text added after
            // the image was inserted.
            el.redactor('code.sync');
        });
        if (!$.clientPortal) {
            options['plugins'] = options['plugins'].concat(
                    'fontcolor', 'fontfamily', 'signature');
        }
        if (el.hasClass('draft')) {
            el.closest('form').append($('<input type="hidden" name="draft_id"/>'));
            options['plugins'].push('draft');
            options['plugins'].push('imagepaste');
            options.draftDelete = el.hasClass('draft-delete');
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
                    $.Redactor.opts.langs[c.short_lang])
                options['lang'] = c.short_lang;
            //if (c.has_rtl)
              //  options['plugins'].push('textdirection');
            if (el.find('rtl').length)
                options['direction'] = 'rtl';
            el.redactor(options);
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
                redactor.core.destroy();
        });
    };
    findRichtextBoxes();
    $(document).ajaxStop(findRichtextBoxes);
    $(document).on('pjax:success', findRichtextBoxes);
    $(document).on('pjax:start', cleanupRedactorElements);
});

$(document).on('focusout.redactor', 'div.redactor_richtext', function (e) {
    $(this).siblings('textarea').trigger('change');
});

$(document).ajaxError(function(event, request, settings) {
    if (settings.url.indexOf('ajax.php/draft') != -1
            && settings.type.toUpperCase() == 'POST') {
        $('.richtext').each(function() {
            var redactor = $(this).data('redactor');
            if (redactor) {
                redactor.autosave.disable();
                clearInterval(redactor.autosaveInterval);
            }
        });
        $.sysAlert(__('Unable to save draft.'),
            __('Refresh the current page to restore and continue your draft.'));
    }
});

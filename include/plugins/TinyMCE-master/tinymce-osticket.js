//Override redactor language
$.Redactor = new Array();
$.Redactor.opts = new Array();
$.Redactor.opts.langs = new Array();

tinymce.PluginManager.add('cannedresponses', function(editor, url) {
    editor.on('init', function(e){
        if(editor.id == "response"){
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
                        if(canned.response) {
                            editor.setContent(editor.getContent() + canned.response);
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
        }
    });
    
    return {
        getMetadata: function () {
            return  {
                name: "osTicket canned responses",
                url: "https://github.com/Micke1101/OSTicket-plugin-TinyMCE"
            };
        }
    };
});

tinymce.PluginManager.add('closeextras', function(editor, url) {
    editor.on('init', function(e){
        editor.notificationManager.open=(function(){
            var original = editor.notificationManager.open;
            return function() {
                if(!$(editor.editorContainer).is(':visible'))
                    return function() {
                        // Events not supported.
                        return undefined;
                    };
                return original.apply(this,arguments);
            };
        })();
    });
    
    return {
        getMetadata: function () {
            return  {
                name: "osTicket prevent extra notifications",
                url: "https://github.com/Micke1101/OSTicket-plugin-TinyMCE"
            };
        }
    };
});

tinymce.PluginManager.add('embedvideo', function(editor, url) {
    var reUrlYoutube = /https?:\/\/(?:[0-9A-Z-]+\.)?(?:youtu\.be\/|youtube\.com\S*[^\w\-\s])([\w\-]{11})(?=[^\w\-]|$)(?![?=&+%\w.-]*(?:['"][^<>]*>|<\/a>))[?=&+%\w.-]*/ig,
    reUrlVimeo = /https?:\/\/(www\.)?vimeo.com\/(\d+)($|\/)/;
    editor.on('PastePreProcess', function(e){
        var iframeStart = '<iframe src="',
            iframeEnd = '" frameborder="0" allowfullscreen></iframe>';

        if (e.content.match(reUrlYoutube))
        {
            e.content = e.content.replace(reUrlYoutube, iframeStart + '//www.youtube.com/embed/$1' + iframeEnd);
        }
        else if (e.content.match(reUrlVimeo))
        {
            e.content = e.content.replace(reUrlVimeo, iframeStart + '//player.vimeo.com/video/$2' + iframeEnd);
        }
    });
    
    return {
        getMetadata: function () {
            return  {
                name: "osTicket embed video",
                url: "https://github.com/Micke1101/OSTicket-plugin-TinyMCE"
            };
        }
    };
});

tinymce.PluginManager.add('autolock', function(editor, url) {
    var code = $(editor.getElement()).closest('form').find('[name=lockCode]');
    if(code.length)
        editor.on('keydown', function(e){
            $(editor.getElement().closest('[data-lock-object-id]')).exclusive('acquire');
        });
    
    return {
        getMetadata: function () {
            return  {
                name: "osTicket autolock",
                url: "https://github.com/Micke1101/OSTicket-plugin-TinyMCE"
            };
        }
    };
});

tinymce.PluginManager.add('signature', function(editor, url) {
    var signature = '';
    editor.on('init', function(e){
        var $el = $($(editor.getElement()).get(0)),
            inner = $('<div class="inner"></div>');
        if ($el.data('signatureField')) {
            $signatureBox = $('<div class="selected-signature"></div>')
                .append(inner)
                .appendTo(editor.editorContainer);
            if ($el.data('signature'))
                inner.html($el.data('signature'));
            else
                $signatureBox.hide();
            $('input[name='+$el.data('signatureField')+']', $el.closest('form'))
                .on('change', false, false, $.proxy(updateSignature, this));
            if ($el.data('deptField'))
                $(':input[name='+$el.data('deptField')+']', $el.closest('form'))
                    .on('change', false, false, $.proxy(updateSignature, this));
            // Expand on hover
            var outer = $signatureBox,
                inner = $('.inner', $signatureBox).get(0),
                originalHeight = outer.height(),
                hoverTimeout = undefined,
                originalShadow = $signatureBox.css('box-shadow');
            $signatureBox.hover(function() {
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
        }
    });
    
    editor.on('SaveContent', function(e){
        e.content = e.content + signature;
    });
    
    function updateSignature(e) {
        var $el = $($(editor.getElement()).get(0));
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
        else if (type == 'none'){
            signature = inner.html();
            return inner.empty().parent().hide();
        }
        else
            url += selected;

        inner.load(url, function() {
            signature = inner.html();
        }).parent().show();
    }
    
    return {
        getMetadata: function () {
            return  {
                name: "osTicket signature",
                url: "https://github.com/Micke1101/OSTicket-plugin-TinyMCE"
            };
        }
    };
});

tinymce.PluginManager.add('contexttypeahead', function(editor, url) {
    // Add a button that opens a window
    var typeahead = false,
        context = false,
        variables = false;
    
    function watch(e) {
        var allText = editor.selection.getRng().commonAncestorContainer.data,
            offset = editor.selection.getRng().endOffset,
            lhs = (allText) ? allText.substring(0, offset) : '',
            search = new RegExp(/%\{([^}]*)$/),
            //search2 = new RegExp(/@((\s|\w|\.)*)$/),
            match/*,
            match2*/;

        if (!lhs) {
            return !e.isDefaultPrevented();
        }
        if (e.which == 27 || !(match = search.exec(lhs))/* && !(match2 = search2.exec(lhs))*/){
            // No longer in a element — close typeahead
            return destroy();
        }
        /*if(match2)
            match = match2;*/

        if (e.type == 'click')
            return;

        // Locate the position of the cursor and the number of characters back
        // to the `%{` symbols
        var sel         = editor.selection,
            range       = sel.getRng(),
            clientRects = range.getClientRects(),
            position    = clientRects[0],
            editorPosition = editor.contentWindow.frameElement.getClientRects()[0],
            backText    = match[1],
            parent      = sel.getNode().parentElement || this.editor,
            plugin      = this;

        // Insert a hidden text input to receive the typed text and add a
        // typeahead widget
        if (!this.typeahead) {
            this.typeahead = $('<input type="text">')
                .css({position: 'absolute', visibility: 'hidden'})
                .width(0).height(position.height - 4)
                .appendTo(document.body)
                .typeahead({
                    property: 'variable',
                    minLength: 0,
                    arrow: $('<span class="pull-right"><i class="icon-muted icon-chevron-right"></i></span>')
                    .css('padding', '0 0 0 6px'),
                    highlighter: function(variable, item) {
                        var base = $.fn.typeahead.Constructor.prototype.highlighter
                            .call(this, variable),
                            further = new RegExp(variable + '\\.'),
                            extendable = Object.keys(plugin.variables).some(function(v) {
                                return v.match(further);
                            }),
                            arrow = extendable ? this.options.arrow.clone() : '';
                        return $('<div/>').html(base).prepend(arrow).html()
                            + $('<span class="faded">')
                            .text(' — ' + item.desc)
                            .wrap('<div>').parent().html();
                    },
                    item: '<li><a href="#" style="display:block"></a></li>',
                    source: getContext.bind(this),
                    sorter: function(items) {
                        items.sort(
                            function(a,b) {return a.variable > b.variable ? 1 : -1;}
                        );
                        return items;
                    },
                    matcher: function(item) {
                        if (item.toLowerCase().indexOf(this.query.toLowerCase()) !== 0)
                            return false;
                        return (this.query.match(/\./g) || []).length == (item.match(/\./g) || []).length;
                    },
                    onselect: select.bind(this),
                    scroll: true,
                    items: 100
                });
        }

        if (position) {
            var width = textWidth(
                backText,
                editor.selection.getNode().parentElement || $('<div class="redactor-editor">')
            ),
            pleft = $(parent).offset().left,
            left = editorPosition.left + position.left - width;

            if (left < pleft)
                // This is a bug in chrome, but I'm not sure how to adjust it
                left += pleft;

            plugin.typeahead
                .css({top: editorPosition.top + position.top + $(window).scrollTop(), left: left});
        }

        plugin.typeahead
            .val(backText)
            .trigger(e);

        return !e.isDefaultPrevented();
    }

    function getContext(typeahead, query) {
        var dfd, that=this,
            root = editor.getElement().dataset.rootContext;
        if (!this.context) {
            dfd = $.Deferred();
            $.ajax('ajax.php/content/context', {
                data: {root: root},
                success: function(json) {
                    var items = $.map(json, function(v,k) {
                        return {variable: k, desc: v};
                    });
                    that.variables = json;
                    dfd.resolve(items);
                }
            });
            this.context = dfd;
        }
        // Only fetch the context once for this redactor box
        this.context.then(function(items) {
            typeahead.process(items);
        });
    }

    function textWidth(text, clone) {
        var c = $(clone),
            o = c.clone().text(text)
            .css({'position': 'absolute', 'float': 'left', 'white-space': 'nowrap', 'visibility': 'hidden'})
            .css({'font-family': c.css('font-family'), 'font-weight': c.css('font-weight'),
            'font-size': c.css('font-size')})
            .appendTo($('body')),
            w = o.width();

        o.remove();

        return w;
    }

    function destroy() {
        if (this.typeahead) {
            this.typeahead.typeahead('hide');
            this.typeahead.remove();
            this.typeahead = false;
        }
    }

    function select(item, event) {
        // Collapse multiple textNodes together
        (editor.getDoc().body).normalize();
        var current = editor.selection.getRng().commonAncestorContainer,
            range   = editor.selection.getRng(),
            cursorAt = editor.selection.getRng().endOffset,
            // TODO: Consume immediately following `}` symbols
            plugin  = this,
            search  = new RegExp(/%\{([^}]*)(\}?)$/)/*,
            search2 = new RegExp(/@.*$/)*/;

        // FIXME: ENTER will end up here, but current will be empty

        if (!current)
            return;

        // Set cursor at the end of the expanded text
        var left = current.textContent.substring(0, cursorAt),
            right = current.textContent.substring(cursorAt),
            autoExpand = event.target.nodeName == 'I',
            selected = item.variable + (autoExpand ? '.' : ''),
            newLeft;
        if(left.match(search)) {
            newLeft = left.replace(search, '%{' + selected + '}');
            current.textContent = newLeft
            // Drop the remaining part of a variable block, if any
            + right.replace(/[^%}]*?[%}]/, '');
        }/* else {
            newLeft = left.replace(search2, '@' + selected + ';');
            current.textContent = newLeft;
            editor.selection.getNode().innerHTML = editor.selection.getNode().innerHTML.replace('@' + selected + ';', '<a href="#" class="staff" data-id="' + selected + '">@' + selected + '</a>');
        }*/

        range.setStart(current, newLeft.length - 1);
        range.setEnd(current, newLeft.length - 1);
        if (!autoExpand)
            return destroy();

        this.typeahead.val(selected);
        this.typeahead.typeahead('lookup');
        return false;
    }
        
    editor.on('click', function(e){
        watch(e);
    });
    editor.on('keyup', function(e){
        watch(e);
    });
    editor.on('keydown', function(e){
        watch(e);
    });

    return {
        getMetadata: function () {
            return  {
                name: "osTicket context typeahead",
                url: "https://github.com/Micke1101/OSTicket-plugin-TinyMCE"
            };
        }
    };
});

$(function() {
    findRichtextBoxes = function() {
        $('.richtext').each(function(i,el) {
            if ($(el).hasClass('ifhtml'))
                // Check if html_thread is enabled first
                getConfig().then(function(c) {
                    if (c.html_thread)
                        tiny(el);
                });
            else
                // Make a rich text editor immediately
                tiny(el);
        });
    },
    cleanupTinyMCEElements = function() {
        tinymce.remove();
    };
    findRichtextBoxes();
    $(document).ajaxStop(findRichtextBoxes);
    $(document).on('pjax:success', findRichtextBoxes);
    $(document).on('pjax:start', cleanupTinyMCEElements);
});
$(document).ready(function() {
	$(document).off('click.note', '.quicknote .action.edit-note');
	$(document).off('click.note', '.quicknote .action.cancel-edit');
	$(document).off('click.note', '.quicknote .action.save-note');
	$(document).off('click', '#new-note');
	$(document).on('click.note', '.quicknote .action.edit-note', function() {
		var note = $(this).closest('.quicknote'),
			body = note.find('.body'),
			T = $('<textarea>').text(body.html());
		if (note.closest('.dialog, .tip_box').length)
			T.addClass('no-bar small');
		body.replaceWith(T);
		$.tiny(T.get(0));
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
			try { tinymce.get(T[0].id).remove(); } catch (e) {}
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
		{ note: tinymce.get(T[0].id).getContent() },
		function(html) {
			var body = $('<div class="body">').html(html);
			try { tinymce.get(T[0].id).remove(); } catch (e) {}
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
	$(document).on('click', '#new-note', function() {
		var note = $(this).closest('.quicknote'),
			T = $('<textarea>'),
			button = $('<input type="button">').val(__('Create'));
		button.click(function() {
			$.post('ajax.php/' + note.data('url'),
				{ note: tinymce.get(T[0].id).getContent(), no_options: note.hasClass('no-options') },
				function(response) {
					tinymce.get(T[0].id).remove();
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
		$.tiny(T.get(0));
		return false;
	});
});
$(document).ajaxError(function(event, request, settings) {
    $.sysAlert(__('Ajax error.'),
        __('An ajax error occured.'));
});

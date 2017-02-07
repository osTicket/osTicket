if (!RedactorPlugins) var RedactorPlugins = {};

(function($)
{
    $.Redactor.prototype.definedlinks = function()
    {
        return {
            init: function()
            {
                if (!this.opts.definedLinks) return;

                this.modal.addCallback('link', $.proxy(this.definedlinks.load, this));

            },
            load: function()
            {
                var $select = $('<select id="redactor-defined-links" />');
                $('#redactor-modal-link-insert').prepend($select);

                this.definedlinks.storage = {};

                $.getJSON(this.opts.definedLinks, $.proxy(function(data)
                {
                    $.each(data, $.proxy(function(key, val)
                    {
                        this.definedlinks.storage[key] = val;
                        $select.append($('<option>').val(key).html(val.name));

                    }, this));

                    $select.on('change', $.proxy(this.definedlinks.select, this));

                }, this));

            },
            select: function(e)
            {
                var key = $(e.target).val();
                var name = '', url = '';
                if (key !== 0)
                {
                    name = this.definedlinks.storage[key].name;
                    url = this.definedlinks.storage[key].url;
                }

                $('#redactor-link-url').val(url);

                var $el = $('#redactor-link-url-text');
                if ($el.val() === '') $el.val(name);
            }
        };
    };
})(jQuery);

RedactorPlugins.fontcolor = function()
{
	return {
		init: function()
		{
			var colors = [
				'#ffffff', '#000000', '#eeece1', '#1f497d', '#4f81bd', '#c0504d', '#9bbb59', '#8064a2', '#4bacc6', '#f79646', '#ffff00',
				'#f2f2f2', '#7f7f7f', '#ddd9c3', '#c6d9f0', '#dbe5f1', '#f2dcdb', '#ebf1dd', '#e5e0ec', '#dbeef3', '#fdeada', '#fff2ca',
				'#d8d8d8', '#595959', '#c4bd97', '#8db3e2', '#b8cce4', '#e5b9b7', '#d7e3bc', '#ccc1d9', '#b7dde8', '#fbd5b5', '#ffe694',
				'#bfbfbf', '#3f3f3f', '#938953', '#548dd4', '#95b3d7', '#d99694', '#c3d69b', '#b2a2c7', '#b7dde8', '#fac08f', '#f2c314',
				'#a5a5a5', '#262626', '#494429', '#17365d', '#366092', '#953734', '#76923c', '#5f497a', '#92cddc', '#e36c09', '#c09100',
				'#7f7f7f', '#0c0c0c', '#1d1b10', '#0f243e', '#244061', '#632423', '#4f6128', '#3f3151', '#31859b',  '#974806', '#7f6000'
			];

			var buttons = ['fontcolor', 'backcolor'];

			for (var i = 0; i < 2; i++)
			{
				var name = buttons[i];

				var button = this.button.addBefore('deleted', name, this.lang.get(name));
				var $dropdown = this.button.addDropdown(button);

				$dropdown.width(242);
				this.fontcolor.buildPicker($dropdown, name, colors);

			}
		},
		buildPicker: function($dropdown, name, colors)
		{
			var rule = (name == 'backcolor') ? 'background-color' : 'color';

			var len = colors.length;
			var self = this;
			var func = function(e)
			{
				e.preventDefault();
                var $this = $(e.target);
				self.fontcolor.set(rule, $this.attr('rel'));
			};
            $dropdown.on('click', 'a.redactor.color-swatch', func);

            var template = $('<a class="redactor color-swatch" href="#"></a>');

			for (var z = 0; z < len; z++)
			{
				var color = colors[z];
				var $swatch = template.clone().attr('rel', color);
				$swatch.css('background-color', color);
				$dropdown.append($swatch);
			}

			var $elNone = $('<a href="#" style="redactor uncolor"></a>').html(this.lang.get('none'));
			$elNone.on('click', $.proxy(function(e)
			{
				e.preventDefault();
				this.fontcolor.remove(rule);

			}, this));

			$dropdown.append($elNone);
		},
		set: function(rule, type)
		{
			this.inline.format('span', 'style', rule + ': ' + type + ';');
		},
		remove: function(rule)
		{
			this.inline.removeStyleRule(rule);
		}
	};
};

RedactorPlugins.fontfamily = function()
{
	return {
		init: function ()
		{
			var fonts = [ 'Arial', 'Helvetica', 'Georgia', 'Times New Roman', 'Monospace' ];
			var that = this;
			var dropdown = {};

			$.each(fonts, function(i, s)
			{
				dropdown['s' + i] = { title: '<span style="font-family:' + s.toLowerCase() + ';">' +
                    s + '</span>', func: function() { that.fontfamily.set(s); }};
			});

			dropdown.remove = { title: __('Remove Font Family'), func: that.fontfamily.reset };

			var button = this.button.addBefore('bold', 'fontfamily', __('Change Font Family'));
			this.button.addDropdown(button, dropdown);

		},
		set: function (value)
		{
			this.inline.format('span', 'style', 'font-family:' + value + ';');
		},
		reset: function()
		{
			this.inline.removeStyleRule('font-family');
		}
	};
};

RedactorPlugins.fullscreen = function()
{
	return {
		init: function()
		{
			this.fullscreen.isOpen = false;

			var button = this.button.add('fullscreen', 'Fullscreen');
			this.button.addCallback(button, this.fullscreen.toggle);

			if (this.opts.fullscreen) this.fullscreen.toggle();
		},
		enable: function()
		{
			this.button.changeIcon('fullscreen', 'normalscreen');
			this.button.setActive('fullscreen');
			this.fullscreen.isOpen = true;

			if (this.opts.toolbarExternal)
			{
				this.fullscreen.toolcss = {};
				this.fullscreen.boxcss = {};
				this.fullscreen.toolcss.width = this.$toolbar.css('width');
				this.fullscreen.toolcss.top = this.$toolbar.css('top');
				this.fullscreen.toolcss.position = this.$toolbar.css('position');
				this.fullscreen.boxcss.top = this.$box.css('top');
			}

			this.fullscreen.height = this.$editor.height();

			if (this.opts.maxHeight) this.$editor.css('max-height', '');
			if (this.opts.minHeight) this.$editor.css('min-height', '');

			if (!this.$fullscreenPlaceholder) this.$fullscreenPlaceholder = $('<div/>');
			this.$fullscreenPlaceholder.insertAfter(this.$box);

			this.$box.appendTo(document.body);

			this.$box.addClass('redactor-box-fullscreen');
			$('body, html').css('overflow', 'hidden');

			this.fullscreen.resize();
			$(window).on('resize.redactor.fullscreen', $.proxy(this.fullscreen.resize, this));
			$(document).scrollTop(0, 0);

			this.$editor.focus();
			this.observe.load();
		},
		disable: function()
		{
			this.button.removeIcon('fullscreen', 'normalscreen');
			this.button.setInactive('fullscreen');
			this.fullscreen.isOpen = false;

			$(window).off('resize.redactor.fullscreen');
			$('body, html').css('overflow', '');

			this.$box.insertBefore(this.$fullscreenPlaceholder);
			this.$fullscreenPlaceholder.remove();

			this.$box.removeClass('redactor-box-fullscreen').css({ width: 'auto', height: 'auto' });

			this.code.sync();

			if (this.opts.toolbarExternal)
			{
				this.$box.css('top', this.fullscreen.boxcss.top);
				this.$toolbar.css({
					'width': this.fullscreen.toolcss.width,
					'top': this.fullscreen.toolcss.top,
					'position': this.fullscreen.toolcss.position
				});
			}

			if (this.opts.minHeight) this.$editor.css('minHeight', this.opts.minHeight);
			if (this.opts.maxHeight) this.$editor.css('maxHeight', this.opts.maxHeight);

			this.$editor.css('height', 'auto');
			this.$editor.focus();
			this.observe.load();
		},
		toggle: function()
		{
			if (this.fullscreen.isOpen)
			{
				this.fullscreen.disable();
			}
			else
			{
				this.fullscreen.enable();
			}
		},
		resize: function()
		{
			if (!this.fullscreen.isOpen) return;

			var toolbarHeight = this.$toolbar.height();

			var height = $(window).height() - toolbarHeight;
			this.$box.width($(window).width() - 2).height(height + toolbarHeight);

			if (this.opts.toolbarExternal)
			{
				this.$toolbar.css({
					'top': '0px',
					'position': 'absolute',
					'width': '100%'
				});

				this.$box.css('top', toolbarHeight + 'px');
			}

			this.$editor.height(height - 14);
		}
	};
};

(function($)
{
    $.Redactor.prototype.imagemanager = function()
    {
        return {
            init: function()
            {
                if (!this.opts.imageManagerJson) return;

                this.modal.addCallback('image', this.imagemanager.load);
            },
            load: function()
            {
                var $modal = this.modal.getModal();

                this.modal.createTabber($modal);
                this.modal.addTab(1, 'Upload', 'active');
                this.modal.addTab(2, 'Choose');

                $('#redactor-modal-image-droparea').addClass('redactor-tab redactor-tab1');

                var $box = $('<div id="redactor-image-manager-box" style="overflow: auto; height: 300px;" class="redactor-tab redactor-tab2">').hide();
                $modal.append($box);

                $.ajax({
                  dataType: "json",
                  cache: false,
                  url: this.opts.imageManagerJson,
                  success: $.proxy(function(data)
                    {
                        $.each(data, $.proxy(function(key, val)
                        {
                            // title
                            var thumbtitle = '';
                            if (typeof val.title !== 'undefined') thumbtitle = val.title;

                            var img = $('<img src="' + val.thumb + '" rel="' + val.image + '" title="' + thumbtitle + '" style="width: 100px; height: 75px; cursor: pointer;" />');
                            $('#redactor-image-manager-box').append(img);
                            $(img).click($.proxy(this.imagemanager.insert, this));

                        }, this));


                    }, this)
                });


            },
            insert: function(e)
            {
                this.image.insert('<img src="' + $(e.target).attr('rel') + '" alt="' + $(e.target).attr('title') + '">');
            }
        };
    };
})(jQuery);

(function($)
{
    $.Redactor.prototype.table = function()
    {
        return {
            getTemplate: function()
            {
                return String()
                + '<section id="redactor-modal-table-insert">'
                    + '<label>' + this.lang.get('rows') + '</label>'
                    + '<input type="text" size="5" value="2" id="redactor-table-rows" />'
                    + '<label>' + this.lang.get('columns') + '</label>'
                    + '<input type="text" size="5" value="3" id="redactor-table-columns" />'
                + '</section>';
            },
            init: function()
            {
                var dropdown = {};

                dropdown.insert_table = {
                                    title: this.lang.get('insert_table'),
                                    func: this.table.show,
                                    observe: {
                                        element: 'table',
                                        'in': {
                                            attr: {
                                                'class': 'redactor-dropdown-link-inactive',
                                                'aria-disabled': true,
                                            }
                                        }
                                    }
                                };

                dropdown.insert_row_above = {
                                    title: this.lang.get('insert_row_above'),
                                    func: this.table.addRowAbove,
                                    observe: {
                                        element: 'table',
                                        out: {
                                            attr: {
                                                'class': 'redactor-dropdown-link-inactive',
                                                'aria-disabled': true,
                                            }
                                        }
                                    }
                                };

                dropdown.insert_row_below = {
                                    title: this.lang.get('insert_row_below'),
                                    func: this.table.addRowBelow,
                                    observe: {
                                        element: 'table',
                                        out: {
                                            attr: {
                                                'class': 'redactor-dropdown-link-inactive',
                                                'aria-disabled': true,
                                            }
                                        }
                                    }
                                };

                dropdown.insert_row_below = {
                                    title: this.lang.get('insert_row_below'),
                                    func: this.table.addRowBelow,
                                    observe: {
                                        element: 'table',
                                        out: {
                                            attr: {
                                                'class': 'redactor-dropdown-link-inactive',
                                                'aria-disabled': true,
                                            }
                                        }
                                    }
                                };

                dropdown.insert_column_left = {
                                    title: this.lang.get('insert_column_left'),
                                    func: this.table.addColumnLeft,
                                    observe: {
                                        element: 'table',
                                        out: {
                                            attr: {
                                                'class': 'redactor-dropdown-link-inactive',
                                                'aria-disabled': true,
                                            }
                                        }
                                    }
                                };

                dropdown.insert_column_right = {
                                    title: this.lang.get('insert_column_right'),
                                    func: this.table.addColumnRight,
                                    observe: {
                                        element: 'table',
                                        out: {
                                            attr: {
                                                'class': 'redactor-dropdown-link-inactive',
                                                'aria-disabled': true,
                                            }
                                        }
                                    }
                                };

                dropdown.add_head = {
                                    title: this.lang.get('add_head'),
                                    func: this.table.addHead,
                                    observe: {
                                        element: 'table',
                                        out: {
                                            attr: {
                                                'class': 'redactor-dropdown-link-inactive',
                                                'aria-disabled': true,
                                            }
                                        }
                                    }
                                };

                dropdown.delete_head = {
                                    title: this.lang.get('delete_head'),
                                    func: this.table.deleteHead,
                                    observe: {
                                        element: 'table',
                                        out: {
                                            attr: {
                                                'class': 'redactor-dropdown-link-inactive',
                                                'aria-disabled': true,
                                            }
                                        }
                                    }
                                };

                dropdown.delete_column = {
                                    title: this.lang.get('delete_column'),
                                    func: this.table.deleteColumn,
                                    observe: {
                                        element: 'table',
                                        out: {
                                            attr: {
                                                'class': 'redactor-dropdown-link-inactive',
                                                'aria-disabled': true,
                                            }
                                        }
                                    }
                                };

                dropdown.delete_row = {
                                    title: this.lang.get('delete_row'),
                                    func: this.table.deleteRow,
                                    observe: {
                                        element: 'table',
                                        out: {
                                            attr: {
                                                'class': 'redactor-dropdown-link-inactive',
                                                'aria-disabled': true,
                                            }
                                        }
                                    }
                                };

                dropdown.delete_row = {
                                    title: this.lang.get('delete_table'),
                                    func: this.table.deleteTable,
                                    observe: {
                                        element: 'table',
                                        out: {
                                            attr: {
                                                'class': 'redactor-dropdown-link-inactive',
                                                'aria-disabled': true,
                                            }
                                        }
                                    }
                                };

                this.observe.addButton('td', 'table');
                this.observe.addButton('th', 'table');

                var button = this.button.addBefore('link', 'table', this.lang.get('table'));
                this.button.addDropdown(button, dropdown);
            },
            show: function()
            {
                this.modal.addTemplate('table', this.table.getTemplate());

                this.modal.load('table', this.lang.get('insert_table'), 300);
                this.modal.createCancelButton();

                var button = this.modal.createActionButton(this.lang.get('insert'));
                button.on('click', this.table.insert);

                this.selection.save();
                this.modal.show();

                $('#redactor-table-rows').focus();

            },
            insert: function()
            {
                this.placeholder.remove();

                var rows = $('#redactor-table-rows').val(),
                    columns = $('#redactor-table-columns').val(),
                    $tableBox = $('<div>'),
                    tableId = Math.floor(Math.random() * 99999),
                    $table = $('<table id="table' + tableId + '"><tbody></tbody></table>'),
                    i, $row, z, $column;

                for (i = 0; i < rows; i++)
                {
                    $row = $('<tr>');

                    for (z = 0; z < columns; z++)
                    {
                        $column = $('<td>' + this.opts.invisibleSpace + '</td>');

                        // set the focus to the first td
                        if (i === 0 && z === 0)
                        {
                            $column.append(this.selection.getMarker());
                        }

                        $($row).append($column);
                    }

                    $table.append($row);
                }

                $tableBox.append($table);
                var html = $tableBox.html();

                this.modal.close();
                this.selection.restore();

                if (this.table.getTable()) return;

                this.buffer.set();

                var current = this.selection.getBlock() || this.selection.getCurrent();
                if (current && current.tagName != 'BODY')
                {
                    if (current.tagName == 'LI') current = $(current).closest('ul, ol');
                    $(current).after(html);
                }
                else
                {
                    this.insert.html(html, false);
                }

                this.selection.restore();

                var table = this.$editor.find('#table' + tableId);

                var p = table.prev("p");

                if (p.length > 0 && this.utils.isEmpty(p.html()))
                {
                    p.remove();
                }

                if (!this.opts.linebreaks && (this.utils.browser('mozilla') || this.utils.browser('msie')))
                {
                    var $next = table.next();
                    if ($next.length === 0)
                    {
                         table.after(this.opts.emptyHtml);
                    }
                }

                this.observe.buttons();

                table.find('span.redactor-selection-marker').remove();
                table.removeAttr('id');

                this.code.sync();
                this.core.setCallback('insertedTable', table);
            },
            getTable: function()
            {
                var $table = $(this.selection.getParent()).closest('table');

                if (!this.utils.isRedactorParent($table)) return false;
                if ($table.size() === 0) return false;

                return $table;
            },
            restoreAfterDelete: function($table)
            {
                this.selection.restore();
                $table.find('span.redactor-selection-marker').remove();
                this.code.sync();
            },
            deleteTable: function()
            {
                var $table = this.table.getTable();
                if (!$table) return;

                this.buffer.set();


                var $next = $table.next();
                if (!this.opts.linebreaks && $next.length !== 0)
                {
                    this.caret.setStart($next);
                }
                else
                {
                    this.caret.setAfter($table);
                }


                $table.remove();

                this.code.sync();
            },
            deleteRow: function()
            {
            var $table = this.table.getTable();
            if (!$table) return;

            var $current = $(this.selection.getCurrent());

            this.buffer.set();

            var $current_tr = $current.closest('tr');
            var $focus_tr = $current_tr.prev().length ? $current_tr.prev() : $current_tr.next();
            if ($focus_tr.length)
            {
                var $focus_td = $focus_tr.children('td, th').first();
                if ($focus_td.length) $focus_td.prepend(this.selection.getMarker());
            }

            $current_tr.remove();
            this.table.restoreAfterDelete($table);
        },
            deleteColumn: function()
            {
            var $table = this.table.getTable();
            if (!$table) return;

            this.buffer.set();

            var $current = $(this.selection.getCurrent());
            var $current_td = $current.closest('td, th');
            var index = $current_td[0].cellIndex;

            $table.find('tr').each($.proxy(function(i, elem)
            {
                var $elem = $(elem);
                var focusIndex = index - 1 < 0 ? index + 1 : index - 1;
                if (i === 0) $elem.find('td, th').eq(focusIndex).prepend(this.selection.getMarker());

                $elem.find('td, th').eq(index).remove();

            }, this));

            this.table.restoreAfterDelete($table);
        },
            addHead: function()
            {
                var $table = this.table.getTable();
                if (!$table) return;

                this.buffer.set();

                if ($table.find('thead').size() !== 0)
                {
                    this.table.deleteHead();
                    return;
                }

                var tr = $table.find('tr').first().clone();
                tr.find('td').replaceWith($.proxy(function()
                {
                    return $('<th>').html(this.opts.invisibleSpace);
                }, this));

                $thead = $('<thead></thead>').append(tr);
                $table.prepend($thead);

                this.code.sync();

            },
            deleteHead: function()
            {
                var $table = this.table.getTable();
                if (!$table) return;

                var $thead = $table.find('thead');
                if ($thead.size() === 0) return;

                this.buffer.set();

                $thead.remove();
                this.code.sync();
            },
            addRowAbove: function()
            {
                this.table.addRow('before');
            },
            addRowBelow: function()
            {
                this.table.addRow('after');
            },
            addColumnLeft: function()
            {
                this.table.addColumn('before');
            },
            addColumnRight: function()
            {
                this.table.addColumn('after');
            },
            addRow: function(type)
            {
                var $table = this.table.getTable();
                if (!$table) return;

                this.buffer.set();

                var $current = $(this.selection.getCurrent());
                var $current_tr = $current.closest('tr');
                var new_tr = $current_tr.clone();

                new_tr.find('th').replaceWith(function()
                {
                    var $td = $('<td>');
                    $td[0].attributes = this.attributes;

                    return $td.append($(this).contents());
                });

                new_tr.find('td').html(this.opts.invisibleSpace);

                if (type == 'after')
                {
                    $current_tr.after(new_tr);
                }
                else
                {
                    $current_tr.before(new_tr);
                }

                this.code.sync();
            },
            addColumn: function (type)
            {
                var $table = this.table.getTable();
                if (!$table) return;

                var index = 0;
                var current = $(this.selection.getCurrent());

                this.buffer.set();

                var $current_tr = current.closest('tr');
                var $current_td = current.closest('td, th');

                $current_tr.find('td, th').each($.proxy(function(i, elem)
                {
                    if ($(elem)[0] === $current_td[0]) index = i;

                }, this));

                $table.find('tr').each($.proxy(function(i, elem)
                {
                    var $current = $(elem).find('td, th').eq(index);

                    var td = $current.clone();
                    td.html(this.opts.invisibleSpace);

                    if (type == 'after')
                    {
                        $current.after(td);
                    }
                    else
                    {
                        $current.before(td);
                    }

                }, this));

                this.code.sync();
            }
        };
    };
})(jQuery);

RedactorPlugins.textdirection = function() {
  return {
    init: function()
    {
        var that = this;
        var dropdown = {};

        dropdown.ltr = { title: __('Left to Right'), callback: this.setLtr };
        dropdown.rtl = { title: __('Right to Left'), callback: this.setRtl };

        var button = this.button.add('textdirection', __('Change Text Direction'),
            false, dropdown);

        if (this.opts.direction == 'rtl')
            this.setRtl();
    },
    setRtl: function()
    {
        var c = this.getCurrent(), s = this.getSelection();
        this.buffer.set();
        if (s.type == 'Range' && s.focusNode.nodeName != 'div') {
            this.linebreakHack(s);
        }
        else if (!c) {
            var repl = '<div dir="rtl">' + this.get() + '</div>';
            this.set(repl, false);
        }
        $(this.getCurrent()).attr('dir', 'rtl');
        this.sync();
    },
    setLtr: function()
    {
        var c = this.getCurrent(), s = this.getSelection();
        this.buffer.set();
        if (s.type == 'Range' && s.focusNode.nodeName != 'div') {
            this.linebreakHack(s);
        }
        else if (!c) {
            var repl = '<div dir="ltr">' + this.get() + '</div>';
            this.set(repl, false);
        }
        $(this.getCurrent()).attr('dir', 'ltr');
        this.sync();
    },
    linebreakHack: function(sel) {
        var range = sel.getRangeAt(0);
        var wrapper = document.createElement('div');
        wrapper.appendChild(range.extractContents());
        range.insertNode(wrapper);
        this.selectionElement(wrapper);
    }
  };
};

(function($)
{
    $.Redactor.prototype.video = function()
    {
        return {
            reUrlYoutube: /https?:\/\/(?:[0-9A-Z-]+\.)?(?:youtu\.be\/|youtube\.com\S*[^\w\-\s])([\w\-]{11})(?=[^\w\-]|$)(?![?=&+%\w.-]*(?:['"][^<>]*>|<\/a>))[?=&+%\w.-]*/ig,
            reUrlVimeo: /https?:\/\/(www\.)?vimeo.com\/(\d+)($|\/)/,
            getTemplate: function()
            {
                return String()
                + '<section id="redactor-modal-video-insert">'
                    + '<label>' + this.lang.get('video_html_code') + '</label>'
                    + '<textarea id="redactor-insert-video-area" style="height: 160px;"></textarea>'
                + '</section>';
            },
            init: function()
            {
                var button = this.button.addAfter('image', 'video', this.lang.get('video'));
                this.button.addCallback(button, this.video.show);
            },
            show: function()
            {
                this.modal.addTemplate('video', this.video.getTemplate());

                this.modal.load('video', this.lang.get('video'), 700);
                this.modal.createCancelButton();

                var button = this.modal.createActionButton(this.lang.get('insert'));
                button.on('click', this.video.insert);

                this.selection.save();
                this.modal.show();

                $('#redactor-insert-video-area').focus();

            },
            insert: function()
            {
                var data = $('#redactor-insert-video-area').val();

                if (!data.match(/<iframe|<video/gi))
                {
                    data = this.clean.stripTags(data);

                    // parse if it is link on youtube & vimeo
                    var iframeStart = '<iframe style="width: 500px; height: 281px;" src="',
                        iframeEnd = '" frameborder="0" allowfullscreen></iframe>';

                    if (data.match(this.video.reUrlYoutube))
                    {
                        data = data.replace(this.video.reUrlYoutube, iframeStart + '//www.youtube.com/embed/$1' + iframeEnd);
                    }
                    else if (data.match(this.video.reUrlVimeo))
                    {
                        data = data.replace(this.video.reUrlVimeo, iframeStart + '//player.vimeo.com/video/$2' + iframeEnd);
                    }
                }

                this.selection.restore();
                this.modal.close();

                var current = this.selection.getBlock() || this.selection.getCurrent();

                if (current) $(current).after(data);
                else
                {
                    this.insert.html(data);
                }

                this.code.sync();
            }

        };
    };
})(jQuery);

RedactorPlugins.imagepaste = function() {
  return {
    init: function() {
      if (this.utils.browser('webkit') && navigator.userAgent.indexOf('Chrome') === -1)
      {
        var arr = this.utils.browser('version').split('.');
        if (arr[0] < 536)
          return true;
      }

      // paste except opera (not webkit)
      if (this.utils.browser('opera'))
          return true;

      this.$editor.on('paste.imagepaste', $.proxy(this.imagepaste.buildEventPaste, this));

      // Capture the selection position every so often as Redactor seems to
      // drop it when attempting an image paste before `paste` browser event
      // fires
      var that = this,
          plugin = this.imagepaste;
      setInterval(function() {
        if (plugin.inpaste)
          return;
        that.selection.get();
        var coords = that.range.getClientRects();
        if (!coords.length)
            return;
        coords = coords[0];
        var proxy = {
          clientX: (Math.max(coords.left, 0) || 0) + 10,
          clientY: (coords.top || 0) + 10,
        };
        if (coords.left < 0)
            return;
        plugin.offset = proxy; //that.caret.getOffset() || plugin.offset;
      }, 300);
    },
    offset: 0,
    inpaste: false,
    buildEventPaste: function(e)
    {
      var event = e.originalEvent || e,
          fileUpload = false,
          files = [],
          i, file,
          plugin = this.imagepaste,
          cd = event.clipboardData,
          self = this, node,
          bail = function() {
            plugin.inpaste = false;
          };

      plugin.inpaste = true;
      if (typeof(cd) === 'undefined')
          return bail();

      if (cd.items && cd.items.length)
      {
        for (i = 0, k = cd.items.length; i < k; i++) {
          if (cd.items[i].kind == 'file' && cd.items[i].type.indexOf('image/') !== -1) {
            file = cd.items[i].getAsFile();
            if (file !== null)
              files.push(file);
            }
        }
      }
      else if (cd.files && cd.files.length)
      {
        files = cd.files;
      }
      else if (cd.types.length) {
        for (i = 0, k = cd.types.length; i < k; i++) {
          if (cd.types[i].indexOf('image/') != -1) {
            var data = cd.getData(cd.types[i]);
            if (data.length) {
                files.push(new Blob([data], {type: cd.types[i]}));
                break;
            }
          }
        }
      }

      if (!files.length)
        return bail();

      e.preventDefault();
      e.stopImmediatePropagation();

      if (plugin.offset == 0) {
        // Assume top left of editor window since no last position is known
        var offset = self.$editor.offset();
        plugin.offset = {
          clientX: offset.left - $(document).scrollLeft() + 20,
          clientY: offset.top - $(document).scrollTop() + self.$toolbar.height() + 20
        }
      }

      // Add cool wait cursor
      var waitCursor = $('<span class="-image-upload-placeholder icon-stack"><i class="icon-circle icon-stack-base"></i><i class="icon-picture icon-light icon-spin"></i></span>');
      self.insert.nodeToCaretPositionFromPoint(plugin.offset, waitCursor);

      var oldIUC = self.opts.imageUploadCallback;
      self.opts.imageUploadCallback = function(image, json) {
        if ($.contains(waitCursor.get(0), image))
          waitCursor.replaceWith(image);
        else
          waitCursor.remove();

        self.opts.imageUploadCallback = oldIUC;
        // Add a zero-width space so that the caret:getOffset will find
        // locations after pictures if only <br> tags exist otherwise. In
        // other words, ensure there is at least one character after the
        // image for text character counting. Additionally, Redactor will
        // strip the zero-width space when saving
        $(document.createTextNode("\u200b")).insertAfter($(image));
        bail();
      };

      // Upload clipboard files
      for (i = 0, k = files.length; i < k; i++)
        self.upload.directUpload(files[i], plugin.offset);
    }
  };
};

var loadedFabric = false;
RedactorPlugins.imageannotate = function() {
  return {
    annotateButton: false,
    init: function() {
      var redactor = this,
          self = this.imageannotate;
      $(document).on('click', '.redactor-box img', function() {
        var $image = $(this),
            image_box = $('#redactor-image-box');
        if (!image_box.length || !redactor.image.editter)
            return;

        var edit_size = redactor.image.editter.outerWidth();

        self.annotateButton = redactor.image.editter
          .on('remove.annotate',
            function() { self.teardownAnnotate.call(redactor, image_box); })
          .clone()
          .text(' '+__('Annotate'))
          .prepend('<i class="icon-pencil"></i>')
          .addClass('annotate-button')
          .insertAfter(redactor.image.editter)
          .data('image', this)
          .on('click',
            function() { self.startAnnotate.call(redactor, $image) });
        var diff = (edit_size - self.annotateButton.outerWidth()) / 2;
        self.annotateButton.css('margin-left',
          (diff + 5) + 'px');
        redactor.image.editter.css('margin-left',
          (-edit_size + diff - 5) + 'px');
      });
    },
    startAnnotate: function(img) {
        canvas = this.imageannotate.initCanvas(img);
        this.imageannotate.buildToolbar(img);
        this.image.editter.hide();
        this.imageannotate.annotateButton.hide();
    },
    teardownAnnotate: function(box) {
        this.image.editter.off('.annotate');
        this.opts.keydownCallback = false;
        this.opts.keyupCallback = false;
        box.find('.annotate-toolbar').remove();
        box.find('.annotate-button').remove();
        var img = box.find('img')[0],
            $img = $(img),
            fcanvas = $img.data('canvas'),
            state = fcanvas.toObject();
        // Capture current annotations
        delete state.backgroundImage;
        $img.attr('data-annotations', btoa(JSON.stringify(state)));
        // Drop the canvas
        fcanvas.dispose();
        box.find('canvas').parent().remove();
        $img.data('canvas', false);
        // Deselect the image
        this.image.hideResize();
        // Show the original image
        $img.removeClass('hidden');
    },
    buildToolbar: function(img) {
        var box = img.parent(),
            redactor = this,
            plugin = this.imageannotate,
            shapes = $('<span>')
              .attr('data-redactor', 'verified')
              .attr('contenteditable', 'false')
              .css({'display': 'inline-block', 'vertical-align': 'top'}),
            swatches = shapes.clone(),
            actions = shapes.clone(),
            container = $('<div></div>')
              .addClass('annotate-toolbar')
              .attr('data-redactor', 'verified')
              .attr('contenteditable', 'false')
              .css({position: 'absolute', bottom: 0, 'min-height': '28px',
                width: '100%', 'background-color': 'rgba(0,0,0,0.5)',
                margin: 0, 'padding-top': '4px' })
              .appendTo(box)
              .append(shapes)
              .append(swatches)
              .append(actions);

        var button = $('<a></a>')
            .attr('href', '#')
            .attr('data-redactor', 'verified')
            .attr('contenteditable', 'false')
            .css({color: 'white', padding: '0 7px 1px', margin: '1px 3px',
                'text-decoration': 'none', 'vertical-align': 'top'});

        shapes
            .append(button.clone()
              .append($('<i class="icon-arrow-right icon-large"></i>')
              .on('click', plugin.drawArrow.bind(redactor))
              .attr('title', __('Add Arrow')))
            )
            .append(button.clone()
              .append($('<i class="icon-check-empty icon-large"></i>')
              .on('click', plugin.drawBox.bind(redactor))
              .attr('title', __('Add Rectangle')))
            )
            .append(button.clone()
              .append($('<i class="icon-circle-blank icon-large"></i>')
              .on('click', plugin.drawEllipse.bind(redactor))
              .attr('title', __('Add Ellipse')))
            )
            .append(button.clone()
              .append($('<i class="icon-text-height icon-large"></i>')
              .on('click', plugin.drawText.bind(redactor))
              .attr('title', __('Add Text')))
            );

      var colors = [
          '#ffffff', '#888888', '#000000', 'fuchsia', 'blue', 'red',
          'lime', 'blueviolet', 'cyan', '#f4a63b', 'yellow']
          len = colors.length;

      swatches.append(
        $('<span><i class="icon-ellipsis-vertical icon-large"></i></span>')
          .css({color: 'white', padding: '0 3px 1px', margin: '1px 3px',
            height: '21px', position: 'relative', bottom: '8px'}
          )
      );
      for (var z = 0; z < len; z++) {
        var color = colors[z];

        var $swatch = $('<a rel="' + color + '" href="#" style="font-size: 0; padding: 0; margin: 2px; width: 22px; height: 22px;"></a>');
        $swatch.css({'background-color': color, 'border': '1px dotted rgba(255,255,255,0.4)'});
        $swatch.attr('data-redactor', 'verified');
        $swatch.attr('contenteditable', 'false');
        $swatch.on('click', plugin.setColor.bind(redactor));

        swatches.append($swatch);
      }

        actions
            .append(
              $('<span><i class="icon-ellipsis-vertical icon-large"></i></span>')
                .css({color: 'white', padding: '0 3px 1px', margin: '1px 3px',
                  height: '21px'}
                )
            )
            .append(button.clone()
              .css('padding-left', '1px')
              .append($('<span></span>').css('position','relative')
                .append($('<i class="icon-font"></i>'))
                .append($('<i class="icon-minus"></i>')
                  .css({position: 'absolute', right: '-4px', top: '5px',
                    'text-shadow': '0 0 2px black', 'font-size':'80%'})
                )
              )
              .on('click', plugin.smallerFont.bind(redactor))
              .attr('title', __('Decrease Font Size'))
            )
            .append(button.clone()
              .css('padding-left', '1px')
              .append($('<span></span>').css('position','relative')
                .append($('<i class="icon-font icon-large"></i>'))
                .append($('<i class="icon-plus"></i>')
                  .css({position: 'absolute', right: '-8px', top: '4px',
                    'text-shadow': '0 0 2px black'})
                )
              )
              .on('click', plugin.biggerFont.bind(redactor))
              .attr('title', __('Increase Font Size'))
            )
            .append(button.clone()
              .attr('id', 'annotate-set-stroke')
              .append($('<span></span>').css({'position': 'relative', 'top': '2px'})
                .append($('<i class="icon-check-empty icon-large"></i>')
                  .css('font-size', '120%')
                ).append($('<i class="icon-tint"></i>')
                  .css({position: 'absolute', left: '4.5px', top: 0})
                )
              )
              .on('click', plugin.paintStroke.bind(redactor))
              .attr('title', __('Set Stroke'))
            )
            .append(button.clone()
              .attr('id', 'annotate-set-fill')
              .append($('<span></span>').css('position','relative')
                .append($('<i class="icon-sign-blank icon-large"></i>'))
                .append($('<i class="icon-tint icon-dark"></i>')
                  .css({position: 'absolute', left: '4px', top: '2px'})
                )
              )
              .on('click', plugin.paintFill.bind(redactor))
              .attr('title', __('Set Fill'))
            )
            .append(button.clone()
              .append($('<i class="icon-eye-close icon-large"></i>'))
              .on('click', plugin.setOpacity.bind(redactor))
              .attr('title', __('Toggle Opacity'))
            )
            .append(button.clone()
              .append($('<i class="icon-double-angle-up icon-large"></i>'))
              .on('click', plugin.bringForward.bind(redactor))
              .attr('title', __('Bring Forward'))
            )
            .append(button.clone()
              .append($('<i class="icon-trash icon-large"></i>'))
              .on('click', plugin.discard.bind(redactor))
              .attr('title', __('Delete Object'))
            );

        container.append(button.clone()
          .append($('<i class="icon-save icon-large"></i>'))
          .on('click', plugin.commit.bind(redactor))
          .addClass('pull-right')
          .attr('title', __('Commit Annotations'))
        );
        plugin.paintStroke();
    },

    setColor: function(e) {
      e.preventDefault();
      var plugin = this.imageannotate,
          redactor = this,
          swatch = e.target,
          image_box = $('#redactor-image-box'),
          img = image_box.find('img')[0],
          fcanvas = $(img).data('canvas');
      $.each(fcanvas.getObjects(), function() {
        if (this.get('active')) {
          if (plugin.paintMode == 'fill')
            this.setFill($(e.target).attr('rel'));
          else
            this.setStroke($(e.target).attr('rel'));
        }
      });
      fcanvas.renderAll();
    },

    // Shapes
    drawShape: function(ondown, onmove, onup, cursor) {
      // @see http://jsfiddle.net/URWru/
      var plugin = this.imageannotate,
          redactor = this,
          image_box = $('#redactor-image-box'),
          img = image_box.find('img')[0],
          fcanvas = $(img).data('canvas'),
          isDown, shape,
          mousedown = function(o) {
            isDown = true;
            plugin.setBuffer();
            var pointer = fcanvas.getPointer(o.e);
            shape = ondown(pointer, o.e);
            fcanvas.add(shape);
          },
          mousemove = function(o) {
            if (!isDown) return;
            var pointer = fcanvas.getPointer(o.e);
            onmove(shape, pointer, o.e);
            fcanvas.renderAll();
          },
          mouseup = function(o) {
            isDown = false;
            if (onup) {
              if (shape2 = onup(shape, fcanvas.getPointer(o.e))) {
                shape.remove();
                fcanvas.add(shape2);
                shape = shape2;
              }
            }
            shape.setCoords()
              .set({
                transparentCorners: false,
                borderColor: 'rgba(102,153,255,0.9)',
                cornerColor: 'rgba(102,153,255,0.5)',
                cornerSize: 10
              });
            fcanvas.calcOffset()
              .off('mouse:down', mousedown)
              .off('mouse:up', mouseup)
              .off('mouse:move', mousemove)
              .deactivateAll()
              .setActiveObject(shape)
              .renderAll();
            fcanvas.selection = true;
            fcanvas.defaultCursor = 'default';
          };

        fcanvas.selection = false;
        fcanvas.defaultCursor = cursor || 'crosshair';
        // Ensure double presses of same button are squelched
        fcanvas.off('mouse:down');
        fcanvas.off('mouse:up');
        fcanvas.off('mouse:move');
        fcanvas.on('mouse:down', mousedown);
        fcanvas.on('mouse:up', mouseup);
        fcanvas.on('mouse:move', mousemove);
        return false;
    },

    drawArrow: function(e) {
      e.preventDefault();
      var top, left;
      return this.imageannotate.drawShape(
        function(pointer) {
          top = pointer.y;
          left = pointer.x;
          return new fabric.Group([
            new fabric.Line([0, 5, 0, 5], {
              strokeWidth: 5,
              fill: 'red',
              stroke: 'red',
              originX: 'center',
              originY: 'center',
              selectable: false,
              hasBorders: false
            }),
            new fabric.Polygon([
              {x: 20, y: 0},
              {x: 0, y: -5},
              {x: 0, y: 5}
              ], {
              strokeWidth: 0,
              fill: 'red',
              originX: 'center',
              originY: 'center',
              selectable: false,
              hasBorders: false
            })
          ], {
            left: pointer.x,
            top: pointer.y,
            originX: 'center',
            originY: 'center'
          });
        },
        function(group, pointer) {
          var dx = pointer.x - left,
              dy = pointer.y - top,
              angle = Math.atan(dy / dx),
              d = Math.sqrt(dx * dx + dy * dy) - 10,
              sign = dx < 0 ? -1 : 1,
              dy2 = Math.sin(angle) * d * sign;
              dx2 = Math.cos(angle) * d * sign,
          group.item(0)
            .set({ x2: dx2, y2: dy2 });
          group.item(1)
            .set({
              angle: angle * 180 / Math.PI,
              flipX: dx < 0,
              flipY: dy < 0
            })
            .setPositionByOrigin(new fabric.Point(dx, dy),
                'center', 'center');
        },
        function(shape, pointer) {
          var dx = pointer.x - left,
              dy = pointer.y - top,
              angle = Math.atan(dy / dx),
              d = Math.sqrt(dx * dx + dy * dy);
          // Mess with the next two lines and you *will* be sorry!
          shape.forEachObject(function(e) { shape.removeWithUpdate(e); });
          return new fabric.Path(
            'M '+left+' '+top+' l '+(d-20)+' 0 0 -3 15 3 -15 3 0 -3 z', {
            angle: angle * 180 / Math.PI + (dx < 0 ? 180 : 0),
            strokeWidth: 5,
            fill: 'red',
            stroke: 'red'
          });
        }
      );
    },

    drawEllipse: function(e) {
      e.preventDefault();
      return this.imageannotate.drawShape(
        function(pointer) {
          return new fabric.Ellipse({
            top: pointer.y,
            left: pointer.x,
            strokeWidth: 5,
            fill: 'transparent',
            stroke: 'red',
            originX: 'left',
            originY: 'top'
          });
        },
        function(circle, pointer, event) {
          var x = circle.get('left'), y = circle.get('top'),
              dx = pointer.x - x, dy = pointer.y - y,
              sw = circle.getStrokeWidth()/2;
          // Use SHIFT to draw circles
          if (event.shiftKey) {
            dy = dx = Math.max(dx, dy);
          }
          circle.set({
            rx: Math.max(0, Math.abs(dx/2) - sw),
            ry: Math.max(0, Math.abs(dy/2) - sw),
            originX: dx < 0 ? 'right' : 'left',
            originY: dy < 0 ? 'bottom' : 'top'});
        }
      );
    },

    drawBox: function(e) {
      e.preventDefault();
      return this.imageannotate.drawShape(
        function(pointer) {
          return new fabric.Rect({
            top: pointer.y,
            left: pointer.x,
            strokeWidth: 5,
            fill: 'transparent',
            stroke: 'red',
            originX: 'left',
            originY: 'top'
          });
        },
        function(rect, pointer, event) {
          var x = rect.get('left'), y = rect.get('top'),
              dx = pointer.x - x, dy = pointer.y - y;
          // Use SHIFT to draw squares
          if (event.shiftKey) {
            dy = dx = Math.max(dx, dy);
          }
          rect.set({ width: Math.abs(dx), height: Math.abs(dy),
            originX: dx < 0 ? 'right' : 'left',
            originY: dy < 0 ? 'bottom' : 'top'});
        }
      );
    },

    drawText: function(e) {
      e.preventDefault();
      return this.imageannotate.drawShape(
        function(pointer) {
          return new fabric.IText(__('Text'), {
            top: pointer.y,
            left: pointer.x,
            fill: 'red',
            originX: 'left',
            originY: 'top',
            fontFamily: 'sans-serif',
            fontSize: 30
          });
        },
        function(rect, pointer, event) {
          var x = rect.get('left'), y = rect.get('top'),
              dx = pointer.x - x, dy = pointer.y - y;
          // Use SHIFT to draw squares
          if (event.shiftKey) {
            dy = dx = Math.max(dx, dy);
          }
          rect.set({ width: Math.abs(dx), height: Math.abs(dy),
            originX: dx < 0 ? 'right' : 'left',
            originY: dy < 0 ? 'bottom' : 'top'});
        },
        function(shape) {
          shape.on('editing:exited', function() {
            if (!shape.getText())
              shape.remove();
          });
        },
        'text'
      );
    },

    // Action buttons
    biggerFont: function(e) {
      e.preventDefault();
      var image_box = $('#redactor-image-box'),
          img = image_box.find('img')[0],
          fcanvas = $(img).data('canvas');
      $.each(fcanvas.getObjects(), function() {
        if (this.get('active') && this instanceof fabric.IText) {
          if (this.getSelectedText()) {
            this.setSelectionStyles({
              fontSize: (this.getSelectionStyles().fontSize || this.getFontSize()) + 5
            });
          }
          else {
            this.setFontSize(this.getFontSize() + 5);
          }
        }
      });
      fcanvas.renderAll();
      return false;
    },
    smallerFont: function(e) {
      e.preventDefault();
      var image_box = $('#redactor-image-box'),
          img = image_box.find('img')[0],
          fcanvas = $(img).data('canvas');
      $.each(fcanvas.getObjects(), function() {
        if (this.get('active') && this instanceof fabric.IText) {
          if (this.getSelectedText()) {
            this.setSelectionStyles({
              fontSize: (this.getSelectionStyles().fontSize || this.getFontSize()) - 5
            });
          }
          else {
            this.setFontSize(this.getFontSize() - 5);
          }
        }
      });
      fcanvas.renderAll();
      return false;
    },

    paintStroke: function(e) {
      $('#annotate-set-stroke').css({'background-color': 'rgba(255,255,255,0.3)'});
      $('#annotate-set-fill').css({'background-color': 'transparent'});
      this.imageannotate.paintMode = 'stroke';
      return false;
    },
    paintFill: function(e) {
      $('#annotate-set-fill').css({'background-color': 'rgba(255,255,255,0.3)'});
      $('#annotate-set-stroke').css({'background-color': 'transparent'});
      this.imageannotate.paintMode = 'fill';
      return false;
    },

    setOpacity: function(e) {
      e.preventDefault();
      var image_box = $('#redactor-image-box'),
          img = image_box.find('img')[0],
          fcanvas = $(img).data('canvas');
      $.each(fcanvas.getObjects(), function() {
        if (this.get('active')) {
          if (this.getOpacity() != 1)
            this.setOpacity(1);
          else
            this.setOpacity(0.6);
        }
      });
      fcanvas.renderAll();
      return false;
    },

    bringForward: function(e) {
      e.preventDefault();
      var image_box = $('#redactor-image-box'),
          img = image_box.find('img')[0],
          fcanvas = $(img).data('canvas');
      $.each(fcanvas.getObjects(), function() {
        if (this.get('active')) {
          this.bringForward();
        }
      });
    },

    keydown: function(e) {
      var image_box = $('#redactor-image-box'),
          img = image_box.find('img')[0],
          fcanvas = $(img).data('canvas');

      if (!fcanvas)
          return;

      var active = fcanvas.getActiveObject();

      // Check if editing a text element
      if (active instanceof fabric.IText && active.get('isEditing')) {
        // This keystroke is not for redactor
        var ss = active.get('selectionStart'),
            se = active.get('selectionEnd');
        active.exitEditing();
        active.enterEditing();
        active.set({
          'selectionStart': ss,
          'selectionEnd': se
        });
        if (e.type == 'keydown')
            active.onKeyDown(e);
        else
            active.onKeyPress(e);
        return false;
      }

      // Check if [delete] was pressed with selected objects
      if (e.keyCode == 8 || e.keyCode == 46)
        return this.imageannotate.discard(e);
      else if (e.keyCode == 90 && (e.metaKey || e.ctrlKey)) {
        fcanvas.loadFromJSON(atob($(img).attr('data-annotations')));
        return false;
      }
    },

    discard: function(e) {
      var image_box = $('#redactor-image-box', this.$editor),
          img = image_box && image_box.find('img')[0],
          fcanvas = img && $(img).data('canvas');

      if (!fcanvas)
        // Not annotating
        return;

      e.preventDefault();
      this.imageannotate.setBuffer();
      $.each(fcanvas.getObjects(), function() {
        if (this.get('active'))
          this.remove();
      });
      fcanvas.renderAll();
      return false;
    },

    commit: function(e) {
      e.preventDefault();
      var redactor = this,
          image_box = $('#redactor-image-box'),
          img = image_box.find('img')[0],
          $img = $(img),
          fcanvas = $(img).data('canvas');
      fcanvas.deactivateAll();

      // Upload to server
      redactor.buffer.set();
      var annotated = fcanvas.toDataURL({
            format: 'jpg', quality: 4,
            multiplier: 1/fcanvas.getZoom()
          }),
          file = new Blob([annotated], {type: 'image/jpeg'});

      // Fallback to the data URL — show while the image is being uploaded
      var origSrc = $img.attr('src');
      $img.attr('src', annotated);

      var origCallback = redactor.opts.imageUploadCallback,
          origErrorCbk = redactor.opts.imageUploadErrorCallback;

      // After successful upload, replace the old image with the new one.
      // Transfer the annotation state to the new image for replay.
      redactor.opts.imageUploadCallback = function(image, json) {
        redactor.opts.imageUploadCallback = origCallback;
        redactor.opts.imageUploadErrorCallback = origErrorCbk;
        // Transfer the annotation JSON data and drop the original image.
        image.attr('data-annotations', $img.attr('data-annotations'));
        // Record the image that was originally annotated. If the committed
        // image is annotated again, it should be the original image with
        // the annotations placed live on the original image. The image
        // being committed here will be discarded.
        image.attr('data-orig-annotated-image-src',
          $img.attr('data-orig-annotated-image-src') || origSrc
        );
        $img.remove();
        // Redactor will add <br> before and after the image in linebreaks
        // mode
        var N = image.next();
        if (N.is('br')) N.remove();
        var P = image.prev();
        if (N.is('br')) P.remove();
      };

      // Handle upload issues
      redactor.opts.imageUploadErrorCallback = function(json) {
        redactor.opts.imageUploadCallback = origCallback;
        redactor.opts.imageUploadErrorCallback = origErrorCbk;
        $img.show();
      };
      redactor.imageannotate.teardownAnnotate(image_box);
      $img.css({opacity: 0.5});
      redactor.upload.directUpload(file, e);
      return false;
    },

    // Utils
    resizeShape: function(o) {
      var shape = o.target;
      if (shape instanceof fabric.Ellipse) {
        shape.set({
          rx: shape.get('rx') * shape.get('scaleX'),
          ry: shape.get('ry') * shape.get('scaleY'),
          scaleX: 1,
          scaleY: 1
        });
      }
      else if (shape instanceof fabric.Rect) {
        shape.set({
          width: shape.get('width') * shape.get('scaleX'),
          height: shape.get('height') * shape.get('scaleY'),
          scaleX: 1,
          scaleY: 1
        });
      }
    },
    setBuffer: function() {
      var image_box = $('#redactor-image-box'),
          img = image_box.find('img')[0],
          $img = $(img),
          fcanvas = $img.data('canvas'),
          state = fcanvas.toObject();
      // Capture current annotations
      delete state.backgroundImage;
      $img.attr('data-annotations', btoa(JSON.stringify(state)));
    },

    // Startup

    initCanvas: function(img) {
      var self = this,
          plugin = this.imageannotate,
          $img = $(img);
      if ($img.data('canvas'))
        return;
      var box = $img.parent(),
          canvas = $('<canvas>').css({
            position: 'absolute',
            top: 0, bottom: 0, left: 0, right: 0,
            width: '100%', height: '100%'
          }).appendTo(box),
          fcanvas = new fabric.Canvas(canvas[0], {
            backgroundColor: 'rgba(0,0,0,0,0)',
            containerClass: 'no-margin',
            includeDefaultValues: false,
          }),
          previous = $(img).attr('data-annotations');

      // Catch [delete] key and map to delete object
      self.opts.keydownCallback = plugin.keydown.bind(self);
      self.opts.keyupCallback = plugin.keydown.bind(self);

      var I = new Image(), scale;
      I.src = $img.attr('src');
      // Use a maximum zoom-out of 0.7, so that very large pictures do not
      // result in unusually small annotations (esp. stroke widths which are
      // not adjustable).
      scale = Math.max(0.7, $img.width() / I.width);
      var scaleWidth = $img.width() / scale,
          scaleHeight = $img.height() / scale;
      fcanvas
        .setDimensions({width: $img.width(), height: $img.height()})
        .setZoom(scale)
        .setBackgroundImage(
            $img.attr('data-orig-annotated-image-src') || $img.attr('src'),
            fcanvas.renderAll.bind(fcanvas), {
          width: scaleWidth,
          height: scaleHeight,
          // Needed to position overlayImage at 0/0
          originX: 'left',
          originY: 'top'
        })
        .on('object:scaling', plugin.resizeShape.bind(self));
      if (previous) {
        fcanvas.loadFromJSON(atob(previous));
        fcanvas.forEachObject(function(o) {
          o.set({
            transparentCorners: false,
            borderColor: 'rgba(102,153,255,0.9)',
            cornerColor: 'rgba(102,153,255,0.5)',
            cornerSize: 10
          });
        });
      }
      $img.data('canvas', fcanvas).addClass('hidden');
      return fcanvas;
    }
  };
};

RedactorPlugins.contexttypeahead = function() {
  return {
    typeahead: false,
    context: false,
    variables: false,

    init: function() {
      if (!this.$element.data('rootContext'))
        return;

      this.opts.keyupCallback = this.contexttypeahead.watch.bind(this);
      this.opts.keydownCallback = this.contexttypeahead.watch.bind(this);
      this.$editor.on('click', this.contexttypeahead.watch.bind(this));
    },

    watch: function(e) {
      var current = this.selection.getCurrent(),
          allText = this.$editor.text(),
          offset = this.caret.getOffset(),
          lhs = allText.substring(0, offset),
          search = new RegExp(/%\{([^}]*)$/),
          match;

      if (!lhs) {
        return !e.isDefaultPrevented();
      }

      if (e.which == 27 || !(match = search.exec(lhs)))
        // No longer in a element — close typeahead
        return this.contexttypeahead.destroy();

      if (e.type == 'click')
        return;

      // Locate the position of the cursor and the number of characters back
      // to the `%{` symbols
      var sel         = this.selection.get(),
          range       = this.sel.getRangeAt(0),
          content     = current.textContent,
          clientRects = range.getClientRects(),
          position    = clientRects[0],
          backText    = match[1],
          parent      = this.selection.getParent() || this.$editor,
          plugin      = this.contexttypeahead;

      // Insert a hidden text input to receive the typed text and add a
      // typeahead widget
      if (!this.contexttypeahead.typeahead) {
        this.contexttypeahead.typeahead = $('<input type="text">')
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
            source: this.contexttypeahead.getContext.bind(this),
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
            onselect: this.contexttypeahead.select.bind(this),
            scroll: true,
            items: 100
          });
      }

      if (position) {
        var width = plugin.textWidth(
              backText,
              this.selection.getParent() || $('<div class="redactor-editor">')
            ),
            pleft = $(parent).offset().left,
            left = position.left - width;

        if (left < pleft)
            // This is a bug in chrome, but I'm not sure how to adjust it
            left += pleft;

        plugin.typeahead
          .css({top: position.top + $(window).scrollTop(), left: left});
      }

      plugin.typeahead
        .val(match[1])
        .trigger(e);

      return !e.isDefaultPrevented();
    },

    getContext: function(typeahead, query) {
      var dfd, that=this.contexttypeahead,
          root = this.$element.data('rootContext');
      if (!this.contexttypeahead.context) {
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
        this.contexttypeahead.context = dfd;
      }
      // Only fetch the context once for this redactor box
      this.contexttypeahead.context.then(function(items) {
        typeahead.process(items);
      });
    },

    textWidth: function(text, clone) {
      var c = $(clone),
          o = c.clone().text(text)
            .css({'position': 'absolute', 'float': 'left', 'white-space': 'nowrap', 'visibility': 'hidden'})
            .css({'font-family': c.css('font-family'), 'font-weight': c.css('font-weight'),
              'font-size': c.css('font-size')})
            .appendTo($('body')),
          w = o.width();

      o.remove();

      return w;
    },

    destroy: function() {
      if (this.contexttypeahead.typeahead) {
        this.contexttypeahead.typeahead.typeahead('hide');
        this.contexttypeahead.typeahead.remove();
        this.contexttypeahead.typeahead = false;
      }
    },

    select: function(item, event) {
      // Collapse multiple textNodes together
      (this.selection.getBlock() || this.$editor.get(0)).normalize();
      var current = this.selection.getCurrent(),
          sel     = this.selection.get(),
          range   = this.sel.getRangeAt(0),
          cursorAt = range.endOffset,
          // TODO: Consume immediately following `}` symbols
          plugin  = this.contexttypeahead,
          search  = new RegExp(/%\{([^}]*)(\}?)$/);

      // FIXME: ENTER will end up here, but current will be empty

      if (!current)
        return;

      // Set cursor at the end of the expanded text
      var left = current.textContent.substring(0, cursorAt),
          right = current.textContent.substring(cursorAt),
          autoExpand = event.target.nodeName == 'I',
          selected = item.variable + (autoExpand ? '.' : '')
          newLeft = left.replace(search, '%{' + selected + '}');

      current.textContent = newLeft
        // Drop the remaining part of a variable block, if any
        + right.replace(/[^%}]*?[%}]/, '');

      this.range.setStart(current, newLeft.length - 1);
      this.range.setEnd(current, newLeft.length - 1);
      this.selection.addRange();
      if (!autoExpand)
          return plugin.destroy();

      plugin.typeahead.val(selected);
      plugin.typeahead.typeahead('lookup');
      return false;
    }
  };
};

RedactorPlugins.translatable = function() {
  return {
    langs: undefined,
    config: undefined,
    textareas: {},
    current: undefined,
    primary: undefined,
    button: undefined,

    init: function() {
      $.ajax({
        url: 'ajax.php/i18n/langs/all',
        success: this.translatable.setLangs.bind(this)
      });
      getConfig().then(this.translatable.setConfig.bind(this));
      this.opts.keydownCallback = this.translatable.showCommit.bind(this);
      this.translatable.translateTag = this.$textarea.data('translateTag');
    },

    setLangs: function(langs) {
      this.translatable.langs = langs;
      this.translatable.buildDropdown();
    },

    setConfig: function(config) {
      this.translatable.config = config;
      this.translatable.buildDropdown();
    },

    buildDropdown: function() {
      if (!this.translatable.config || !this.translatable.langs)
        return;

      var plugin = this.translatable,
          primary = this.$textarea,
          primary_lang = plugin.config.primary_language.replace('-','_'),
          primary_info = plugin.langs[primary_lang],
          dropdown = {},
          items = {};

      langs = plugin.langs;
      plugin.textareas[primary_lang] = primary;
      plugin.primary = plugin.current = primary_lang;

      dropdown[primary_lang] = {
        title: '<i class="flag flag-'+primary_info.flag+'"></i> '+primary_info.name,
        func: function() { plugin.switchTo(primary_lang); }
      }

      $.each(langs, function(lang, info) {
        if (lang == primary_lang)
          return;
        dropdown[lang] = {
          title: '<i class="flag flag-'+info.flag+'"></i> '+info.name,
          func: function() { plugin.switchTo(lang); }
        };
        plugin.textareas[lang] = primary.clone(false).attr({
          lang: lang,
          dir: info['direction'],
          'class': '',
        })
        .removeAttr('name').removeAttr('data-translate-tag')
        .text('')
        .insertAfter(primary);
      });

      // Add the button to the toolbar
      plugin.button = this.button.add('translate', __('Translate')),
      this.button.setAwesome('translate', 'flag flag-' + plugin.config.primary_lang_flag);
      plugin.button.parent().addClass('pull-right');
      this.button.addDropdown(plugin.button, dropdown);

      // Flip back to primary language before submitting
      this.$textarea.closest('form').submit(function() {
        plugin.switchTo(primary_lang);
      });
    },

    switchTo: function(lang) {
      var that = this;

      if (lang == this.translatable.current)
        return;

      if (this.translatable.translations === undefined) {
        this.translatable.fetch('ajax.php/i18n/translate/' + this.translatable.translateTag)
        .then(function(json) {
          that.translatable.translations = json;
          $.each(json, function(l, text) {
            that.translatable.textareas[l].val(text);
          });
          // Now switch to the language
          that.translatable.switchTo(lang);
        });
        return;
      }

      var html = this.$editor.html();
      this.$textarea.val(this.clean.onSync(html));
      this.$textarea = this.translatable.textareas[lang];
      this.code.set(this.$textarea.val());
      this.translatable.current = lang;

      this.button.setAwesome('translate', 'flag flag-' + this.translatable.langs[lang].flag);
      this.$editor.attr({lang: lang, dir: this.translatable.langs[lang].direction});
    },

    showCommit: function() {
      var plugin = this.translatable;

      if (this.translatable.current == this.translatable.primary) {
        if (this.translatable.$commit)
          this.translatable.$commit
          .slideUp(function() { $(this).remove(); plugin.$commit = undefined; });
        return true;
      }

      if (this.translatable.$commit)
        return true;

      this.translatable.$commit = $('<div class="language-commit"></div>')
      .hide()
      .appendTo(this.$box)
      .append($('<button type="button" class="white button commit"><i class="fa fa-save icon-save"></i> '+__('Save')+'</button>')
        .on('click', $.proxy(this.translatable.commit, this))
      )
      .slideDown();
    },

    commit: function() {
      var changes = {}, self = this,
          plugin = this.translatable,
          $commit = plugin.$commit;
      $commit.find('button').empty().text(' '+__('Saving'))
          .prop('disabled', true)
          .prepend($('<i>').addClass('fa icon-spin icon-spinner'));
      changes[plugin.current] = this.code.get();
      $.ajax('ajax.php/i18n/translate/' + plugin.translateTag, {
        type: 'post',
        data: changes,
        success: function() {
          $commit.slideUp(function() { $(this).remove(); plugin.$commit = undefined; });
        }
      });
    },

    urlcache: {},
    fetch: function( url, data, callback ) {
      var urlcache = this.translatable.urlcache;
      if ( !urlcache[ url ] ) {
        urlcache[ url ] = $.Deferred(function( defer ) {
          $.ajax( url, { data: data, dataType: 'json' } )
            .then( defer.resolve, defer.reject );
        }).promise();
      }
      return urlcache[ url ].done( callback );
    },
  };
};

if (!RedactorPlugins) var RedactorPlugins = {};

(function ($R) {
  $R.add('plugin', 'definedlinks', {
    init: function (app) {
      this.app = app;
      this.opts = app.opts;
      this.component = app.component;
      // local
      this.links = [];
    },
    // messages
    onmodal: {
      link: {
        open: function ($modal, $form) {
          if (!this.opts.definedlinks) return;
          this.$modal = $modal;
          this.$form = $form;
          this._load();
        }
      }
    },
    // private
    _load: function () {
      if (typeof this.opts.definedlinks === 'object') {
        this._build(this.opts.definedlinks);
      } else {
        $R.ajax.get({
          url: this.opts.definedlinks,
          success: this._build.bind(this)
        });
      }
    },
    _build: function (data) {
      var $selector = this.$modal.find('#redactor-defined-links');
      if ($selector.length === 0) {
        var $body = this.$modal.getBody();
        var $item = $R.dom('<div class="form-item" />');
        var $selector = $R.dom('<select id="redactor-defined-links" />');
        $item.append($selector);
        $body.prepend($item);
      }
      this.links = [];
      $selector.html('');
      $selector.off('change');
      for (var key in data) {
        if (!data.hasOwnProperty(key) || typeof data[key] !== 'object') {
          continue;
        }
        this.links[key] = data[key];
        var $option = $R.dom('<option>');
        $option.val(key);
        $option.html(data[key].name);
        $selector.append($option);
      }
      $selector.on('change', this._select.bind(this));
    },
    _select: function (e) {
      var formData = this.$form.getData();
      var key = $R.dom(e.target)
        .val();
      var data = {
        text: '',
        url: ''
      };
      if (key !== '0') {
        data.text = this.links[key].name;
        data.url = this.links[key].url;
      }
      if (formData.text !== '') {
        data = {
          url: data.url
        };
      }
      this.$form.setData(data);
    }
  });

  $R.add('plugin', 'fontcolor', {
    translations: {
      en: {
        "fontcolor": "Text Color",
        "text": "Text",
        "highlight": "Highlight"
      }
    },
    init: function (app) {
      this.app = app;
      this.opts = app.opts;
      this.lang = app.lang;
      this.inline = app.inline;
      this.toolbar = app.toolbar;
      this.selection = app.selection;
      // local
      this.colors = (this.opts.fontcolors) ? this.opts.fontcolors : ['#ffffff', '#000000', '#eeece1', '#1f497d', '#4f81bd', '#c0504d', '#9bbb59', '#8064a2', '#4bacc6', '#f79646', '#ffff00', '#f2f2f2', '#7f7f7f', '#ddd9c3', '#c6d9f0', '#dbe5f1', '#f2dcdb', '#ebf1dd', '#e5e0ec', '#dbeef3', '#fdeada', '#fff2ca', '#d8d8d8', '#595959', '#c4bd97', '#8db3e2', '#b8cce4', '#e5b9b7', '#d7e3bc', '#ccc1d9', '#b7dde8', '#fbd5b5', '#ffe694', '#bfbfbf', '#3f3f3f', '#938953', '#548dd4', '#95b3d7', '#d99694', '#c3d69b', '#b2a2c7', '#b7dde8', '#fac08f', '#f2c314', '#a5a5a5', '#262626', '#494429', '#17365d', '#366092', '#953734', '#76923c', '#5f497a', '#92cddc', '#e36c09', '#c09100', '#7f7f7f', '#0c0c0c', '#1d1b10', '#0f243e', '#244061', '#632423', '#4f6128', '#3f3151', '#31859b', '#974806', '#7f6000'];
    },
    // messages
    onfontcolor: {
      set: function (rule, value) {
        this._set(rule, value);
      },
      remove: function (rule) {
        this._remove(rule);
      }
    },
    // public
    start: function () {
      var btnObj = {
        title: this.lang.get('fontcolor')
      };
      var $dropdown = this._buildDropdown();
      this.$button = this.toolbar.addButton('fontcolor', btnObj);
      this.$button.setIcon('<i class="re-icon-fontcolor"></i>');
      this.$button.setDropdown($dropdown);
    },
    // private
    _buildDropdown: function () {
      var $dropdown = $R.dom('<div class="redactor-dropdown-cells">');
      this.$selector = this._buildSelector();
      this.$selectorText = this._buildSelectorItem('text', this.lang.get('text'));
      this.$selectorText.addClass('active');
      this.$selectorBack = this._buildSelectorItem('back', this.lang.get('highlight'));
      this.$selector.append(this.$selectorText);
      this.$selector.append(this.$selectorBack);
      this.$pickerText = this._buildPicker('textcolor');
      this.$pickerBack = this._buildPicker('backcolor');
      $dropdown.append(this.$selector);
      $dropdown.append(this.$pickerText);
      $dropdown.append(this.$pickerBack);
      this._buildSelectorEvents();
      $dropdown.width(242);
      return $dropdown;
    },
    _buildSelector: function () {
      var $selector = $R.dom('<div>');
      $selector.addClass('redactor-dropdown-selector');
      return $selector;
    },
    _buildSelectorItem: function (name, title) {
      var $item = $R.dom('<span>');
      $item.attr('rel', name)
        .html(title);
      $item.addClass('redactor-dropdown-not-close');
      return $item;
    },
    _buildSelectorEvents: function () {
      this.$selectorText.on('mousedown', function (e) {
        e.preventDefault();
        this.$selector.find('span')
          .removeClass('active');
        this.$pickerBack.hide();
        this.$pickerText.show();
        this.$selectorText.addClass('active');
      }.bind(this));
      this.$selectorBack.on('mousedown', function (e) {
        e.preventDefault();
        this.$selector.find('span')
          .removeClass('active');
        this.$pickerText.hide();
        this.$pickerBack.show();
        this.$selectorBack.addClass('active');
      }.bind(this));
    },
    _buildPicker: function (name) {
      var $box = $R.dom('<div class="re-dropdown-box-' + name + '">');
      var rule = (name == 'backcolor') ? 'background-color' : 'color';
      var len = this.colors.length;
      var self = this;
      var func = function (e) {
        e.preventDefault();
        var $el = $R.dom(e.target);
        self._set($el.data('rule'), $el.attr('rel'));
      };
      for (var z = 0; z < len; z++) {
        var color = this.colors[z];
        var $swatch = $R.dom('<span>');
        $swatch.attr({
          'rel': color,
          'data-rule': rule
        });
        $swatch.css({
          'background-color': color,
          'font-size': 0,
          'border': '2px solid #fff',
          'width': '22px',
          'height': '22px'
        });
        $swatch.on('mousedown', func);
        $box.append($swatch);
      }
      var $el = $R.dom('<a>');
      $el.attr({
        'href': '#'
      });
      $el.css({
        'display': 'block',
        'clear': 'both',
        'padding': '8px 5px',
        'font-size': '12px',
        'line-height': 1
      });
      $el.html(this.lang.get('none'));
      $el.on('click', function (e) {
        e.preventDefault();
        self._remove(rule);
      });
      $box.append($el);
      if (name == 'backcolor') $box.hide();
      return $box;
    },
    _set: function (rule, value) {
      var style = {};
      style[rule] = value;
      var args = {
        tag: 'span',
        style: style,
        type: 'toggle'
      };
      this.inline.format(args);
    },
    _remove: function (rule) {
      this.inline.remove({
        style: rule
      });
    }
  });

  $R.add('plugin', 'fontfamily', {
    translations: {
      en: {
        "fontfamily": "Font",
        "remove-font-family": "Remove Font Family"
      }
    },
    init: function (app) {
      this.app = app;
      this.opts = app.opts;
      this.lang = app.lang;
      this.inline = app.inline;
      this.toolbar = app.toolbar;
      // local
      this.fonts = (this.opts.fontfamily) ? this.opts.fontfamily : ['Arial', 'Helvetica', 'Georgia', 'Times New Roman', 'Monospace'];
    },
    // public
    start: function () {
      var dropdown = {};
      for (var i = 0; i < this.fonts.length; i++) {
        var font = this.fonts[i].replace(/'/g, '');
        dropdown[i] = {
          title: $R.dom('<span>').css('font-family', font).text(font).get().outerHTML,
          api: 'plugin.fontfamily.set',
          args: font,
        };
      }
      dropdown.remove = {
        title: this.lang.get('remove-font-family'),
        api: 'plugin.fontfamily.remove'
      };
      var $button = this.toolbar.addButton('fontfamily', {
        title: this.lang.get('fontfamily')
      });
      $button.setIcon('<i class="re-icon-fontfamily"></i>');
      $button.setDropdown(dropdown);
    },
    set: function (value) {
      var args = {
        tag: 'span',
        style: {
          'font-family': value
        },
        type: 'toggle'
      };
      this.inline.format(args);
    },
    remove: function () {
      this.inline.remove({
        style: 'font-family'
      });
    }
  });

  $R.add('plugin', 'table', {
    translations: {
      en: {
        "table": "Table",
        "insert-table": "Insert table",
        "insert-row-above": "Insert row above",
        "insert-row-below": "Insert row below",
        "insert-column-left": "Insert column left",
        "insert-column-right": "Insert column right",
        "add-head": "Add head",
        "delete-head": "Delete head",
        "delete-column": "Delete column",
        "delete-row": "Delete row",
        "delete-table": "Delete table"
      }
    },
    init: function (app) {
      this.app = app;
      this.lang = app.lang;
      this.opts = app.opts;
      this.caret = app.caret;
      this.editor = app.editor;
      this.toolbar = app.toolbar;
      this.component = app.component;
      this.inspector = app.inspector;
      this.insertion = app.insertion;
      this.selection = app.selection;
    },
    // messages
    ondropdown: {
      table: {
        observe: function (dropdown) {
          this._observeDropdown(dropdown);
        }
      }
    },
    onbottomclick: function () {
      this.insertion.insertToEnd(this.editor.getLastNode(), 'table');
    },
    // public
    start: function () {
      var dropdown = {
        observe: 'table',
        'insert-table': {
          title: this.lang.get('insert-table'),
          api: 'plugin.table.insert'
        },
        'insert-row-above': {
          title: this.lang.get('insert-row-above'),
          classname: 'redactor-table-item-observable',
          api: 'plugin.table.addRowAbove'
        },
        'insert-row-below': {
          title: this.lang.get('insert-row-below'),
          classname: 'redactor-table-item-observable',
          api: 'plugin.table.addRowBelow'
        },
        'insert-column-left': {
          title: this.lang.get('insert-column-left'),
          classname: 'redactor-table-item-observable',
          api: 'plugin.table.addColumnLeft'
        },
        'insert-column-right': {
          title: this.lang.get('insert-column-right'),
          classname: 'redactor-table-item-observable',
          api: 'plugin.table.addColumnRight'
        },
        'add-head': {
          title: this.lang.get('add-head'),
          classname: 'redactor-table-item-observable',
          api: 'plugin.table.addHead'
        },
        'delete-head': {
          title: this.lang.get('delete-head'),
          classname: 'redactor-table-item-observable',
          api: 'plugin.table.deleteHead'
        },
        'delete-column': {
          title: this.lang.get('delete-column'),
          classname: 'redactor-table-item-observable',
          api: 'plugin.table.deleteColumn'
        },
        'delete-row': {
          title: this.lang.get('delete-row'),
          classname: 'redactor-table-item-observable',
          api: 'plugin.table.deleteRow'
        },
        'delete-table': {
          title: this.lang.get('delete-table'),
          classname: 'redactor-table-item-observable',
          api: 'plugin.table.deleteTable'
        }
      };
      var obj = {
        title: this.lang.get('table')
      };
      var $button = this.toolbar.addButtonBefore('link', 'table', obj);
      $button.setIcon('<i class="re-icon-table"></i>');
      $button.setDropdown(dropdown);
    },
    insert: function () {
      var rows = 2;
      var columns = 3;
      var $component = this.component.create('table');
      for (var i = 0; i < rows; i++) {
        $component.addRow(columns);
      }
      $component = this.insertion.insertHtml($component);
      this.caret.setStart($component);
    },
    addRowAbove: function () {
      var $component = this._getComponent();
      if ($component) {
        var current = this.selection.getCurrent();
        var $row = $component.addRowTo(current, 'before');
        this.caret.setStart($row);
      }
    },
    addRowBelow: function () {
      var $component = this._getComponent();
      if ($component) {
        var current = this.selection.getCurrent();
        var $row = $component.addRowTo(current, 'after');
        this.caret.setStart($row);
      }
    },
    addColumnLeft: function () {
      var $component = this._getComponent();
      if ($component) {
        var current = this.selection.getCurrent();
        this.selection.save();
        $component.addColumnTo(current, 'left');
        this.selection.restore();
      }
    },
    addColumnRight: function () {
      var $component = this._getComponent();
      if ($component) {
        var current = this.selection.getCurrent();
        this.selection.save();
        $component.addColumnTo(current, 'right');
        this.selection.restore();
      }
    },
    addHead: function () {
      var $component = this._getComponent();
      if ($component) {
        this.selection.save();
        $component.addHead();
        this.selection.restore();
      }
    },
    deleteHead: function () {
      var $component = this._getComponent();
      if ($component) {
        var current = this.selection.getCurrent();
        var $head = $R.dom(current)
          .closest('thead');
        if ($head.length !== 0) {
          $component.removeHead();
          this.caret.setStart($component);
        } else {
          this.selection.save();
          $component.removeHead();
          this.selection.restore();
        }
      }
    },
    deleteColumn: function () {
      var $component = this._getComponent();
      if ($component) {
        var current = this.selection.getCurrent();
        var $currentCell = $R.dom(current)
          .closest('td, th');
        var nextCell = $currentCell.nextElement()
          .get();
        var prevCell = $currentCell.prevElement()
          .get();
        $component.removeColumn(current);
        if (nextCell) this.caret.setStart(nextCell);
        else if (prevCell) this.caret.setEnd(prevCell);
        else this.deleteTable();
      }
    },
    deleteRow: function () {
      var $component = this._getComponent();
      if ($component) {
        var current = this.selection.getCurrent();
        var $currentRow = $R.dom(current)
          .closest('tr');
        var nextRow = $currentRow.nextElement()
          .get();
        var prevRow = $currentRow.prevElement()
          .get();
        $component.removeRow(current);
        if (nextRow) this.caret.setStart(nextRow);
        else if (prevRow) this.caret.setEnd(prevRow);
        else this.deleteTable();
      }
    },
    deleteTable: function () {
      var table = this._getTable();
      if (table) {
        this.component.remove(table);
      }
    },
    // private
    _getTable: function () {
      var current = this.selection.getCurrent();
      var data = this.inspector.parse(current);
      if (data.isTable()) {
        return data.getTable();
      }
    },
    _getComponent: function () {
      var current = this.selection.getCurrent();
      var data = this.inspector.parse(current);
      if (data.isTable()) {
        var table = data.getTable();
        return this.component.create('table', table);
      }
    },
    _observeDropdown: function (dropdown) {
      var table = this._getTable();
      var items = dropdown.getItemsByClass('redactor-table-item-observable');
      var tableItem = dropdown.getItem('insert-table');
      if (table) {
        this._observeItems(items, 'enable');
        tableItem.disable();
      } else {
        this._observeItems(items, 'disable');
        tableItem.enable();
      }
    },
    _observeItems: function (items, type) {
      for (var i = 0; i < items.length; i++) {
        items[i][type]();
      }
    }
  });

  $R.add('class', 'table.component', {
    mixins: ['dom', 'component'],
    init: function (app, el) {
      this.app = app;
      // init
      return (el && el.cmnt !== undefined) ? el : this._init(el);
    },
    // public
    addHead: function () {
      this.removeHead();
      var columns = this.$element.find('tr')
        .first()
        .children('td, th')
        .length;
      var $head = $R.dom('<thead>');
      var $row = this._buildRow(columns, '<th>');
      $head.append($row);
      this.$element.prepend($head);
    },
    addRow: function (columns) {
      var $row = this._buildRow(columns);
      this.$element.append($row);
      return $row;
    },
    addRowTo: function (current, type) {
      return this._addRowTo(current, type);
    },
    addColumnTo: function (current, type) {
      var $current = $R.dom(current);
      var $currentRow = $current.closest('tr');
      var $currentCell = $current.closest('td, th');
      var index = 0;
      $currentRow.find('td, th')
        .each(function (node, i) {
          if (node === $currentCell.get()) index = i;
        });
      this.$element.find('tr')
        .each(function (node) {
          var $node = $R.dom(node);
          var origCell = $node.find('td, th')
            .get(index);
          var $origCell = $R.dom(origCell);
          var $td = $origCell.clone();
          $td.html('');
          if (type === 'right') $origCell.after($td);
          else $origCell.before($td);
        });
    },
    removeHead: function () {
      var $head = this.$element.find('thead');
      if ($head.length !== 0) $head.remove();
    },
    removeRow: function (current) {
      var $current = $R.dom(current);
      var $currentRow = $current.closest('tr');
      $currentRow.remove();
    },
    removeColumn: function (current) {
      var $current = $R.dom(current);
      var $currentRow = $current.closest('tr');
      var $currentCell = $current.closest('td, th');
      var index = 0;
      $currentRow.find('td, th')
        .each(function (node, i) {
          if (node === $currentCell.get()) index = i;
        });
      this.$element.find('tr')
        .each(function (node) {
          var $node = $R.dom(node);
          var origCell = $node.find('td, th')
            .get(index);
          var $origCell = $R.dom(origCell);
          $origCell.remove();
        });
    },
    // private
    _init: function (el) {
      var wrapper, element;
      if (typeof el !== 'undefined') {
        var $node = $R.dom(el);
        var node = $node.get();
        var $figure = $node.closest('figure');
        if ($figure.length !== 0) {
          wrapper = $figure;
          element = $figure.find('table')
            .get();
        } else if (node.tagName === 'TABLE') {
          element = node;
        }
      }
      this._buildWrapper(wrapper);
      this._buildElement(element);
      this._initWrapper();
    },
    _addRowTo: function (current, position) {
      var $current = $R.dom(current);
      var $currentRow = $current.closest('tr');
      if ($currentRow.length !== 0) {
        var columns = $currentRow.children('td, th')
          .length;
        var $newRow = this._buildRow(columns);
        $currentRow[position]($newRow);
        return $newRow;
      }
    },
    _buildRow: function (columns, tag) {
      tag = tag || '<td>';
      var $row = $R.dom('<tr>');
      for (var i = 0; i < columns; i++) {
        var $cell = $R.dom(tag);
        $cell.attr('contenteditable', true);
        $row.append($cell);
      }
      return $row;
    },
    _buildElement: function (node) {
      if (node) {
        this.$element = $R.dom(node);
      } else {
        this.$element = $R.dom('<table>');
        this.append(this.$element);
      }
    },
    _buildWrapper: function (node) {
      node = node || '<figure>';
      this.parse(node);
    },
    _initWrapper: function () {
      this.addClass('redactor-component');
      this.attr({
        'data-redactor-type': 'table',
        'tabindex': '-1',
        'contenteditable': false
      });
    }
  });

  $R.add('plugin', 'imagemanager', {
    translations: {
      en: {
        "choose": "Choose"
      }
    },
    init: function (app) {
      this.app = app;
      this.lang = app.lang;
      this.opts = app.opts;
    },
    // messages
    onmodal: {
      image: {
        open: function ($modal, $form) {
          if (!this.opts.imageManagerJson) return;
          this._load($modal)
        }
      }
    },
    // private
    _load: function ($modal) {
      var $body = $modal.getBody();
      this.$box = $R.dom('<div>');
      this.$box.attr('data-title', this.lang.get('choose'));
      this.$box.addClass('redactor-modal-tab');
      this.$box.hide();
      this.$box.css({
        overflow: 'auto',
        height: '300px',
        'line-height': 1
      });
      $body.append(this.$box);
      $R.ajax.get({
        url: this.opts.imageManagerJson,
        success: this._parse.bind(this)
      });
    },
    _parse: function (data) {
      for (var key in data) {
        var obj = data[key];
        if (typeof obj !== 'object') continue;
        var $img = $R.dom('<img>');
        var url = (obj.thumb) ? obj.thumb : obj.url;
        $img.attr('src', url);
        $img.attr('data-params', encodeURI(JSON.stringify(obj)));
        $img.css({
          width: '96px',
          height: '72px',
          margin: '0 4px 2px 0',
          cursor: 'pointer'
        });
        $img.on('click', this._insert.bind(this));
        this.$box.append($img);
      }
    },
    _insert: function (e) {
      e.preventDefault();
      var $el = $R.dom(e.target);
      var data = JSON.parse(decodeURI($el.attr('data-params')));
      this.app.api('module.image.insert', {
        image: data
      });
    }
  });

  $R.add('plugin', 'fullscreen', {
    translations: {
      en: {
        "fullscreen": "Fullscreen"
      }
    },
    init: function (app) {
      this.app = app;
      this.opts = app.opts;
      this.lang = app.lang;
      this.$win = app.$win;
      this.$doc = app.$doc;
      this.$body = app.$body;
      this.editor = app.editor;
      this.toolbar = app.toolbar;
      this.container = app.container;
      this.selection = app.selection;
      // local
      this.isOpen = false;
      this.docScroll = 0;
    },
    // public
    start: function () {
      var data = {
        title: this.lang.get('fullscreen'),
        api: 'plugin.fullscreen.toggle'
      };
      var button = this.toolbar.addButton('fullscreen', data);
      button.setIcon('<i class="re-icon-expand"></i>');
      button.addClass('pull-right');
      this.$target = (this.toolbar.isTarget()) ? this.toolbar.getTargetElement() : this.$body;
      if (this.opts.fullscreen) this.toggle();
    },
    toggle: function () {
      return (this.isOpen) ? this.close() : this.open();
    },
    open: function () {
      this.docScroll = this.$doc.scrollTop();
      this._createPlacemarker();
      this.selection.save();
      var $container = this.container.getElement();
      var $editor = this.editor.getElement();
      var $html = (this.toolbar.isTarget()) ? $R.dom('body, html') : this.$target;
      if (this.opts.toolbarExternal) this._buildInternalToolbar();
      this.$target.prepend($container);
      this.$target.addClass('redactor-body-fullscreen');
      $container.addClass('redactor-box-fullscreen');
      if (this.isTarget) $container.addClass('redactor-box-fullscreen-target');
      $html.css('overflow', 'hidden');
      if (this.opts.maxHeight) $editor.css('max-height', '');
      if (this.opts.minHeight) $editor.css('min-height', '');
      if (this.opts.maxWidth) {
        this.toolbar.$wrapper.css({'max-width': this.opts.maxWidth, 'margin': 'auto'});
      }
      this._resize();
      this.$win.on('resize.redactor-plugin-fullscreen', this._resize.bind(this));
      this.$doc.scrollTop(0);
      var button = this.toolbar.getButton('fullscreen');
      button.setIcon('<i class="re-icon-retract"></i>');
      this.selection.restore();
      this.isOpen = true;
      this.opts.zindex = 1051;
    },
    close: function () {
      this.isOpen = false;
      this.opts.zindex = false;
      this.selection.save();
      var $container = this.container.getElement();
      var $editor = this.editor.getElement();
      var $html = $R.dom('body, html');
      if (this.opts.toolbarExternal) this._buildExternalToolbar();
      this.$target.removeClass('redactor-body-fullscreen');
      this.$win.off('resize.redactor-plugin-fullscreen');
      $html.css('overflow', '');
      $container.removeClass('redactor-box-fullscreen redactor-box-fullscreen-target');
      $editor.css('height', 'auto');
      if (this.opts.minHeight) $editor.css('minHeight', this.opts.minHeight);
      if (this.opts.maxHeight) $editor.css('maxHeight', this.opts.maxHeight);
      if (this.opts.maxWidth) this.toolbar.$wrapper.css('max-width', '');
      var button = this.toolbar.getButton('fullscreen');
      button.setIcon('<i class="re-icon-expand"></i>');
      this._removePlacemarker($container);
      this.selection.restore();
      this.$doc.scrollTop(this.docScroll);
    },
    // private
    _resize: function () {
      var $toolbar = this.toolbar.getElement();
      var $editor = this.editor.getElement();
      var height = this.$win.height() - $toolbar.height();
      $editor.height(height);
    },
    _buildInternalToolbar: function () {
      var $wrapper = this.toolbar.getWrapper();
      var $toolbar = this.toolbar.getElement();
      $wrapper.addClass('redactor-toolbar-wrapper');
      $wrapper.append($toolbar);
      $toolbar.removeClass('redactor-toolbar-external');
      $container.prepend($wrapper);
    },
    _buildExternalToolbar: function () {
      var $wrapper = this.toolbar.getWrapper();
      var $toolbar = this.toolbar.getElement();
      this.$external = $R.dom(this.opts.toolbarExternal);
      $toolbar.addClass('redactor-toolbar-external');
      this.$external.append($toolbar);
      $wrapper.remove();
    },
    _createPlacemarker: function () {
      var $container = this.container.getElement();
      this.$placemarker = $R.dom('<span />');
      $container.after(this.$placemarker);
    },
    _removePlacemarker: function ($container) {
      this.$placemarker.before($container);
      this.$placemarker.remove();
    }
  });

  $R.add('plugin', 'video', {
    translations: {
      en: {
        "video": "Video",
        "video-html-code": "Video Embed Code or Youtube/Vimeo Link"
      }
    },
    modals: {
      'video':
        '<form action=""> \
           <div class="form-item"> \
             <label for="modal-video-input">## video-html-code ## <span class="req">*</span></label> \
             <textarea id="modal-video-input" name="video" style="height: 160px;"></textarea> \
           </div> \
         </form>'
    },
    init: function (app) {
      this.app = app;
      this.lang = app.lang;
      this.opts = app.opts;
      this.toolbar = app.toolbar;
      this.component = app.component;
      this.insertion = app.insertion;
      this.inspector = app.inspector;
    },
    // messages
    onmodal: {
      video: {
        opened: function ($modal, $form) {
          $form.getField('video')
            .focus();
        },
        insert: function ($modal, $form) {
          var data = $form.getData();
          this._insert(data);
        }
      }
    },
    oncontextbar: function (e, contextbar) {
      var data = this.inspector.parse(e.target)
      if (data.isComponentType('video')) {
        var node = data.getComponent();
        var buttons = {
          "remove": {
            title: this.lang.get('delete'),
            api: 'plugin.video.remove',
            args: node
          }
        };
        contextbar.set(e, node, buttons, 'bottom');
      }
    },
    // public
    start: function () {
      var obj = {
        title: this.lang.get('video'),
        api: 'plugin.video.open'
      };
      var $button = this.toolbar.addButtonAfter('image', 'video', obj);
      $button.setIcon('<i class="re-icon-video"></i>');
    },
    open: function () {
      var options = {
        title: this.lang.get('video'),
        width: '600px',
        name: 'video',
        handle: 'insert',
        commands: {
          insert: {
            title: this.lang.get('insert')
          },
          cancel: {
            title: this.lang.get('cancel')
          }
        }
      };
      this.app.api('module.modal.build', options);
    },
    remove: function (node) {
      this.component.remove(node);
    },
    // private
    _insert: function (data) {
      this.app.api('module.modal.close');
      if (data.video.trim() === '') {
        return;
      }
      // parsing
      data.video = this._matchData(data.video);
      // inserting
      if (this._isVideoIframe(data.video)) {
        var $video = this.component.create('video', data.video);
        this.insertion.insertHtml($video);
      }
    },
    _isVideoIframe: function (data) {
      return (data.match(/<iframe|<video/gi) !== null);
    },
    _matchData: function (data) {
      var iframeStart = '<iframe style="width: 500px; height: 281px;" src="';
      var iframeEnd = '" frameborder="0" allowfullscreen></iframe>';
      if (this._isVideoIframe(data)) {
        var allowed = ['iframe', 'video', 'source'];
        var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi;
        data = data.replace(tags, function ($0, $1) {
          return (allowed.indexOf($1.toLowerCase()) === -1) ? '' : $0;
        });
      }
      if (data.match(this.opts.regex.youtube)) {
        data = data.replace(this.opts.regex.youtube, iframeStart + '//www.youtube.com/embed/$1' + iframeEnd);
      } else if (data.match(this.opts.regex.vimeo)) {
        data = data.replace(this.opts.regex.vimeo, iframeStart + '//player.vimeo.com/video/$2' + iframeEnd);
      }
      return data;
    }
  });
  $R.add('class', 'video.component', {
    mixins: ['dom', 'component'],
    init: function (app, el) {
      this.app = app;
      // init
      return (el && el.cmnt !== undefined) ? el : this._init(el);
    },
    // private
    _init: function (el) {
      if (typeof el !== 'undefined') {
        var $node = $R.dom(el);
        var $wrapper = $node.closest('figure');
        if ($wrapper.length !== 0) {
          this.parse($wrapper);
        } else {
          this.parse('<figure>');
          this.append(el);
        }
      } else {
        this.parse('<figure>');
      }
      this._initWrapper();
    },
    _initWrapper: function () {
      this.addClass('redactor-component');
      this.attr({
        'data-redactor-type': 'video',
        'tabindex': '-1',
        'contenteditable': false
      });
    }
  });
  $R.add('plugin', 'textdirection', {
    translations: {
      en: {
        "change-text-direction": "RTL-LTR",
        "left-to-right": "Left to Right",
        "right-to-left": "Right to Left"
      }
    },
    init: function (app) {
      this.app = app;
      this.lang = app.lang;
      this.block = app.block;
      this.toolbar = app.toolbar;
    },
    // public
    start: function () {
      var dropdown = {};
      dropdown.ltr = {
        title: this.lang.get('left-to-right'),
        api: 'plugin.textdirection.set',
        args: 'ltr'
      };
      dropdown.rtl = {
        title: this.lang.get('right-to-left'),
        api: 'plugin.textdirection.set',
        args: 'rtl'
      };
      var $button = this.toolbar.addButton('textdirection', {
        title: this.lang.get('change-text-direction')
      });
      $button.setIcon('<i class="re-icon-textdirection"></i>');
      $button.setDropdown(dropdown);
    },
    set: function (type) {
      if (type === 'rtl') this.block.add({
        attr: {
          dir: 'rtl'
        }
      });
      else this.block.remove({
        attr: 'dir'
      });
    }
  });

  // Monkey patch context bar to have ability to add a button
  var contextbar = $R[$R.env['module']]['contextbar'];
  $R.add('module', 'contextbar', $R.extend(contextbar.prototype, {
    append: function(e, button) {
      var $btn = $R.create('contextbar.button', this.app, button);
      if ($btn.html() !== '')
      {
          this.$contextbar.append($btn);
      }
      var pos = this._buildPosition(e, this.$el);
      this.$contextbar.css(pos);
    }
  }));

  $R.add('plugin', 'contexttypeahead', {
    typeahead: false,
    context: false,
    variables: false,

    init: function(app) {
      this.app = app;
    },

    start: function() {
      this.$editor = this.app.editor.getElement();
      this.$element = $(this.app.rootElement);
      if (!this.$element.data('rootContext'))
        return;

      this.$editor.on('keyup', this.watch.bind(this));
      this.$editor.on('keydown', this.watch.bind(this));
      this.$editor.on('click', this.watch.bind(this));
    },

    watch: function(e) {
      var current = this.app.api('selection.getCurrent'),
          allText = this.$editor.text(),
          offset = this.app.api('offset.get', this.app.editor.$editor),
          lhs = allText.substring(0, offset.start),
          search = new RegExp(/%\{([^}]*)$/),
          match,
          e = $.Event(e);

      if (!lhs) {
        return !e.isDefaultPrevented();
      }

      if (e.which == 27 || !(match = search.exec(lhs)))
        // No longer in a element — close typeahead
        return this.destroy();

      if (e.type == 'click')
        return;

      // Locate the position of the cursor and the number of characters back
      // to the `%{` symbols
      var sel         = this.app.api('selection.get'),
          range       = sel.getRangeAt(0),
          content     = current.textContent,
          clientRects = range.getClientRects(),
          position    = clientRects[0],
          backText    = match[1],
          parent      = this.app.api('selection.getParent') || this.$element,
          that        = this;

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
                  extendable = Object.keys(that.variables).some(function(v) {
                    return v.match(further);
                  }),
                  arrow = extendable ? this.options.arrow.clone() : '';

              return $('<div/>').html(base).prepend(arrow).html()
                + $('<span class="faded">')
                  .text(' — ' + item.desc)
                  .wrap('<div>').parent().html();
            },
            item: '<li><a href="#" style="display:block"></a></li>',
            source: this.getContext.bind(this),
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
            onselect: this.select.bind(this),
            scroll: true,
            items: 100
          });
      }

      if (position) {
        var width = this.textWidth(
              backText,
              this.app.api('selection.getParent') || $('<div class="redactor-editor">')
            ),
            pleft = $(parent).offset().left,
            left = position.left - width;

        if (left < pleft)
            // This is a bug in chrome, but I'm not sure how to adjust it
            left += pleft;

        this.typeahead
          .css({top: position.top + this.app.$win.scrollTop(), left: left});
      }

      this.typeahead
        .val(match[1])
        .triggerHandler(e);

      return !e.isDefaultPrevented();
    },

    getContext: function(typeahead, query) {
      var dfd, that=this,
          root = this.$element.data('rootContext');
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
      if (this.typeahead) {
        this.typeahead.typeahead('hide');
        this.typeahead.remove();
        this.typeahead = false;
      }
    },

    select: function(item, event) {
      // Collapse multiple textNodes together
      (this.app.api('selection.getBlock') || this.$element.get(0)).normalize();
      var current = this.app.api('selection.getCurrent'),
          sel     = this.app.api('selection.get'),
          range   = sel.getRangeAt(0),
          cursorAt = range.endOffset,
          // TODO: Consume immediately following `}` symbols
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

      range.setStart(current, newLeft.length - 1);
      range.setEnd(current, newLeft.length - 1);
      this.app.api('selection.setRange', range);
      if (!autoExpand)
          return this.destroy();

      this.typeahead.val(selected);
      this.typeahead.typeahead('lookup');
      return false;
    }
  });

  $R.add('plugin', 'translatable', {
    langs: undefined,
    config: undefined,
    current: undefined,
    primary: undefined,
    changed: {},

    init: function(app) {
      this.app = app;
      this.statusbar = app.statusbar;
      this.$textarea = $R.dom(this.app.rootElement);
      this.$editor = app.editor.getElement();
    },

    start: function() {
      this.fetch('ajax.php/i18n/langs/all')
        .then(this.setLangs.bind(this));
      getConfig().then(this.setConfig.bind(this));
      $editor = this.app.editor.getElement();
      this.translateTag = this.$textarea.data()['translateTag'];
    },

    setLangs: function(langs) {
      this.langs = langs;
      this.buildDropdown();
    },

    setConfig: function(config) {
      this.config = config;
      this.buildDropdown();
    },

    buildDropdown: function() {
      if (!this.config || !this.langs)
        return;

      var primary = this.$textarea,
          primary_lang = this.config.primary_language.replace('-','_'),
          primary_info = this.langs[primary_lang],
          items = {},
          dropdown = {
            primary_lang: {
              title: '<i class="flag flag-'+primary_info.flag+'"></i> '+primary_info.name,
              api: 'plugin.translatable.switchTo',
              args: primary_lang,
            },
          },
          button = this.app.toolbar.addButton('flag', {
            title: __('Translate'),
          });

      this.primary = this.current = primary_lang;
      this.button = button;

      $.each(this.langs, function(lang, info) {
        if (lang == primary_lang)
          return;
        dropdown[lang] = {
          title: '<i class="flag flag-'+info.flag+'"></i> '+info.name,
          api: 'plugin.translatable.switchTo',
          args: lang,
        };
      });

      // Add the button to the toolbar
      button.setDropdown(dropdown);

      // Flip back to primary language before submitting
      var that=this;
      this.app.editor.getElement().closest('form').on('submit', function() {
        that.switchTo(primary_lang);
      });

      this.showStatus(this.primary);
    },

    showStatus: function(lang) {
      var tstatus = $R.dom('<span>').text('lang: ')
      tstatus.append($R.dom('<i class="flag flag-'+this.langs[this.current].flag+'"></i>'))
      tstatus.append(document.createTextNode(' ' + this.current))
      this.statusbar.add('translatable', tstatus);

      this.button.setIcon('<i class="flag flag-'+this.langs[lang].flag+'"></i>');
    },

    switchTo: function(lang) {
      if (lang == this.current)
        return;

      var that = this;
      this.fetch('ajax.php/i18n/translate/' + this.translateTag)
        .then(function(json) {
          // Preserve current text
          json[that.current] = that.app.source.getCode();
          that.current = lang;
          that.app.insertion.set(json[lang] || '', false, true);
          that.app.api('module.source.sync');

          that.app.editor.getElement()
            .attr({lang: lang, dir: that.langs[lang].direction});

          that.showStatus(lang);
          that.showCommit();
        });
    },

    onchanged: function() {
        this.showCommit();
        this.changed[this.current] = true;
    },

    showCommit: function() {
      if (this.current == this.primary) {
        this.statusbar.remove('translatable:commit');
        return true;
      }

      if (!this.changed[this.current])
        return true;

      var tstatus = $R.dom('<a href="#"></a>')
        .text(__('save translation'))
        .on('click', this.commit.bind(this))
      this.statusbar.add('translatable:commit', tstatus);
    },

    commit: function() {
      if (!this.changed[this.current])
          return this.app.statusbar.remove('translatable:commit');

      var changes = {}, self = this;

      this.app.statusbar.add('translatable:commit', __('saving...'))
      changes[this.current] = this.app.source.getCode();

      $.ajax('ajax.php/i18n/translate/' + this.translateTag, {
        type: 'post',
        data: changes,
        success: function() {
          self.changed[self.current] = false;
          self.app.statusbar.remove('translatable:commit');
        }
      });
      // Don't bubble the click event
      return false;
    },

    urlcache: {},
    fetch: function( url, data, callback ) {
      var urlcache = this.urlcache;
      if ( !urlcache[ url ] ) {
        urlcache[ url ] = $.Deferred(function( defer ) {
          $.ajax( url, { data: data, dataType: 'json' } )
            .then( defer.resolve, defer.reject );
        }).promise();
      }
      return urlcache[ url ].done( callback );
    },
  });
})(Redactor);
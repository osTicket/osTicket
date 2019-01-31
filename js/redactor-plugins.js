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
})(Redactor);

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

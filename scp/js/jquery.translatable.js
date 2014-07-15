
!function( $ ){

  "use strict";

  var Translatable = function( element, options ) {
    this.$element = $(element);
    this.options = $.extend({}, $.fn.translatable.defaults, options);
    if (!this.$element.data('translateTag'))
        return;

    this.$translations = $('<ul class="translations"></ul>');
    this.$status = $('<li class="status"><i class="icon-spinner icon-spin"></i> Loading ...</li>')
      .appendTo(this.$translations);
    this.$footer = $('<div class="add-translation"></div>');
    this.$select = $('<select name="locale"></select>');
    this.$menu = $(this.options.menu).appendTo('body');
    this.$element.wrap(
      $('<span></span>').css({display:'inline-block', 'white-space':'nowrap'})
    )
    this.$button = $(this.options.button).insertAfter(this.$element);
    //this.$menu.append('<a class="close pull-right" href=""><i class="icon-remove-circle"></i></a>')
    //    .on('click', $.proxy(this.hide, this));
    this.$menu.append(this.$translations).append(this.$footer);
    this.shown = false;
    this.populated = false;
    this.decorate();
  },
  // Class-static variables
  urlcache = {};

  Translatable.prototype = {

    constructor: Translatable,

    fetch: function( url, data, callback ) {
      if ( !urlcache[ url ] ) {
        urlcache[ url ] = $.Deferred(function( defer ) {
          $.ajax( url, { data: data, dataType: 'json' } )
            .then( defer.resolve, defer.reject );
        }).promise();
      }
      return urlcache[ url ].done( callback );
    },

    decorate: function() {
      this.$button.on('click', $.proxy(this.toggle, this));
      var self = this;
      this.fetch('ajax.php/i18n/langs').then(function(json) { self.langs = json; });
    },

    buildAdd: function() {
      var self=this;
      this.$footer
        .append($('<form method="post"></form>')
          .append(this.$select)
          .append($('<button type="button"><i class="icon-plus-sign"></i> Add</button>')
            .on('click', $.proxy(this.define, this))
          )
        );
      this.fetch('ajax.php/i18n/langs').then(function(langs) {
        $.each(langs, function(k, v) {
          self.$select.append($('<option>').val(k).text(v));
        });
      });
    },

    populate: function() {
      var self=this;
      if (this.populated)
        return;
      this.buildAdd();
      this.fetch('ajax.php/i18n/translate/' + this.$element.data('translateTag'))
      .then(function(json) {
        $.each(json, function(k,v) {
          self.add(k, v);
        });
        if (!Object.keys(json).length) {
          self.$status.text('Not currently translated');
        }
        else
          self.$status.remove();
      });
      self.populated = true;
    },

    define: function(e) {
      this.add($('option:selected', this.$select).val());
    },

    add: function(lang, text) {
      this.$translations.append(
        $('<li>')
        .append($('<label class="language">').text(this.langs[lang])
          .append($('<input type="text" data-lang="'+lang+'">')
            .on('change', $.proxy(this.showCommit, this))
            .val(text)
          )
        )
      );
      $('option[value='+lang+']', this.$select).remove();
      if (!$('option', this.$select).length)
        this.$footer.hide();
      this.$status.remove();
    },

    showCommit: function(e) {
      if (this.$commit)
          return this.$commit.show();

      return this.$commit = $('<div class="language-commit"></div>')
        .insertAfter(this.$translations)
        .append($('<button type="button" class="commit"><i class="fa fa-save icon-save"></i> Save</button>')
          .on('click', $.proxy(this.commit, this))
        );
    },

    commit: function(e) {
      var changes = {}, self = this;
      $('input[type=text]', this.$translations).each(function() {
        changes[$(this).data('lang')] = $(this).val();
      });
      $.ajax('ajax.php/i18n/translate/' + this.$element.data('translateTag'), {
        type: 'post',
        data: changes,
        success: function() {
          self.$commit.hide();
        }
      });
    },

    toggle: function(e) {
      e.stopPropagation();
      e.preventDefault();

      if (this.shown)
        this.hide();
      else
        this.show();
    },

    show: function() {
      if (this.shown)
          return this;

      var pos = $.extend({}, this.$element.offset(), {
        height: this.$element[0].offsetHeight
      })

      this.$menu.css({
        top: pos.top + pos.height
      , left: pos.left
      });

      this.populate();

      this.$menu.show();
      this.shown = true;
      return this;
    },

    hide: function() {
      if (this.shown) {
        this.$menu.hide();
        this.shown = false;
      }
      return this;
    }


  };

  /* PLUGIN DEFINITION
   * =========================== */

  $.fn.translatable = function ( option ) {
    return this.each(function () {
      var $this = $(this),
        data = $this.data('translatable'),
        options = typeof option == 'object' && option;
      if (!data) $this.data('translatable', (data = new Translatable(this, options)));
      if (typeof option == 'string') data[option]();
    });
  };

  $.fn.translatable.defaults = {
    menu: '<div class="translatable dropdown-menu"></div>',
    button: '<button class="translatable"><i class="fa fa-globe icon-globe"></i></button>'
  };

  $.fn.translatable.Constructor = Translatable;

}( window.jQuery );

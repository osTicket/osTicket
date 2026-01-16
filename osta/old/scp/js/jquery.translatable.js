
!function( $ ){

  "use strict";

  var Translatable = function( element, options ) {
    this.$element = $(element);
    this.options = $.extend({}, $.fn.translatable.defaults, options);
    if (!this.$element.data('translateTag'))
        return;

    this.shown = false;
    this.populated = false;

    this.fetch('ajax.php/i18n/langs').then($.proxy(function(json) {
      this.langs = json;
      if (Object.keys(this.langs).length) this.decorate();
    }, this));
  },
  // Class-static variables
  urlcache = {};

  Translatable.prototype = {

    constructor: Translatable,

    fetch: function( url, data, callback ) {
      if ( !urlcache[ url ] ) {
        urlcache[ url ] = $.Deferred(function( defer ) {
          $.ajax( url, { data: data, dataType: 'json', global: false } )
            .then( defer.resolve, defer.reject );
        }).promise();
      }
      return urlcache[ url ].done( callback );
    },

    decorate: function() {
      this.$translations = $('<ul class="translations"></ul>');
      this.$status = $('<li class="status"><i class="icon-spinner icon-spin"></i> '+__('Loading')+' ...</li>')
        .appendTo(this.$translations);
      this.$footer = $('<div class="add-translation"></div>');
      this.$select = $('<select name="locale"></select>');
      this.$menu = $(this.options.menu).appendTo('body');
      this.$element.wrap('<div class="translatable"></div>');
      this.$container = this.$element.parent();
      if (this.$element.width() > 100)
          this.$element.width(this.$element.width()-35);
      this.$container.wrap('<div style="display:inline-block;position:relative;width:auto;white-space:nowrap;"></div>');
      this.$button = $(this.options.button).appendTo(this.$container);
      this.$menu.append($('<span class="close"><i class="icon-remove"></i></span>')
          .on('click', $.proxy(this.hide, this)));
      if (this.$element.is('textarea')) {
          this.$container.addClass('textarea');
      }
      this.$menu.append(this.$translations).append(this.$footer);

      this.$button.on('click', $.proxy(this.toggle, this));

      this.$element
        .addClass('translatable')
        .focus($.proxy(function() { this.addClass('focus'); }, this.$container))
        .blur($.proxy(function() { this.removeClass('focus'); }, this.$container));
      getConfig().then($.proxy(function(c) {
        this.attr({'spellcheck': 'true', 'lang': c.primary_language})
        $('<span class="flag"></span>')
          .addClass('flag-' + c.primary_lang_flag)
          .insertAfter(this);
        }, this.$element));
    },

    buildAdd: function() {
      var self=this;
      this.$footer
        .append($('<form method="post"></form>')
          .append(this.$select)
          .append($('<button type="button"><i class="icon-plus-sign"></i> '+__('Add')+'</button>')
            .on('click', $.proxy(this.define, this))
          )
        );
      this.fetch('ajax.php/i18n/langs').then(function(langs) {
        $.each(langs, function(k, v) {
          self.$select.append($('<option>').val(k).text(v.name));
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
          self.$status.text(__('Not currently translated'));
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
      var info = this.langs[lang];
      this.$translations.append(
        $('<li>')
        .append($('<label class="language">')
          .text(info.name)
          .prepend($('<span>').addClass('flag flag-'+info.flag))
          .append($('<input type="text" data-lang="'+lang+'">')
            .attr('lang', lang)
            .attr('spellcheck', 'true')
            .attr('dir', info.direction || 'ltr')
            .on('change keydown', $.proxy(this.showCommit, this))
            .val(text)
          )
        )
        .effect('highlight')
      );
      $('option[value='+lang+']', this.$select).remove();
      if (!$('option', this.$select).length)
        this.$footer.hide();
      this.$status.remove();
    },

    showCommit: function(e) {
      if (this.$commit) {
          this.$commit.find('button').empty().text(' '+__('Save'))
              .prepend($('<i>').addClass('fa icon-save'));
          return !this.$commit.is(':visible')
              ? this.$commit.slideDown() : true;
      }
      return this.$commit = $('<div class="language-commit"></div>')
        .hide()
        .insertAfter(this.$translations)
        .append($('<button type="button" class="white button commit"><i class="fa fa-save icon-save"></i> '+__('Save')+'</button>')
          .on('click', $.proxy(this.commit, this))
        )
        .slideDown();
    },

    commit: function(e) {
      var changes = {}, self = this;
      $('input[type=text]', this.$translations).each(function() {
        var trans = $(this).val();
        if (!trans)
            $(this).closest('li').slideUp();
        changes[$(this).data('lang')] = trans;
      });
      this.$commit.prop('disabled', true);
      this.$commit.find('button').empty().text(' '+__('Saving'))
          .prepend($('<i>').addClass('fa icon-spin icon-spinner'));
      $.ajax('ajax.php/i18n/translate/' + this.$element.data('translateTag'), {
        type: 'post',
        data: changes,
        success: function() {
          self.$commit.slideUp();
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

      var pos = $.extend({}, this.$container.offset(), {
        height: this.$container[0].offsetHeight
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
    menu: '<div class="translations"></div>',
    button: '<button type="button" class="translatable"><i class="fa fa-globe icon-globe"></i></button>'
  };

  $.fn.translatable.Constructor = Translatable;

}( window.jQuery );

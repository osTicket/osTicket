/*
 * jQuery jFontSize Plugin
 * Examples and documentation: http://jfontsize.com
 * Original Author: Frederico Soares Vanelli
 *   fredsvanelli@gmail.com
 *   http://twitter.com/fredvanelli
 *   http://facebook.com/fred.vanelli
 *
 * Copyright (c) 2011
 * Version: 1.0 (2011-07-15)
 * Dual licensed under the MIT and GPL licenses.
 * http://jfontsize.com/license
 * Requires: jQuery v1.2.6 or later
 * 
 * Version 2.0 by Vincent Chabredier / Ouvrages
 * http://www.ouvrages-web.fr
 *
 * Copyright (c) 2013
 * Requires: 
 ** jQuery v1.2.6 or later
 ** jStorage (http://www.jstorage.info/)
 * 
*/

(function($) {
  return $.fn.jfontsize = function(opcoes) {
    var $this, apply, current_size, defaults, save;
    $this = $(this);
    defaults = {
      btnMinusClasseId: "#jfontsize-minus",
      btnDefaultClasseId: "#jfontsize-default",
      btnPlusClasseId: "#jfontsize-plus",
      btnMinusMaxHits: 10,
      btnPlusMaxHits: 10,
      sizeChange: 1
    };
    if (opcoes) {
      opcoes = $.extend(defaults, opcoes);
    }
    save = function() {
      return $.jStorage.set("jfontsize", current_size);
    };
    apply = function() {
      return $this.each(function(i) {
        var fontsize, size;
        if (!($(this).data("initial_size") != null)) {
          fontsize = $(this).css("font-size");
          fontsize = parseInt(fontsize.replace("px", ""));
          $(this).data("initial_size", fontsize);
        }
        size = $(this).data("initial_size") + (current_size * opcoes.sizeChange);
        return $(this).css("font-size", size + "px");
      });
    };
    $(opcoes.btnMinusClasseId + ", " + opcoes.btnDefaultClasseId + ", " + opcoes.btnPlusClasseId).removeAttr("href");
    $(opcoes.btnMinusClasseId + ", " + opcoes.btnDefaultClasseId + ", " + opcoes.btnPlusClasseId).css("cursor", "pointer");
    current_size = $.jStorage.get("jfontsize", 0);
    if (current_size === (-opcoes.btnMinusMaxHits)) {
      $(opcoes.btnMinusClasseId).addClass("jfontsize-disabled");
    }
    if (current_size === opcoes.btnPlusMaxHits) {
      $(opcoes.btnPlusClasseId).addClass("jfontsize-disabled");
    }
    apply();
    $(opcoes.btnMinusClasseId).click(function() {
      $(opcoes.btnPlusClasseId).removeClass("jfontsize-disabled");
      if (current_size > (-opcoes.btnMinusMaxHits)) {
        current_size--;
        if (current_size === (-opcoes.btnMinusMaxHits)) {
          $(opcoes.btnMinusClasseId).addClass("jfontsize-disabled");
        }
        apply();
        return save();
      }
    });
    $(opcoes.btnDefaultClasseId).click(function() {
      $(opcoes.btnMinusClasseId).removeClass("jfontsize-disabled");
      $(opcoes.btnPlusClasseId).removeClass("jfontsize-disabled");
      current_size = 0;
      $this.each(function(i) {
        return $(this).css("font-size", $(this).data("initial_size") + "px");
      });
      return save();
    });
    return $(opcoes.btnPlusClasseId).click(function() {
      $(opcoes.btnMinusClasseId).removeClass("jfontsize-disabled");
      if (current_size < opcoes.btnPlusMaxHits) {
        current_size++;
        if (current_size === opcoes.btnPlusMaxHits) {
          $(opcoes.btnPlusClasseId).addClass("jfontsize-disabled");
        }
        apply();
        return save();
      }
    });
  };
})(jQuery);
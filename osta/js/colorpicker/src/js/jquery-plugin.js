'use strict';

import Colorpicker from './Colorpicker';
import $ from 'jquery';

let plugin = 'colorpicker';

$[plugin] = Colorpicker;

$.fn[plugin] = function (option) {
  let apiArgs = Array.prototype.slice.call(arguments, 1),
    isSingleElement = (this.length === 1),
    returnValue = null;

  let $jq = this.each(function () {
    let $this = $(this),
      inst = $this.data(plugin),
      options = ((typeof option === 'object') ? option : {});

    if (!inst) {
      inst = new Colorpicker(this, options);
      $this.data(plugin, inst);
    }

    if (typeof option === 'string') {
      if (option === 'colorpicker') {
        returnValue = inst;
      } else if ($.isFunction(inst[option])) {
        returnValue = inst[option].apply(inst, apiArgs);
      } else { // its a property ?
        if (apiArgs.length) {
          // set property
          inst[option] = apiArgs[0];
        }
        returnValue = inst[option];
      }
    } else {
      returnValue = $this;
    }
  });

  return isSingleElement ? returnValue : $jq;
};

$.fn[plugin].constructor = Colorpicker;

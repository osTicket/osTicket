'use strict';

import Palette from './Palette';
import $ from 'jquery';

let defaults = {
  barTemplate: '<div class="colorpicker-bar colorpicker-swatches"></div>',
  swatchTemplate: '<i class="colorpicker-swatch"></i>'
};

class Swatches extends Palette {
  constructor(colorpicker, options = {}) {
    super(colorpicker, Object.assign({}, defaults, options));
  }

  isEnabled() {
    return this.getLength() > 0;
  }

  onCreate(event) {
    super.onCreate(event);

    if (!this.isEnabled()) {
      return;
    }

    let colorpicker = this.colorpicker,
      swatchContainer = $(this.options.barTemplate),
      isAliased = (this.options.namesAsValues === true) && !Array.isArray(this.colors);

    $.each(this.colors, (name, value) => {
      let $swatch = $(this.options.swatchTemplate)
        .css('background-color', value)
        .attr('data-name', name)
        .attr('data-value', value)
        .attr('title', `${name}: ${value}`);

      $swatch.on('mousedown.colorpicker touchstart.colorpicker',
        function (e) {
          e.preventDefault();
          colorpicker.setValue(isAliased ? $(this).data('name') : $(this).data('value'));
        }
      );
      swatchContainer.append($swatch);
    });

    colorpicker.picker.append(swatchContainer);
  }
}

export default Swatches;

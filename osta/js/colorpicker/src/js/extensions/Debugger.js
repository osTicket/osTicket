'use strict';

import Extension from 'Extension';
import $ from 'jquery';

class Debugger extends Extension {
  constructor(colorpicker, options = {}) {
    super(colorpicker, options);

    /**
     * @type {number}
     */
    this.eventCounter = 0;
    if (this.colorpicker.hasInput()) {
      this.colorpicker.input.on('change.colorpicker-ext', $.proxy(this.onChangeInput, this));
    }
  }

  /**
   * @fires colorpickerDebug
   * @param {string} eventName
   * @param {*} args
   */
  log(eventName, ...args) {
    this.eventCounter += 1;

    let logMessage = `#${this.eventCounter}: Colorpicker#${this.colorpicker.id} [${eventName}]`;

    console.debug(logMessage, ...args);

    /**
     * (Colorpicker) Fired by the ConsoleDebug extension whenever it logs something
     *
     * @event colorpickerDebug
     */
    this.colorpicker.element.trigger({
      type: 'colorpickerDebug',
      colorpicker: this.colorpicker,
      color: this.color,
      debug: {
        debugger: this,
        eventName: eventName,
        logArgs: args,
        logMessage: logMessage
      }
    });
  }

  resolveColor(color) {
    this.log('resolveColor()', color);
    return false;
  }

  onCreate(event) {
    this.log('colorpickerCreate');
    return super.onCreate(event);
  }

  onDestroy(event) {
    this.log('colorpickerDestroy');
    this.eventCounter = 0;

    if (this.colorpicker.hasInput()) {
      this.colorpicker.input.off('.colorpicker-ext');
    }

    return super.onDestroy(event);
  }

  onUpdate(event) {
    this.log('colorpickerUpdate');
  }

  /**
   * @listens _change
   * @param {Event} event
   */
  onChangeInput(event) {
    this.log('input:change.colorpicker', event.value, event.color);
  }

  onChange(event) {
    this.log('colorpickerChange', event.value, event.color);
  }

  onInvalid(event) {
    this.log('colorpickerInvalid', event.value, event.color);
  }

  onHide(event) {
    this.log('colorpickerHide');
    this.eventCounter = 0;
  }

  onShow(event) {
    this.log('colorpickerShow');
  }

  onDisable(event) {
    this.log('colorpickerDisable');
  }

  onEnable(event) {
    this.log('colorpickerEnable');
  }
}

export default Debugger;

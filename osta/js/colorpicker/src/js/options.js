'use strict';
/**
 * @module
 */

/**
 * Colorpicker default options
 */
export default {
  /**
   * If true, loads the Debugger extension automatically into the current instance
   * @type {boolean}
   * @default false
   */
  debug: false,
  /**
   * Forces a color, ignoring the one from the elements value or data-color attribute.
   *
   * @type {(String|Color|boolean)}
   * @default false
   */
  color: false,
  /**
   * Forces an specific color format. If false, it will be automatically detected the first time,
   * but if null it will be always recalculated.
   *
   * Note that the ending 'a' of the format meaning "alpha" has currently no effect, meaning that rgb is the same as
   * rgba excepting if the alpha channel is disabled (see useAlpha).
   *
   * @type {('rgb'|'rgba'|'prgb'|'prgba'|'hex'|'hex3'|'hex6'|'hex8'|'hsl'|'hsla'|'hsv'|'hsva'|'name'|boolean)}
   * @default false
   */
  format: false,
  /**
   * Horizontal mode layout.
   *
   * If true, the hue and alpha channel bars will be rendered horizontally, above the saturation selector.
   *
   * @type {boolean}
   * @default false
   */
  horizontal: false,
  /**
   * Forces to show the colorpicker as an inline element
   *
   * @type {boolean}
   * @default false
   */
  inline: false,
  /**
   * Children input CSS selector
   *
   * @type {String}
   * @default 'input'
   */
  input: 'input',
  /**
   * Colorpicker container CSS selector. If given, the colorpicker will be placed inside this container.
   * If true, the colorpicker element itself will be used as the container.
   *
   * @type {String|boolean}
   * @default false
   */
  container: false, // container selector
  /**
   * Children color component CSS selector.
   * If it exists, the child <i> element background will be changed on color change.
   *
   * @type {String|boolean}
   * @default '.add-on, .input-group-addon'
   */
  component: '.add-on, .input-group-addon',
  /**
   * Fallback color to use when the given color is invalid.
   * If false, the latest valid color will be used as a fallback.
   *
   * @type {String|Color|boolean}
   * @default false
   */
  fallbackColor: false,
  /**
   * If enabled, the input content will be replaced always with a valid color,
   * if not enabled the invalid color will be left in the input, but valid in the internal color object.
   *
   * @type {boolean}
   * @default false
   */
  autoInputFallback: false,
  /**
   * If true a hash will be prepended to hexadecimal colors.
   * If false, the hash will be removed.
   * This only affects the input values.
   *
   * @type {boolean}
   * @default false
   */
  useHashPrefix: true,
  /**
   * If true or false the alpha adjustment bar will be displayed no matter what.
   * If false it will be always hidden and alpha channel won't be allowed programmatically for this instance,
   * so the selected or typed color will be always opaque.
   *
   * @type {boolean}
   * @default true
   */
  useAlpha: true,
  /**
   * This only applies when the color format is hexadecimal.
   * If true, the alpha channel will be allowed for hexadecimal formatted colors, making them having 4 or 8 chars
   * (RGBA or RRGGBBAA). This format is not yet supported in all modern browsers, so it is disabled by default.
   * If false, rgba will be used whenever there is an alpha change different than 1 and the color format is
   * automatic.
   *
   * @type {boolean}
   * @default true
   */
  enableHex8: false,
  /**
   * Vertical sliders configuration
   * @type {Object}
   */
  sliders: {
    saturation: {
      maxLeft: 100,
      maxTop: 100,
      callLeft: 'setSaturationRatio',
      callTop: 'setBrightnessRatio'
    },
    hue: {
      maxLeft: 0,
      maxTop: 100,
      callLeft: false,
      callTop: 'setHueRatio'
    },
    alpha: {
      maxLeft: 0,
      maxTop: 100,
      callLeft: false,
      callTop: 'setAlphaRatio'
    }
  },
  /**
   * Horizontal sliders configuration
   * @type {Object}
   */
  slidersHorz: {
    saturation: {
      maxLeft: 100,
      maxTop: 100,
      callLeft: 'setSaturationRatio',
      callTop: 'setBrightnessRatio'
    },
    hue: {
      maxLeft: 100,
      maxTop: 0,
      callLeft: 'setHueRatio',
      callTop: false
    },
    alpha: {
      maxLeft: 100,
      maxTop: 0,
      callLeft: 'setAlphaRatio',
      callTop: false
    }
  },
  /**
   * Colorpicker popup alignment.
   * For now only right is supported.
   *
   * @type {('right')}
   * @default 'right'
   */ // TODO: add 'left' and 'auto' support.
  align: 'right',
  /**
   * Custom class to be added to the colorpicker element
   *
   * @type {String}
   */
  customClass: null,
  /**
   * Colorpicker widget template
   * @type {String}
   * @example
   * <!-- This is the default template: -->
   * <div class="colorpicker">
   *   <div class="colorpicker-saturation"><i class="colorpicker-guide"><i></i></i></div>
   *   <div class="colorpicker-hue"><i class="colorpicker-guide"></i></div>
   *   <div class="colorpicker-alpha"><i class="colorpicker-guide"></i></div>
   *   <div class="colorpicker-color"><div /></div>
   * </div>
   */
  template: `<div class="colorpicker">
    <div class="colorpicker-saturation"><i class="colorpicker-guide"><i /></div>
    <div class="colorpicker-hue"><i class="colorpicker-guide"></i></div>
    <div class="colorpicker-alpha"><i class="colorpicker-guide"></i></div></div>`,
  /**
   *
   * Associative object with the extension class name and its config.
   * Colorpicker comes with many bundled extensions: debugger, palette, preview and swatches (a superset of Palette).
   *
   * @type {Object}
   * @example
   *   extensions: [
   *     {
   *       name: 'swatches'
   *       colors: {
   *         'primary': '#337ab7',
   *         'success': '#5cb85c',
   *         'info': '#5bc0de',
   *         'warning': '#f0ad4e',
   *         'danger': '#d9534f'
   *       },
   *       namesAsValues: true
   *     }
   *   ]
   */
  extensions: [
    {
      name: 'preview',
      showText: false
    }
  ]
};

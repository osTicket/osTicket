'use strict';

import tinycolor from 'tinycolor2';

function unwrapColor(color) {
  if (color instanceof tinycolor) {
    return {
      r: color._r,
      g: color._g,
      b: color._b,
      a: color._a
    };
  }
  return color;
}

/**
 * Sanitizes a format string, so it is compatible with tinycolor,
 * or returns the same value if it is not a string.
 *
 * @param {String} format
 * @returns {String|*}
 * @private
 */
function getCompatibleFormat(format) {
  if (format instanceof String || typeof format === 'string') {
    return format.replace(/a$/gi, '');
  }

  return format;
}

/**
 * Color manipulation class that extends the tinycolor library class.
 */
class Color extends tinycolor {
  /**
   * Identifier of the color instance.
   *
   * @type {int}
   * @readonly
   */
  get id() {
    return this._tc_id;
  }

  /**
   * Format of the parsed color.
   *
   * @type {String}
   * @readonly
   */
  get format() {
    return this._format;
  }

  /**
   * All options of the current instance.
   *
   * @type {{format: String, gradientType: String}}
   * @readonly
   */
  get options() {
    return {
      format: this._format,
      gradientType: this._gradientType
    };
  }

  /**
   * @returns {{h, s, v, a}}
   */
  get hsva() {
    return this.toHsv();
  }

  /**
   * @returns {{h, s, v, a}}
   */
  get hsvaRatio() {
    let hsv = this.hsva;

    return {
      h: hsv.h / 360,
      s: hsv.s,
      v: hsv.v,
      a: hsv.a
    };
  }

  /**
   * foo bar
   * @param {Color|*} color
   * @param {{format}} [options]
   * @constructor
   */
  constructor(color, options = {format: null}) {
    if (options.format) {
      options.format = getCompatibleFormat(options.format);
    }
    super(unwrapColor(color), options);

    /**
     * @type {Color|*}
     */
    this._originalInput = color; // keep real original color
    /**
     * Hue backup to not lose the information when saturation is 0.
     * @type {number}
     */
    this._hbak = this.hsva.h;
    /**
     * If set, it contains a reference to a previous color that caused the creation of this one.
     * @type {Color}
     */
    this.previous = null;
  }

  /**
   * Compares a color object with this one and returns true if it is equal or false if not.
   *
   * @param {Color} color
   * @returns {boolean}
   */
  equals(color) {
    if (!(color instanceof tinycolor)) {
      return false;
    }
    return this._r === color._r &&
      this._g === color._g &&
      this._b === color._b &&
      this._a === color._a &&
      this._roundA === color._roundA &&
      this._format === color._format &&
      this._gradientType === color._gradientType &&
      this._ok === color._ok;
  }

  /**
   * Imports all variables of the given color to this instance, excepting `_tc_id`.
   * @param {Color} color
   */
  importColor(color) {
    if (!(color instanceof tinycolor)) {
      throw new Error('Color.importColor: The color argument is not an instance of tinycolor.');
    }
    this._originalInput = color._originalInput;
    this._r = color._r;
    this._g = color._g;
    this._b = color._b;
    this._a = color._a;
    this._roundA = color._roundA;
    this._format = getCompatibleFormat(color._format);
    this._gradientType = color._gradientType;
    this._ok = color._ok;
    // omit .previous and ._tc_id import
  }

  /**
   * Imports the _r, _g, _b, _a, _hbak and _ok variables of the given color to this instance.
   * @param {Color} color
   */
  importRgb(color) {
    if (!color instanceof Color) {
      throw new Error('Color.importColor: The color argument is not an instance of tinycolor.');
    }
    this._r = color._r;
    this._g = color._g;
    this._b = color._b;
    this._a = color._a;
    this._ok = color._ok;
    this._hbak = color._hbak;
  }

  /**
   * @param {{h,s,v,a}} hsv
   */
  importHsv(hsv) {
    this._hbak = hsv.h;
    this.importRgb(new Color(hsv, this.options));
  }

  /**
   * @returns {Color}
   */
  getCopy() {
    return new Color(this.hsva, this.options);
  }

  /**
   * @returns {Color}
   */
  getHueOnlyCopy() {
    return new Color({h: this._hbak ? this._hbak : this.hsva.h, s: 100, v: 100}, this.options);
  }

  /**
   * @returns {Color}
   */
  getOpaqueCopy() {
    return new Color(Object.assign({}, this.hsva, {a: 1}), this.options);
  }

  /**
   * @param {number} h Degrees from 0 to 360
   */
  setHue(h) {
    this.importHsv(Object.assign({}, this.hsva, {h: h}));
  }

  /**
   * @param {number} s Percent from 0 o 100
   */
  setSaturation(s) {
    this.importHsv(Object.assign({}, this.hsva, {s: s}));
  }

  /**
   * @param {number} v Percent from 0 o 100
   */
  setBrightness(v) {
    this.importHsv(Object.assign({}, this.hsva, {v: v}));
  }

  /**
   * @param {number} h Ratio from 0.0 to 1.0
   */
  setHueRatio(h) {
    if (h === 0) {
      return;
    }
    this.setHue((1 - h) * 360);
  }

  /**
   * @param {number} s Ratio from 0.0 to 1.0
   */
  setSaturationRatio(s) {
    this.setSaturation(s);
  }

  /**
   * @param {number} v Ratio from 0.0 to 1.0
   */
  setBrightnessRatio(v) {
    this.setBrightness(1 - v);
  }

  /**
   * @param {number} a Ratio from 0.0 to 1.0
   */
  setAlphaRatio(a) {
    this.setAlpha(1 - a);
  }

  /**
   * @returns {boolean}
   */
  isTransparent() {
    return this._a === 0;
  }

  /**
   * @returns {boolean}
   */
  hasTransparency() {
    return this._a !== 1;
  }

  /**
   * @param {string|null} [format] One of "rgb", "prgb", "hex"/"hex6", "hex3", "hex8", "hsl", "hsv"/"hsb", "name"
   * @returns {String}
   */
  toString(format = null) {
    format = format ? getCompatibleFormat(format) : this.format;

    let colorStr = super.toString(format);

    if (colorStr && colorStr.match(/^#[0-9a-f]{3,8}$/i)) {
      // Support transparent for hex formats
      if (this.isTransparent() && (this._r === 0) && (this._g === 0) && (this._b === 0)) {
        return 'transparent';
      }
    }

    return colorStr;
  }
}

export default Color;

//'use strict';

import Color from '../src/js/Color';
import test from 'ava';

let tests = {
  "hex6": {
    "#5367ce": "#5367ce",
    "#5367ce55": "#5367ce",
    "invalid": "#000000",
    "rgb(83, 103, 206)": "#5367ce"
  },
  "hex8": {
    "#5367ce": "#5367ceff",
    "#5367ce55": "#5367ce55",
    "invalid": "#000000ff",
    "rgb(83, 103, 206)": "#5367ceff"
  },
  "rgb": {
    "#5367ce": "rgb(83, 103, 206)",
    "#5367ce55": "rgba(83, 103, 206, 0.33)",
    "invalid": "rgb(0, 0, 0)",
    "rgb(83, 103, 206)": "rgb(83, 103, 206)"
  }
};

tests.rgba = tests.rgb;

let runColorTest = function (title, data, fn, fnArgs = [], ctorArgs = []) {
  test(title, t => {
    for (let colorInput in data) {
      if (!data.hasOwnProperty(colorInput)) continue;
      let expectedColorOutput = data[colorInput];

      // Lower case input
      let color = new Color(colorInput, ...ctorArgs);
      t.is(color[fn].call(color, ...fnArgs), expectedColorOutput);

      // Upper case input
      color = new Color(colorInput.toUpperCase(), ...ctorArgs);
      t.is(color[fn].call(color, ...fnArgs), expectedColorOutput);

      // Input without hash
      color = new Color(colorInput.replace(/^#/g, ''), ...ctorArgs);
      t.is(color[fn].call(color, ...fnArgs), expectedColorOutput);

      // Input without hash, uppercase
      color = new Color(colorInput.replace(/^#/g, '').toUpperCase(), ...ctorArgs);
      t.is(color[fn].call(color, ...fnArgs), expectedColorOutput);
    }
  });
};

for (let format in tests) {
  if (!tests.hasOwnProperty(format)) continue;
  runColorTest(`Color.toString returns a ${format} color`, tests[format], 'toString', [format], []);
}

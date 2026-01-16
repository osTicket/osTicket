'use strict';
const path = require('path');
const webpack = require('webpack');
const libraryName = 'bootstrap-colorpicker';
let entries = {};

entries[libraryName] = './src/js/jquery-plugin.js';
entries[libraryName + '.min'] = './src/js/jquery-plugin.js';

module.exports = {
  entry: entries,
  output: {
    filename: '[name].js',
    library: libraryName,
    libraryTarget: 'umd',
    umdNamedDefine: true
  },
  devtool: 'source-map',
  resolve: {
    modules: [
      path.resolve('./src/js'),
      path.resolve('./node_modules')
    ]
  },
  module: {
    loaders: [
      {
        test: /(\.jsx|\.js)$/,
        loader: 'babel-loader',
        exclude: /node_modules/,
        query: {
          presets: [
            require.resolve('babel-preset-env')
          ]
        }
      },
      {
        test: /(\.jsx|\.js)$/,
        loader: 'eslint-loader',
        exclude: /node_modules|docs/
      }
    ]
  },
  externals: {
    'jquery': 'jQuery'
  },
  plugins: [
    new webpack.ProvidePlugin({
      $: 'jquery',
      jQuery: 'jquery',
      'window.$': 'jquery',
      'window.jQuery': 'jquery'
    }),
    new webpack.optimize.UglifyJsPlugin({
      include: /\.min\.js$/,
      minimize: true,
      sourceMap: true
    })
  ]
};

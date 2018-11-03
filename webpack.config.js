const path = require('path');
const CleanWebpackPlugin = require('clean-webpack-plugin');
const ExtractTextPlugin = require('extract-text-webpack-plugin');
const webpack = require('webpack');
const ModernizrWebpackPlugin = require('modernizr-webpack-plugin');
const UglifyJsPlugin = require('uglifyjs-webpack-plugin');
const OptimizeCSSAssetsPlugin = require("optimize-css-assets-webpack-plugin");

const extractPlugin = new ExtractTextPlugin({
  filename: './css/bundle.css'
});

const modernizrConfig = {
  filename: 'js/vendor/modernizr-bundle.js',
  'options': [
    'setClasses'
  ],
  'feature-detects': [
    "emoji",
    "touchevents",
    "css/animations",
    "css/backgroundblendmode",
    "css/columns",
    "css/filters",
    "css/flexbox",
    "css/hyphens",
    "css/positionsticky",
    "css/scrollbars",
    "css/transforms",
    "css/transforms3d",
    "css/transitions",
    "img/srcset",
  ],
  minify: {
    output: {
      comments: false,
      beautify: false
    }
  }
}

const config = {
  entry: './index.js',
  output: {
    filename: 'js/bundle.js',
    path: path.resolve(__dirname, 'www', 'assets'),
    publicPath: "/assets/"
  },
  plugins: [
    new CleanWebpackPlugin(['www/assets/js']),
    extractPlugin,
    new ModernizrWebpackPlugin(modernizrConfig)
  ],
  devServer: {
    compress: true
  },
  devtool: 'inline-source-map',
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['@babel/preset-env']
          }
        }
      },
      {
        test: /\.scss$/,
        include: [path.resolve(__dirname, 'src', 'scss')],
        use: extractPlugin.extract({
          use: [
            {
              loader: 'css-loader',
              options: {
                sourceMap: true
              }
            },
            {
              loader: 'sass-loader',
              options: {
                sourceMap: true
              }
            }
          ],
          fallback: 'style-loader'
        })
      },
      {
        test: /\.(jpg|png|gif|svg)$/,
        use: [
          {
            loader: 'file-loader',
            options: {
              name: '[name].[ext]',
              outputPath: './www/assets/images/'
            }
          }
        ]
      },
      {
        test: /\.(woff|woff2|eot|ttf|otf)$/,
        use: [
          {
            loader: 'font-loader',
            options: {
              name: '[name].[ext]',
              outputPath: './www/assets/fonts/'
            }
          }
        ]
      }
    ]
  },
  optimization: {
    minimize: true,
    minimizer: [
      new UglifyJsPlugin({
        cache: true,
        parallel: true,
        sourceMap: true
      }),
      new OptimizeCSSAssetsPlugin({})
    ]
  }
}

module.exports = config;

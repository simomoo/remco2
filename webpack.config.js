const path = require('path');

const config = {
  context: path.resolve(__dirname, 'src'),
  entry: {
    app: './js/application.js'
  },
  output: {
    filename: 'js/build/bundle.js',
    path: path.resolve(__dirname, 'www/assets/')
  }
}

module.exports = config;

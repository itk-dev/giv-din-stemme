// webpack.config.js
// with symfony encore https://symfony.com/doc/current/frontend/encore/installation.html

const Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('build')
    .setPublicPath('/build')
    .addEntry('giv_din_stemme', './js/giv_din_stemme.js')
    .cleanupOutputBeforeBuild()
    .disableSingleRuntimeChunk()
;

module.exports = Encore.getWebpackConfig();

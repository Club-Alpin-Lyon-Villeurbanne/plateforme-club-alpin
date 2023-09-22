require('dotenv').config({path: __dirname + '/.env.local'});
const webpack = require('webpack');
const Encore = require('@symfony/webpack-encore');
const fs = require("fs");
const domain = process.env.WEBPACK_DOMAIN;
const port = process.env.WEBPACK_PORT;

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

console.log(`Setting-up Encore in ${Encore.isProduction() ? '\x1b[32mproduction\x1b[0m ⚡⚡' : '\x1b[32mdevelopment\x1b[0m 🤓'} mode`);
console.log('');

Encore
    // enable Sass
    .enableSassLoader()
    // directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // public path used by the web server to access the output path
    .setPublicPath(Encore.isProduction() ? '/build' : `http://${domain}:${port}/build`)
    // only needed for CDN's or sub-directory deploy
    .setManifestKeyPrefix('build/')

    .addEntry('dashboard-bundle', [
        // Necessary once you want to use webpack chunks
        // on CDN
        './assets/app.js',
    ])

    // setup sass
    .addEntry('app-styles', './assets/scss/index.scss')

    .addPlugin(new webpack.IgnorePlugin({
        resourceRegExp: /^\.\/locale$/,
        contextRegExp: /moment$/,
    }))

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    .configureBabel((config) => {
        config.plugins.push('@babel/plugin-proposal-class-properties');
    })

    // enables @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    })

    // enables Sass/SCSS support
    //.enableSassLoader()

    // uncomment if you use TypeScript
    //.enableTypeScriptLoader()

    // uncomment if you use React
    //.enableReactPreset()

    // uncomment to get integrity="..." attributes on your script & link tags
    // requires WebpackEncoreBundle 1.4 or higher
    .enableIntegrityHashes(Encore.isProduction())

    // uncomment if you're having problems with a jQuery plugin
    .autoProvidejQuery()

    .configureDevServerOptions((options) => {
        options.devMiddleware = {
            publicPath: `http://${domain}:8348/static/`,
        };
        options.hot = true;
        options.host = domain;
        options.port = 8348;

        options.allowedHosts = 'all';
        options.client = {
            webSocketURL: {
                hostname: domain,
                port: 8348,
            },
        };
        options.headers = {
            'Access-Control-Allow-Origin': '*',
            'Access-Control-Allow-Credentials': true,
        };
    });
;

module.exports = Encore.getWebpackConfig();

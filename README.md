# ViteHelper plugin for CakePHP 5

The plugin provides a Helper Class for CakePHP to facilitate the use of [Vite JS](https://vitejs.dev/).

When running the Vite dev server, the Helper provides the right script tags for hot module replacement and page
reloading.

In production mode, the Helper loads the bundled files. `@vitejs/plugin-legacy` is supported, which will
insert `nomodule`-tags for older browsers, e.g. older iOS devices, which do not support js modules.

> This readme is for **version 1.x.** If you are migrating from 0.x and something is unclear, read the Migration guide
> under `/docs`. Feel free to open an issue if you run into problems.

## Installation

You can install this plugin into your CakePHP application using [composer](https://getcomposer.org).

### CakePHP Version Map

| CakePHP version | Plugin Version | Branch | min. PHP Version |
|-----------------|----------------|--------|------------------|
| ^3.10           | /              | cake3  | ^7.4             |
| ^4.2            | 0.x            | master | ^7.4             |
| ^4.2            | 1.x            | master | ^8.0             |
| ^5.0            | 2.x            | cake5  | ^8.1             |
| ^5.0            | 3.x            | cake5  | ^8.1             |

The recommended way to install the plugin is:

```shell
composer require passchn/cakephp-vite
```

Load the plugin in your Application.php:

```shell
bin/cake plugin load ViteHelper
```

Load the Helper in your AppView.php:

```php
$this->loadHelper('ViteHelper.ViteScripts');
```

## Usage

In your php-layout, include this in your html head:

```php
<?= $this->fetch('css') ?>
```

Just before the closing `</body>` tag, insert this line:

```php
<?= $this->fetch('script') ?>
```

These are the default view blocks in CakePHP.
[Lern more about view blocks in the Book](https://book.cakephp.org/4/en/views.html#using-view-blocks).

In your php-template or in layout you can import javascript files with:

```php
<?php $this->ViteScripts->script('resources/main.ts') ?>
```

… or multiple files

```php
<?php $this->ViteScripts->script(['resources/main.ts', 'resources/main2.ts', 'resources/main3.ts']) ?>
```

If you imported CSS files inside your JavaScript files, this method automatically
appends your css tags to the css view block.

> If you don't have any css-entries defined in your vite-config, you can skip the `::css()` method call.

In your php-template you can import css files with:

```php
<?php $this->ViteScripts->css('resources/style.css') ?>
```

… or multiple files

```php
<?php $this->ViteScripts->css(['resources/style.css', 'resources/style2.css', 'resources/style3.css']) ?>
```

## Configuration

The plugin comes with some default configuration. You may need to change it depending on your setup. Or you might not
need any config at all.

You can override some of these config settings through the `$options` of the helper methods. Or you can pass
your own instance of `ViteHelperConfig` to a helper method as a second parameter.

```php
'ViteHelper' => [
    'plugin' => false, // or string 'MyPlugin' to serve plugin build assets
    'environment' => \ViteHelper\Enum\Environment::PRODUCTION, // available options PRODUCTION, DEVELOPMENT, FROM_DETECTOR
    'development' => [
        'url' => 'http://localhost:3000', // url of the vite dev server
    ],
    'build' => [
        'outDirectory' => false, // output directory of build assets. string (e.g. 'dist') or false.
        'manifest' => WWW_ROOT . 'manifest.json', // absolute path to manifest
    ],
],
```

You can override the defaults in your `app.php`, `app_local.php`, or `app_vite.php`.

See the plugin's [app_vite.php](https://github.com/brandcom/cakephp-vite/blob/master/config/app_vite.php) for reference.

## Environment

The plugin MUST know for sure, if you are in development mode or production mode. You must explicitly set in the config
you are in `\ViteHelper\Enum\Environment::PRODUCTION` or `\ViteHelper\Enum\Environment::DEVELOPMENT`. To increase the
flexibility of the plugin you can use `\ViteHelper\Enum\Environment::FROM_DETECTOR`. This settings will use a
[detector](https://book.cakephp.org/5/en/controllers/request-response.html#Cake\Http\ServerRequest::is) to detect the
environment.

```php
$this->request->addDetector(
    ViteHelper\View\Helper\ViteScriptsHelper::VITESCRIPT_DETECTOR_NAME,
    function ($serverRequest) {
        // your logic goes here
        // return true for prod, false for dev
    }
);
```

## Helper method usage with options

The options are the same for `::script()` and `::css()`.

### Example

```php
$this->ViteScripts->script(
    // files for the block
    files: ['resource/file1.js', 'resource/file2.js'], // can be also a string

    // filter for environment
    // default null the file(s) will be printed both on prod and dev
    // possible values: \ViteHelper\Enum\Environment::PRODUCTION, \ViteHelper\Enum\Environment::DEVELOPMENT, null
    environment: null,

    // name of the view block to render the scripts in
    // default null
    // on null uses `css` for style, `script` for javascript files
    block: null,

    // plugin prefix
    // default null
    // on null uses the plugin used in default config
    plugin: null,
);
```

// TODO pluginScript should be removed, devEntries and prodFilter makes no-sense anymore

**Note:** You need to set `devEntries` when running the dev server. They have to either be set in the config or
through the helper method. In contrast, you only need `files` or `prodFilter` if you are interested in php-side
code-splitting and don't use dynamic imports in js.

It depends on your project and use case how you define entries. If you don't use `prodFilter` or `files`, the plugin
will serve all your entry files which might just be the case you want. So don't overconfigure it ;)

## Opt out of global config + Plugin development

You can use the helper methods with multiple configurations through the `$config` argument.

> **New in version 2.3:** You can pass a config key instead of a config instance to the helper. The default config key
> is `ViteHelper`.

This might be useful when using plugin scripts or when developing plugins:

```php
<?php $this->ViteScripts->pluginScript('MyPlugin', devMode: true, config: 'MyPlugin.ViteConfig'); ?>
```

The example above uses a convenience method to load plugin scripts for `MyPlugin`. DevMode is enabled and the helper
will use a CakePHP config under the key `MyPlugin.ViteConfig`. In this way, you can scope your App's and your plugin's
config.

It is assumed that the `manifest.json` is available directly in your plugin's `webroot`. If this is not the case, you
should define the absolute path throuh the `build.manifest` config option.

## Vite JS bundler / Dev server

Install Vite e.g. via yarn:

```shell
yarn create vite
```

It is recommended to add the legacy plugin:

```shell
yarn add -D @vitejs/plugin-legacy
```

> See [Scaffolding Your First Vite Project](https://vitejs.dev/guide/#scaffolding-your-first-vite-project) on vitejs.dev
> for more information.

### Configuration

After installing, you will need to refactor the files a bit to make sense of it in a php project. The default config of
this plugin assumes that you put your js, ts, scss etc. in `/resources`.

The build files will end up in `/webroot/assets` by default. Your `vite.config.js or *.ts` file for vite stays in the
project root.

> Wanted: Examples for vite/plugin configs and directory structures. Feel free to contribute with a PR to show how your
> project uses this plugin.

#### Recommended configuration:

See the example [vite.config.ts content here](https://github.com/brandcom/cakephp-vite/wiki/example-vite-config). Note
that the config changes when upgrading to vite version `2.9.0` or higher.

A difference to other dev servers, e.g. webpack or gulp is that you won't access your
local site via the port where Vite is serving. This does not work with php.

Therefore, the `server.hmr` config will enable you to use hot module replacement via the websocket protocol.

The `build` options define where the bundled js and css will end up. These need to match plugin config.

More about configuring Vite can be found at [vitejs.dev/config](https://vitejs.dev/config/).

## Contributions

You can contribute to this plugin via pull requests. If you run into an error, you can open an issue.

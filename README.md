# ViteHelper plugin for CakePHP 4

The plugin provides a Helper Class for CakePHP to facilitate the use of [Vite JS](https://vitejs.dev/).

When running the Vite dev server, the Helper provides the right script tags for hot module replacement and page reloading.

In production mode, the Helper loads the bundled files. `@vitejs/plugin-legacy` is supported, which will
insert `nomodule`-tags for older browsers, e.g. older iOS devices, which do not support js modules.

> This readme is for **version 1.x.** If you are migrating from 0.x and something is unclear, read the Migration guide under `/docs`. Feel free to open an issue if you run into problems.

## Installation

You can install this plugin into your CakePHP application using [composer](https://getcomposer.org).

The recommended way to install composer packages is:

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
<?php $this->ViteScripts->script($options) ?>
```

… or by using this shortcut for a single entrypoint:

```php
<?php $this->ViteScripts->script('webroot_src/main.ts') ?>
```

If you imported CSS files inside your JavaScript files, this method automatically
appends your css tags to the css view block.

> If you don't have any css-entries defined in your vite-config, you can skip the `::css()` method call.

In your php-template you can import css files with:

```php
<?php $this->ViteScripts->css($options) ?>
```

… or by using this shortcut for a single entrypoint:

```php
<?php $this->ViteScripts->css('webroot_src/style.css') ?>
```

## Configuration

The plugin comes with some default configuration. You may need to change it depending on your setup. Or you might not need any config at all.

You can override some of these config settings through the `$options` of the helper methods. Or you can pass
your own instance of `ViteHelperConfig` to a helper method as a second parameter.

```php
'ViteHelper' => [
    'build' => [
        'outDirectory' => false, // output directory of build assets. string (e.g. 'dist') or false.
        'manifest' => WWW_ROOT . 'manifest.json', // absolute path to manifest
    ],
    'development' => [
        'scriptEntries' => ['someFolder/myScriptEntry.ts'], // relative to project root
        'styleEntries' =>  ['someFolder/myStyleEntry.scss'], // relative to project root. Unnecessary when using css-in-js.
        'hostNeedles' => ['.test', '.local'], // to check if the app is running locally
        'url' => 'http://localhost:3000', // url of the vite dev server
    ],
    'forceProductionMode' => false, // or true to always serve build assets
    'plugin' => false, // or string 'MyPlugin' to serve plugin build assets
    'productionHint' => 'vprod', // can be a true-ish cookie or url-param to serve build assets without changing the forceProductionMode config
    'viewBlocks' => [
        'css' => 'css', // name of the css view block
        'script' => 'script', // name of the script view block
    ],
],
```

You can override the defaults in your `app.php`, `app_local.php`, or `app_vite.php`.

See the plugin's [app_vite.php](https://github.com/brandcom/cakephp-vite/blob/master/config/app_vite.php) for reference.

Example:

```php
return [
    'ViteHelper' => [
        'forceProductionMode' => 1,
        'development' => [
            'hostNeedles' => ['.dev'], // if you don't use one of the defaults
            'url' => 'https://192.168.0.88:3000',
        ],
    ],
];
```

## Helper method usage with options

You can pass an `$options` array to override config or to completely skip the necessity to have a ViteHelper config.

The options are mostly the same for `::script()` and `::css()`.

### Example

```php
$this->ViteScripts->script([

    // this would append both the scripts and the css to a block named 'myCustomBlock'
    // don't forget to use the block through $this->fetch('myCustomBlock')
    'block' => 'myCustomBlock',
    'cssBlock' => 'myCustomBlock', // for ::script() only – if you use css imports inside js.

    // files that are entry files during development and that should be served during production
    'files' => [
        'webroot_src/main.ts',
    ],

    // "devEntries" is like "files". If you set "files", it will override both "devEntries" and "prodFilters"
    'devEntries' => ['webroot_src/main.ts']

    // "prodFilter" filters the entry files. Useful for code-splitting if you don't use dynamic imports
    'prodFilter' => 'webroot_src/main.ts' // as string if there's only one option
    'prodFilter' => 'main.ts' // also works - only looks for parts of the string
    'prodFilter' => ['main.ts'] // as array - same as above with multiple files
    'prodFilter' => function (ManifestRecord $record) { /* do something with the record and return true or false */ }
]);
```

**Note:** You need to set `devEntries` when running the dev server. They have to either be set in the config or
through the helper method. In contrast, you only need `files` or `prodFilter` if you are interested in php-side code-splitting and don't use dynamic imports in js.

It depends on your project and use case how you define entries. If you don't use `prodFilter` or `files`, the plugin will serve all your entry files which might just be the case you want. So don't overconfigure it ;)

## Vite JS bundler / Dev server

Install Vite e.g. via yarn:

```shell
yarn create vite
```

It is recommended to add the legacy plugin:

```shell
yarn add -D @vitejs/plugin-legacy
```

> See [Scaffolding Your First Vite Project](https://vitejs.dev/guide/#scaffolding-your-first-vite-project) on vitejs.dev for more information.

### Configuration

After installing, you will need to refactor the files a bit to make sense of it in a php project. The default config of this plugin assumes that you put your js, ts, scss etc. in `/webroot_src`.

The build files will end up in `/webroot/assets` by default. Your `vite.config.js or *.ts` file for vite stays in the project root.

> Wanted: Examples for vite/plugin configs and directory structures. Feel free to contribute with a PR to show how your project uses this plugin.

#### Recommended configuration:

See the example [vite.config.ts content here](https://github.com/brandcom/cakephp-vite/wiki/example-vite-config). Note that the config changes when upgrading to vite version `2.9.0` or higher.

A difference to other dev servers, e.g. webpack or gulp is that you won't access your
local site via the port where Vite is serving. This does not work with php.

Therefore, the `server.hmr` config will enable you to use hot module replacement via the websocket protocol.

The `build` options define where the bundled js and css will end up. These need to match plugin config.

More about configuring Vite can be found at [vitejs.dev/config](https://vitejs.dev/config/).

## Contributions

You can contribute to this plugin via pull requests. If you run into an error, you can open an issue.

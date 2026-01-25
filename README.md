# ViteHelper plugin for CakePHP 5

The plugin provides a Helper Class for CakePHP to facilitate the use of [Vite JS](https://vitejs.dev/).

When running the Vite dev server, the Helper provides the right script tags for hot module replacement and page
reloading.

In production mode, the Helper loads the bundled files. `@vitejs/plugin-legacy` is supported, which will
insert `nomodule`-tags for older browsers, e.g. older iOS devices, which do not support js modules.

> This readme is for **version 3.x.** If you are migrating from 0.x and something is unclear, read the Migration guide
> under `/docs`. Feel free to open an issue if you run into problems.

## Installation

You can install this plugin into your CakePHP application using [composer](https://getcomposer.org).

### CakePHP Version Map

| CakePHP version | Plugin Version | Branch      | min. PHP Version |
|-----------------|----------------|-------------|------------------|
| ^3.10           | /              | cake3       | ^7.4             |
| ^4.2            | 0.x            | master      | ^7.4             |
| ^4.2            | 1.x            | master      | ^8.0             |
| ^5.0            | 2.x            | cake5       | ^8.1             |
| ^5.0            | 3.x            | cake5-next  | ^8.3             |

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

The default configuration is:

```php
'ViteHelper' => [
    'environment' => \ViteHelper\Enum\Environment::PRODUCTION, // available options PRODUCTION, DEVELOPMENT, FROM_DETECTOR
    'development' => [
        'url' => 'http://localhost:3000', // url of the vite dev server
    ],
    'builds' => [
        [
            'plugin' => null, // the plugin name or null if it doesn't exist. Default: null
            'outputDirectory' => 'build', // the output directory relative to `webroot`. Default: 'build'
            'manifest' => 'build' . DS . '.vite' . DS . 'manifest.json', // the relative path to the manifest file. Default: 'build' . DS . '.vite' . DS . 'manifest.json'
            'environment' => \ViteHelper\Enum\Environment::PRODUCTION, // the forced environment, all files what 'falls' in this manifest file will be rendered this way. Default: the globally set environment
        ],
    ],
],
```

You can override the defaults in your `app.php`, `app_local.php`, or `app_vite.php`, also you can override in
`AppView.php` when you are loading the helper.

```php
$this->loadHelper('ViteHelper.ViteScripts', [
    // ...your config goes here
]);
```

Since every build option has a default value:

```php
'ViteHelper' => [
    'environment' => \ViteHelper\Enum\Environment::DEVELOPMENT, // available options PRODUCTION, DEVELOPMENT, FROM_DETECTOR
    'development' => [
        'url' => 'http://localhost:3000', // url of the vite dev server
    ],
    'builds' => [
        [],
        ['plugin' => 'MyAwesomePlugin', 'environment' => 'prod']
    ],
],
```

These are valid builds. Assets from `MyAwesomePlugin` will be loaded like in production, the assets from root in development mode.


## Environment

The plugin MUST accurately determine whether you are in development or production mode. You must explicitly set in the
config that you are in either `\ViteHelper\Enum\Environment::PRODUCTION` or `\ViteHelper\Enum\Environment::DEVELOPMENT`.
To enhance the flexibility of the plugin, you can utilize `\ViteHelper\Enum\Environment::FROM_DETECTOR`. This setting
will employ a [detector](https://book.cakephp.org/5/en/controllers/request-response.html#Cake\Http\ServerRequest::is)
to automatically detect the environment.

```php
$this->request->addDetector(
    \ViteHelper\View\Helper\ViteScriptsHelper::VITESCRIPT_DETECTOR_NAME,
    function ($serverRequest) {
        // your logic goes here
        // return true for prod, false for dev
    }
);
```

In the development mode the plugins adds the necessary tags for development, in production this isn't happening.

## Helper method usage with options

The options are the same for `::script()` and `::css()`.

### Example

```php
$this->ViteScripts->script(
    files: ['resource/file1.js', 'resource/file2.js'], // Files as array/file as string
    block: null, // name of the view block to render the scripts in. Default: null
    plugin: null, // Plugin's name. Default: null

    // Filter for environment. Default: null (in case of null the file(s) will be rendered both on prod and dev)
    // possible values: \ViteHelper\Enum\Environment::PRODUCTION, \ViteHelper\Enum\Environment::DEVELOPMENT, null
    environment: null,
);
```

## Performance

In production, it's possible that the manifest.json file is too large. If the default render mode is set to `AUTO`,
every `::script()` and `::css()` call automatically and instantly adds the HTML tag to the block. You can disable this
feature by setting render mode `MANUAL`.

```php
$this->loadHelper('ViteHelper.ViteScripts', [
    'render_mode' => \ViteHelper\Enum\RenderMode::MANUAL
]);
```

In your php-layout, right before the `viewBlocks` you should manually call the `::render()` method or dispatch
the `Vite.render` event.

```php
$this->ViteScripts->script('resource/myscript1.js');
$this->ViteScripts->script('resource/myscript2.js');
// ... + a lot of script
$this->ViteScripts->script('resource/myscriptN.js');
$this->ViteScripts->render();
<?= $this->fetch('css') ?>
```
or

```php
$this->ViteScripts->script('resource/myscript1.js');
$this->ViteScripts->script('resource/myscript2.js');
// ... + a lot of script
$this->ViteScripts->script('resource/myscriptN.js');
// dispatch 'Vite.render' event
$this->getEventManager()->dispatch('Vite.render');
<?= $this->fetch('css') ?>
```

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

The build files will end up in `/webroot/build` by default. Your `vite.config.js or *.ts` file for vite stays in the
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

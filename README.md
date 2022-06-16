# ViteHelper plugin for CakePHP

The plugin provides a Helper Class for CakePHP to facilitate the use of [Vite JS](https://vitejs.dev/).

When running the Vite dev server, the Helper provides the right script tags for hot module replacement and page reloading.

In production mode, the Helper loads the bundled files. `@vitejs/plugin-legacy` is supported, which will
insert `nomodule`-tags for older browsers, e.g. older iOS devices, which do not support js modules.

In your php-template, include this in your html head: \
`<?= $this->ViteScripts->head() ?>`

Just before the closing `</body>` tag, insert this line: \
`<?= $this->ViteScripts->body() ?>`

> New in **version 0.2**  
> `$this->Vite->getHeaderTags()` etc. is deprecated. The ViteHelper got refactored to the new `ViteScriptsHelper`. 
> Manifest handling is done by the new `Utilities/ViteManifest.php`.

## Installation

You can install this plugin into your CakePHP application using [composer](https://getcomposer.org).

The recommended way to install composer packages is:

```
composer require passchn/cakephp-vite
```

Load the plugin in your Application.php:

```
bin/cake plugin load ViteHelper
```

Load the Helper in your AppView.php:
```
$this->loadHelper('ViteHelper.ViteScripts');
```

### Configuration

Available options:
* forceProductionMode `bool`: Defaults to `false`
* devHostNeedles `string[]`: defaults to `['.test', 'localhost', '127.0.0.1']`
* devPort `int`: defaults to `3000`
* jsSrcDirectory `string`: defaults to `webroot_src`
* mainJs `string`: defaults to `main.js`
* manifestDir `string`: defaults to `manifest.json`

You can override the defaults in your `app.php`, `app_local.php`, or `app_vite.php`. 

See the plugin's [app_vite.php](https://github.com/passchn/cakephp-vite/blob/main/config/app_vite.php) for reference. 

Example: 

```
return [
    'ViteHelper' => [
        'forceProductionMode' => true,
        'mainJs' => 'main.ts',
    ],
];
```

## Vite JS bundler / Dev server

Install Vite e.g. via yarn:
````
yarn create vite
````

It is recommended to add the legacy plugin:
```
yarn add -D @vitejs/plugin-legacy
```

> See [Scaffolding Your First Vite Project](https://vitejs.dev/guide/#scaffolding-your-first-vite-project) on vitejs.dev for more information.

### Configuration

After installing, you will need to refactor the files a bit to make sense of it in a php project. The default config of this plugin assumes that you put your js, ts, scss etc. in `/webroot_src`.

The build files will end up in `/webroot/build` by default. Your `vite.config.js or *.ts` file for vite stays in the project root.

#### Recommended configuration: 

See the example [vite.config.ts content here](https://github.com/brandcom/cakephp-vite/wiki/example-vite-config#vite-below-290). Note that the config changes when upgrading to vite version `2.9.0` or higher. 

A difference to other dev servers, e.g. webpack or gulp is that you won't access your
local site via the port where Vite is serving. This does not work with php.

Therefore, the `server.hmr` config will enable you to use hot module replacement via the websocket protocol.

The `build` options define where the bundled js and css will end up.
These need to match the `$config` array when loading the ViteHelper.

More about configuring Vite can be found here:
[vitejs.dev/config](https://vitejs.dev/config/)

## Contributions

You can contribute to this plugin via pull requests. If you run into an error, you can open an issue. 

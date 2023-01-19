# ViteHelper plugin for CakePHP v1.x

The plugin provides a Helper Class for CakePHP to facilitate the use of [Vite JS](https://vitejs.dev/).

When running the Vite dev server, the Helper provides the right script tags for hot module replacement and page reloading.

In production mode, the Helper loads the bundled files. `@vitejs/plugin-legacy` is supported, which will
insert `nomodule`-tags for older browsers, e.g. older iOS devices, which do not support js modules.

In your php-layout, include this in your html head: \
`<?= $this->fetch('css') ?>`

Just before the closing `</body>` tag, insert this line: \
`<?= $this->fetch('script') ?>`

(These tags are default in cakephp-app.)

In your php-template or in layout you can import javascript files with: \
`<?php $this->ViteScripts->script(['webroot_src/js/main.js']) ?>`

If you imported CSS files in the Js file, this method automatically appends to the __css__ tag.

In your php-template you can import css files with: \
`<?php $this->ViteScripts->css(['webroot_src/css/style.scss']) ?>`

If the source was a __webroot_src/css/style.scss__ will be imported to the __css__ tag automatically.

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
* build.outDirectory `string`: Defaults to `build`
* build.manifest `string`: Defaults to `WWW_ROOT . 'build' . DS . 'manifest.json'`
* developmentUrl `string`: Defaults to `http://localhost:3000`
* developmentHostNeedles `string[]`: defaults to `['.test', 'localhost', '127.0.0.1']`
* forceProductionMode `bool`: Defaults to `false`
* productionHint `string`: Defaults to `vprod`

Deprecated options:
* `devPort` use the `developmentUrl` instead
* `jsSrcDirectory` and `mainJS` was replaced by parameters when calling the helper method
* `manifestDir` was replaced with `build.manifest`

You can override the defaults in your `app.php`, `app_local.php`, or `app_vite.php`.

See the plugin's [app_vite.php](https://github.com/passchn/cakephp-vite/blob/main/config/app_vite.php) for reference.

Example:

```
return [
    'ViteHelper' => [
        'developmentUrl' => 'https://192.168.0.88:3000',
        'developmentHostNeedles' => ['mydomain.local'],
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

See the example [vite.config.ts content here](https://github.com/brandcom/cakephp-vite/wiki/example-vite-config). Note that the config changes when upgrading to vite version `2.9.0` or higher.

A difference to other dev servers, e.g. webpack or gulp is that you won't access your
local site via the port where Vite is serving. This does not work with php.

Therefore, the `server.hmr` config will enable you to use hot module replacement via the websocket protocol.

The `build` options define where the bundled js and css will end up.
These need to match the `$config` array when loading the ViteHelper.

More about configuring Vite can be found here:
[vitejs.dev/config](https://vitejs.dev/config/)

## Contributions

You can contribute to this plugin via pull requests. If you run into an error, you can open an issue.

# ViteHelper plugin for CakePHP

The plugin provides a Helper Class for CakePHP to facilitate the use of [Vite JS](https://vitejs.dev/).

When running the Vite dev server, the Helper provides the right script tags for hot module replacement and page reloading.

In production mode, the Helper loads the bundled files. `@vitejs/plugin-legacy` is supported, which will
insert `nomodule`-tags for older browsers, e.g. older iOS devices, which do not support js modules.

In your php-template, include this in your html head: \
`<?= $this->Vite->getHeaderTags() ?>`

Just before the closing `</body>` tag, insert this line: \
`<?= $this->Vite->getBodyTags() ?>`

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
* mainTs `string`: defaults to `main.js`
* manifestDir `string`: defaults to `manifest.json`

## Vite JS bundler / Dev server

Install Vite e.g. via yarn:
````
yarn add -D vite
````

It is recommended to add the legacy plugin:
```
yarn add -D @vitejs/plugin-legacy
```

### Configuration

Vite comes with useful default configuration build in, however,
in a php environment, some changes have to be made.

The recommended configuration needs to be saved in the CakePHP
root folder (not /webroot) as `vite.config.js` (or `.ts` if you are using TypeScript).

```
import { defineConfig } from 'vite';
import legacy from '@vitejs/plugin-legacy';

export default defineConfig({
    plugins: [
        legacy({
            targets: ['defaults', 'not IE 11'],
        }),
    ],
    build: {
        emptyOutDir: false,
        outDir: './webroot/',
        assetsDir: 'build',
        manifest: true,
        rollupOptions: {
            input: './webroot_src/main.js',
        },
    },
    server: {
        hmr: {
            protocol: 'ws',
            host: 'localhost',
            port: 3000,
        },
    },
});
```

A difference to other dev servers, e.g. webpack or gulp is that you won't access your
local site via the port where Vite is serving. This does not work with php.

Therefore, the `server.hmr` config will enable you to use hot module replacement via the websocket protocol.

The `build` options define where the bundled js and css will end up.
These need to match the `$config` array when loading the ViteHelper.

More about configuring Vite can be found here:
[vitejs.dev/config](https://vitejs.dev/config/)


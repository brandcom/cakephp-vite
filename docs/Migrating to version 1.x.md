# Migrating to version 1.x

Version 1 of the ViteHelper plugin is coming with some major changes to the api and how you can use the plugin within your CakePHP project.

## Serving JS and CSS

The signature of the js and css method have changed significantly. In 0.x, the methods returned a html tag as a string. Now, the plugin utilizes [view blocks](https://book.cakephp.org/4/en/views.html#using-view-blocks) to render your tags.

### view blocks

In your php-layout, include this in your html head:

```php
<?= $this->fetch('css') ?>
```

Just before the closing `</body>` tag, insert this line:

```php
<?= $this->fetch('script') ?>
```

### JS:

Change your body tags from

```php
<?= $this->ViteScripts->body($options) ?>
```

to

```php
/**
* @var array<string>|string $files - the js file or list of files
* @var array $options - will be passed to the <script> tag as parameters
*/
<?php $this->ViteScripts->script($files, $options) ?>
```

Where `$files` can e.g. be `webroot_src/js/main.js`.

**Note:** The new `ViteScriptsHelper::script()` method is now `void`.

### CSS

**Note:** If you are importing your css/scss from within your JavaScript, this is unnecessary.

Change your html head from

```php
<?= $this->ViteScripts->head($options) ?>
```

to

```php
/**
* @var array<string>|string $files - the css file or list of files
* @var array $options - will be passed to the <link> tag as parameters
*/
<?php $this->ViteScripts->css($files, $options) ?>
```

Where `$files` can e.g. be `webroot_src/css/style.scss`.

**Note:** The new `ViteScriptsHelper::css()` method is now `void`.

## Other breaking changes

### Configuration

The configuration structure has changed, e.g., some config keys have been renamed or nested. Check the current `app_vite.php` and compare it with your app's config file.

### Namespaces

* Exceptions this plugin is throwing are now in the `ViteHelper\Exception` namespace. Before, the namespace was `ViteHelper\Errors`.

### Deprecations and removed code

* The `ViteHelper` which was deprecated since v0.2 has been removed.
* The `ViteCommand` has been removed since the same result can be achieved by vite itself, [see the _build.emptyOutDir_ config](https://vitejs.dev/config/build-options.html#build-emptyoutdir).

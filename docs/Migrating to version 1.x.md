# Migrating to version 1.x

Version 1 of the ViteHelper plugin is coming with some major changes to the api and how you can use the plugin within your CakePHP project.

This guide should help you upgrading from 0.x to 1.x.

## Serving JS and CSS

The signature of the js and css method have changed significantly.
In 0.x, the methods returned a html tag as a string. Now, the plugin utilizes view blocks.

### View blocks

In your php-layout, include this in your html head:

```php
<?= $this->fetch('css') ?>
```

Just before the closing `</body>` tag, insert this line:

```php
<?= $this->fetch('script') ?>
```

The name of the view blocks can be changed in config or per call to the Helper-methods.

Lear more about [view blocks](https://book.cakephp.org/4/en/views.html#using-view-blocks) in the CakePHP book.

### JS:

Change your body tags from

```php
<?= $this->ViteScripts->body($options) ?>
```

to

```php
/**
* @var array $options
*/
<?php $this->ViteScripts->script($options) ?>
```

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
* @var array $options
*/
<?php $this->ViteScripts->css($options) ?>
```

**Note:** The new `ViteScriptsHelper::css()` method is now `void`.

### Arguments for Helper methods

Learn more about the `$options` in the current `README.md` file or consult the doc block of the respective method. Passing `$options` is not required. It might be useful depending on your setup, however.

You can now also pass an instance of the new `ViteHelperConfig` to the methods to use a different config. This might be useful for plugins.

## Other breaking changes

### Configuration

The configuration structure has changed, e.g., some config keys have been renamed
or nested. Check the current `app_vite.php` inside `/config` and compare it with your app's config file.

### Namespaces

* Exceptions this plugin is throwing are now in the `ViteHelper\Exception` namespace. Before, the namespace was `ViteHelper\Errors`.

### Deprecations and removed code

* The `ViteHelper` which was deprecated since v0.2 has been removed.
* The `ViteCommand` has been removed since the same result can be achieved by vite itself, [see the _build.emptyOutDir_ config](https://vitejs.dev/config/build-options.html#build-emptyoutdir).

## Something missing?

If you ran into an error or something was unclear after upgrading from 0.x to 1.x, please, open an issue on GitHub or contribute with a pr for this guide.

# Migrating to version 1.x

Version 1 of the ViteHelper plugin is coming with some major changes to the api and how you can use the plugin within your CakePHP project.

## Serving JS and CSS

The signature of the js and css method have changed significantly. In 0.x, the methods returned a html tag as a string. Now, the plugin utilizes view blocks to render your tags.

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

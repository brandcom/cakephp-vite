# Changelog

## v3.0.0

The new version changes the plugin architecture and requires some modifications. It's highly recommended to check the
README.md

* Updated the plugin's configuration array
* The methods `$this->ViteScripts->pluginScript($pluginName)` and `pluginCss()` were removed
* The `$this->ViteScripts->css()` and `$this->ViteScripts->script()` were updated
* The running environment MUST be defined explicitly or a detector should be used

## v2.3.0

### Changed

* An instance of `ViteHelperConfig` can now be initialized only with a config key (`string`)
* The `ViteScriptsHelper` accepts `string` for the `$config` argument

## v2.2.0

### Added

* New convenience methods for the `ViteScriptsHelper`:
    * The methods `$this->ViteScripts->pluginScript($pluginName)` as well as `pluginCss()` were added to simplify the
      usage of the helper with plugins that use Vite.
    * The new methods assume you want the production build, but you can set the `$devMode` flag to `true`.

### Changed

* `ViteHelperConfig` (internal changes):
    * Two configs can now be merged.
    * The `$config` array is now `public readonly`.

## v2.1.0

### Added

* Autoloading of the manifest.json for plugins. When the config passed to the helper defines a plugin name through the
  config key `plugin` and does not specify a fill path to the manifest, the plugin expects the manifest.json to be
  available at `/your/plugin_root/webroot/manifest.json`.

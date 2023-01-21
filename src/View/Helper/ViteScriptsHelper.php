<?php
declare(strict_types=1);

namespace ViteHelper\View\Helper;

use Cake\Core\Configure;
use Cake\Utility\Text;
use Cake\View\Helper;
use Nette\Utils\Arrays;
use Nette\Utils\Strings;
use ViteHelper\Exception\ConfigurationException;
use ViteHelper\Utilities\ConfigDefaults;
use ViteHelper\Utilities\ManifestRecords;
use ViteHelper\Utilities\ViteManifest;

/**
 * ViteScripts helper
 *
 * After loading the Helper in your AppView.php, you can call
 * $this->ViteScripts->head() in your html head, and
 * $this->ViteScripts->body() in the body.
 *
 * You can override the default config in your app.php, app_local.php, or you create a app_vite.php file.
 *
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class ViteScriptsHelper extends Helper
{
    public $helpers = ['Html'];

    /**
     * Check if the app is currently in development state.
     *
     * Production mode can be forced in config through `forceProductionMode`,
     *   or by setting a cookie or a url-parameter.
     *
     * Otherwise, it will look for a hint that the app
     *   is in development mode through the  `developmentHostNeedles`
     *
     * @return bool
     */
    public function isDev(): bool
    {
        if (Configure::read('ViteHelper.forceProductionMode', ConfigDefaults::FORCE_PRODUCTION_MODE)) {
            return false;
        }

        $productionHint = Configure::read('ViteHelper.productionHint', ConfigDefaults::PRODUCTION_HINT);
        $hasCookieOrQuery = $this->getView()->getRequest()->getCookie($productionHint) || $this->getView()->getRequest()->getQuery($productionHint);
        if ($hasCookieOrQuery) {
            return false;
        }

        $needles = Configure::read('ViteHelper.developmentHostNeedles', ConfigDefaults::DEVELOPMENT_HOST_NEEDLES);
        foreach ($needles as $needle) {
            if (Strings::contains((string)$this->getView()->getRequest()->host(), $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Adds scripts to the script view block
     *
     * @param array $options options for the script tag
     * @param \ViteHelper\Utilities\ManifestRecords|null $records arbitrary records e.g. from a plugin's vite manifest
     * @return void
     * @throws \ViteHelper\Exception\ConfigurationException
     * @throws \ViteHelper\Exception\ManifestNotFoundException
     */
    public function script(array $options = [], ?ManifestRecords $records = null): void
    {
        $options['block'] = Configure::read('viewBlocks.script', ConfigDefaults::VIEW_BLOCK_SCRIPT);

        if ($this->isDev()) {
            $this->devScript($options);

            return;
        }

        $this->productionScript($options, $records);
    }

    /**
     * @param array $options passed to script tag
     * @return void
     * @throws \ViteHelper\Exception\ConfigurationException
     */
    private function devScript(array $options): void
    {
        $this->Html->script(
            Configure::read('ViteHelper.developmentUrl', ConfigDefaults::DEVELOPMENT_URL)
            . '/@vite/client',
            [
                'type' => 'module',
                'block' => Configure::read('viewBlocks.css', ConfigDefaults::VIEW_BLOCK_CSS),
            ]
        );

        $options['type'] = 'module';
        foreach ($this->getFiles('scriptEntries') as $file) {
            $this->Html->script(Text::insert(':host/:file', [
                'host' => Configure::read('ViteHelper.developmentUrl', ConfigDefaults::DEVELOPMENT_URL),
                'file' => ltrim($file, DS),
            ]), $options);
        }
    }

    /**
     * @param array $options vite manifest records to use
     * @param \ViteHelper\Utilities\ManifestRecords|null $records will be passed to script tag
     * @return void
     * @throws \ViteHelper\Exception\ManifestNotFoundException
     */
    private function productionScript(array $options, ?ManifestRecords $records): void
    {
        $pluginPrefix = !empty($options['plugin']) ? $options['plugin'] . '.' : null;
        unset($options['plugin']);

        $records = $records ?: ViteManifest::getRecords();
        foreach ($records as $record) {
            if (!$record->isEntryScript()) {
                continue;
            }

            unset($options['type']);
            unset($options['nomodule']);
            if ($record->isModuleEntryScript()) {
                $options['type'] = 'module';
            } else {
                $options['nomodule'] = 'nomodule';
            }

            $this->Html->script($record->getFileUrl($pluginPrefix), $options);

            // the js files has css dependency ?
            $cssFiles = $record->getCss();
            if (count($cssFiles)) {
                $this->Html->css($cssFiles, [
                    'block' => Configure::read('viewBlocks.css', ConfigDefaults::VIEW_BLOCK_CSS),
                ]);
            }
        }
    }

    /**
     * Adds the gives CSS styles to the configured block
     * The $options array is directly passed to the Html-Helper.
     *
     * @param array $options are passed to the <link> tags as parameters, e.g. for media="screen" etc.
     * @param \ViteHelper\Utilities\ManifestRecords|null $records arbitrary records e.g. from a plugin's vite manifest
     * @return void
     * @throws \ViteHelper\Exception\ManifestNotFoundException
     * @throws \ViteHelper\Exception\ConfigurationException
     */
    public function css(array $options = [], ?ManifestRecords $records = null): void
    {
        if ($this->isDev()) {
            $options['block'] = Configure::read('viewBlocks.css', ConfigDefaults::VIEW_BLOCK_CSS);
            foreach ($this->getFiles('styleEntries') as $file) {
                $this->Html->css(Text::insert(':host/:file', [
                    'host' => Configure::read('ViteHelper.developmentUrl', ConfigDefaults::DEVELOPMENT_URL),
                    'file' => ltrim($file, '/'),
                ]), $options);
            }

            return;
        }

        $records = $records ?: ViteManifest::getRecords();
        $pluginPrefix = !empty($options['plugin']) ? $options['plugin'] . '.' : null;
        unset($options['plugin']);
        foreach ($records as $record) {
            if (!$record->isEntry() || !$record->isStylesheet() || $record->isLegacy()) {
                continue;
            }

            $this->Html->css($record->getFileUrl($pluginPrefix), $options);
        }
    }

    /**
     * @param string $configKey key for ViteHelper.development.*Entries
     * @return array
     * @throws \ViteHelper\Exception\ConfigurationException
     */
    private function getFiles(string $configKey): array
    {
        $files = Configure::read('ViteHelper.development.' . $configKey, ConfigDefaults::DEVELOPMENT_SCRIPT_ENTRIES);

        if (empty($files) || !Arrays::isList($files)) {
            throw new ConfigurationException(
                'There are no valid entry points for the dev server. Be sure to set the ViteHelper.development.scriptEntries config.'
            );
        }
        foreach ($files as $file) {
            if (!file_exists(ROOT . DS . ltrim($file, DS))) {
                throw new ConfigurationException(sprintf('The entry file "%s" does not exist.', $file));
            }
        }

        return $files;
    }
}

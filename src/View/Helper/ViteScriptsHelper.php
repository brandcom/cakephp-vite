<?php
declare(strict_types=1);

namespace ViteHelper\View\Helper;

use Cake\Core\Configure;
use Cake\Utility\Text;
use Cake\View\Helper;
use Nette\Utils\Strings;
use ViteHelper\Utilities\ConfigDefaults;
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
     * Adds the gives scripts to the configured block
     *
     * @param array|string $files the source path of javascript files, without extension
     * @param array $options Additional option to the script tag
     * @return void
     */
    public function script(array|string $files = 'webroot_src/js/main', array $options = []): void
    {
        $files = (array)$files;
        $viewBlockCss = Configure::read('viewBlocks.css', ConfigDefaults::VIEW_BLOCK_CSS);
        $options['block'] = Configure::read('viewBlocks.script', ConfigDefaults::VIEW_BLOCK_SCRIPT);
        // in development
        if ($this->isDev()) {
            $this->Html->script(
                Configure::read('ViteHelper.developmentUrl', ConfigDefaults::DEVELOPMENT_URL)
                . '/@vite/client',
                [
                    'type' => 'module',
                    'block' => $viewBlockCss,
                ]
            );

            $options['type'] = 'module';
            foreach ($files as $file) {
                $this->Html->script(Text::insert(':host/:file', [
                    'host' => Configure::read('ViteHelper.developmentUrl', ConfigDefaults::DEVELOPMENT_URL),
                    'file' => ltrim($file, '/'),
                ]), $options);
            }

            return;
        }

        $pluginPrefix = !empty($options['plugin']) ? $options['plugin'] . '.' : null;
        unset($options['plugin']);

        // in production
        foreach ($files as $_filter) {
            foreach (ViteManifest::getInstance()->getRecords() as $record) {
                if (!$record->isEntryScript($_filter)) {
                    continue;
                }

                unset($options['type']);
                unset($options['nomodule']);
                if ($record->isModuleEntryScript()) {
                    $options['type'] = 'module';
                } else {
                    $options['nomodule'] = 'nomodule';
                }

                $this->Html->script($record->url($pluginPrefix), $options);

                // the js files has css dependency ?
                $css_files = $record->getCss($pluginPrefix);
                if (!empty($css_files)) {
                    $this->Html->css($css_files, ['block' => $viewBlockCss]);
                }
            }
        }
    }

    /**
     * Adds the gives CSS styles to the configured block
     * The $options array is directly passed to the Html-Helper.
     *
     * @param array|string $files the CSS files
     * @param array $options are passed to the <link> tags as parameters, e.g. for media="screen" etc.
     * @return void
     */
    public function css(array|string $files = 'webroot_src/scss/style', array $options = []): void
    {
        $files = (array)$files;

        // in development
        if ($this->isDev()) {
            $options['block'] = Configure::read('viewBlocks.css', ConfigDefaults::VIEW_BLOCK_CSS);
            foreach ($files as $file) {
                $this->Html->css(Text::insert(':host/:file', [
                    'host' => Configure::read('ViteHelper.developmentUrl', ConfigDefaults::DEVELOPMENT_URL),
                    'file' => ltrim($file, '/'),
                ]), $options);
            }

            return;
        }

        $pluginPrefix = !empty($options['plugin']) ? $options['plugin'] . '.' : null;
        unset($options['plugin']);

        // add CSS files to head
        foreach (ViteManifest::getInstance()->getRecords() as $record) {
            if (!$record->isEntry() || !$record->isStylesheet() || $record->isLegacy()) {
                continue;
            }

            $this->Html->css($record->url($pluginPrefix), $options);
        }
    }
}

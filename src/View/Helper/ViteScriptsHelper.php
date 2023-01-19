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

    protected $_defaultConfig = [
        'bodyBlock' => true,
        'headBlock' => true,
        'isDevelopment' => false,
    ];

    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        if (is_null($this->getConfig('isDevelopment'))) {
            $this->setConfig('isDevelopment', $this->isDev());
        }
    }

    /**
     * Decide what files to serve.
     *
     * If
     * * $this->forceProductionMode is set to true
     * * or a ?vprod URL-param is set,
     * * or a vprod Cookie not false-ish,
     * it will return false.
     *
     * @return bool
     */
    public function isDev(): bool
    {
        if (Configure::read('ViteHelper.forceProductionMode', ConfigDefaults::FORCE_PRODUCTION_MODE)) {
            return false;
        }

        $productionHint = Configure::read('ViteHelper.productionHint', ConfigDefaults::PRODUCTION_HINT);
        if (
            $this->getView()->getRequest()->getCookie($productionHint)
            || $this->getView()->getRequest()->getQuery($productionHint)
        ) {
            return false;
        }

        foreach (
            Configure::read('ViteHelper.developmentHostNeedles', ConfigDefaults::DEV_HOST_NEEDLES) as $needle
        ) {
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
        $options['block'] = $this->getConfig('bodyBlock');
        // in development
        if ($this->getConfig('isDevelopment')) {
            $this->Html->script(
                Configure::read('ViteHelper.developmentUrl', ConfigDefaults::DEVELOPMENT_URL)
                . '/@vite/client',
                [
                    'type' => 'module',
                    'block' => $this->getConfig('headBlock'),
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
                if (
                    !(
                        $record->isEntry() &&
                        (($record->isJavascript() && $record->match($_filter)) || $record->isPolyfill())
                    )
                ) {
                    continue;
                }

                if ($record->isLegacy()) {
                    unset($options['type']);
                    $options['nomodule'] = 'nomodule';
                } else {
                    unset($options['nomodule']);
                    $options['type'] = 'module';
                }

                $this->Html->script($record->url($pluginPrefix), $options);

                // the js files has css dependency ?
                $css_files = $record->getCss($pluginPrefix);
                if (!empty($css_files)) {
                    $this->Html->css($css_files, ['block' => $this->getConfig('headBlock')]);
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
        if ($this->getConfig('isDevelopment')) {
            $options['block'] = $this->getConfig('headBlock');
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

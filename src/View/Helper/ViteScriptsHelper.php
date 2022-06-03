<?php
declare(strict_types=1);

namespace ViteHelper\View\Helper;

use Cake\Core\Configure;
use Cake\View\Helper;
use Nette\Utils\Strings;
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
    public $helpers = [
        'Html',
    ];

    /**
     * @param array $config configuration array, see config/app_vite.php
     * @return void
     * @throws \Exception
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setConfig(array_merge($this->getSettings(), $config));
    }

    /**
     * Returns css-tags for use in <head>
     * The $options array is directly passed to the Html-Helper.
     *
     * @param array $options are passed to the <link> tags as parameters, e.g. for media="screen" etc.
     * @param array $config config passed to ViteManifest
     * @return string
     */
    public function head(array $options = [], array $config = []): string
    {
        if ($this->isDev()) {
            return $this->Html->script(
                'http://localhost:'
                . $this->_config['devPort'] . '/'
                . $this->_config['jsSrcDirectory']
                . $this->_config['mainJs'],
                [
                    'type' => 'module',
                ]
            );
        }

        $pluginPrefix = !empty($options['plugin']) ? $options['plugin'] . '.' : null;
        unset($options['plugin']);

        $tags = [];
        foreach ($this->getViteManifest($config)->getCssFiles() as $path) {
            $tags[] = $this->Html->css($pluginPrefix . $path, $options);
        }

        return implode("\n", $tags);
    }

    /**
     * Returns javascript-script tags for use at the end of <body>
     *
     * @param array $options set a plugin prefix, or pass to script-tag as parameters
     * @param array $config passed to ViteManifest
     * @return string
     */
    public function body(array $options = [], array $config = []): string
    {
        if ($this->isDev()) {
            return $this->Html->script('http://localhost:'
                . $this->_config['devPort']
                . '/@vite/client', [
                'type' => 'module',
            ]);
        }

        if ($options['plugin']) {
            $pluginPrefix = $options['plugin'] . '.';
            unset($options['plugin']);
        }

        $tags = [];
        foreach ($this->getViteManifest($config)->getJsFiles() as $path) {
            if (Strings::contains($path, 'legacy')) {
                $options['nomodule'] = 'nomodule';
            } else {
                $options['type'] = 'module';
            }

            $tags[] = $this->Html->script($pluginPrefix . $path, $options);
        }

        return implode("\n", $tags);
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function getSettings(): array
    {
        $config = Configure::read('ViteHelper');
        if (!$config || !is_array($config)) {
            throw new \Exception('No valid configuration found for ViteHelper. ');
        }

        return $config;
    }

    /**
     * @param array $config see config/app_vite.php
     * @return \ViteHelper\Utilities\ViteManifest
     * @throws \Exception
     */
    private function getViteManifest(array $config = []): ViteManifest
    {
        $config = array_merge($this->_config, $config);

        return new ViteManifest($config);
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
    private function isDev(): bool
    {
        if ($this->_config['forceProductionMode']) {
            return false;
        }

        if (
            $this->getView()->getRequest()->getCookie($this->_config['productionHint'])
            || $this->getView()->getRequest()->getQuery($this->_config['productionHint'])
        ) {
            return false;
        }

        foreach ($this->_config['devHostNeedles'] ?? [] as $needle) {
            if (Strings::contains((string)$this->getView()->getRequest()->host(), $needle)) {
                return true;
            }
        }

        /**
         * @deprecated
         *
         * You should switch to the array-version "devHostNeedles".
         */
        if (!empty($this->_config['devHostNeedle'])) {
            return Strings::contains((string)$this->getView()->getRequest()->host(), $this->_config['devHostNeedle']);
        }

        return false;
    }
}

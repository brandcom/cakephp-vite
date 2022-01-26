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
 * @property Helper\HtmlHelper $Html
 */
class ViteScriptsHelper extends Helper
{
    public $helpers = [
        'Html',
    ];

    protected ViteManifest $manifest;
    protected bool $isDev;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setConfig(array_merge($this->getSettings(), $config));
        $this->manifest = new ViteManifest();
        $this->isDev = $this->isDev();
    }

    public function head(): string
    {
        if ($this->isDev) {

            return $this->Html->script(
                'http://localhost:'
                . $this->_config['devPort'] . '/'
                . $this->_config['jsSrcDirectory']
                . $this->_config['mainJs'], [
                    'type' => 'module',
                ]
            );
        }

        $tags = [];
        foreach ($this->manifest->getCssFiles() as $path) {
            $tags[] = $this->Html->css($path);
        }

        return implode("\n", $tags);
    }

    public function body(): string
    {
        if ($this->isDev) {

            return $this->Html->script('http://localhost:'
                . $this->_config['devPort']
                . '/@vite/client', [
                'type' => 'module'
            ]);
        }

        $tags = [];
        foreach ($this->manifest->getJsFiles() as $path) {

            $type = Strings::contains($path, "legacy") ? "nomodule" : "module";
            $tags[] = $this->Html->script($path, [
                'type' => $type,
            ]);
        }

        return implode("\n", $tags);
    }

    protected function getSettings(): array
    {
        $config = Configure::read('ViteHelper');
        if (!$config || !is_array($config)) {
            throw new \Exception("No valid configuration found for ViteHelper. ");
        }

        return $config;
    }


    /**
     * Decide what files to serve.
     *
     * If
     * * $this->forceProductionMode is set to true
     * * or a ?vprod URL-param is set,
     * * or a vprod Cookie not false-ish,
     * it will return false.
     */
    private function isDev(): bool
    {
        if ($this->_config['forceProductionMode']) {

            return false;
        }

        if ($this->getView()->getRequest()->getCookie($this->_config['productionHint'])
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

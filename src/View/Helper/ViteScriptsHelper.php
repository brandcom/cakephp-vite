<?php
declare(strict_types=1);

namespace ViteHelper\View\Helper;

use Cake\Core\Configure;
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
    public $helpers = [
        'Html',
    ];

    /**
     * Returns css-tags for use in <head>
     * The $options array is directly passed to the Html-Helper.
     *
     * @param array $options are passed to the <link> tags as parameters, e.g. for media="screen" etc.
     * @return string
     * @throws \ViteHelper\Exception\ManifestNotFoundException
     */
    public function head(array $options = []): string
    {
        if ($this->isDev()) {
            return $this->Html->script(
				Configure::read('ViteHelper.developmentUrl', ConfigDefaults::DEVELOPMENT_URL) . '/' .
                Configure::read('ViteHelper.jsSrcDirectory', 'webroot_src') . '/' .
                Configure::read('ViteHelper.mainJs', 'main.js'),
                [
                    'type' => 'module',
                ]
            );
        }

        $pluginPrefix = !empty($options['plugin']) ? $options['plugin'] . '.' : null;
        unset($options['plugin']);

        $tags = [];
        foreach ($this->getViteManifest()->getCssFiles() as $path) {
            $tags[] = $this->Html->css($pluginPrefix . $path, $options);
        }

        return implode("\n", $tags);
    }

    /**
     * Returns javascript-script tags for use at the end of <body>
     *
     * @param array $options set a plugin prefix, or pass to script-tag as parameters
     * @return string
     * @throws \ViteHelper\Exception\ManifestNotFoundException
     */
    public function body(array $options = []): string
    {
        if ($this->isDev()) {
            return $this->Html->script(
				Configure::read('ViteHelper.developmentUrl', ConfigDefaults::DEVELOPMENT_URL)
				. '/@vite/client',
				['type' => 'module']
			);
        }

        $pluginPrefix = !empty($options['plugin']) ? $options['plugin'] . '.' : null;
        unset($options['plugin']);

        $tags = [];
        foreach ($this->getViteManifest()->getJsFiles() as $path) {
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
     * @return \ViteHelper\Utilities\ViteManifest
     */
    private function getViteManifest(): ViteManifest
    {
        return new ViteManifest();
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
}

<?php
declare(strict_types=1);

namespace ViteHelper\View\Helper;

use Cake\Core\Configure;
use Cake\Utility\Text;
use Cake\View\Helper;
use Nette\Utils\Strings;
use ViteHelper\Exception\ConfigurationException;
use ViteHelper\Utilities\ConfigDefaults;
use ViteHelper\Utilities\ManifestRecord;
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
     * @param array|string $files the source path of javascript file or files
     * @param array $options Additional options for the script tag
     * @return void
     * @throws \ViteHelper\Exception\ConfigurationException
     * @throws \ViteHelper\Exception\ManifestNotFoundException
     */
    public function script(array|string $files = [], array $options = []): void
    {
        $files = (array)$files;
        $options['block'] = Configure::read('viewBlocks.script', ConfigDefaults::VIEW_BLOCK_SCRIPT);

        if ($this->isDev()) {
            $this->devScript($files, $options);

            return;
        }

        $this->productionScript($files, $options);
    }

    /**
     * @param array $files
     * @param array $options
     * @return void
     * @throws \ViteHelper\Exception\ConfigurationException
     */
    private function devScript(array $files, array $options): void
    {
        $this->Html->script(
            Configure::read('ViteHelper.developmentUrl', ConfigDefaults::DEVELOPMENT_URL)
            . '/@vite/client',
            [
                'type' => 'module',
                'block' => Configure::read('viewBlocks.css', ConfigDefaults::VIEW_BLOCK_CSS),
            ]
        );

        if (empty($files)) {
            $files = Configure::read('ViteHelper.developmentEntryFiles', ConfigDefaults::DEVELOPMENT_ENTRY_FILES);

            if (empty($files)) {
                throw new ConfigurationException('There are no entry points for the dev server. Be sure to set the ViteHelper.developmentEntryFiles config.');
            }
        }

        $options['type'] = 'module';
        foreach ($files as $file) {
            $this->Html->script(Text::insert(':host/:file', [
                'host' => Configure::read('ViteHelper.developmentUrl', ConfigDefaults::DEVELOPMENT_URL),
                'file' => ltrim($file, DS),
            ]), $options);
        }
    }

    /**
     * @param array<string> $files list of files
     * @param array $options will be passed to script tag
     * @return void
     * @throws \ViteHelper\Exception\ManifestNotFoundException
     */
    private function productionScript(array $files, array $options): void
    {
        $pluginPrefix = !empty($options['plugin']) ? $options['plugin'] . '.' : null;
        unset($options['plugin']);

		$records = ViteManifest::getRecords();
		if (count($files)) {
			$records->filter(function (ManifestRecord $record) use ($files) {
				foreach ($files as $file) {
					if (str_contains($record->getKey(), $file)) {
						return true;
					}
				}

				return false;
			});
		}

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
     * @param array|string $files the CSS files
     * @param array $options are passed to the <link> tags as parameters, e.g. for media="screen" etc.
     * @return void
     * @throws \ViteHelper\Exception\ManifestNotFoundException
     */
    public function css(array|string $files = 'webroot_src/scss/style', array $options = []): void
    {
        $files = (array)$files;
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
        foreach (ViteManifest::getRecords() as $record) {
            if (!$record->isEntry() || !$record->isStylesheet() || $record->isLegacy()) {
                continue;
            }

            $this->Html->css($record->getFileUrl($pluginPrefix), $options);
        }
    }
}

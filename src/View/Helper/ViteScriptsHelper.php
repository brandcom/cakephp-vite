<?php
declare(strict_types=1);

namespace ViteHelper\View\Helper;

use Cake\Collection\CollectionInterface;
use Cake\Utility\Text;
use Cake\View\Helper;
use Nette\Utils\Arrays;
use Nette\Utils\Strings;
use ViteHelper\Exception\ConfigurationException;
use ViteHelper\Exception\InvalidArgumentException;
use ViteHelper\Utilities\ConfigDefaults;
use ViteHelper\Utilities\ManifestRecord;
use ViteHelper\Utilities\ManifestRecords;
use ViteHelper\Utilities\ViteHelperConfig;
use ViteHelper\Utilities\ViteManifest;

/**
 * ViteScripts helper
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
	 * @param ViteHelperConfig|null $config config instance to use
     * @return bool
     */
    public function isDev(?ViteHelperConfig $config = null): bool
    {
		$config = $config ?: ViteHelperConfig::create();
        if ($config->read('forceProductionMode', ConfigDefaults::FORCE_PRODUCTION_MODE)) {
            return false;
        }

        $productionHint = $config->read('productionHint', ConfigDefaults::PRODUCTION_HINT);
        $hasCookieOrQuery = $this->getView()->getRequest()->getCookie($productionHint) || $this->getView()->getRequest()->getQuery($productionHint);
        if ($hasCookieOrQuery) {
            return false;
        }

        $needles = $config->read('developmentHostNeedles', ConfigDefaults::DEVELOPMENT_HOST_NEEDLES);
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
     * @param \ViteHelper\Utilities\ViteHelperConfig|null $config config instance
     * @return void
     * @throws \ViteHelper\Exception\ConfigurationException
     * @throws \ViteHelper\Exception\ManifestNotFoundException|InvalidArgumentException
	 */
    public function script(array $options = [], ?ViteHelperConfig $config = null): void
    {
        $config = $config ?: ViteHelperConfig::create();
        $options['block'] = $options['block'] ?? $config->read('viewBlocks.script', ConfigDefaults::VIEW_BLOCK_SCRIPT);
        $options['cssBlock'] = $options['cssBlock'] ?? $config->read('viewBlocks.css', ConfigDefaults::VIEW_BLOCK_SCRIPT);
		$options['filter'] = $options['filter'] ?? false;

        if ($this->isDev($config)) {
            $this->devScript($options, $config);

            return;
        }

        $this->productionScript($options, $config);
    }

    /**
     * @param array $options passed to script tag
     * @param \ViteHelper\Utilities\ViteHelperConfig $config config instance
     * @return void
     * @throws \ViteHelper\Exception\ConfigurationException
     */
    private function devScript(array $options, ViteHelperConfig $config): void
    {
        $this->Html->script(
            $config->read('developmentUrl', ConfigDefaults::DEVELOPMENT_URL)
            . '/@vite/client',
            [
                'type' => 'module',
                'block' => $options['cssBlock'],
            ]
        );

		unset($options['cssBlock']);
		unset($options['filter']);

        $options['type'] = 'module';
        $files = $this->getFiles($config->read('development.scriptEntries', ConfigDefaults::DEVELOPMENT_SCRIPT_ENTRIES));
        foreach ($files as $file) {
            $this->Html->script(Text::insert(':host/:file', [
                'host' => $config->read('developmentUrl', ConfigDefaults::DEVELOPMENT_URL),
                'file' => ltrim($file, DS),
            ]), $options);
        }
    }

	/**
	 * @param array $options will be passed to script tag
	 * @param \ViteHelper\Utilities\ViteHelperConfig $config config instance
	 * @return void
	 * @throws \ViteHelper\Exception\ManifestNotFoundException
	 * @throws InvalidArgumentException
	 */
    private function productionScript(array $options, ViteHelperConfig $config): void
    {
        $pluginPrefix = $config->read('plugin');
        $pluginPrefix = $pluginPrefix ? $pluginPrefix . '.' : null;

        $records = $this->getFilteredRecords(ViteManifest::getRecords($config), $options);
		unset($options['filter']);
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

            $this->Html->script($pluginPrefix . $record->getFileUrl(), $options);

            // the js files has css dependency ?
            $cssFiles = $record->getCss();
            if (!count($cssFiles)) {
                continue;
            }

            foreach ($cssFiles as $cssFile) {
                $this->Html->css($pluginPrefix . $cssFile, [
                    'block' => $cssBlock,
                ]);
            }
        }
    }

	/**
	 * Adds the gives CSS styles to the configured block
	 * The $options array is directly passed to the Html-Helper.
	 *
	 * @param array $options are passed to the <link> tags as parameters, e.g. for media="screen" etc.
	 * @param \ViteHelper\Utilities\ViteHelperConfig|null $config config instance
	 * @return void
	 * @throws \ViteHelper\Exception\ManifestNotFoundException
	 * @throws \ViteHelper\Exception\ConfigurationException
	 * @throws InvalidArgumentException
	 */
    public function css(array $options = [], ?ViteHelperConfig $config = null): void
    {
        $config = $config ?: ViteHelperConfig::create();

		$options['block'] = $options['block'] ?? $config->read('viewBlocks.css', ConfigDefaults::VIEW_BLOCK_SCRIPT);
		$options['filter'] = $options['filter'] ?? false;

        if ($this->isDev($config)) {
            $files = $this->getFiles($config->read('development.styleEntries', ConfigDefaults::DEVELOPMENT_SCRIPT_ENTRIES));
            foreach ($files as $file) {
                $this->Html->css(Text::insert(':host/:file', [
                    'host' => $config->read('ViteHelper.developmentUrl', ConfigDefaults::DEVELOPMENT_URL),
                    'file' => ltrim($file, '/'),
                ]), $options);
            }

            return;
        }

        $pluginPrefix = $config->read('plugin');
        $pluginPrefix = $pluginPrefix ? $pluginPrefix . '.' : null;
        $records = $this->getFilteredRecords(ViteManifest::getRecords($config), $options);
		unset($options['filter']);
        foreach ($records as $record) {
            if (!$record->isEntry() || !$record->isStylesheet() || $record->isLegacy()) {
                continue;
            }

            $this->Html->css($pluginPrefix . $record->getFileUrl(), $options);
        }
    }

    /**
     * @param mixed $files entry points from config
     * @return array
     * @throws \ViteHelper\Exception\ConfigurationException
     */
    private function getFiles(mixed $files): array
    {
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

	/**
	 * @param ManifestRecords $records
	 * @param array $options
	 * @return ManifestRecords|CollectionInterface
	 * @throws InvalidArgumentException
	 */
	private function getFilteredRecords(ManifestRecords $records, array $options): ManifestRecords|CollectionInterface
	{
		$filter = $options['filter'];
		if (empty($filter)) {
			return $records;
		}

		if (is_callable($filter)) {
			return $records->filter($filter);
		}

		if (is_string($filter)) {
			$filter = (array)$filter;
		}

		if (!is_array($filter)) {
			throw new InvalidArgumentException('$options["filter"] must be empty or of type string, array, or callable.');
		}

		return $records->filter(function (ManifestRecord $record) use ($filter) {
			foreach ($filter as $property => $file) {
				$property = is_string($property) ? $property : null;
				if ($record->match($file, $property)) {

					return true;
				}
			}

			return false;
		});
	}
}

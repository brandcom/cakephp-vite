<?php
declare(strict_types=1);

namespace ViteHelper\View\Helper;

use Cake\Collection\Collection;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Event\EventInterface;
use Cake\Utility\Text;
use Cake\View\Helper;
use ViteHelper\Enum\Environment;
use ViteHelper\Exception\ConfigurationException;
use ViteHelper\Exception\ManifestNotFoundException;
use ViteHelper\Model\Entity\ScriptRecord;
use ViteHelper\Model\Entity\StyleRecord;
use ViteHelper\Utilities\ManifestRecord;
use ViteHelper\Utilities\ViteManifest;

/**
 * ViteScripts helper
 *
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class ViteScriptsHelper extends Helper
{
	public const VITESCRIPT_DETECTOR_NAME = 'vite_in_production';

	private const COMMON_ENTRIES_ARRAY_KEY = 'all';

	public array $helpers = ['Html'];

	protected Collection $entries;

	public array $_defaultConfig = [
		'plugin' => false,
		'environment' => Environment::PRODUCTION,
		'build' => [
			'outDirectory' => false,
			'manifest' => WWW_ROOT . 'manifest.json',
		],
		'viewBlocks' => [
			'css' => 'css',
			'script' => 'script',
		],
		'development' => [
			'url' => 'http://localhost:3000',
		],
	];

	/**
	 * @inheritDoc
	 * @throws \ViteHelper\Exception\ConfigurationException
	 */
	public function initialize(array $config): void
	{
		parent::initialize($config);
		$this->setConfig(Configure::read('ViteHelper'), true);
		$this->setConfig($config, true);
		$env = $this->getConfig('environment', 'prod');
		if (is_string($env)) {
			$env = Environment::from($env);
		}

		if (!($env instanceof Environment)) {
			throw new ConfigurationException('Invalid environment config!');
		}

		if ($env === Environment::FROM_DETECTOR) {
			$this->setConfig(
				'environment',
				$this->getView()->getRequest()->is(self::VITESCRIPT_DETECTOR_NAME) ?
					Environment::PRODUCTION : Environment::DEVELOPMENT
			);
		}

		if ($env === Environment::DEVELOPMENT) {
			$this->Html->script(
				$this->getConfig('development.url')
				. '/@vite/client',
				[
					'type' => 'module',
					'block' => $this->getConfig('viewBlocks.css'),
				]
			);
		}

		$this->entries = new Collection([]);
		$this->getView()->getEventManager()->on('Vite.render', [$this, 'render']);
	}

	/**
	 * Adds styles and scripts to the blocks
	 *
	 * @return void
	 */
	public function render(): void
	{
		if ($this->getConfig('environment', Environment::PRODUCTION) === Environment::DEVELOPMENT) {
			$this->outputDevelopmentScripts();
			$this->outputDevelopmentStyles();
			foreach ($this->entries as $entry) {
				if ($entry->environment === Environment::DEVELOPMENT) {
					$entry->is_rendered = true;
				}
			}
		} else {
			$this->outputProductionScripts();
			$this->outputProductionStyles();
			foreach ($this->entries as $entry) {
				if ($entry->environment === Environment::PRODUCTION) {
					$entry->is_rendered = true;
				}
			}
		}
	}

	/**
	 * Is called after each view file is rendered. This includes elements, views, parent views and layouts.
	 * A callback can modify and return $content to change how the rendered content will be displayed in the browser.
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param $viewFile
	 * @param $content
	 * @return void
	 */
	public function afterRenderFile(EventInterface $event, $viewFile, $content): void
	{
	}

	/**
	 * Adds scripts to the script view block
	 *
	 * @param array|string $files files to serve
	 * @param \ViteHelper\Enum\Environment|null|string $environment the files will be served only in this environment, null on both
	 * @param string|null $block name of the view block to render the scripts in
	 * @param string|null $plugin
	 * @param array $elementOptions options to the html tag
	 * @return void
	 */
	public function script(
		array|string $files = [],
		Environment|null|string $environment = null,
		string|null $block = null,
		string|null $plugin = null,
		array $elementOptions = [],
	): void {
		if (is_string($environment)) {
			$environment = Environment::tryFrom($environment);
		}
		$elementOptions['block'] = $block ?? $this->getConfig('viewBlocks.script');
		$files = (array)$files;
		foreach ($files as $file) {
			switch ($environment) {
				case Environment::DEVELOPMENT:
					$this->entries[] = new ScriptRecord(
						$file,Environment::DEVELOPMENT, $block, $plugin, $elementOptions,
					);
					break;
				case Environment::PRODUCTION:
					$this->entries[] = new ScriptRecord(
						$file,Environment::PRODUCTION, $block, $plugin, $elementOptions,
					);
					break;
				default:
					$this->entries[] = new ScriptRecord(
						$file,Environment::DEVELOPMENT, $block, $plugin, $elementOptions,
					);
					$this->entries[] = new ScriptRecord(
						$file,Environment::PRODUCTION, $block, $plugin, $elementOptions,
					);
					break;
			}
		}
		$this->render();
	}

	/**
	 * Adds style to the css view block
	 *
	 * @param array|string $files files to serve
	 * @param \ViteHelper\Enum\Environment|null|string $environment the files will be served only in this environment, null on both
	 * @param string|null $block name of the view block to render the scripts in
	 * @param string|null $plugin
	 * @param array $elementOptions options to the html tag
	 * @return void
	 */
	public function css(
		array|string $files = [],
		Environment|null|string $environment = null,
		string|null $block = null,
		string|null $plugin = null,
		array $elementOptions = [],
	): void {
		if (is_string($environment)) {
			$environment = Environment::tryFrom($environment);
		}
		$elementOptions['block'] = $block ?? $this->getConfig('viewBlocks.css');
		$files = (array)$files;
		foreach ($files as $file) {
			switch ($environment) {
				case Environment::DEVELOPMENT:
					$this->entries[] = new StyleRecord(
						$file,Environment::DEVELOPMENT, $block, $plugin, $elementOptions,
					);
					break;
				case Environment::PRODUCTION:
					$this->entries[] = new StyleRecord(
						$file,Environment::PRODUCTION, $block, $plugin, $elementOptions,
					);
					break;
				default:
					$this->entries[] = new StyleRecord(
						$file,Environment::DEVELOPMENT, $block, $plugin, $elementOptions,
					);
					$this->entries[] = new StyleRecord(
						$file,Environment::PRODUCTION, $block, $plugin, $elementOptions,
					);
					break;
			}
		}
		$this->render();
	}

	/**
	 * Appends development script tags to configured block
	 *
	 * @return void
	 */
	private function outputDevelopmentScripts(): void
	{
		$files = $this->entries->filter(function ($record) {
			return
				$record instanceof ScriptRecord &&
				!$record->is_rendered &&
				Environment::DEVELOPMENT === $record->environment;
		});

		/** @var \ViteHelper\Model\Entity\ScriptRecord $record */
		foreach ($files as $record) {
			$record->elementOptions['type'] = 'module';
			$this->Html->script(Text::insert(':host/:file', [
				'host' => $this->getConfig('development.url'),
				'file' => ltrim($record->file, DS),
			]), $record->elementOptions);
		}
	}

	/**
	 * Appends development style tags to configured block
	 *
	 * @return void
	 */
	private function outputDevelopmentStyles(): void
	{
		$files = $this->entries->filter(function ($record) {
			return
				$record instanceof StyleRecord &&
				!$record->is_rendered &&
				Environment::DEVELOPMENT === $record->environment;
		});

		/** @var \ViteHelper\Model\Entity\StyleRecord $record */
		foreach ($files as $record) {
			$this->Html->css(Text::insert(':host/:file', [
				'host' => $this->getConfig('development.url'),
				'file' => ltrim($record->file, '/'),
			]), $record->elementOptions);
		}
	}

	/**
	 * Appends production script tags to configured block
	 *
	 * @return void
	 */
	private function outputProductionScripts(): void
	{
		$files = $this->entries->filter(function ($record) {
			return
				$record instanceof ScriptRecord &&
				!$record->is_rendered &&
				Environment::PRODUCTION === $record->environment;
		});

		$records = $this->getManifestRecords($files);

		$pluginPrefix = $this->getConfig('plugin');
		$pluginPrefix = $pluginPrefix ? $pluginPrefix . '.' : null;
		/** @var \ViteHelper\Utilities\ManifestRecord $record */
		foreach ($records as $record) {
			if (!$record->isEntryScript()) {
				continue;
			}

			$options = $record->getMetadata();
			if ($record->isModuleEntryScript()) {
				$options['type'] = 'module';
			} else {
				$options['nomodule'] = 'nomodule';
			}

			$recordPluginPrefix = $pluginPrefix;
			if (isset($options['plugin'])) {
				$recordPluginPrefix = $options['plugin'] . '.';
				unset($options['plugin']);
			}
			$this->Html->script($recordPluginPrefix . $record->getFileUrl(), $options);

			// the js files has css dependency ?
			$cssFiles = $record->getCss();
			if (!count($cssFiles)) {
				continue;
			}

			foreach ($cssFiles as $cssFile) {
				$this->Html->css($recordPluginPrefix . $cssFile, [
					'block' => $this->getConfig('viewBlocks.css'),
				]);
			}
			unset($recordPluginPrefix);
		}
	}

	/**
	 * Appends production style tags to configured block
	 *
	 * @return void
	 */
	private function outputProductionStyles(): void
	{
		$pluginPrefix = $this->getConfig('plugin');
		$pluginPrefix = $pluginPrefix ? $pluginPrefix . '.' : null;
		$records = $this->getManifestRecords(array_merge(
			$this->getConfig('styleEntries.prod'),
			$this->getConfig('styleEntries.' . self::COMMON_ENTRIES_ARRAY_KEY),
		));

		foreach ($records as $record) {
			if (!$record->isEntry() || !$record->isStylesheet() || $record->isLegacy()) {
				continue;
			}
			$options = $record->getMetadata();
			$recordPluginPrefix = $pluginPrefix;
			if (isset($options['plugin'])) {
				$recordPluginPrefix = $options['plugin'] . '.';
				unset($options['plugin']);
			}

			$this->Html->css($pluginPrefix . $record->getFileUrl(), $options);
			unset($recordPluginPrefix);
		}
	}

	/**
	 * Returns manifest records with the correct metadata
	 *
	 * @param array $files
	 * @return iterable
	 */
	private function getManifestRecords(iterable $files): iterable
	{
		if ($this->getConfig('plugin') && $this->getConfig('build.manifest') === null) {
			$manifestPath = Plugin::path($this->getConfig('plugin')) . 'webroot' . DS . 'manifest.json';
		} else {
			$manifestPath = $this->getConfig('build.manifest');
		}

		try {
			$records = ViteManifest::getRecords($manifestPath, $this->getConfig('build.outDirectory'));
			$records = $records->map(function (ManifestRecord $record) use ($files) {
				/** @var \ViteHelper\Model\Entity\StyleRecord|\ViteHelper\Model\Entity\ScriptRecord $file */
				foreach ($files as $file) {
					if ($record->match($file->file)) {
						$record->setMetadata([
							'options' => $file->elementOptions,
							'plugin' => $file->plugin,
						]);
					}
				}

				return $record;
			});
		} catch (ManifestNotFoundException|\JsonException $e) {
			$records = [];
		}

		return $records;
	}
}

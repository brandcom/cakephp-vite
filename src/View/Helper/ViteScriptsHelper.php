<?php
declare(strict_types=1);

namespace ViteHelper\View\Helper;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Event\EventInterface;
use Cake\Utility\Text;
use Cake\View\Helper;
use ViteHelper\Enum\Environment;
use ViteHelper\Exception\ConfigurationException;
use ViteHelper\Exception\ManifestNotFoundException;
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
		'scriptEntries' => [
			'prod' => [],
			'dev' => [],
			'all' => [],
		],
		'styleEntries' => [
			'prod' => [],
			'dev' => [],
			'all' => [],
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
	}

	/**
	 * The beforeRender method is called after the controller’s beforeRender method but before the controller renders
	 * view and layout. Receives the file being rendered as an argument.
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param $viewFile
	 * @return void
	 */
	public function beforeRender(EventInterface $event, $viewFile): void
	{
		if ($this->getConfig('environment', Environment::PRODUCTION) === Environment::DEVELOPMENT) {
			$this->outputDevelopmentScripts();
			$this->outputDevelopmentStyles();
		} else {
			$this->outputProductionScripts();
			$this->outputProductionStyles();
		}
	}

	/**
	 * Adds scripts to the script view block
	 *
	 * @param array|string $files files to serve
	 * @param \ViteHelper\Enum\Environment|null|string $environment the files will be served only in this environment, null on both
	 * @param string|null $block name of the view block to render the scripts in
	 * @param string|null $plugin
	 * @param array $attr attributes to the html tag
	 * @return void
	 */
    public function script(
		array|string $files = [],
		Environment|null|string $environment = null,
		string|null $block = null,
		string|null $plugin = null,
		array $attr = [],
	): void {
		if (is_string($environment)) {
			$environment = Environment::tryFrom($environment);
		}
		$config_key = 'scriptEntries.' . $environment?->value ?? self::COMMON_ENTRIES_ARRAY_KEY;
		$attr += ['type' => 'module'];
		$attr['block'] = $block;
		$files = (array)$files;
		foreach ($files as $file) {
			$this->_config->{$config_key}[$file] = [
				'attr' => $attr,
				'plugin' => $plugin,
			];
		}
    }

	/**
	 * Adds style to the css view block
	 *
	 * @param array|string $files files to serve
	 * @param \ViteHelper\Enum\Environment|null|string $environment the files will be served only in this environment, null on both
	 * @param string|null $block name of the view block to render the scripts in
	 * @param string|null $plugin
	 * @param array $attr attributes to the html tag
	 * @return void
	 */
    public function css(
		array|string $files = [],
		Environment|null|string $environment = null,
		string|null $block = null,
		string|null $plugin = null,
		array $attr = [],
	): void {
		if (is_string($environment)) {
			$environment = Environment::tryFrom($environment);
		}
		$config_key = 'styleEntries.' . $environment?->value ?? self::COMMON_ENTRIES_ARRAY_KEY;
		$attr['block'] = $block;
		$files = (array)$files;
		foreach ($files as $file) {
			$this->_config->{$config_key}[$file] = [
				'attr' => $attr,
				'plugin' => $plugin,
			];
		}
    }

	/**
	 * Appends development script tags to configured block
	 *
	 * @return void
	 */
	private function outputDevelopmentScripts(): void
	{
		$files = array_merge(
			$this->getConfig('scriptEntries.dev'),
			$this->getConfig('scriptEntries.' . self::COMMON_ENTRIES_ARRAY_KEY),
		);
		$this->Html->script(
			$this->getConfig('development.url')
			. '/@vite/client',
			[
				'type' => 'module',
				'block' => $this->getConfig('viewBlocks.css'),
			]
		);
		foreach ($files as $file => $attr) {
			$this->Html->script(Text::insert(':host/:file', [
				'host' => $this->getConfig('development.url'),
				'file' => ltrim($file, DS),
			]), $attr);
		}
	}

	/**
	 * Appends development style tags to configured block
	 *
	 * @return void
	 */
	private function outputDevelopmentStyles(): void
	{
		$files = array_merge(
			$this->getConfig('styleEntries.dev'),
			$this->getConfig('styleEntries.' . self::COMMON_ENTRIES_ARRAY_KEY),
		);
		foreach ($files as $file => $options) {
			$this->Html->css(Text::insert(':host/:file', [
				'host' => $this->getConfig('development.url'),
				'file' => ltrim($file, '/'),
			]), $options);
		}
	}

	/**
	 * Appends production script tags to configured block
	 *
	 * @return void
	 */
	private function outputProductionScripts(): void
	{
		$records = $this->getManifestRecords(array_merge(
			$this->getConfig('scriptEntries.prod'),
			$this->getConfig('scriptEntries.' . self::COMMON_ENTRIES_ARRAY_KEY),
		));

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
	private function getManifestRecords(array $files): iterable
	{
		if ($this->getConfig('plugin') && $this->getConfig('build.manifest') === null) {
			$manifestPath = Plugin::path($this->getConfig('plugin')) . 'webroot' . DS . 'manifest.json';
		} else {
			$manifestPath = $this->getConfig('build.manifest');
		}

		try {
			$records = ViteManifest::getRecords($manifestPath, $this->getConfig('build.outDirectory'));
			$records = $records->map(function (ManifestRecord $record) use ($files) {
				foreach ($files as $file => $attributes) {
					if ($record->match($file)) {
						$record->setMetadata($attributes);
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

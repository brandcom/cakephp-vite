<?php
declare(strict_types=1);

namespace ViteHelper\View\Helper;

use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\Utility\Text;
use Cake\View\Helper;
use ViteHelper\Enum\Environment;
use ViteHelper\Enum\RenderMode;
use ViteHelper\Exception\ConfigurationException;
use ViteHelper\Model\Entity\BuildConfig;
use ViteHelper\Model\Entity\ScriptRecord;
use ViteHelper\Model\Entity\StyleRecord;
use ViteHelper\Utilities\ManifestRecord;

/**
 * ViteScripts helper
 *
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class ViteScriptsHelper extends Helper
{
	public const VITESCRIPT_DETECTOR_NAME = 'vite_in_production';

	private const DEFAULT_BUILD_KEY = '_default_build';

	/**
	 * @var array|string[]
	 */
	public array $helpers = ['Html'];

	/**
	 * The style and script files
	 *
	 * @var array
	 */
	protected array $entries;

	/**
	 * Build information
	 * These settings comes from the configuration
	 *
	 * @var array<\ViteHelper\Model\Entity\BuildConfig> $builds
	 */
	protected array $builds;

	/**
	 * @inheritdoc
	 */
	protected array $_defaultConfig = [
		'environment' => Environment::PRODUCTION,
		'development' => [
			'url' => 'http://localhost:3000',
		],
		'builds' => [],
		'render_mode' => RenderMode::AUTO,
		'viewBlocks' => [
			'css' => 'css',
			'script' => 'script',
		],
	];

	/**
	 * @inheritDoc
	 * @throws \ViteHelper\Exception\ConfigurationException
	 */
	public function initialize(array $config): void
	{
		parent::initialize($config);
		$this->setConfig(Configure::read('ViteHelper'));
		$this->setConfig($config);
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
			$env = $this->getConfig('environment');
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

		$this->builds = [];
		foreach ($this->getConfig('builds') as $build) {
			if (isset($build['environment']) && is_string($build['environment'])) {
				$environment = Environment::tryFrom($build['environment']) ?? $env;
			} else {
				$environment = $build['environment'] ?? $env;
			}
			if (isset($build['plugin'])) {
				$this->builds[$build['plugin']] = new BuildConfig(
					manifest: $build['manifest'] ?? 'build' . DS . '.vite' . DS . 'manifest.json',
					outputDirectory: $build['outputDirectory'] ?? 'build',
					plugin: $build['plugin'],
					environment: $environment,
				);
			} else {
				$this->builds[self::DEFAULT_BUILD_KEY] = new BuildConfig(
					manifest: $build['manifest'] ?? 'build' . DS . '.vite' . DS . 'manifest.json',
					outputDirectory: $build['outputDirectory'] ?? 'build',
					plugin: null,
					environment: $environment,
				);
			}
		}

		$this->getView()->getEventManager()->on('Vite.render', [$this, 'render']);
	}

	/**
	 * Adds scripts to the script view block
	 *
	 * @param array|string $files files to serve
	 * @param string|null $block name of the view block to render the scripts in
	 * @param string|null $plugin
	 * @param array $elementOptions options to the html tag
	 * @param \ViteHelper\Enum\Environment|string|null $environment the files will be served only in this environment, null on both
	 * @return void
	 */
	public function script(
		array|string            $files = [],
		?string                 $block = null,
		?string                 $plugin = null,
		array                   $elementOptions = [],
		Environment|string|null $environment = null,
	): void
	{
		$env = is_string($environment) ? Environment::tryFrom($environment) : $environment;
		$elementOptions['block'] = $block ?? $this->getConfig('viewBlocks.script');
		$files = (array)$files;
		foreach ($files as $file) {
			$this->entries[] = match ($env) {
				Environment::DEVELOPMENT => new ScriptRecord(
					$file,
					[Environment::DEVELOPMENT],
					$block,
					$plugin,
					$elementOptions,
				),
				Environment::PRODUCTION => new ScriptRecord(
					$file,
					[Environment::PRODUCTION],
					$block,
					$plugin,
					$elementOptions,
				),
				default => new ScriptRecord(
					$file,
					[Environment::PRODUCTION, Environment::DEVELOPMENT],
					$block,
					$plugin,
					$elementOptions,
				),
			};
		}
		if ($this->getConfig('render_mode') === RenderMode::AUTO) {
			$this->render();
		}
	}

	/**
	 * Adds style to the css view block
	 *
	 * @param array|string $files files to serve
	 * @param string|null $block name of the view block to render the scripts in
	 * @param string|null $plugin
	 * @param array $elementOptions options to the html tag
	 * @param \ViteHelper\Enum\Environment|string|null $environment the files will be served only in this environment, null on both
	 * @return void
	 */
	public function css(
		array|string            $files = [],
		?string                 $block = null,
		?string                 $plugin = null,
		array                   $elementOptions = [],
		Environment|string|null $environment = null,
	): void
	{
		$env = is_string($environment) ? Environment::tryFrom($environment) : $environment;
		$elementOptions['block'] = $block ?? $this->getConfig('viewBlocks.css');
		$files = (array)$files;
		foreach ($files as $file) {
			$this->entries[] = match ($env) {
				Environment::DEVELOPMENT => new StyleRecord(
					$file,
					[Environment::DEVELOPMENT],
					$block,
					$plugin,
					$elementOptions,
				),
				Environment::PRODUCTION => new StyleRecord(
					$file,
					[Environment::PRODUCTION],
					$block,
					$plugin,
					$elementOptions,
				),
				default => new StyleRecord(
					$file,
					[Environment::PRODUCTION, Environment::DEVELOPMENT],
					$block,
					$plugin,
					$elementOptions,
				),
			};
		}
		if ($this->getConfig('render_mode') === RenderMode::AUTO) {
			$this->render();
		}
	}

	/**
	 * Adds styles and scripts to the blocks
	 *
	 * @return void
	 */
	public function render(): void
	{
		$currentEnvironment = $this->getConfig('environment', Environment::PRODUCTION);
		$records = array_filter($this->entries, static function ($record) {
			/** @var \ViteHelper\Model\Entity\ScriptRecord|\ViteHelper\Model\Entity\StyleRecord $record */
			return !$record->is_rendered;
		});

		/** @var \ViteHelper\Model\Entity\ScriptRecord|\ViteHelper\Model\Entity\StyleRecord $record */
		foreach ($records as $record) {
			// should we render ?!
			if (!$record->isValidEnvironment($currentEnvironment)) {
				continue;
			}

			// how we should do it ?
			$renderEnvironment = $this->getBuildEnvironment($record);
			if ($renderEnvironment === Environment::DEVELOPMENT) {
				$this->renderDevelopmentRecord($record);
			} else {
				$this->renderProductionRecord($record);
			}
		}
	}

	/**
	 * Appends development style/script tag to configured block
	 *
	 * @param \ViteHelper\Model\Entity\StyleRecord|\ViteHelper\Model\Entity\ScriptRecord $record
	 * @return void
	 */
	private function renderDevelopmentRecord(StyleRecord|ScriptRecord $record): void
	{
		$record->is_rendered = true;
		if ($record instanceof ScriptRecord) {
			$record->elementOptions['type'] = 'module';
			$this->Html->script(Text::insert(':host/:file', [
				'host' => $this->getConfig('development.url'),
				'file' => ltrim($record->file, DS),
			]), $record->elementOptions);
		} else {
			$this->Html->css(Text::insert(':host/:file', [
				'host' => $this->getConfig('development.url'),
				'file' => ltrim($record->file, '/'),
			]), $record->elementOptions);
		}
	}

	/**
	 * Appends production style/script tag to configured block
	 *
	 * @param \ViteHelper\Model\Entity\StyleRecord|\ViteHelper\Model\Entity\ScriptRecord $record
	 * @return void
	 */
	private function renderProductionRecord(StyleRecord|ScriptRecord $record): void
	{
		$record->is_rendered = true;
		if ($record instanceof ScriptRecord) {
			$this->renderProductionScripts($record);
		} else {
			$this->renderProductionStyle($record);
		}
	}

	/**
	 * Appends production script tags to configured block
	 *
	 * @param \ViteHelper\Model\Entity\ScriptRecord $file
	 * @return void
	 */
	private function renderProductionScripts(ScriptRecord $file): void
	{
		$manifestRecords = $this->getManifestRecords($file);

		/** @var \ViteHelper\Utilities\ManifestRecord $manifestRecord */
		foreach ($manifestRecords as $manifestRecord) {
			if (!$manifestRecord->isEntryScript()) {
				continue;
			}

			$options = $manifestRecord->getMetadata();
			if ($manifestRecord->isModuleEntryScript()) {
				$options['options']['type'] = 'module';
			} else {
				$options['options']['nomodule'] = 'nomodule';
			}

			$recordPluginPrefix = $file->plugin;
			if (isset($options['plugin'])) {
				$recordPluginPrefix = $options['plugin'] . '.';
				unset($options['plugin']);
			}
			$this->Html->script($recordPluginPrefix . $manifestRecord->getFileUrl(), $options['options']);

			// the js files has css dependency ?
			$cssFiles = $manifestRecord->getCss();
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
	 * @param \ViteHelper\Model\Entity\StyleRecord $file
	 * @return void
	 */
	private function renderProductionStyle(StyleRecord $file): void
	{
		$records = $this->getManifestRecords($file);
		foreach ($records as $record) {
			if (!$record->isEntry() || !$record->isStylesheet() || $record->isLegacy()) {
				continue;
			}

			$options = $record->getMetadata();
			$recordPluginPrefix = $file->plugin;
			if (isset($options['plugin'])) {
				$recordPluginPrefix = $options['plugin'] . '.';
				unset($options['plugin']);
			}

			$this->Html->css($recordPluginPrefix . $record->getFileUrl(), $options['options']);
			unset($recordPluginPrefix);
		}
	}

	/**
	 * Returns manifest records with the correct metadata
	 *
	 * @param \ViteHelper\Model\Entity\StyleRecord|\ViteHelper\Model\Entity\ScriptRecord $file
	 * @return \Cake\Collection\CollectionInterface
	 */
	private function getManifestRecords(StyleRecord|ScriptRecord $file): CollectionInterface
	{
		if (!isset($this->builds[$file->plugin ?? self::DEFAULT_BUILD_KEY])) {
			trigger_error('Invalid plugin name: ' . $file->plugin, E_USER_WARNING);

			return new Collection([]);
		}
		$build = $this->builds[$file->plugin ?? self::DEFAULT_BUILD_KEY];

		return $build->getManifestRecords()->filter(function (ManifestRecord $record) use ($file) {
			if ($record->match($file->file)) {
				$record->setMetadata([
					'options' => $file->elementOptions,
					'plugin' => $file->plugin,
				]);

				return $record;
			}

			return false;
		});
	}

	/**
	 * Returns a record forced environment
	 *
	 * @param \ViteHelper\Model\Entity\StyleRecord|\ViteHelper\Model\Entity\ScriptRecord $record
	 * @return \ViteHelper\Enum\Environment
	 */
	private function getBuildEnvironment(StyleRecord|ScriptRecord $record): Environment
	{
		if (!isset($this->builds[$record->plugin ?? self::DEFAULT_BUILD_KEY])) {
			return $this->getConfig('environment', Environment::PRODUCTION);
		}

		return $this->builds[$record->plugin ?? self::DEFAULT_BUILD_KEY]->getEnvironment();
	}
}

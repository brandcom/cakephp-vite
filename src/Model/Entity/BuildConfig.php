<?php
declare(strict_types=1);

namespace ViteHelper\Model\Entity;

use Cake\Core\Plugin;
use Exception;
use ViteHelper\Enum\Environment;
use ViteHelper\Exception\ConfigurationException;
use ViteHelper\Utilities\ManifestRecords;
use ViteHelper\Utilities\ViteManifest;

/**
 * One build entry in configuration
 */
class BuildConfig
{
	/**
	 * All records in the manifest file
	 *
	 * @var \ViteHelper\Utilities\ManifestRecords|null
	 */
	protected ?ManifestRecords $manifestRecords = null;

	/**
	 * Default constructor
	 *
	 * @param string $manifest
	 * @param string $outputDirectory
	 * @param string|null $plugin
	 * @param \ViteHelper\Enum\Environment $environment
	 * @throws \ViteHelper\Exception\ConfigurationException
	 */
	public function __construct(
		readonly private string $manifest,
		readonly private string $outputDirectory = 'build',
		readonly private ?string $plugin = null,
		readonly private Environment $environment = Environment::PRODUCTION,
	) {
		if ($environment === Environment::FROM_DETECTOR) {
			throw new ConfigurationException('Invalid environment in build config');
		}
	}

	/**
	 * The plugin's name (if provided)
	 *
	 * @return string|null
	 */
	public function getPlugin(): ?string
	{
		return $this->plugin;
	}

	/**
	 * Returns true if the build is part of a plugin
	 *
	 * @return bool
	 */
	public function hasPlugin(): bool
	{
		return !is_null($this->plugin);
	}

	/**
	 * Returns the output directory name
	 *
	 * @return string
	 */
	public function getOutputDirectory(): string
	{
		return $this->outputDirectory;
	}

	/**
	 * Returns the full path of the manifest file
	 *
	 * @return string
	 */
	public function getManifest(): string
	{
		if (!is_null($this->plugin)) {
			return Plugin::path($this->plugin) . 'webroot' . DS . $this->manifest;
		}

		return WWW_ROOT . $this->manifest;
	}

	/**
	 * Returns the records
	 *
	 * @return \ViteHelper\Utilities\ManifestRecords
	 */
	public function getManifestRecords(): ManifestRecords
	{
		if (!is_null($this->manifestRecords)) {
			return $this->manifestRecords;
		}

		try {
			$this->manifestRecords = ViteManifest::getRecords($this->getManifest(), $this->getOutputDirectory());
		} catch (Exception $e) {
			$this->manifestRecords = new ManifestRecords([], $this->getOutputDirectory());
			trigger_error($e->getMessage(), E_USER_WARNING);
		}

		return $this->manifestRecords;
	}

	/**
	 * Returns the forced environment
	 *
	 * @return \ViteHelper\Enum\Environment
	 */
	public function getEnvironment(): Environment
	{
		return $this->environment;
	}
}

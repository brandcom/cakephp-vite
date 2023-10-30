<?php
declare(strict_types=1);

namespace ViteHelper\Utilities;

use Cake\Core\Plugin;
use ViteHelper\Exception\ManifestNotFoundException;

/**
 * Reads the information in the manifest.json file provided by ViteJs after running 'vite build'
 */
class ViteManifest
{
    /**
     * Returns the manifest records as a Collection
     *
     * @param \ViteHelper\Utilities\ViteHelperConfig $config plugin config instance
     * @return \ViteHelper\Utilities\ManifestRecords|array<\ViteHelper\Utilities\ManifestRecord>
     * @throws \ViteHelper\Exception\ManifestNotFoundException
     * @internal
     */
    public static function getRecords(ViteHelperConfig $config): ManifestRecords
    {
        if ($config->read('plugin') && $config->read('build.manifest') === null) {
            $manifestPath = static::getPluginManifestPath($config->read('plugin'));
        } else {
            $manifestPath = $config->read('build.manifest', ConfigDefaults::BUILD_MANIFEST);
        }

        if (!is_readable($manifestPath)) {
            throw new ManifestNotFoundException(
                "No valid manifest.json found at path {$manifestPath}. Did you build your js?",
            );
        }

		// phpcs:ignore
		$json = @file_get_contents($manifestPath);

        if ($json === false) {
            throw new ManifestNotFoundException('Could not parse manifest.json');
        }

        $json = str_replace(
            [
                "\u0000",
            ],
            '',
            $json
        );

        $manifest = json_decode($json, false, 512, JSON_THROW_ON_ERROR);

        $manifestArray = [];
        foreach (get_object_vars($manifest) as $property => $value) {
            $manifestArray[$property] = new ManifestRecord($property, $value, $config);
        }

        /**
         * Legacy Polyfills must come first.
         */
        usort($manifestArray, function ($file) {
            /** @var \ViteHelper\Utilities\ManifestRecord $file */
            return $file->isPolyfill() ? 0 : 1;
        });

        /**
         * ES-module scripts must come last.
         */
        usort($manifestArray, function ($file) {
            /** @var \ViteHelper\Utilities\ManifestRecord $file */
            return !$file->isPolyfill() && !$file->isLegacy() ? 1 : 0;
        });

        return new ManifestRecords($manifestArray, $manifestPath);
    }

    /**
     * Get the default location of a plugin's vite manifest.json
     *
     * @param string $pluginName
     * @return string
     */
    protected static function getPluginManifestPath(string $pluginName): string
    {
        return Plugin::path($pluginName) . 'webroot' . DS . 'manifest.json';
    }
}

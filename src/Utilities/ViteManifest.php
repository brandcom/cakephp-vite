<?php
declare(strict_types=1);

namespace ViteHelper\Utilities;

use Nette\Utils\FileSystem;
use Nette\Utils\Json;
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
     * @return \ViteHelper\Utilities\ManifestRecords|\ViteHelper\Utilities\ManifestRecord[]
     * @throws \ViteHelper\Exception\ManifestNotFoundException
     * @internal
     */
    public static function getRecords(ViteHelperConfig $config): ManifestRecords
    {
        $manifestPath = $config->read('build.manifest', ConfigDefaults::BUILD_MANIFEST);

        try {
            $json = FileSystem::read($manifestPath);

            $json = str_replace([
                "\u0000",
            ], '', $json);

            $manifest = Json::decode($json);
        } catch (\Exception $e) {
            throw new ManifestNotFoundException(
                "No valid manifest.json found at path {$manifestPath}. Did you build your js? Error: {$e->getMessage()}"
            );
        }

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
}

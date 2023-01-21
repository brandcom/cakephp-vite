<?php
declare(strict_types=1);

namespace ViteHelper\Utilities;

use Cake\Core\Configure;
use Nette\Utils\FileSystem;
use Nette\Utils\Json;
use ViteHelper\Exception\ManifestNotFoundException;

/**
 * Reads the information in the manifest.json file provided by ViteJs after running 'vite build'
 */
class ViteManifest
{
    /**
     * Returns the manifest records
     *
     * Can be filtered by keys with the $filters argument
     *
     * @param array<string> $filters filter by record-keys
     * @return array<\ViteHelper\Utilities\ManifestRecord>
     * @throws \ViteHelper\Exception\ManifestNotFoundException
     */
    public static function getRecords(array $filters = []): array
    {
        $records = self::readAndCreateRecords();

        if (!count($filters)) {
            return $records;
        }

        return array_filter($records, function (ManifestRecord $record) use ($filters) {
            return in_array($record->getKey(), $filters);
        });
    }

    /**
     * Converts the manifest json file from vite to more useful objects.
     * Entries are sorted so that legacy polyfills come before legacy scripts.
     *
     * @internal
     * @return array
     * @throws \ViteHelper\Exception\ManifestNotFoundException
     */
    protected static function readAndCreateRecords(): array
    {
        $manifestPath = Configure::read('ViteHelper.build.manifest', ConfigDefaults::BUILD_MANIFEST);

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
            $manifestArray[$property] = new ManifestRecord($property, $value);
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

        return $manifestArray;
    }
}

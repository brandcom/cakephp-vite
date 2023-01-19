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
    protected ?string $baseDir;

    /**
     * Relative path to the output directory
     *
     * @see https://vitejs.dev/config/build-options.html#build-outdir
     * @var string
     */
    protected string $outDir;

    /**
     * Manifest file's name
     *
     * @var string
     */
    protected string $manifest;

    /**
     * Contains all records from a manifest.json file
     *
     * @var array<\ViteHelper\Utilities\ManifestRecord>
     */
    public array $manifestRecords;

    /**
     * This class instance
     *
     * @var \ViteHelper\Utilities\ViteManifest|null
     */
    private static ?ViteManifest $instance = null;

    /**
     * Default constructor
     *
     * @throws \ViteHelper\Exception\ManifestNotFoundException
     */
    private function __construct()
    {
        $this->baseDir = Configure::read('ViteHelper.baseDirectory', ConfigDefaults::BASE_DIRECTORY);
        $this->outDir = Configure::read('ViteHelper.build.outDirectory', ConfigDefaults::BUILD_OUT_DIRECTORY);
        $this->manifest = Configure::read('ViteHelper.build.manifest', ConfigDefaults::BUILD_MANIFEST);
        $this->manifestRecords = $this->getManifest();
    }

    /**
     * Returns a ViteManifest instance
     *
     * @return self
     */
    public static function getInstance(): ViteManifest
    {
        if (is_null(self::$instance)) {
            self::$instance = new ViteManifest();
        }

        return self::$instance;
    }

    /**
     * Returns all manifest records
     *
     * @return array|\ViteHelper\Utilities\ManifestRecord[]
     */
    public function getRecords(): array
    {
        return $this->manifestRecords;
    }

    /**
     * @return array
     * @throws \ViteHelper\Exception\ManifestNotFoundException
     */
    protected function getManifest(): array
    {
        try {
            $json = FileSystem::read($this->manifest);

            $json = str_replace([
                "\u0000",
            ], '', $json);

            $manifest = Json::decode($json);
        } catch (\Exception $e) {
            throw new ManifestNotFoundException(
                "No valid manifest.json found at path {$this->manifest}. Did you build your js? Error: {$e->getMessage()}"
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

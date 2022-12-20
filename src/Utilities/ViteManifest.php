<?php
declare(strict_types=1);

namespace ViteHelper\Utilities;

use Cake\Core\Configure;
use Nette\Utils\FileSystem;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use ViteHelper\Errors\ManifestNotFoundException;

/**
 * Reads the information in the manifest.json file provided by ViteJs after running 'vite build'
 */
class ViteManifest
{
	protected ?string $baseDir;
	protected string $outDir;
    protected string $manifestFile;
    protected array $manifest;

    /**
     * @throws \ViteHelper\Errors\ManifestNotFoundException
     */
    public function __construct()
    {
        $this->baseDir = Configure::read('ViteHelper.baseDirectory', ConfigDefaults::BASE_DIR);
		$this->outDir = Configure::read('ViteHelper.build.outDir', ConfigDefaults::BUILD_OUT_DIRECTORY);
        $this->manifestFile = Configure::read('ViteHelper.build.manifest', ConfigDefaults::BUILD_MANIFEST);
        $this->manifest = $this->getManifest();
    }

    /**
     * @return array
     */
    public function getCssFiles(): array
    {
        $css_paths = [];

        foreach ($this->manifest as $file) {
            if (empty($file->isEntry) || empty($file->css)) {
                continue;
            }

            foreach ($file->css as $css_path) {
                $css_paths[] = DS . ltrim($css_path, DS);
            }
        }

        return $css_paths;
    }

    /**
     * @param bool $only_entry only return files that are entry points, e.g. the main.js or polyfills
     * @return array
     */
    public function getJsFiles(bool $only_entry = true): array
    {
        $script_paths = [];

        foreach ($this->manifest as $file) {
            /**
             * @var \stdClass $file
             */
            if ($only_entry && empty($file->isEntry)) {
                continue;
            }

            $script_paths[] = DS . ltrim($file->file, DS);
        }

        /**
         * Legacy Polyfills must come first.
         */
        usort($script_paths, function ($tag) {
            return Strings::contains($tag, 'polyfills') ? 0 : 1;
        });

        /**
         * ES-module scripts must come last.
         */
        usort($script_paths, function ($tag) {
            return !Strings::contains($tag, 'legacy') ? 1 : 0;
        });

        return $script_paths;
    }

    /**
     * @return string
     */
    public function getBuildAssetsDir(): string
    {
        $file = current($this->getJsFiles());

        if ($this->baseDir) {
            return rtrim($this->baseDir, DS)
				. DS . rtrim($this->outDir, DS)
				. DS . ltrim(Strings::before($file, DS, -1), DS);
        }

        return WWW_ROOT . ltrim(Strings::before($file, DS, -1), DS);
    }

    /**
     * @return array
     * @throws \ViteHelper\Errors\ManifestNotFoundException
     */
    protected function getManifest(): array
    {
		if ($this->baseDir) {
			$path =
				rtrim($this->baseDir, DS) . DS .
				rtrim($this->outDir, DS) . DS .
				ltrim($this->manifestFile, DS);
		} else {
			$path = rtrim($this->outDir, DS) . DS . ltrim($this->manifestFile, DS);
		}

        try {
            $json = FileSystem::read($path);

            $json = str_replace([
                "\u0000",
            ], '', $json);

            $manifest = Json::decode($json);
        } catch (\Exception $e) {
            throw new ManifestNotFoundException("No valid manifest.json found at path $path. Did you build your js? Error: {$e->getMessage()}");
        }

        $manifestArray = [];
        foreach (get_object_vars($manifest) as $property => $value) {
            $manifestArray[$property] = $value;
        }

        return $manifestArray;
    }
}

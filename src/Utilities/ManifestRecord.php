<?php
declare(strict_types=1);

namespace ViteHelper\Utilities;

use Nette\Utils\Strings;
use stdClass;

class ManifestRecord
{
    private string $key;

    private stdClass $chunk;

    private ViteHelperConfig $config;

    /**
     * Default constructor
     *
     * @param string $key The unique key for this record
     * @param \stdClass $chunk The chunks
     * @param \ViteHelper\Utilities\ViteHelperConfig $config config instance
     * @see https://vitejs.dev/guide/backend-integration.html
     */
    public function __construct(string $key, stdClass $chunk, ViteHelperConfig $config)
    {
        $this->key = $key;
        $this->chunk = $chunk;
        $this->config = $config;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

	/**
	 * @param string|null $property optional property of the chunk
	 * @return mixed the chunk itself if $property is null
	 */
    public function getChunk(?string $property = null): mixed
    {
		if (empty($property)) {
        	return $this->chunk;
		}

		return $this->chunk->{$property} ?? null;
    }

    /**
     * The current Record is an entry
     *
     * @return bool
     */
    public function isEntry(): bool
    {
        if (!empty($this->chunk->isEntry)) {
            return (bool)$this->chunk->isEntry;
        }

        return false;
    }

    /**
     * Checks if a string matches with the filename
     *
     * @param string $name the name to check
     * @param string $property the property of the chunk, defaults to `src`
     * @return bool
     */
    public function match(string $name, string $property = 'src'): bool
    {
        return property_exists($this->chunk, $property) && Strings::contains($this->chunk->{$property}, $name);
    }

    /**
     * The current Record is legacy
     *
     * @return bool
     */
    public function isLegacy(): bool
    {
        return Strings::contains($this->chunk->file, 'legacy');
    }

    /**
     * The current Record is polyfill
     *
     * @return bool
     */
    public function isPolyfill(): bool
    {
        return Strings::contains($this->chunk->file, 'polyfills');
    }

    /**
     * The current Record is a javascript file
     *
     * @return bool
     */
    public function isJavascript(): bool
    {
        return Strings::endsWith($this->chunk->file, '.js');
    }

    /**
     * The current Record is a stylesheet file
     *
     * @return bool
     */
    public function isStylesheet(): bool
    {
        return Strings::endsWith((string)$this->chunk->file, '.css');
    }

    /**
     * Returns the file's relative url
     *
     * todo support plugin build assets
     *
     * @return string
     */
    public function getFileUrl(): string
    {
        return DS . ltrim($this->config->read('build.outDirectory', ConfigDefaults::BUILD_OUT_DIRECTORY), DS)
            . DS . $this->chunk->file;
    }

    /**
     * Returns the urls to dependent CSS files
     *
     * This is the case if styles are imported in javascript and this record
     * represents a javascript file.
     *
     * @return array
     */
    public function getCss(): array
    {
        $files = $this->chunk->css ?? [];
        if (!count($files)) {
            return $files;
        }

        return array_map(function ($file) {
            return DS .
                ltrim($this->config->read('build.outDirectory', ConfigDefaults::BUILD_OUT_DIRECTORY), DS)
                . DS . $file;
        }, $files);
    }

    /**
     * Check whether this record is a javascript entry point
     * and should be appended to the script block.
     *
     * @return bool
     */
    public function isEntryScript(): bool
    {
        return $this->isEntry() && $this->isJavascript();
    }

    /**
     * Check whether this record is an entry script and a module file,
     *   i.e. not a polyfill or legacy build
     *
     * @return bool
     */
    public function isModuleEntryScript(): bool
    {
        return $this->isEntry() && $this->isJavascript() && !$this->isLegacy() && !$this->isPolyfill();
    }
}

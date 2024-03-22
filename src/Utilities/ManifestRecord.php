<?php
declare(strict_types=1);

namespace ViteHelper\Utilities;

use stdClass;

class ManifestRecord
{
    /**
     * @var array Record metadata
     */
    private array $metadata = [];

    /**
     * Default constructor
     *
     * @param string $key The unique key for this record
     * @param \stdClass $chunk The chunks
     * @param string|bool $outDirectory
     * @see https://vitejs.dev/guide/backend-integration.html
     */
    public function __construct(
        private readonly string $key,
        private readonly stdClass $chunk,
        private readonly string|bool $outDirectory
    ) {
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
     * Checks if a needle matches a property value.
     *
     * @param string $needle needle that must be contained in the respective property value
     * @param string $property the property of the chunk, defaults to `src`
     * @return bool
     */
    public function match(string $needle, string $property = 'src'): bool
    {
        $field = $this->getChunk($property);

        return is_string($field) && str_contains($field, $needle);
    }

    /**
     * Checks if at least one needle matches a property value.
     *
     * @param array $needles needles whereof at least one must be contained in the respective property value
     * @param string $property the property of the chunk, defaults to `src`
     * @return bool
     */
    public function matchMany(array $needles, string $property = 'src'): bool
    {
        foreach ($needles as $name) {
            if ($this->match($name, $property)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if all needles matche a property value.
     *
     * @param array $names needles that must be contained in the respective property value
     * @param string $property the property of the chunk, defaults to `src`
     * @return bool
     */
    public function matchAll(array $names, string $property = 'src'): bool
    {
        foreach ($names as $name) {
            if (!$this->match($name, $property)) {
                return false;
            }
        }

        return true;
    }

    /**
     * The current Record is legacy
     *
     * @return bool
     */
    public function isLegacy(): bool
    {
        return str_contains($this->chunk->file, 'legacy');
    }

    /**
     * The current Record is polyfill
     *
     * @return bool
     */
    public function isPolyfill(): bool
    {
        return str_contains($this->chunk->file, 'polyfills');
    }

    /**
     * The current Record is a javascript file
     *
     * @return bool
     */
    public function isJavascript(): bool
    {
        return str_ends_with($this->chunk->file, '.js');
    }

    /**
     * The current Record is a stylesheet file
     *
     * @return bool
     */
    public function isStylesheet(): bool
    {
        return str_ends_with((string)$this->chunk->file, '.css');
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
        return $this->getLinkFromOutDirectory($this->chunk->file);
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
            return $this->getLinkFromOutDirectory($file);
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

    /**
     * Adds a key value to metadata (if exists overwrites)
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public function addMetadata(string $key, string $value): void
    {
        $this->metadata[$key] = $value;
    }

    /**
     * Replaces the metadata array
     *
     * @param array $metadata
     * @return void
     */
    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    /**
     * Returns a metadata value
     *
     * @param string|null $key
     * @return mixed
     */
    public function getMetadata(?string $key = null): mixed
    {
        if (is_null($key)) {
            return $this->metadata;
        }

        return array_key_exists($key, $this->metadata) ? $this->metadata[$key] : null;
    }

    /**
     * Enables users to set build.outDirectory in app_vite.php to false,
     * so that the outDir equals the webroot.
     *
     * @param string $assetLink link to asset from manifest inside outDir
     * @return string
     */
    private function getLinkFromOutDirectory(string $assetLink): string
    {
        $outDirectory = $this->outDirectory;
        if (empty($outDirectory) && $outDirectory !== false) {
            // TODO Needs to be verified
            $outDirectory = false;
        }

        $outDirectory = ltrim((string)$outDirectory, DS);
        $outDirectory = $outDirectory ? '/' . $outDirectory : '';

        return $outDirectory . '/' . $assetLink;
    }
}

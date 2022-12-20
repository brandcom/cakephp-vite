<?php
declare(strict_types=1);

namespace ViteHelper\Utilities;

use Cake\Core\Configure;
use Nette\Utils\Strings;

class ManifestRecord
{
    private string $key;

    private object $chunk;

    /**
     * Default constructor
     *
     * @param string $key The unique key for this record
     * @param object $chunk The chunks
     * @see https://vitejs.dev/guide/backend-integration.html
     */
    public function __construct(string $key, object $chunk)
    {
        $this->key = $key;
        $this->chunk = $chunk;
    }

    /**
     * The current Record is an entry
     *
     * @return bool
     */
    public function isEntry(): bool
    {
        return property_exists($this->chunk, 'isEntry') && $this->chunk->isEntry;
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
        return property_exists($this->chunk, 'src') && Strings::endsWith($this->chunk->src, 'js');
    }

    /**
     * The current Record is a stylesheet file
     *
     * @return bool
     */
    public function isStylesheet(): bool
    {
        return property_exists($this->chunk, 'src') && Strings::endsWith($this->chunk->src, 'ss');// less, scss, css, sass
    }

    /**
     * Checks if a string matches with the filename
     *
     * @param string $name the name to check
     * @return bool
     */
    public function match(string $name): bool
    {
        return property_exists($this->chunk, 'src') && Strings::contains($this->chunk->src, DS . $name);
    }

    /**
     * Returns the files relative path
     *
     * @param string|null $pluginPrefix Plugin prefix
     * @return string
     */
    public function url(?string $pluginPrefix = null): string
    {
        return sprintf(
            '%s/%s/%s',
            $pluginPrefix ?? '',
            Configure::read('ViteHelper.build.outDir', ConfigDefaults::BUILD_OUT_DIRECTORY),
            $this->chunk->file
        );
    }

	/**
	 * Returns all dependent CSS file
	 *
	 * @param string|null $pluginPrefix Plugin prefix
	 * @return array
	 */
	public function getCss(?string $pluginPrefix = null): array
	{
		if (!(property_exists($this->chunk, 'css') && is_array($this->chunk->css))) {
			return [];
		}

		$css = [];
		foreach ($this->chunk->css as $css_file) {
			$css[] = sprintf(
				'%s/%s/%s',
				$pluginPrefix ?? '',
				Configure::read('ViteHelper.build.outDir', ConfigDefaults::BUILD_OUT_DIRECTORY),
				$css_file
			);
		}

		return $css;
	}
}

<?php
declare(strict_types=1);

namespace ViteHelper\Utilities;

use Cake\Core\Configure;
use Cake\Utility\Hash;

class ViteHelperConfig
{
    public const DEFAULT_CONFIG_KEY = 'ViteHelper';

    public readonly array $config;

    /**
     * @param array|string|null $config config array or config key - leave empty for default
     */
    public function __construct(array|string|null $config = null)
    {
        if ($config === null) {
            $config = self::DEFAULT_CONFIG_KEY;
        }

        $this->config = is_array($config) ? $config : Configure::readOrFail($config);
    }

    /**
     * @param array|string|null $config config array or key
     * @return self
     */
    public static function create(array|string|null $config = null): self
    {
        return new self($config);
    }

    /**
     * @param string $path path to config
     * @param mixed $default default value
     * @return mixed
     */
    public function read(string $path, mixed $default = null): mixed
    {
        return Hash::get($this->config, $path, $default);
    }

    /**
     * Merge two configs
     *
     * @param \ViteHelper\Utilities\ViteHelperConfig $config
     * @return self
     */
    public function merge(ViteHelperConfig $config): self
    {
        return self::create(array_merge(
            $this->config,
            $config->config,
        ));
    }
}

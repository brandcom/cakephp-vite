<?php
declare(strict_types=1);

namespace ViteHelper\Utilities;

use Cake\Core\Configure;
use Cake\Utility\Hash;

class ViteHelperConfig
{
    private array $config;

    /**
     * @param array|null $config config array - leave empty to read from app_vite etc.
     */
    public function __construct(?array $config = null)
    {
        $config = $config ?: Configure::read('ViteHelper');
        $this->config = (array)$config;
    }

    /**
     * @param array|null $config config array
     * @return self
     */
    public static function create(?array $config = null): self
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
}

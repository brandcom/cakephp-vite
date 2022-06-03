<?php
declare(strict_types=1);

namespace ViteHelper\View\Helper;

use Cake\View\Helper;

/**
 * @deprecated use ViteScriptsHelper
 */
class ViteHelper extends Helper
{
    /**
     * Throws an exception with a deprecation warning
     *
     * @param array $config useless config
     * @return void
     * @throws \Exception
     */
    public function initialize(array $config): void
    {
        throw new \Exception('ViteHelper is deprecated since version 0.2. Use ViteScriptsHelper instead.');
    }
}

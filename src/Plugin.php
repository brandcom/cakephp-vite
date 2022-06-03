<?php
declare(strict_types=1);

namespace ViteHelper;

use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\PluginApplicationInterface;

/**
 * Plugin for ViteHelper
 */
class Plugin extends BasePlugin
{
    /**
     * @inheritDoc
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        parent::bootstrap($app);
        Configure::load('ViteHelper.app_vite');

        if (file_exists(ROOT . DS . 'config' . DS . 'app_vite.php')) {
            Configure::load('app_vite');
        }
    }
}

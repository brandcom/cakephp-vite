<?php
declare(strict_types=1);

namespace ViteHelper;

use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\PluginApplicationInterface;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\RouteBuilder;
use Cake\Console\CommandCollection;

/**
 * Plugin for ViteHelper
 */
class Plugin extends BasePlugin
{
    /**
     * Add commands for the plugin.
     *
     * @param \Cake\Console\CommandCollection $commands The command collection to update.
     * @return \Cake\Console\CommandCollection
     */
    public function console(CommandCollection $commands) : CommandCollection
    {
        // Add your commands here

        $commands = parent::console($commands);

        return $commands;
    }

    public function bootstrap(PluginApplicationInterface $app): void
    {
        parent::bootstrap($app);
        $this->loadConfig();
    }

    private function loadConfig(): void
    {
        Configure::load('VitePlugin.app_vite');

        $configs = [
            'app_vite',
            'app',
            'app_local',
        ];

        foreach ($configs as $config) {
            try {
                Configure::load($config);
            } catch (\Exception $e) {
                continue;
            }
        }
    }
}

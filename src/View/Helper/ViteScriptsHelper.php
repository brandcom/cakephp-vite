<?php
declare(strict_types=1);

namespace ViteHelper\View\Helper;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Utility\Text;
use Cake\View\Helper;
use JsonException;
use ViteHelper\Enum\Environment;
use ViteHelper\Enum\RenderMode;
use ViteHelper\Exception\ConfigurationException;
use ViteHelper\Exception\ManifestNotFoundException;
use ViteHelper\Model\Entity\ScriptRecord;
use ViteHelper\Model\Entity\StyleRecord;
use ViteHelper\Utilities\ManifestRecord;
use ViteHelper\Utilities\ViteManifest;

/**
 * ViteScripts helper
 *
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class ViteScriptsHelper extends Helper
{
    public const VITESCRIPT_DETECTOR_NAME = 'vite_in_production';

    public array $helpers = ['Html'];

    protected array $entries;

    protected array $_defaultConfig = [
        'plugin' => false,
        'render_mode' => RenderMode::AUTO,
        'environment' => Environment::PRODUCTION,
        'build' => [
            'outDirectory' => 'build',
            'manifest' => WWW_ROOT . 'build' . DS . '.vite' . DS . 'manifest.json',
        ],
        'viewBlocks' => [
            'css' => 'css',
            'script' => 'script',
        ],
        'development' => [
            'url' => 'http://localhost:3000',
        ],
    ];

    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setConfig(Configure::read('ViteHelper'));
        $this->setConfig($config);
        $env = $this->getConfig('environment', 'prod');
        if (is_string($env)) {
            $env = Environment::from($env);
        }

        if (!($env instanceof Environment)) {
            throw new ConfigurationException('Invalid environment config!');
        }

        if ($env === Environment::FROM_DETECTOR) {
            $this->setConfig(
                'environment',
                $this->getView()->getRequest()->is(self::VITESCRIPT_DETECTOR_NAME) ?
                    Environment::PRODUCTION : Environment::DEVELOPMENT
            );
        }

        if ($env === Environment::DEVELOPMENT) {
            $this->Html->script(
                $this->getConfig('development.url')
                . '/@vite/client',
                [
                    'type' => 'module',
                    'block' => $this->getConfig('viewBlocks.css'),
                ]
            );
        }
        $this->getView()->getEventManager()->on('Vite.render', [$this, 'render']);
    }

    /**
     * Adds styles and scripts to the blocks
     *
     * @return void
     */
    public function render(): void
    {
        if ($this->getConfig('environment', Environment::PRODUCTION) === Environment::DEVELOPMENT) {
            $this->outputDevelopmentScripts();
            $this->outputDevelopmentStyles();
        } else {
            $this->outputProductionScripts();
            $this->outputProductionStyles();
        }
    }

    /**
     * Adds scripts to the script view block
     *
     * @param array|string $files files to serve
     * @param \ViteHelper\Enum\Environment|string|null $environment the files will be served only in this environment, null on both
     * @param string|null $block name of the view block to render the scripts in
     * @param string|null $plugin
     * @param array $elementOptions options to the html tag
     * @return void
     */
    public function script(
        array|string $files = [],
        Environment|string|null $environment = null,
        ?string $block = null,
        ?string $plugin = null,
        array $elementOptions = [],
    ): void {
        if (is_string($environment)) {
            $environment = Environment::tryFrom($environment);
        }
        $elementOptions['block'] = $block ?? $this->getConfig('viewBlocks.script');
        $files = (array)$files;
        foreach ($files as $file) {
            switch ($environment) {
                case Environment::DEVELOPMENT:
                    $this->entries[] = new ScriptRecord(
                        $file,
                        Environment::DEVELOPMENT,
                        $block,
                        $plugin,
                        $elementOptions,
                    );
                    break;
                case Environment::PRODUCTION:
                    $this->entries[] = new ScriptRecord(
                        $file,
                        Environment::PRODUCTION,
                        $block,
                        $plugin,
                        $elementOptions,
                    );
                    break;
                default:
                    $this->entries[] = new ScriptRecord(
                        $file,
                        Environment::DEVELOPMENT,
                        $block,
                        $plugin,
                        $elementOptions,
                    );
                    $this->entries[] = new ScriptRecord(
                        $file,
                        Environment::PRODUCTION,
                        $block,
                        $plugin,
                        $elementOptions,
                    );
                    break;
            }
        }
        if ($this->getConfig('render_mode') === RenderMode::AUTO) {
            $this->render();
        }
    }

    /**
     * Adds style to the css view block
     *
     * @param array|string $files files to serve
     * @param \ViteHelper\Enum\Environment|string|null $environment the files will be served only in this environment, null on both
     * @param string|null $block name of the view block to render the scripts in
     * @param string|null $plugin
     * @param array $elementOptions options to the html tag
     * @return void
     */
    public function css(
        array|string $files = [],
        Environment|string|null $environment = null,
        ?string $block = null,
        ?string $plugin = null,
        array $elementOptions = [],
    ): void {
        if (is_string($environment)) {
            $environment = Environment::tryFrom($environment);
        }
        $elementOptions['block'] = $block ?? $this->getConfig('viewBlocks.css');
        $files = (array)$files;
        foreach ($files as $file) {
            switch ($environment) {
                case Environment::DEVELOPMENT:
                    $this->entries[] = new StyleRecord(
                        $file,
                        Environment::DEVELOPMENT,
                        $block,
                        $plugin,
                        $elementOptions,
                    );
                    break;
                case Environment::PRODUCTION:
                    $this->entries[] = new StyleRecord(
                        $file,
                        Environment::PRODUCTION,
                        $block,
                        $plugin,
                        $elementOptions,
                    );
                    break;
                default:
                    $this->entries[] = new StyleRecord(
                        $file,
                        Environment::DEVELOPMENT,
                        $block,
                        $plugin,
                        $elementOptions,
                    );
                    $this->entries[] = new StyleRecord(
                        $file,
                        Environment::PRODUCTION,
                        $block,
                        $plugin,
                        $elementOptions,
                    );
                    break;
            }
        }
        if ($this->getConfig('render_mode') === RenderMode::AUTO) {
            $this->render();
        }
    }

    /**
     * Appends development script tags to configured block
     *
     * @return void
     */
    private function outputDevelopmentScripts(): void
    {
        $files = array_filter($this->entries, function ($record) {
            return $record instanceof ScriptRecord &&
                !$record->is_rendered &&
                $record->environment === Environment::DEVELOPMENT;
        });

        /** @var \ViteHelper\Model\Entity\ScriptRecord $record */
        foreach ($files as $record) {
            $record->is_rendered = true;
            $record->elementOptions['type'] = 'module';
            $this->Html->script(Text::insert(':host/:file', [
                'host' => $this->getConfig('development.url'),
                'file' => ltrim($record->file, DS),
            ]), $record->elementOptions);
        }
    }

    /**
     * Appends development style tags to configured block
     *
     * @return void
     */
    private function outputDevelopmentStyles(): void
    {
        $files = array_filter($this->entries, function ($record) {
            return $record instanceof StyleRecord &&
                !$record->is_rendered &&
                $record->environment === Environment::DEVELOPMENT;
        });

        /** @var \ViteHelper\Model\Entity\StyleRecord $record */
        foreach ($files as $record) {
            $record->is_rendered = true;
            $this->Html->css(Text::insert(':host/:file', [
                'host' => $this->getConfig('development.url'),
                'file' => ltrim($record->file, '/'),
            ]), $record->elementOptions);
        }
    }

    /**
     * Appends production script tags to configured block
     *
     * @return void
     */
    private function outputProductionScripts(): void
    {
        $files = array_filter($this->entries, function ($record) {
            return $record instanceof ScriptRecord &&
                !$record->is_rendered &&
                $record->environment === Environment::PRODUCTION;
        });

        $records = $this->getManifestRecords($files);

        $pluginPrefix = $this->getConfig('plugin');
        $pluginPrefix = $pluginPrefix ? $pluginPrefix . '.' : null;
        /** @var \ViteHelper\Utilities\ManifestRecord $record */
        foreach ($records as $record) {
            if (!$record->isEntryScript()) {
                continue;
            }

            $options = $record->getMetadata();
            if ($record->isModuleEntryScript()) {
                $options['options']['type'] = 'module';
            } else {
                $options['options']['nomodule'] = 'nomodule';
            }

            $recordPluginPrefix = $pluginPrefix;
            if (isset($options['plugin'])) {
                $recordPluginPrefix = $options['plugin'] . '.';
                unset($options['plugin']);
            }
            $this->Html->script($recordPluginPrefix . $record->getFileUrl(), $options['options']);

            // the js files has css dependency ?
            $cssFiles = $record->getCss();
            if (!count($cssFiles)) {
                continue;
            }

            foreach ($cssFiles as $cssFile) {
                $this->Html->css($recordPluginPrefix . $cssFile, [
                    'block' => $this->getConfig('viewBlocks.css'),
                ]);
            }
            unset($recordPluginPrefix);
        }

        array_map(fn ($file) => $file->is_rendered = true, $files);
    }

    /**
     * Appends production style tags to configured block
     *
     * @return void
     */
    private function outputProductionStyles(): void
    {
        $pluginPrefix = $this->getConfig('plugin');
        $pluginPrefix = $pluginPrefix ? $pluginPrefix . '.' : null;
        $files = array_filter($this->entries, function ($record) {
            return $record instanceof StyleRecord &&
                !$record->is_rendered &&
                $record->environment === Environment::PRODUCTION;
        });

        $records = $this->getManifestRecords($files);

        foreach ($records as $record) {
            if (!$record->isEntry() || !$record->isStylesheet() || $record->isLegacy()) {
                continue;
            }
            $options = $record->getMetadata();
            $recordPluginPrefix = $pluginPrefix;
            if (isset($options['plugin'])) {
                $recordPluginPrefix = $options['plugin'] . '.';
                unset($options['plugin']);
            }

            $this->Html->css($pluginPrefix . $record->getFileUrl(), $options['options']);
            unset($recordPluginPrefix);
        }

        array_map(fn ($file) => $file->is_rendered = true, $files);
    }

    /**
     * Returns manifest records with the correct metadata
     *
     * @param array $files
     * @return iterable
     */
    private function getManifestRecords(iterable $files): iterable
    {
        if ($this->getConfig('plugin') && $this->getConfig('build.manifest') === null) {
            $manifestPath = Plugin::path($this->getConfig('plugin')) . 'webroot' . DS . 'manifest.json';
        } else {
            $manifestPath = $this->getConfig('build.manifest');
        }

        try {
            // TODO: Cache ?!
            $records = ViteManifest::getRecords($manifestPath, $this->getConfig('build.outDirectory'));
            $records = $records->filter(function (ManifestRecord $record) use ($files) {
                /** @var \ViteHelper\Model\Entity\StyleRecord|\ViteHelper\Model\Entity\ScriptRecord $file */
                foreach ($files as $file) {
                    if ($record->match($file->file, 'src')) {
                        $record->setMetadata([
                            'options' => $file->elementOptions,
                            'plugin' => $file->plugin,
                        ]);

                        return $record;
                    }
                }

                return false;
            });
        } catch (ManifestNotFoundException | JsonException $e) {
            $records = [];
        }

        return $records;
    }
}

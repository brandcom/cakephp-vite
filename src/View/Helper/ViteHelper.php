<?php

namespace ViteHelper\View\Helper;

use Cake\Log\Log;
use Cake\View\Helper;
use Nette\Utils\FileSystem;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use stdClass;

/**
 * Usage:
 *
 * Call the Tags in the template:
 *
 * These will figure out if your application is in Dev mode, depending on the isDev() method.
 * $this->Vite->getHeaderTags()
 * $this->Vite->getBodyTags()
 *
 * If $forceProductionMode is set to true, or a URL-param ?vprod is set,
 * or a vprod Cookie is not false-ish (e.g. 0 or null),
 * production versions will be served.
 *
 * Or decide on your own:
 *
 * Dev-tags (available while vite dev-server is running):
 * $this->Vite->getClientScripts() - Header
 * $this->Vite->getDevScripts() - after Body
 *
 * Production Tags (available after running vite build):
 * $this->Vite->getCSS()
 * $->Vite->getJS()
 *
 * @property Helper\HtmlHelper $Html
 *
 * @deprecated
 */
class ViteHelper extends Helper
{
    public $helpers = [
        "Html",
    ];

    /**
     * Disable dev scripts and serve the production files.
     * Defaults to true, if the application is in debug mode.
     *
     * You must run the vite build command in order to be able to access production files.
     */
    private bool $forceProductionMode;

    /**
     * Defaults to "vprod"
     *
     * Visit the page with ?vprod=1 or save a cookie named "vprod" with a
     * true-ish value (i.e. not 0 or empty) to enable production mode.
     */
    private string $productionHint;

    /**
     * Used in isDev() as a needle to test $_SERVER['HTTP_HOST'] if the site is running locally / in dev mode.
     * Set it to e.g. ".test", ".dev", "localhost" etc. to distinguish it from a live server environment.
     * Default: ".test"
     */
    private string $devHostNeedle;

    /**
     * Port where the ViteJS dev server will serve, defaults to 3000.
     */
    private int $devPort;

    /**
     * Source directory for .js/.ts/.vue/.scss etc.
     * Defaults to "webroot_src"
     */
    private string $jsSrcDirectory;

    /**
     * Main js / ts file. Default: "main.js"
     */
    private string $mainJS;

    /**
     * Relative path (from /public or /webroot etc.) to the manifest.json
     * which is created by ViteJS after running the build command.
     * Default: "manifest.json"
     */
    private string $manifestDir;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->forceProductionMode = $config['forceProductionMode'] ?? false;
        $this->devHostNeedle = $config['devHostNeedle'] ?? '.test';
        $this->productionHint = $config['productionHint'] ?? 'vprod';
        $this->devPort = $config['devPort'] ?? 3000;
        $this->jsSrcDirectory = $config['jsSrcDirectory'] ?? 'webroot_src' . DS;
        $this->mainJS = $config['mainJs'] ?? 'main.js';
        $this->manifestDir = $config['manifestDir'] ?? 'manifest.json';
    }


    /**
     * Serve script tags for insertion in the HTML head,
     * either for dev od production, depending on the isDev() method.
     */
    public function getHeaderTags(): string
    {
        return $this->isDev() ? $this->getClientScript() : $this->getCSS();
    }

    /**
     * Serve script tags for insertion at the end of HTML body,
     * either for dev od production, depending on the isDev() method.
     */
    public function getBodyTags(): string
    {
        return $this->isDev() ? $this->getDevScript() : $this->getJS();
    }

    /**
     * For production. Available after build.
     * Return the css files created by ViteJS
     */
    public function getCSS(): string
    {
        $manifest = $this->getManifest() ?? [];
        $css_paths = [];

        foreach ($manifest ?? [] as $item) {

            if (empty($item->isEntry) || empty($item->css)) {
                continue;
            }

            foreach ($item->css as $css_path) {
                $css_paths[] = DS . ltrim($css_path, DS);
            }
        }

        return $this->Html->css($css_paths);
    }

    /**
     * For production. Available after build.
     * Return the most recent js file created by ViteJS
     *
     * Will return additional <script type="nomodule"> tags
     * if @vite/plugin-legacy is installed.
     */
    public function getJS(): string
    {
        $manifest = $this->getManifest() ?? [];
        $script_tags = [];

        foreach ($manifest as $file) {
            /**
             * @var stdClass $file
             */
            if (!empty($file->isEntry)) {
                $type = Strings::contains($file->src, "legacy") ? "nomodule" : "module";
                $script_tags[] = $this->Html->script(
                    DS . ltrim($file->file, DS), [
                        'type' => $type
                    ]
                );
            }
        }

        /**
         * Legacy Polyfills must come first.
         */
        usort($script_tags, function ($tag) {
            return Strings::contains($tag, "polyfills") ? 0 : 1;
        });

        /**
         * ES-module scripts must come last.
         */
        usort($script_tags, function ($tag) {
            return Strings::contains($tag, 'type="module"') ? 1 : 0;
        });

        return implode("\n", $script_tags);
    }

    /**
     * For dev mode at the end of HTML body.
     */
    public function getDevScript(): string
    {
        return $this->Html->script('http://localhost:' . $this->devPort . '/@vite/client', ['type' => 'module']);
    }

    /**
     * For dev mode in HTML head.
     */
    public function getClientScript(): string
    {
        return $this->Html->script(
            'http://localhost:' . $this->devPort . '/' . $this->jsSrcDirectory . $this->mainJS, [
                'type' => 'module',
            ]
        );
    }

    /**
     * Get data on the files created by ViteJS
     * from /public/manifest.json
     */
    private function getManifest(): ?stdClass
    {
        $path = WWW_ROOT . ltrim($this->manifestDir, DS);

        try {
            $json = FileSystem::read($path);

            $json = str_replace([
                "\u0000",
            ], '', $json);

            $manifest = Json::decode($json);

        } catch (\Exception $e) {
            Log::write('debug', "No valid manifest.json found for ViteHelper at path $path. Error: {$e->getMessage()}");
            return null;
        }

        return $manifest;
    }

    /**
     * Decide what files to serve.
     *
     * If
     * * $this->forceProductionMode is set to true
     * * or a ?vprod URL-param is set,
     * * or a vprod Cookie not false-ish,
     * it will return false.
     */
    private function isDev(): bool
    {
        if ($this->forceProductionMode === true) {

            return false;
        }

        if ($this->getView()->getRequest()->getCookie($this->productionHint)) {
            return false;
        }

        if ($this->getView()->getRequest()->getQuery($this->productionHint)) {
            return false;
        }

        return Strings::contains((string)$this->getView()->getRequest()->host(), $this->devHostNeedle);
    }
}

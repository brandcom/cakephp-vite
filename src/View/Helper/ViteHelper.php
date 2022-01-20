<?php

namespace ViteHelper\View\Helper;

use Cake\Core\Configure;
use Cake\View\Helper;

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
 */
class ViteHelper extends Helper
{
    /**
     * Disable dev scripts and serve the production files.
     * Defaults to true, if the application is in debug mode.
     *
     * You must run the vite build command in order to be able to access production files.
     */
    private bool $forceProductionMode;

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
        $manifest = $this->getManifest();
        if (!$manifest) {
            return '';
        }

        $style_tags = [];
        foreach ($manifest as $item) {

            if (!empty($item->isEntry) && true === $item->isEntry && !empty($item->css)) {

                foreach ($item->css as $css_path) {
                    $style_tags[] = $this->css(DS . $css_path);
                }
            }
        }

        return implode("\n", $style_tags);
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
        $manifest = $this->getManifest();
        if (!$manifest) {
            return '';
        }

        $script_tags = [];
        foreach ($manifest as $item) {

            /**
             * @var \stdClass $item
             */
            if (!empty($item->isEntry) && true === $item->isEntry) {
                $type = str_contains($item->src, 'legacy') ? 'nomodule' : 'module';
                $script_tags[] = $this->script(DS . $item->file, $type);
            }
        }

        /**
         * Legacy Polyfills must come first.
         */
        usort($script_tags, function ($tag) {
            return str_contains($tag, 'polyfills') ? 0 : 1;
        });

        /**
         * ES-module scripts must come last.
         */
        usort($script_tags, function ($tag) {
            return str_contains($tag, 'type="module"') ? 1 : 0;
        });

        return implode("\n", $script_tags);
    }

    /**
     * For dev mode at the end of HTML body.
     */
    public function getDevScript(): string
    {
        return $this->script('http://localhost:' . $this->devPort . '/@vite/client', 'module');
    }

    /**
     * For dev mode in HTML head.
     */
    public function getClientScript(): string
    {
        return $this->script(
            'http://localhost:' . $this->devPort . '/' . $this->jsSrcDirectory . $this->mainJS,
            "module"
        );
    }

    /**
     * Get data on the files created by ViteJS
     * from /public/manifest.json
     */
    private function getManifest(): ?\stdClass
    {
        $path = WWW_ROOT . $this->manifestDir;

        if (!file_exists($path)) {
            throw new \Exception('Could not find manifest.json at ' . $path);
        }

        $manifest = file_get_contents($path);
        $manifest = utf8_encode($manifest);

        if (!$manifest) {
            throw new \Exception('No ViteDataExtension manifest.json found. ');
        }

        $manifest = str_replace([
            "\u0000",
        ], '', $manifest);

        return json_decode($manifest);
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

        if (!empty($_COOKIE['vprod']) && $_COOKIE['vprod']) {

            return false;
        }

        if (isset($_GET['vprod'])) {

            return false;
        }

        return !empty($_SERVER['HTTP_HOST']) && str_contains($_SERVER['HTTP_HOST'], $this->devHostNeedle);
    }

    private function css(string $url): string
    {
        return '<link rel="stylesheet" href="' . $url . '">';
    }

    private function script(string $url, ?string $type = null): string
    {
        $type = $type ? ' type="' . $type . '"' : null;

        return '<script src="' . $url . '"' . $type . '></script>';
    }
}

<?php
declare(strict_types=1);

namespace ViteHelper\Utilities;

/**
 * Defaults of this plugin's configuration
 *
 * Define your own config through config files as in config/app_vite.php, e.g. in your app_local.php
 */
class ConfigDefaults
{
    /**
     * If true, files built for production will always be served
     */
    public const FORCE_PRODUCTION_MODE = false;

    /**
     * The Dev-Server files will be loaded if one of these needles is present in the URL
     */
    public const DEVELOPMENT_HOST_NEEDLES = [
        '.test',
        '.local',
        'localhost',
        '127.0.0.1',
    ];

    /**
     * for Cookies or URL-params to force production mode
     */
    public const PRODUCTION_HINT = 'vprod';

    /**
     * the url of the ViteJS dev server
     */
    public const DEVELOPMENT_URL = 'http://localhost:3000';

    /**
     * Path to js/ts entry files during development relative to project root.
     */
    public const DEVELOPMENT_SCRIPT_ENTRIES = [];

    /**
     * Path to style entry files during development relative to project root.
     */
    public const DEVELOPMENT_STYLE_ENTRIES = [];

    /**
     * Output directory (build.outDir) - string (e.g. 'dist') or false
     *
     * @see https://vitejs.dev/config/build-options.html#build-outdir
     */
    public const BUILD_OUT_DIRECTORY = false;

    /**
     * The absolute path to the manifest file
     *
     * @see https://vitejs.dev/config/build-options.html#build-manifest
     */
    public const BUILD_MANIFEST = WWW_ROOT . 'manifest.json';

    /**
     * Name of the view block where the link tags for css should be rendered.
     *
     * @see https://book.cakephp.org/4/en/views.html#using-view-blocks
     */
    public const VIEW_BLOCK_CSS = 'css';

    /**
     * Name of the view block where the script tags should be rendered.
     *
     * @see https://book.cakephp.org/4/en/views.html#using-view-blocks
     */
    public const VIEW_BLOCK_SCRIPT = 'script';
}

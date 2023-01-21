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
	 * path to entry files during development
	 */
    public const DEVELOPMENT_ENTRY_FILES = [
		'webroot_src/js/main.js',
	];

    /**
     * If the project is not in the webroot, define a base directory as an absolute path.
     * This is useful for plugins.
     */
    public const BASE_DIRECTORY = null;

    /**
     * Output directory
     *
     * @see https://vitejs.dev/config/build-options.html#build-outdir
     */
    public const BUILD_OUT_DIRECTORY = 'build';

    /**
     * The full absolute path to the manifest file
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

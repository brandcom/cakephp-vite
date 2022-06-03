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
    public const DEV_HOST_NEEDLES = [
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
     * port of the ViteJS dev server
     */
    public const DEV_PORT = 3000;

    /**
     * Directory where the javascript files are located
     */
    public const JS_SRC_DIRECTORY = 'webroot_src' . DS;

    /**
     * Name of the entry js file
     */
    public const MAIN_JS = 'main.js';

    /**
     * If the project is not in the webroot, define a base directory as an absolute path.
     * This is useful for plugins.
     */
    public const BASE_DIR = null;

    /**
     * Directory/name of the manifest.json file
     */
    public const MANIFEST_DIR = 'manifest.json';
}

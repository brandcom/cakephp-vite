<?php

use \ViteHelper\Utilities\ConfigDefaults;

return [
	'ViteHelper' => [
		'build' => [
			'outDirectory' => ConfigDefaults::BUILD_OUT_DIRECTORY,
			'manifest' => ConfigDefaults::BUILD_MANIFEST,
		],
		'development' => [
			'scriptEntries' => ConfigDefaults::DEVELOPMENT_SCRIPT_ENTRIES,
			'styleEntries' => ConfigDefaults::DEVELOPMENT_STYLE_ENTRIES,
			'hostNeedles' => ConfigDefaults::DEVELOPMENT_HOST_NEEDLES,
			'checkManifest' => ConfigDefaults::DEVELOPMENT_CHECK_MANIFEST,
			'url' => ConfigDefaults::DEVELOPMENT_URL,
		],
		'forceProductionMode' => ConfigDefaults::FORCE_PRODUCTION_MODE,
		'plugin' => false,
		'productionHint' => ConfigDefaults::PRODUCTION_HINT,
		'viewBlocks' => [
			'css' => ConfigDefaults::VIEW_BLOCK_CSS,
			'script' => ConfigDefaults::VIEW_BLOCK_SCRIPT,
		],
	],
];

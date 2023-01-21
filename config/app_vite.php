<?php

use \ViteHelper\Utilities\ConfigDefaults;

return [
	'ViteHelper' => [
		'baseDirectory' => ConfigDefaults::BASE_DIRECTORY,
		'build' => [
			'assetsDirectory' => ConfigDefaults::BUILD_ASSETS_DIRECTORY,
			'outDirectory' => ConfigDefaults::BUILD_OUT_DIRECTORY,
			'manifest' => ConfigDefaults::BUILD_MANIFEST,
		],
		'developmentEntryFiles' => ConfigDefaults::DEVELOPMENT_ENTRY_FILES,
		'developmentHostNeedles' => ConfigDefaults::DEVELOPMENT_HOST_NEEDLES,
		'developmentUrl' => ConfigDefaults::DEVELOPMENT_URL,
		'forceProductionMode' => ConfigDefaults::FORCE_PRODUCTION_MODE,
		'productionHint' => ConfigDefaults::PRODUCTION_HINT,
		'viewBlocks' => [
			'css' => ConfigDefaults::VIEW_BLOCK_CSS,
			'script' => ConfigDefaults::VIEW_BLOCK_SCRIPT,
		],
	],
];

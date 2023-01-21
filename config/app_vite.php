<?php

use \ViteHelper\Utilities\ConfigDefaults;

return [
	'ViteHelper' => [
		'baseDirectory' => ConfigDefaults::BASE_DIRECTORY,
		'build' => [
			'outDirectory' => ConfigDefaults::BUILD_OUT_DIRECTORY,
			'manifest' => ConfigDefaults::BUILD_MANIFEST,
		],
		'development' => [
			'scriptEntries' => ConfigDefaults::DEVELOPMENT_SCRIPT_ENTRIES,
			'styleEntries' => ConfigDefaults::DEVELOPMENT_STYLE_ENTRIES,
			'HostNeedles' => ConfigDefaults::DEVELOPMENT_HOST_NEEDLES,
			'Url' => ConfigDefaults::DEVELOPMENT_URL,
		],
		'forceProductionMode' => ConfigDefaults::FORCE_PRODUCTION_MODE,
		'productionHint' => ConfigDefaults::PRODUCTION_HINT,
		'viewBlocks' => [
			'css' => ConfigDefaults::VIEW_BLOCK_CSS,
			'script' => ConfigDefaults::VIEW_BLOCK_SCRIPT,
		],
	],
];

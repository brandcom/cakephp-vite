<?php

use \ViteHelper\Utilities\ConfigDefaults;

return [
	'ViteHelper' => [
		'baseDirectory' => ConfigDefaults::BASE_DIR,
		'build' => [
			'outDir' => ConfigDefaults::BUILD_OUT_DIRECTORY,
			'manifest' => ConfigDefaults::BUILD_MANIFEST,
		],
		'developmentUrl' => ConfigDefaults::DEVELOPMENT_URL,
		'developmentHostNeedles' => ConfigDefaults::DEV_HOST_NEEDLES,
		'forceProductionMode' => ConfigDefaults::FORCE_PRODUCTION_MODE,
		'productionHint' => ConfigDefaults::PRODUCTION_HINT,
	],
];

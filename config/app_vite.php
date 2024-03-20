<?php

return [
	'ViteHelper' => [
		'plugin' => false,
		'environment' => \ViteHelper\Enum\Environment::FROM_DETECTOR,
		'development' => [
			'url' => 'http://localhost:3000',
		],
		'build' => [
			'outDirectory' => false,
			'manifest' => WWW_ROOT . 'manifest.json',
		],
	],
];

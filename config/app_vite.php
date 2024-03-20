<?php

return [
	'ViteHelper' => [
		'plugin' => false,
		'environment' => \ViteHelper\Enum\Environment::PRODUCTION,
		'build' => [
			'outDirectory' => 'build',
			'manifest' => WWW_ROOT . 'build' . DS . '.vite' . DS . 'manifest.json',
		],
		'development' => [
			'url' => 'http://localhost:3000',
		],
	],
];

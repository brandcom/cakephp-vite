<?php

return [
	'ViteHelper' => [
		'plugin' => false,
		'render_mode' => \ViteHelper\Enum\RenderMode::AUTO,
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

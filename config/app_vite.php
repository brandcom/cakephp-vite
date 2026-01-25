<?php

return [
	'ViteHelper' => [
		// the running environment
		'environment' => \ViteHelper\Enum\Environment::PRODUCTION,
		// development settings
		'development' => [
			'url' => 'http://localhost:3000',
		],
		// list of builds
		'builds' => [
			[
				// the plugin name or null if it doesn't exist
				'plugin' => null,
				// the output directory relative to `webroot`
				'outputDirectory' => 'build',
				// the relative path to the manifest file
				'manifest' => 'build' . DS . '.vite' . DS . 'manifest.json',
			],
		],
	],
];

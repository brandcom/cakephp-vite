<?php
declare(strict_types=1);

namespace App\Test\TestCase\Utilities;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use ViteHelper\Utilities\ConfigDefaults;
use ViteHelper\Utilities\ViteManifest;

class ViteManifestTest extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		Configure::write('ViteHelper', [
			'baseDirectory' => null,
			'build' => [
				'outDir' => 'etc',
				'manifest' => 'example.manifest.json'
			],
			'developmentUrl' => ConfigDefaults::DEVELOPMENT_URL,
			'developmentHostNeedles' => ConfigDefaults::DEV_HOST_NEEDLES,
			'forceProductionMode' => ConfigDefaults::FORCE_PRODUCTION_MODE,
			'productionHint' => ConfigDefaults::PRODUCTION_HINT,
		]);
	}

	public function testGetManifest(): void
	{
		$viteManifest = ViteManifest::getInstance();
		self::assertGreaterThan(0, count($viteManifest->manifestElements));
	}
}


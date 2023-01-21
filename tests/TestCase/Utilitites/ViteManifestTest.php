<?php
declare(strict_types=1);

namespace TestCase\Utilitites;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use ViteHelper\Utilities\ConfigDefaults;
use ViteHelper\Utilities\ManifestRecord;
use ViteHelper\Utilities\ViteHelperConfig;
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
				'manifest' => 'tests/resources/test-manifest.json',
			],
			'developmentUrl' => ConfigDefaults::DEVELOPMENT_URL,
			'developmentHostNeedles' => ConfigDefaults::DEVELOPMENT_HOST_NEEDLES,
			'forceProductionMode' => true,
			'productionHint' => ConfigDefaults::PRODUCTION_HINT,
		]);
	}

	public function testGetManifest(): void
	{
		$this->assertGreaterThan(0, ViteManifest::getRecords(ViteHelperConfig::create())->count());
	}

	public function testRecords(): void
	{
		$records = ViteManifest::getRecords(ViteHelperConfig::create());

		// we have one polyfill
		$this->assertEquals(1, $records->filter(fn (ManifestRecord $record) => $record->isPolyfill())->count());

		// 3 main, compiled, legacy, css
		$this->assertEquals(3, $records->filter(fn (ManifestRecord $file) => $file->match('main'))->count());
		$this->assertEquals(1, $records->filter(fn (ManifestRecord $file) => $file->match('main') && $file->isStylesheet())->count());

		// we have 1 stylesheet + 1 legacy
		$this->assertEquals(2, $records->filter(fn (ManifestRecord $file) => $file->match('style'))->count());

		$this->assertEquals(8, $records->filter(fn (ManifestRecord $file) => $file->isJavascript())->count());
	}
}


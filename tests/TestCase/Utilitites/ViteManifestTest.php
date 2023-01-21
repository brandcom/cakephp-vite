<?php
declare(strict_types=1);

namespace TestCase\Utilitites;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use ViteHelper\Utilities\ConfigDefaults;
use ViteHelper\Utilities\ManifestRecord;
use ViteHelper\Utilities\ViteManifest;

/**
 * todo fix
 */
class ViteManifestTest extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		Configure::write('ViteHelper', [
			'baseDirectory' => null,
			'build' => [
				'outDir' => 'etc',
				'manifest' => 'etc/example.manifest.json'
			],
			'developmentUrl' => ConfigDefaults::DEVELOPMENT_URL,
			'developmentHostNeedles' => ConfigDefaults::DEVELOPMENT_HOST_NEEDLES,
			'forceProductionMode' => true,
			'productionHint' => ConfigDefaults::PRODUCTION_HINT,
		]);
	}

	public function testGetManifest(): void
	{
		$this->assertGreaterThan(0, count(ViteManifest::getRecords()));
	}

	public function testRecords(): void
	{
		// we have one polyfill
		$this->assertEquals(1, count(ViteManifest::getRecords(fn (ManifestRecord $record) => $record->isPolyfill())));

		return;

		// 3 main, compiled, legacy, css
		$this->assertEquals(3, count(array_filter($records, function ($file) {
			return $file->match('main');
		})));

		$this->assertEquals(1, count(array_filter($records, function ($file) {
			return $file->match('main') && $file->isStylesheet();
		})));

		$this->assertEquals(2, count(array_filter($records, function ($file) {
			return $file->match('main') && !$file->isStylesheet();
		})));

		// we have 1 stylesheet + 1 legacy
		$this->assertEquals(2, count(array_filter($records, function ($file) {
			return $file->match('style');
		})));

		$this->assertEquals(4, count(array_filter($records, function ($file) {
			return $file->isJavascript();
		})));

		$this->assertEquals(4, count(array_filter($records, function ($file) {
			return $file->isJavascript();
		})));
	}
}


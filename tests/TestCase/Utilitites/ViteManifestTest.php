<?php
declare(strict_types=1);

namespace TestCase\Utilitites;

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
			'forceProductionMode' => true,
			'productionHint' => ConfigDefaults::PRODUCTION_HINT,
		]);
	}

	public function testGetManifest(): void
	{
		$viteManifest = ViteManifest::getInstance();
		$this->assertGreaterThan(0, count($viteManifest->manifestRecords));
	}

	public function testRecords(): void
	{
		$viteManifest = ViteManifest::getInstance();
		
		// we have one polyfill
		$this->assertEquals(1, count(array_filter($viteManifest->getRecords(), function ($file) {
			return $file->isPolyfill();
		})));

		// 3 main, compiled, legacy, css
		$this->assertEquals(3, count(array_filter($viteManifest->getRecords(), function ($file) {
			return $file->match('main');
		})));

		$this->assertEquals(1, count(array_filter($viteManifest->getRecords(), function ($file) {
			return $file->match('main') && $file->isStylesheet();
		})));

		$this->assertEquals(2, count(array_filter($viteManifest->getRecords(), function ($file) {
			return $file->match('main') && !$file->isStylesheet();
		})));

		// we have 1 stylesheet + 1 legacy
		$this->assertEquals(2, count(array_filter($viteManifest->getRecords(), function ($file) {
			return $file->match('style');
		})));

		$this->assertEquals(4, count(array_filter($viteManifest->getRecords(), function ($file) {
			return $file->isJavascript();
		})));

		$this->assertEquals(4, count(array_filter($viteManifest->getRecords(), function ($file) {
			return $file->isJavascript();
		})));
	}
}


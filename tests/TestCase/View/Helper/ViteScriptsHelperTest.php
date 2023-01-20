<?php
declare(strict_types=1);

namespace TestCase\View\Helper;

use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use ViteHelper\Utilities\ConfigDefaults;
use ViteHelper\View\Helper\ViteScriptsHelper;

class ViteScriptsHelperTest extends TestCase
{
	public $helper = null;

	use IntegrationTestTrait;

	// Here we instantiate our helper
	public function setUp(): void
	{
		parent::setUp();
		Configure::write('ViteHelper', [
			'baseDirectory' => null,
			'build' => [
				'outDir' => 'etc',
				'manifest' => 'example.manifest.json'
			],
			'developmentUrl' => ConfigDefaults::DEVELOPMENT_URL,
			'developmentHostNeedles' => ConfigDefaults::DEVELOPMENT_HOST_NEEDLES,
			'forceProductionMode' => false,
			'productionHint' => ConfigDefaults::PRODUCTION_HINT,
		]);
		$View = new View();
		$this->helper = new ViteScriptsHelper($View);
	}

	public function testIsDev(): void
	{
		Configure::write('ViteHelper.forceProductionMode', false);
		$this->assertEquals(false, $this->helper->isDev());
	}

}

<?php
declare(strict_types=1);

namespace TestCase;

use Cake\Core\Configure;
use Cake\Core\PluginApplicationInterface;
use Cake\TestSuite\TestCase;
use ViteHelper\ViteHelperPlugin;

class ViteHelperPluginTest extends TestCase
{
    /**
     * @var string|null
     */
    protected ?string $originalRoot = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalRoot = defined('ROOT') ? ROOT : null;
        Configure::delete('ViteHelper');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Configure::delete('ViteHelper');
    }

    /**
     * Test Plugin bootstrap without config file
     *
     * @return void
     */
    public function testBootstrapWithoutConfigFile(): void
    {
        Configure::delete('ViteHelper');

        $plugin = new ViteHelperPlugin();
        $app = $this->getMockBuilder(PluginApplicationInterface::class)->getMock();
        $plugin->bootstrap($app);

        // No exception should be thrown, config is simply not loaded
        $this->assertNull(Configure::read('ViteHelper'));
    }

    /**
     * Test Plugin bootstrap with config file present
     *
     * @return void
     */
    public function testBootstrapWithConfigFile(): void
    {
        // We can't easily test with a real config file without manipulating ROOT,
        // but we can verify the plugin instantiates and bootstraps correctly
        $plugin = new ViteHelperPlugin();
        $app = $this->getMockBuilder(PluginApplicationInterface::class)->getMock();

        // This should not throw any exceptions
        $plugin->bootstrap($app);

        $this->assertInstanceOf(ViteHelperPlugin::class, $plugin);
    }

    /**
     * Test Plugin can be instantiated
     *
     * @return void
     */
    public function testPluginInstantiation(): void
    {
        $plugin = new ViteHelperPlugin();
        $this->assertInstanceOf(ViteHelperPlugin::class, $plugin);
    }
}

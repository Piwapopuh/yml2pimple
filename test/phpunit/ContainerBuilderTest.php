<?php

namespace test;

use Prophecy\Argument;

use G\Yaml2Pimple\ContainerBuilder;

class ContainerBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function setup()
    {
        require_once(__DIR__ . '/fixtures/App.php');
        require_once(__DIR__ . '/fixtures/Proxy.php');
        require_once(__DIR__ . '/fixtures/Curl.php');
    }

    public function tearDown()
    {
    }

    public function testHasNoDebugOutput()
    {
        $container      = new \Pimple();
        $builder        = new ContainerBuilder($container);

        $builder->load(__DIR__ . '/fixtures/services.yml');

        $app = $container['App'];
        echo $app->hello();

        $this->expectOutputString('Hello Gonzalo');
    }

    public function testItReturnsASharedInstance()
    {
        $container      = new \Pimple();
        $builder        = new ContainerBuilder($container);

        $builder->load(__DIR__ . '/fixtures/services.yml');

        $app1 = $container['shared'];
        $app2 = $container['shared'];

        $this->assertSame($app1, $app2);
    }

    public function testItReturnsANotSharedInstance()
    {
        $container      = new \Pimple();
        $builder        = new ContainerBuilder($container);

        $builder->load(__DIR__ . '/fixtures/services.yml');

        $app1 = $container['not_shared'];
        $app2 = $container['not_shared'];

        $this->assertNotSame($app1, $app2);
    }

    public function testItMergesParameters()
    {
        $container      = new \Pimple();
        $builder        = new ContainerBuilder($container);

        $builder->load(__DIR__ . '/fixtures/test.yml');
        $builder->load(__DIR__ . '/fixtures/test2.yml');

        $this->assertEquals($container['test2'],
            array(
                'host' => 'example.org',
                'user' => 'root',
                'pass' => 'test'
            )
        );
        $this->assertEquals($container['test'], array(1,2,3,4,5,6));
    }
}

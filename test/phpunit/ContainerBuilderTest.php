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
}

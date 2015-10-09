<?php
/**
 * Created by PhpStorm.
 * User: draeger
 * Date: 08.10.2015
 * Time: 09:19
 */

namespace test;

use Prophecy\Argument;
use G\Yaml2Pimple\Normalizer\PimpleNormalizer;

class PimpleNormalizerTest extends \PHPUnit_Framework_TestCase
{
    private function getServiceDummy()
    {
        //$prophecy = $this->prophesize('App');
        return null;//$prophecy->reveal();
    }

    private function getContainerStub($params)
    {
        $container = $this->prophesize('\Pimple');

        $container->offsetExists(Argument::type('string'))->will(function($args) use ($params) {
            return isset($params[$args[0]]);
        });

        $container->offsetGet(Argument::type('string'))->will(function($args) use ($params) {
            return $params[$args[0]];
        });

        return $container->reveal();

    }

    /**
     * @dataProvider parameterDataProvider
     */
    public function testNormalizeParameter($pass, $expects, $params)
    {
        $container = $this->getContainerStub($params);
        $normalizer = new PimpleNormalizer();

        static::assertEquals($expects, $normalizer->normalize($pass, $container));
    }

    /**
     * @dataProvider serviceDataProvider
     */
    public function testNormalizeService($pass, $expects, $params)
    {
        $container = $this->getContainerStub($params);
        $normalizer = new PimpleNormalizer();

        static::assertEquals($expects, $normalizer->normalize($pass, $container));
    }

    public function testNormalizeContainer()
    {
        $container = $this->getContainerStub(array());
        $normalizer = new PimpleNormalizer();

        static::assertEquals($container, $normalizer->normalize('@service_container', $container));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testServiceNotFound()
    {
        $container = $this->prophesize('\Pimple');
        $container->offsetExists(Argument::type('string'))->willReturn(false);

        $normalizer = new PimpleNormalizer();
        $normalizer->normalize('@test', $container->reveal());
    }

    public function serviceDataProvider()
    {
        $app = new \stdClass();

        return array
        (
            'quoted @' => array
            (
                '@@something',
                '@something',
                array(),
            ),
            'optional service' => array
            (
                '@?something',
                null,
                array(),
            ),
            'a service' => array
            (
                '@app',
                $app,
                array('app' => $app),
            )
        );
    }

    public function parameterDataProvider()
    {
        return array
        (
            'array values' => array
            (
                '%foo%',
                array('a','b','c'),
                array
                (
                    'foo' => array('a','b','c')
                ),
            ),

            'simple values' => array
            (
                '%foo%',
                'bar',
                array
                (
                    'foo' => 'bar'
                ),
            ),

            'multiple replacements' => array
            (
                '%foo% %bar%',
                'Hello World',
                array
                (
                    'foo'   => 'Hello',
                    'bar'   => 'World'
                ),
            ),

            'multiple replacements2' => array
            (
                '%foo% %bar% %foobar%',
                'Hello cruel World',
                array
                (
                    'foo'   => 'Hello',
                    'bar'   => 'cruel',
                    'foobar'   => 'World'
                ),
            ),

            'property access' => array
            (
                '%foo..bar%',
                'Hello World',
                array
                (
                    'foo'   => array( 'bar'   => 'Hello World' )
                ),
            )
        );
    }
}

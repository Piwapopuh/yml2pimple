<?php

namespace spec\G\Yaml2Pimple\Normalizer;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

/** @mixin \G\Yaml2Pimple\Normalizer\PimpleNormalizer */
class PimpleNormalizerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('G\Yaml2Pimple\Normalizer\PimpleNormalizer');
    }

    public function it_can_quote_at_characters(\Pimple $container)
    {
        $this->normalize('@@something', $container)->shouldReturn('@something');
    }

    public function it_accepts_optional_services(\Pimple $container)
    {
        $container->offsetExists('something')->willReturn(false);
        $this->normalize('@?something', $container)->shouldReturn(null);
    }

    public function it_can_return_a_named_service(\Pimple $container, $app)
    {
        $container->offsetExists('app')->willReturn(true);
        $container->offsetGet('app')->willReturn($app);
        $this->normalize('@app', $container)->shouldReturn($app);
    }

    public function it_throws_an_exception_when_it_doesnt_find_a_service(\Pimple $container)
    {
        $container->offsetExists(Argument::any())->willReturn(false);
        $this->shouldThrow('\RuntimeException')->duringNormalize('@app', $container);
    }

    public function it_can_normalize_simple_parameters(\Pimple $container)
    {
        $container->offsetExists('foo')->willReturn(true);
        $container->offsetGet('foo')->willReturn('bar');
        $this->normalize('%foo%', $container)->shouldReturn('bar');
    }

    public function it_can_normalize_simple_parameters_with_brackets(\Pimple $container)
    {
        $container->offsetExists('foo')->willReturn(true);
        $container->offsetGet('foo')->willReturn('bar');
        $this->normalize('%[foo]%', $container)->shouldReturn('bar');
    }

    public function it_can_normalize_multiple_parameters_in_a_string(\Pimple $container)
    {
        $container->offsetExists('foo')->willReturn(true);
        $container->offsetGet('foo')->willReturn('Hello');

        $container->offsetExists('bar')->willReturn(true);
        $container->offsetGet('bar')->willReturn('World');

        $this->normalize('%foo% %[bar]%', $container)->shouldReturn('Hello World');
    }

    public function it_can_normalize_array_access_style(\Pimple $container)
    {
        $container->offsetExists('foo')->willReturn(true);
        $container->offsetGet('foo')->willReturn(array('bar' => 'Hello World'));

        $this->normalize('%foo..bar%', $container)->shouldBe('Hello World');
    }
}

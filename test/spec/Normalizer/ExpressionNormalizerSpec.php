<?php

namespace spec\G\Yaml2Pimple\Normalizer;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ExpressionNormalizerSpec extends ObjectBehavior
{
    /**
     * @param \Symfony\Component\ExpressionLanguage\ExpressionLanguage $parser
     */
    public function let($parser)
    {
        $this->beConstructedWith('_normalize', $parser);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('G\Yaml2Pimple\Normalizer\ExpressionNormalizer');
    }

    public function it_normalizes_expressions($parser, \Pimple $container)
    {
        $container->offsetExists('_normalize')->willReturn(true);
        $container->offsetGet('_normalize')->willReturn(array('foo' => 'bar'));

        $parser->evaluate('foo', array('foo' => 'bar'))->willReturn('bar')->shouldBeCalled();
        $this->normalize('?foo', $container)->shouldReturn('bar');
    }
}

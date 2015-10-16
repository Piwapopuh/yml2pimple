<?php

namespace spec\G\Yaml2Pimple\Normalizer;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class EnvironmentNormalizerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('G\Yaml2Pimple\Normalizer\EnvironmentNormalizer');
    }

    public function it_normalizes_environment_vars(\Pimple $container)
    {
        putenv('TEST_ENV=Hallo Welt');

        $this->normalize('$TEST_ENV$', $container)->shouldReturn('Hallo Welt');
        $this->normalize('Env=$TEST_ENV$!', $container)->shouldReturn('Env=Hallo Welt!');
    }
}

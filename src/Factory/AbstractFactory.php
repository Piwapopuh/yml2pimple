<?php
namespace G\Yaml2Pimple\Factory;

use G\Yaml2Pimple\Normalizer\NormalizerInterface;

abstract class AbstractFactory
{
    /** @var  NormalizerInterface */
    protected $normalizer;

    public function setNormalizer(NormalizerInterface $normalizer)
    {
        $this->normalizer = $normalizer;

        return $this;
    }

    public function normalize($value, \Pimple $container)
    {
        if (null === $this->normalizer) {
            return $value;
        }

        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $k = (string) $this->normalize($k, $container);
                $v = $this->normalize($v, $container);
                $value[ $k ] = $v;
            }

            return $value;
        }

        if (is_string($value)) {
            return $this->normalizer->normalize($value, $container);
        }

        return $value;
    }

}

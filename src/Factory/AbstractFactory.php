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

    public function normalize($value, \Pimple $container, $skip = null)
    {
        if (null === $this->normalizer) {
            return $value;
        }

        if (is_array($value)) {
            $result = array();
            foreach ($value as $k => $v) {
                $k = (string) $this->normalize($k, $container, $skip);
                $v = $this->normalize($v, $container, $skip);
                $result[ $k ] = $v;
            }

            return $result;
        }

        if (is_string($value)) {
            if (null !== $skip && false !== strpos($value, '%'.$skip)) {
                return $value;
            }
            return $this->normalizer->normalize($value, $container);
        }

        return $value;
    }

}

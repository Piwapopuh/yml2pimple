<?php

namespace G\Yaml2Pimple\Normalizer;

class ChainNormalizer
{
    protected $normalizers = array();

    /**
     * @param array $normalizers
     */
    public function __construct(array $normalizers = array())
    {
        array_map(array($this, 'add'), $normalizers);
    }

    /**
     * @param Normalizer $normalizer
     */
    public function add($normalizer)
    {
        $this->normalizers[] = $normalizer;
    }

    /**
     * {@inheritDoc}
     */
    public function normalize($value, $container)
    {
        foreach ($this->normalizers as $normalizer) {
            $value = $normalizer->normalize($value, $container);
        }

        return $value;
    }
}

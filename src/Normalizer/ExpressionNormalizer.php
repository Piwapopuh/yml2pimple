<?php

namespace G\Yaml2Pimple\Normalizer;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ExpressionNormalizer
{
    private $parser;
    protected $key;

    public function __construct($key = '_normalize', ExpressionLanguage $parser = null)
    {
        $this->key = $key;
        
        if (is_null($parser))
        {
            $this->parser = new ExpressionLanguage();
        }
    }

    public function normalize($value, $container) {
        if (is_string($value) && '?' == substr($value, 0, 1)) {
            $value = $this->parser->evaluate(substr($value, 1), $container[$this->key]);
        }
        return $value;
    }
}
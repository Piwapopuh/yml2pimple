<?php

namespace G\Yaml2Pimple\Normalizer;

class TranslationNormalizer
{
    private $container;

    /**
     * @param Pimple $pimple
     */
    public function __construct(\Pimple $container)
    {
        $this->container = $container;
    }
	
    public function normalize($value, $container)
    {	
		if (is_array($value)) {
            foreach($value as $k => $v) {
                $value[$k] = $this->normalize($v, $container);
            }
            return $value;
        }
        if (!is_string($value)) {
			return $value;
		}

        if ('!!' == substr($value, 0, 2)) {
			$value = substr($value, 2);
            if (isset($container['translation.normalizer.messages']) && isset($container['translation.normalizer.messages'][$value])) {
                return $container['translation.normalizer.messages'][$value];
            }
            elseif (isset($container['translator'])) {
                $trans = $container['translator']->trans($value);
                $container->merge('translation.normalizer.messages', array($value => $trans)); 
                return $trans;
            }
        }

        return $value;
    }
}

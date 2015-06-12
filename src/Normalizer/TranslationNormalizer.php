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
            if (isset($container['translator'])) {
                // todo: save id for later dumping pot file
                $container->merge('translation.normalizer.messages', $value); 
                return $container['translator']->trans($value);
            }
        }

        return $value;
    }
}

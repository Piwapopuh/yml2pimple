<?php

namespace G\Yaml2Pimple\Filter;

class TranslationFilter implements FilterInterface
{
    public function getFunc()
    {
        return 'trans';
    }

    public function filter($container, $value, $args)
    {	
        if (is_array($value)) {
            foreach($value as $k => $v) {
                $value[$k] = $this->filter($container, $v, $args);
            }
            return $value;
        }
        if (isset($container['translation.normalizer.messages']) && isset($container['translation.normalizer.messages'][$value])) {
            return $container['translation.normalizer.messages'][$value];
        }
        elseif (isset($container['translator'])) {
            $trans = $container['translator']->trans($value);
            if (!isset($container['translation.normalizer.messages'])) {
                $container['translation.normalizer.messages'] = array();
            }
            $container['translation.normalizer.messages'] = array_replace($container['translation.normalizer.messages'], array($value => $trans)); 
            return $trans;
        }
        return $value;
    }
}

<?php
namespace G\Yaml2Pimple\Factory;

use \G\Yaml2Pimple\Parameter;

class ParameterFactory extends AbstractParameterFactory
{
    public function create(Parameter $parameterConf, \Pimple $container)
    {
        $parameterName  = $parameterConf->getParameterName();
        $parameterValue = $parameterConf->getParameterValue();

        $value = $this->normalize($parameterValue, $container);

        if ($parameterConf->mergeExisting() && isset($container[ $parameterName ])) {
            $old = $container[ $parameterName ];
            $value = call_user_func($parameterConf->getMergeStrategy($old), $old, $value);
        }

        $container[ $parameterName ] = $value;

        return $container;
    }
}

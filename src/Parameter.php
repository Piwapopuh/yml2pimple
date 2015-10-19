<?php
/**
 * Created by PhpStorm.
 * User: draeger
 * Date: 15.09.2015
 * Time: 10:58
 */

namespace G\Yaml2Pimple;

class Parameter
{
    protected $parameterName;
    protected $parameterValue;
    protected $frozen;
    protected $mergeExisting;
    protected $mergeStrategy;
    protected $file;

    /**
     * Parameter constructor.
     *
     * @param $parameterName
     * @param $parameterValue
     * @param $frozen
     * @param $mergeExisting
     */
    public function __construct($parameterName, $parameterValue, $frozen = true, $mergeExisting = false)
    {
        if (is_array($parameterValue)) {
            $mergeExisting = true;
        }

        // freeze our value on first access (as singleton)
        if (0 === strpos($parameterName, '$')) {
            $parameterName = substr($parameterName, 1);
            $frozen        = false;
        }

        $this->parameterName  = $parameterName;
        $this->parameterValue = $parameterValue;
        $this->frozen         = $frozen;
        $this->mergeExisting  = $mergeExisting;
    }

    /**
     * @return string $file
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param string $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * @return string
     */
    public function getParameterName()
    {
        return $this->parameterName;
    }

    /**
     * @return array
     */
    public function getParameterValue()
    {
        return $this->parameterValue;
    }

    /**
     * @return boolean
     */
    public function isFrozen()
    {
        return $this->frozen;
    }

    /**
     * @return boolean
     */
    public function mergeExisting()
    {
        return $this->mergeExisting;
    }

    public function getMergeStrategy($old = null)
    {
        $this->mergeStrategy = 'array_merge_recursive';

        if (is_array($this->parameterValue) && $this->isAssoc($this->parameterValue)) {
            $this->mergeStrategy = 'array_replace_recursive';
        }

        if (is_array($old) && $this->isAssoc($old)) {
            $this->mergeStrategy = 'array_replace_recursive';
        }

        return $this->mergeStrategy;
    }

    public function initialize($array)
    {
        foreach ($array as $key => $value) {
            $this->$key = $value;
        }
    }

    public static function __set_state($array)
    {
        $obj = new Parameter;
        $obj->initialize($array);

        return $obj;
    }

    private function isAssoc($array)
    {
        return ($array !== array_values($array));
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: draeger
 * Date: 15.09.2015
 * Time: 15:09
 */

namespace G\Yaml2Pimple\Loader;


class CacheLoader
{
    private $loader;
    private $cacheDir;
    private $cacheFile;

    public function __construct($loader)
    {
        $this->loader = $loader;
    }

    /**
     * @param mixed $cacheDir
     */
    public function setCacheDir($cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    public function load($file, &$builder = null)
    {
        $crc32 = crc32($file);
        $this->cacheFile = $this->cacheDir . '/___SC___' . $crc32 . '.php';

        if (file_exists($this->cacheFile)) {
            $conf = require $this->cacheFile;
            if ($this->isFresh($conf['resources'])) {
                $builder->buildFromArray($conf);
                return;
            }
        }

        $conf = $this->loader->load($file);
        $builder->buildFromArray($conf);

        $data = '<?php return ' . var_export($conf, true) . ';';
        file_put_contents($this->cacheFile, $data);
    }

    protected function isFresh($resources = array())
    {
        foreach($resources as $resource) {
            if (filemtime($resource) > filemtime($this->cacheFile)) {
                return false;
            }
        }
        return true;
    }

}
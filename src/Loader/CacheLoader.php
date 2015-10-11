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

    public function __construct($loader, $cacheDir = null)
    {
        $this->loader = $loader;
        if (null === $cacheDir) {
            $cacheDir = sys_get_temp_dir();
        }
        $this->cacheDir = $cacheDir;
    }

    /**
     * @param mixed $cacheDir
     */
    public function setCacheDir($cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    public function getCacheFileName($file)
    {
        return $this->cacheDir . '/___SC___' . crc32($file) . '.php';        
    }
    
    /**
     * @param $file
     * @return mixed
     */
    public function load($file)
    {
        $cacheFile = $this->getCacheFileName($file);
        
        if (file_exists($cacheFile)) 
        {
            $conf = include $cacheFile;
            if (isset($conf['resources']) && $this->isFresh($cacheFile, $conf['resources'])) {
                return $conf;
            }
        }
        
        $conf = $this->loader->load($file);

        $data = '<?php return ' . var_export($conf, true) . ';';
        file_put_contents($cacheFile, $data);
        
        return $conf;
    }

    protected function isFresh($cacheFile, array $resources = array())
    {
        foreach($resources as $resource) {
            if (filemtime($resource) > filemtime($cacheFile)) {
                return false;
            }
        }
        return true;
    }

}

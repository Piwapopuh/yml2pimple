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

    /**
     * @param $file
     * @return mixed
     */
    public function load($file)
    {
        $crc32 = crc32($file);
        $this->cacheFile = $this->cacheDir . '/___SC___' . $crc32 . '.php';

        if (file_exists($this->cacheFile)) {
            $conf = include $this->cacheFile;
            if (isset($conf['resources']) && $this->isFresh($conf['resources'])) {
                return $conf;
            }
        }
        $conf = $this->loader->load($file);

        $data = '<?php return ' . var_export($conf, true) . ';';
        file_put_contents($this->cacheFile, $data);
        return $conf;
    }

    protected function isFresh(array $resources = array())
    {
        foreach($resources as $resource) {
            if (filemtime($resource) > filemtime($this->cacheFile)) {
                return false;
            }
        }
        return true;
    }

}
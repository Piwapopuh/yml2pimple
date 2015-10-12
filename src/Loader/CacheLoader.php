<?php
/**
 * Created by PhpStorm.
 * User: draeger
 * Date: 15.09.2015
 * Time: 15:09
 */

namespace G\Yaml2Pimple\Loader;

use G\Yaml2Pimple\FileCache;

class CacheLoader
{
    private $loader = null;
    private $cacheDir;
    private $cache = null;

    public function __construct($loader, $cacheDir = null)
    {
        $this->loader = $loader;
        if (null === $cacheDir) {
            $cacheDir = sys_get_temp_dir();
        }
        $this->cacheDir = $cacheDir;
    }
    
    public function setCache($cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param mixed $cacheDir
     */
    public function setCacheDir($cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    public function load($resource)
    {
        $id = sprintf('%s/%s.php', $this->cacheDir, crc32($resource));
        
        if (null === $this->cache) {
            $this->cache = new FileCache();
        }
                
        if (!$this->cache->contains($id)) {
            $conf = $this->loader->load($resource);
            $this->cache->setResources($this->loader->getResources());
        }

        if (isset($conf)) {
            $this->cache->save($id, $conf);
        }

        if (!isset($conf)) {
            $conf = $this->cache->fetch($id);
        }

        return $conf;
    }    

}

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
    private $loader;
    private $resources;
    private $cacheDir;
    /** @var FileCache $cache */
    private $cache;

    /**
     * @param             $loader
     * @param \G\Yaml2Pimple\ResourceCollection $resources
     * @param null|string $cacheDir
     */
    public function __construct($loader, $resources, $cacheDir = null)
    {
        $this->loader = $loader;
        if (null === $cacheDir) {
            $cacheDir = sys_get_temp_dir();
        }
        $this->resources = $resources;
        $this->cacheDir = $cacheDir;
    }

    /**
     * @param FileCache $cache
     *
     * @return $this
     */
    public function setCache($cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * @param mixed $cacheDir
     *
     * @return $this
     */
    public function setCacheDir($cacheDir)
    {
        $this->cacheDir = $cacheDir;

        return $this;
    }

    /**
     * @param string $resource
     *
     * @return mixed
     */
    public function load($resource)
    {
        $id = sprintf('%s/%s.php', $this->cacheDir, crc32($resource));

        if (null === $this->cache) {
            $this->cache = new FileCache();
        }

        if (!$this->cache->contains($id)) {
            $conf = $this->loader->load($resource);
            $this->cache->setResources($this->resources->all());
            $this->resources->clear();
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

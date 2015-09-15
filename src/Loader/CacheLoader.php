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
        $cacheFile = $this->cacheDir . '/___SC___' . $crc32 . '.txt';

        if (file_exists($cacheFile) && filemtime($file) < filemtime($cacheFile)) {
            $data = file_get_contents($cacheFile);
            try {
                $data = $builder->unserialize($data);
                $builder->add($data);
                return;
            } catch(\Exception $e) {
                //
            }
        }

        $this->loader->load($file, $builder);

        $data = $builder->getResources($file);
        $data = $builder->serialize($data);
        file_put_contents($cacheFile, $data);
    }
}
<?php

namespace Bisna\Common\Cache;

use Cache\Adapter\Filesystem\FilesystemCachePool;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;

class FilesystemCache extends FilesystemCachePool
{
    private string $_namespace;
    
    public function __construct(array $options)
    {
        $this->checkCacheDirExists();
        $filesystemRoot = $options['directory'];
        $filesystemAdapter = new Local($filesystemRoot);
        $filesystem = new Filesystem($filesystemAdapter);
        parent::__construct($filesystem);
    }

    public function setNamespace($namespace) {
        $this->_namespace = $namespace;
    }

    public function getNamespace() {
        return $this->_namespace;
    }

    private function checkCacheDirExists() {
        $temp = sys_get_temp_dir() . '/cache';
        if (!realpath($temp) || !is_dir($temp)) {
            mkdir($temp);
        }
    }
}
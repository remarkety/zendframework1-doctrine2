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
}
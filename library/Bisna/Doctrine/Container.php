<?php

namespace Bisna\Doctrine;

use Bisna\Exception,
    Doctrine\DBAL\Types\Type,
    Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;

/**
 * Doctrine Container class.
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class Container
{

    /**
     * @var array The initial configuration sent in the constructor. Used for resetting the container.
     */
    private $_containerConfig = [];

    /**
     * @var string Default DBAL Connection name.
     */
    public $defaultConnection = 'default';

    /**
     * @var string Default Cache Instance name.
     */
    public $defaultCacheInstance = 'default';

    /**
     * @var string Default ODM DocumentManager name.
     */
    public $defaultDocumentManager = 'default';

    /**
     * @var string Default ORM EntityManager name.
     */
    public $defaultEntityManager = 'default';

    /**
     * @var array Doctrine Context configuration.
     */
    private $configuration = array();

    /**
     * @var array Available DBAL Connections.
     */
    private $connections = array();

    /**
     * @var array Available Cache Instances.
     */
    private $cacheInstances = array();

    /**
     * @var array Available ODM DocumentManagers.
     */
    private $documentManagers = array();

    /**
     * @var array Available ORM EntityManagers.
     */
    private $entityManagers = array();


    /**
     * Constructor.
     *
     * @param array $config Doctrine Container configuration
     */
    public function __construct(array $config = array())
    {
        $this->_containerConfig = $config;
        $this->_init($config);
    }

    public function reset() {
        $this->configuration = array();
        $this->connections = array();
        $this->cacheInstances = array();
        $this->documentManagers = array();
        $this->entityManagers = array();
        $this->_init($this->_containerConfig);
    }

    private function _init(array $config = array()) {
        // Registering Class Loaders
        if (isset($config['classLoader'])) {
            $this->registerClassLoaders($config['classLoader']);
        }

        // Defining DBAL configuration
        $dbalConfig = $this->prepareDBALConfiguration($config);

        // Defining default DBAL Connection name
        $this->defaultConnection = $dbalConfig['defaultConnection'];

        // Defining Cache configuration
        $cacheConfig = array();

        if (isset($config['cache'])) {
            $cacheConfig = $this->prepareCacheInstanceConfiguration($config);

            // Defining default Cache instance
            $this->defaultCacheInstance = $cacheConfig['defaultCacheInstance'];
        }

        // Defining ORM configuration
        $ormConfig = array();

        if (isset($config['orm'])) {
            $ormConfig  = $this->prepareORMConfiguration($config);

            // Defining default ORM EntityManager
            $this->defaultEntityManager = $ormConfig['defaultEntityManager'];
        }

        // Defining ODM configuration
        if (isset($config['odm'])) {
            $odmConfig  = $this->prepareODMConfiguration($config);

            // Defining default ORM EntityManager
            $this->defaultDocumentManager = $odmConfig['defaultDocumentManager'];
        }

        // Defining Doctrine Context configuration
        $this->configuration = array(
            'dbal'  => $dbalConfig['connections'],
            'cache' => $cacheConfig['instances'],
            'orm'   => $ormConfig['entityManagers']
        );

        // In case a ODM configuration is available, add it to the main configuration
        if (isset($odmConfig['documentManagers'])) {
            $this->configuration['odm']	= $odmConfig['documentManagers'];
        }
    }

    /**
     * Register Doctrine Class Loaders
     *
     * @param array $config Doctrine Class Loader configuration
     */
    private function registerClassLoaders(array $config = array())
    {
        $classLoaderClass = $config['loaderClass'];
        $classLoaderFile  = $config['loaderFile'];

        require_once $classLoaderFile;

        foreach ($config['loaders'] as $loaderItem) {
            if (! isset($loaderItem['includePath'])) {
                $loaderItem['includePath'] = null;
            }

            $classLoader = new $classLoaderClass($loaderItem['namespace'], $loaderItem['includePath']);
            $classLoader->register();
        }
    }

    /**
     * Prepare DBAL Connections configurations.
     *
     * @param array $config Doctrine Container configuration
     *
     * @return array
     */
    private function prepareDBALConfiguration(array $config = array())
    {
        $dbalConfig = $config['dbal'];
        $defaultConnectionName = isset($dbalConfig['defaultConnection'])
            ? $dbalConfig['defaultConnection'] : $this->defaultConnection;

        unset($dbalConfig['defaultConnection']);

        $defaultConnection = array(
            'eventManagerClass'   => 'Doctrine\Common\EventManager',
            'eventSubscribers'    => array(),
            'configurationClass'  => 'Doctrine\DBAL\Configuration',
            'sqlLoggerClass'      => null,
            'sqlLoggerParams'     => null,
            'types'               => array(),
            'parameters'          => array(
                'wrapperClass'       => null,
                'driver'              => 'pdo_mysql',
                'host'                => 'localhost',
                'user'                => 'root',
                'password'            => null,
                'port'                => null,
                'driverOptions'       => array()
            )
        );

        $connections = array();

        if (isset($dbalConfig['connections'])) {
            $configConnections = $dbalConfig['connections'];

            foreach ($configConnections as $name => $connection) {
                $name = isset($connection['id']) ? $connection['id'] : $name;
                $connections[$name] = array_replace_recursive($defaultConnection, $connection);
            }
        } else {
            $connections = array(
                $defaultConnectionName => array_replace_recursive($defaultConnection, $dbalConfig)
            );
        }

        return array(
            'defaultConnection' => $defaultConnectionName,
            'connections'       => $connections
        );
    }

    /**
     * Prepare Cache Instances configurations.
     *
     * @param array $config Doctrine Container configuration
     *
     * @return array
     */
    private function prepareCacheInstanceConfiguration(array $config = array())
    {
        $cacheConfig = $config['cache'];
        $defaultCacheInstanceName = isset($cacheConfig['defaultCacheInstance'])
            ? $cacheConfig['defaultCacheInstance'] : $this->defaultCacheInstance;

        unset($cacheConfig['defaultCacheInstance']);

        $defaultCacheInstance = array(
            'adapterClass' => 'Doctrine\Common\Cache\ArrayCache',
            'namespace'    => '',
            'options'      => array()
        );

        $instances = array();

        if (isset($cacheConfig['instances'])) {
            $configInstances = $cacheConfig['instances'];

            foreach ($configInstances as $name => $instance) {
                $name = isset($instance['id']) ? $instance['id'] : $name;
                $instances[$name] = array_replace_recursive($defaultCacheInstance, $instance);
            }
        } else {
            $instances = array(
                $defaultCacheInstanceName => array_replace_recursive($defaultCacheInstance, $cacheConfig)
            );
        }

        return array(
            'defaultCacheInstance' => $defaultCacheInstanceName,
            'instances'            => $instances
        );
    }

    /**
     * Prepare ODM EntityManagers configurations.
     *
     * @param array $config Doctrine Container configuration
     *
     * @return array
     */
    private function prepareODMConfiguration(array $config = array())
    {
        $odmConfig = $config['odm'];
        $defaultDocumentManagerName = isset($odmConfig['defaultDocumentManager'])
                ? $odmConfig['defaultDocumentManager']
                : $this->defaultDocumentManager;

        unset($odmConfig['defaultDocumentManager']);

        $defaultDocumentManager = array(
            'documentManagerClass' => 'Doctrine\ODM\MongoDB\DocumentManager',
            'configurationClass' => 'Doctrine\ODM\MongoDB\Configuration',
            'documentNamespaces' => array(),
            'connection' => $this->defaultConnection,
            'proxy' => array(
                    'autoGenerateClasses' => true,
                    'namespace' => 'Proxy',
                    'dir' => APPLICATION_PATH . '/../library/Proxy'
            ),
            'hydrator' => array(
                    'namespace' => 'Hydrators',
                    'dir' => APPLICATION_PATH . '/../cache'
            ),
            'queryCache' => $this->defaultCacheInstance,
            'resultCache' => $this->defaultCacheInstance,
            'metadataCache' => $this->defaultCacheInstance,
            'metadataDrivers' => array(),
            'connectionString' => ''
        );

        $documentManagers = array();

        if (isset($odmConfig['documentManagers'])) {
            $configDocumentManagers = $odmConfig['documentManagers'];

            foreach ($configDocumentManagers as $name => $documentManager) {
                $name = isset($documentManager['id']) ? $documentManager['id'] : $name;
                $documentManagers[$name] = array_replace_recursive($defaultDocumentManager, $documentManager);
            }
        } else {
            $documentManagers = array(
                    $this->defaultConnection => array_replace_recursive($defaultDocumentManager, $odmConfig)
            );
        }

        return array(
                'defaultDocumentManager' => $defaultDocumentManagerName,
                'documentManagers' => $documentManagers
        );
    }

    /**
     * Prepare ORM EntityManagers configurations.
     *
     * @param array $config Doctrine Container configuration
     *
     * @return array
     */
    private function prepareORMConfiguration(array $config = array())
    {
        $ormConfig = $config['orm'];
        $defaultEntityManagerName = isset($ormConfig['defaultEntityManager'])
            ? $ormConfig['defaultEntityManager'] : $this->defaultEntityManager;

        unset($ormConfig['defaultEntityManager']);

        $defaultEntityManager = array(
            'entityManagerClass'      => 'Doctrine\ORM\EntityManager',
            'configurationClass'      => 'Doctrine\ORM\Configuration',
            'entityNamespaces'        => array(),
            'connection'              => $this->defaultConnection,
            'proxy'                   => array(
                'autoGenerateClasses' => true,
                'namespace'           => 'Proxy',
                'dir'                 => APPLICATION_PATH . '/../library/Proxy'
            ),
            'queryCache'              => $this->defaultCacheInstance,
            'resultCache'             => $this->defaultCacheInstance,
            'metadataCache'           => $this->defaultCacheInstance,
            'metadataDrivers'         => array(),
            'namingStrategyClass'     => 'Doctrine\ORM\Mapping\DefaultNamingStrategy',
            'DQLFunctions'            => array(
                'numeric'             => array(),
                'datetime'            => array(),
                'string'              => array()
            )
        );

        $entityManagers = array();

        if (isset($ormConfig['entityManagers'])) {
            $configEntityManagers = $ormConfig['entityManagers'];

            foreach ($configEntityManagers as $name => $entityManager) {
                $name = isset($entityManager['id']) ? $entityManager['id'] : $name;
                $entityManagers[$name] = array_replace_recursive($defaultEntityManager, $entityManager);
            }
        } else {
            $entityManagers = array(
                $this->defaultConnection => array_replace_recursive($defaultEntityManager, $ormConfig)
            );
        }

        return array(
            'defaultEntityManager' => $defaultEntityManagerName,
            'entityManagers'       => $entityManagers
        );
    }

    /**
     * Retrieve DBAL Connection based on its name. If no argument is provided,
     * it will attempt to get the default Connection.
     * If DBAL Connection name could not be found, NameNotFoundException is thrown.
     *
     * @throws \Bisna\Exception\NameNotFoundException
     *
     * @param string $connName Optional DBAL Connection name
     *
     * @return \Doctrine\DBAL\Connection DBAL Connection
     */
    public function getConnection($connName = null)
    {
        $connName = $connName ?: $this->defaultConnection;

        // Check if DBAL Connection has not yet been initialized
        if ( ! isset($this->connections[$connName])) {
            // Check if DBAL Connection is configured
            if ( ! isset($this->configuration['dbal'][$connName])) {
                throw new Exception\NameNotFoundException("Unable to find Doctrine DBAL Connection '{$connName}'.");
            }

            $this->connections[$connName] = $this->startDBALConnection($this->configuration['dbal'][$connName]);

            unset($this->configuration['dbal'][$connName]);
        }

        return $this->connections[$connName];
    }

    /**
     * Retrieves a list of names for all Connections configured and/or loaded
     *
     * @return array
     */
    public function getConnectionNames()
    {
       $configuredConnections = array_keys($this->configuration['dbal']);
       $loadedConnections = array_keys($this->connections);

       return array_merge($configuredConnections, $loadedConnections);
    }

    /**
     * Retrieve Cache Instance based on its name. If no argument is provided,
     * it will attempt to get the default Instance.
     * If Cache Instance name could not be found, NameNotFoundException is thrown.
     *
     * @throws \Bisna\Exception\NameNotFoundException
     *
     * @param string $cacheName Optional Cache Instance name
     *
     * @return \Doctrine\Common\Cache\Cache Cache Instance
     */
    public function getCacheInstance($cacheName = null)
    {
        $cacheName = $cacheName ?: $this->defaultCacheInstance;

        // Check if Cache Instance has not yet been initialized
        if ( ! isset($this->cacheInstances[$cacheName])) {
            // Check if Cache Instance is configured
            if ( ! isset($this->configuration['cache'][$cacheName])) {
                throw new Exception\NameNotFoundException("Unable to find Doctrine Cache Instance '{$cacheName}'.");
            }

            $this->cacheInstances[$cacheName] = $this->startCacheInstance($this->configuration['cache'][$cacheName]);

            unset($this->configuration['cache'][$cacheName]);
        }

        return $this->cacheInstances[$cacheName];
    }

    /**
     * Retrieves a list of names for all cache instances configured
     *
     * @return array
     */
    public function getCacheInstanceNames()
    {
       $configuredInstances = array_keys($this->configuration['cache']);
       $loadedInstances = array_keys($this->cacheInstances);

       return array_merge($configuredInstances, $loadedInstances);
    }

    /**
     * Retrieve ODM DocumentManager based on its name. If no argument provided,
     * it will attempt to get the default DocumentManager.
     * If ODM DocumentManager name could not be found, NameNotFoundException is thrown.
     *
     * @throws Bisna\Application\Exception\NameNotFoundException
     * @param string $dmName Optional ODM DocumentManager name
     * @return Doctrine\ODM\MongoDB\DocumentManager
     */
    public function getDocumentManager($dmName = null)
    {
        $dmName = $dmName ?: $this->defaultDocumentManager;

        // Check if ORM Entity Manager has not yet been initialized
        if ( ! isset($this->documentManagers[$dmName])) {
            // Check if ORM EntityManager is configured
            if ( ! isset($this->configuration['odm'][$dmName])) {
                throw new \Core\Application\Exception\NameNotFoundException("Unable to find Doctrine ODM DocumentManager '{$dmName}'.");
            }

            $this->documentManagers[$dmName] = $this->startODMDocumentManager($this->configuration['odm'][$dmName]);

            unset($this->configuration['odm'][$dmName]);
        }

        return $this->documentManagers[$dmName];
    }

    /**
     * Retrieve ORM EntityManager based on its name. If no argument provided,
     * it will attempt to get the default EntityManager.
     * If ORM EntityManager name could not be found, NameNotFoundException is thrown.
     *
     * @throws \Bisna\Exception\NameNotFoundException
     *
     * @param string $emName Optional ORM EntityManager name
     *
     * @return \Doctrine\ORM\EntityManager ORM EntityManager
     */
    public function getEntityManager($emName = null)
    {
        $emName = $emName ?: $this->defaultEntityManager;

        // Check if ORM Entity Manager has not yet been initialized
        if ( ! isset($this->entityManagers[$emName])) {
            // Check if ORM EntityManager is configured
            if ( ! isset($this->configuration['orm'][$emName])) {
                throw new Exception\NameNotFoundException("Unable to find Doctrine ORM EntityManager '{$emName}'.");
            }

            $this->entityManagers[$emName] = $this->startORMEntityManager($this->configuration['orm'][$emName]);

            unset($this->configuration['orm'][$emName]);
        }

        return $this->entityManagers[$emName];
    }

    /**
     * Retrieves a list of names for all Entity Managers configured and/or loaded
     *
     * @return array
     */
    public function getEntityManagerNames()
    {
       $configuredEMs = array_keys($this->configuration['orm']);
       $loadedEMs = array_keys($this->entityManagers);

       return array_merge($configuredEMs, $loadedEMs);
    }

    /**
     * Initialize the DBAL Connection.
     *
     * @param array $config DBAL Connection configuration.
     *
     * @return \Doctrine\DBAL\Connection
     */
    private function startDBALConnection(array $config = array())
    {
        $connection = \Doctrine\DBAL\DriverManager::getConnection(
            $config['parameters'],
            $this->startDBALConfiguration($config),
            $this->startDBALEventManager($config)
        );

        // Type mappings
        if (isset($config['typeMapping'])) {
            foreach ($config['typeMapping'] as $dbType => $doctrineType) {
                $connection->getDatabasePlatform()->registerDoctrineTypeMapping($dbType, $doctrineType);
            }
        }

        return $connection;
    }

    /**
     * Initialize the DBAL Configuration.
     *
     * @param array $config DBAL Connection configuration.
     *
     * @return \Doctrine\DBAL\Configuration
     */
    private function startDBALConfiguration(array $config = array())
    {
        $configClass = $config['configurationClass'];
        $configuration = new $configClass();

        // SQL Logger configuration
        if ( ! empty($config['sqlLoggerClass'])) {
            $sqlLoggerClass = $config['sqlLoggerClass'];
            if ( !empty($config['sqlLoggerParams']) ) {
                $configuration->setSQLLogger(new $sqlLoggerClass($config['sqlLoggerParams']));
            } else {
                $configuration->setSQLLogger(new $sqlLoggerClass());
            }
        }

        //DBAL Types configuration
        $types = $config['types'];

        foreach ($types as $name => $className) {
            if (Type::hasType($name)) {
                Type::overrideType($name, $className);
            } else {
                Type::addType($name, $className);
            }
        }

        return $configuration;
    }

    /**
     * Initialize the EventManager.
     *
     * @param array $config DBAL Connection configuration.
     *
     * @return \Doctrine\Common\EventManager
     */
    private function startDBALEventManager(array $config = array())
    {
        $eventManagerClass = $config['eventManagerClass'];
        $eventManager = new $eventManagerClass();

        // Event Subscribers configuration
        foreach ($config['eventSubscribers'] as $subscriber) {
            if ($subscriber) {
                $eventManager->addEventSubscriber(new $subscriber());
            }
        }

        return $eventManager;
    }

    /**
     * Initialize Cache Instance.
     *
     * @param array $config Cache Instance configuration.
     *
     * @return \Doctrine\Common\Cache\Cache
     */
    private function startCacheInstance(array $config = array()): \Psr\Cache\CacheItemPoolInterface
    {
        $adapterClass = $config['adapterClass'];
        $adapterConfig = $config['options'];
        $adapter = new $adapterClass($adapterConfig);

        // Define namespace for cache
        if (isset($config['namespace']) && ! empty($config['namespace'])) {
            $adapter->setNamespace($config['namespace']);
        }

        if (method_exists($adapter, 'initialize')) {
            $adapter->initialize($config);
        }

        return $adapter;

    }

    /**
     * Initialize the ODM Document Manager
     *
     * @param array $config
     * @return Doctrine\ODM\MongoDB\DocumentManager
     */
    private function startODMDocumentManager(array $config = array())
    {
        return \Doctrine\ODM\MongoDB\DocumentManager::create(
            new \Doctrine\MongoDB\Connection($config['connectionString']),
            $this->startODMConfiguration($config)
        );
    }

    /**
     * Initialize ORM EntityManager.
     *
     * @param array $config ORM EntityManager configuration.
     *
     * @return \Doctrine\ORM\EntityManager
     */
    private function startORMEntityManager(array $config = array())
    {
        if (isset($config['entityManagerClass'])) {
            $entityManagerClass = $config['entityManagerClass'];
        } else {
            $entityManagerClass = '\Doctrine\ORM\EntityManager';
        }
        return $entityManagerClass::create(
            $this->getConnection($config['connection']),
            $this->startORMConfiguration($config)
        );
    }

    /**
     * Initialize ODM Configuration.
     *
     * @param array $config ODM DocumentManager configuration.
     * @return Doctrine\ODM\MongoDB\Configuration
     */
    private function startODMConfiguration(array $config = array())
    {
        $configClass = $config['configurationClass'];
        $configuration = new $configClass();

        $configuration = new \Doctrine\ODM\MongoDB\Configuration();

        // Entity Namespaces configuration
        foreach ($config['documentNamespaces'] as $alias => $namespace) {
            $configuration->addDocumentNamespace($alias, $namespace);
        }

        // Proxy configuration
        $configuration->setAutoGenerateProxyClasses(
            $config['proxy']['autoGenerateClasses'] === true ||
            ! in_array($config['proxy']['autoGenerateClasses'], array("0", "false", false))
        );
        $configuration->setProxyNamespace($config['proxy']['namespace']);
        $configuration->setProxyDir($config['proxy']['dir']);

        $configuration->setHydratorDir($config['hydrator']['dir']);
        $configuration->setHydratorNamespace($config['hydrator']['namespace']);

        // Cache configuration
        $configuration->setMetadataCache($this->getCacheInstance($config['metadataCache']));

        // Metadata configuration
        $configuration->setMetadataDriverImpl($this->startODMMetadata($config['metadataDrivers']));

        if (isset($config['defaultDb'])) {
            $configuration->setDefaultDB($config['defaultDb']);
        }

        if (isset($config['environment'])) {
            $configuration->setDefaultDB($config['environment']);
        }

        return $configuration;
    }

    /**
     * Initialize ORM Configuration.
     *
     * @param array $config ORM EntityManager configuration.
     *
     * @return \Doctrine\ORM\Configuration
     */
    private function startORMConfiguration(array $config = array())
    {
        $configClass = $config['configurationClass'];
        $configuration = new $configClass();

        $configuration = new \Doctrine\ORM\Configuration();

        // Entity Namespaces configuration
        foreach ($config['entityNamespaces'] as $alias => $namespace) {
            $configuration->addEntityNamespace($alias, $namespace);
        }

        // Proxy configuration
        $configuration->setAutoGenerateProxyClasses(
            $config['proxy']['autoGenerateClasses'] === true ||
            ! in_array($config['proxy']['autoGenerateClasses'], array("0", "false", false))
        );
        $configuration->setProxyNamespace($config['proxy']['namespace']);
        $configuration->setProxyDir($config['proxy']['dir']);

        // Cache configuration
        $configuration->setMetadataCache($this->getCacheInstance($config['metadataCache']));
        $configuration->setResultCacheImpl(DoctrineProvider::wrap($this->getCacheInstance($config['resultCache'])));
        $configuration->setQueryCacheImpl(DoctrineProvider::wrap($this->getCacheInstance($config['queryCache'])));

        // Metadata configuration
        $configuration->setMetadataDriverImpl($this->startORMMetadata($config['metadataDrivers']));

        // DQL Functions configuration
        $dqlFunctions = $config['DQLFunctions'];

        foreach ($dqlFunctions['datetime'] as $name => $className) {
            $configuration->addCustomDatetimeFunction($name, $className);
        }

        foreach ($dqlFunctions['numeric'] as $name => $className) {
            $configuration->addCustomNumericFunction($name, $className);
        }

        foreach ($dqlFunctions['string'] as $name => $className) {
            $configuration->addCustomStringFunction($name, $className);
        }

        // Repository Class configuration
        if (isset($config['defaultRepositoryClass'])) {
            $configuration->setDefaultRepositoryClassName($config['defaultRepositoryClass']);
        }

        // Naming strategy for ORM
        $configuration->setNamingStrategy(new $config['namingStrategyClass']);

        return $configuration;
    }

    /**
     * Initialize ODM Metadata drivers.
     *
     * @param array $config ODM Mapping drivers.
     * @return Doctrine\ODM\MongoDB\Mapping\Driver\DriverChain
     */
    private function startODMMetadata(array $config = array())
    {
        $metadataDriver = new \Doctrine\Persistence\Mapping\Driver\MappingDriverChain();

        // Default metadata driver configuration
        $defaultMetadataDriver = array(
            'adapterClass'               => 'Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver',
            'mappingNamespace'           => '',
            'mappingDirs'                => array(),
            'annotationReaderClass'      => 'Doctrine\Common\Annotations\AnnotationReader',
            'annotationReaderCache'      => $this->defaultCacheInstance,
            'annotationReaderNamespaces' => array()
        );

        // Setup ODM AnnotationRegistry
        if (isset($config['annotationRegistry'])) {
            $this->startAnnotationRegistry($config['annotationRegistry']);
        }

        foreach ($config['drivers'] as $driver) {
            $driver = array_replace_recursive($defaultMetadataDriver, $driver);

            if (method_exists($driver['adapterClass'], 'registerAnnotationClasses')) {
                $driver['adapterClass']::registerAnnotationClasses();
            }
            
            $reflClass = new \ReflectionClass($driver['adapterClass']);
            $nestedDriver = null;

            if (
                $reflClass->getName() == 'Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver' ||
                $reflClass->isSubclassOf('Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver')
            ) {
                $annotationReaderClass = $driver['annotationReaderClass'];
                $annotationReader = new $annotationReaderClass();
                if (method_exists($annotationReader, 'setDefaultAnnotationNamespace')) {
                    $annotationReader->setDefaultAnnotationNamespace('Doctrine\ODM\MongoDB\Mapping\\');
                }

                if (method_exists($annotationReader, 'setAnnotationNamespaceAlias')) {
                    $driver['annotationReaderNamespaces']['ODM'] = 'Doctrine\ODM\MongoDB\Mapping\\';

                    foreach ($driver['annotationReaderNamespaces'] as $alias => $namespace) {
                        $annotationReader->setAnnotationNamespaceAlias($namespace, $alias);
                    }
                }

                $indexedReader = new \Doctrine\Common\Annotations\CachedReader(
                    new \Doctrine\Common\Annotations\IndexedReader($annotationReader),
                    $this->getCacheInstance($driver['annotationReaderCache'])
                );

                $nestedDriver = $reflClass->newInstance($indexedReader, $driver['mappingDirs']);
            } else {
                $nestedDriver = $reflClass->newInstance($indexedReader, $driver['mappingDirs']);
            }

            $metadataDriver->addDriver($nestedDriver, $driver['mappingNamespace']);
        }

        if (($drivers = $metadataDriver->getDrivers()) && count($drivers) == 1) {
            reset($drivers);
            $metadataDriver = $drivers[key($drivers)];
        }

        return $metadataDriver;
    }

    /**
     * Initialize ORM Metadata drivers.
     *
     * @param array $config ORM Mapping drivers.
     *
     * @return \Doctrine\ORM\Mapping\Driver\DriverChain
     */
    private function startORMMetadata(array $config = array())
    {
        $metadataDriver = new \Doctrine\Persistence\Mapping\Driver\MappingDriverChain();

        // Default metadata driver configuration
        $defaultMetadataDriver = array(
            'adapterClass'               => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
            'mappingNamespace'           => '',
            'mappingDirs'                => array(),
            'annotationReaderClass'      => 'Doctrine\Common\Annotations\AnnotationReader',
            'annotationReaderCache'      => $this->defaultCacheInstance,
            'annotationReaderNamespaces' => array()
        );


        // Setup AnnotationRegistry
        if (isset($config['annotationRegistry'])) {
            $this->startAnnotationRegistry($config['annotationRegistry']);
        }

        foreach ($config['drivers'] as $driver) {
            $driver = array_replace_recursive($defaultMetadataDriver, $driver);

            $reflClass = new \ReflectionClass($driver['adapterClass']);
            $nestedDriver = null;

            if (
                $reflClass->getName() == 'Doctrine\ORM\Mapping\Driver\AnnotationDriver' ||
                $reflClass->isSubclassOf('Doctrine\ORM\Mapping\Driver\AnnotationDriver')
            ) {
                $annotationReaderClass = $driver['annotationReaderClass'];
                $annotationReader = new $annotationReaderClass();

                // For Doctrine >= 2.2
                if (method_exists($annotationReader, 'addNamespace')) {
                    $annotationReader->addNamespace('Doctrine\ORM\Mapping');
                }

                if (method_exists($annotationReader, 'setDefaultAnnotationNamespace')) {
                    $annotationReader->setDefaultAnnotationNamespace('Doctrine\ORM\Mapping\\');
                }

                if (method_exists($annotationReader, 'setAnnotationNamespaceAlias')) {
                    $driver['annotationReaderNamespaces']['ORM'] = 'Doctrine\ORM\Mapping\\';

                    foreach ($driver['annotationReaderNamespaces'] as $alias => $namespace) {
                        $annotationReader->setAnnotationNamespaceAlias($namespace, $alias);
                    }
                }

                $annotationReaderCache = $this->getCacheInstance($driver['annotationReaderCache']);
                $wrappedAnnotationReaderCache = DoctrineProvider::wrap($annotationReaderCache);
                $indexedReader = new \Doctrine\Common\Annotations\CachedReader(
                    new \Doctrine\Common\Annotations\IndexedReader($annotationReader),
                    $wrappedAnnotationReaderCache
                );

                $nestedDriver = $reflClass->newInstance($indexedReader, $driver['mappingDirs']);
            } else {
                $nestedDriver = $reflClass->newInstance($driver['mappingDirs']);
            }

            $metadataDriver->addDriver($nestedDriver, $driver['mappingNamespace']);
        }

        if (($drivers = $metadataDriver->getDrivers()) && count($drivers) == 1) {
            reset($drivers);
            $metadataDriver = $drivers[key($drivers)];
        }

        return $metadataDriver;
    }

    /**
     * Initialize ORM Metatada Annotation Registry driver
     *
     * @param array $config  ORM Annotation Registry configuration.
     */
    private function startAnnotationRegistry($config)
    {
        // Load annotations from Files
        if (isset($config['annotationFiles']) && is_array($config['annotationFiles'])) {
            foreach($config['annotationFiles'] as $file) {
                AnnotationRegistry::registerFile($file);
            }
        }

        // Load annotation namespaces
        if (isset($config['annotationNamespaces']) && is_array($config['annotationNamespaces'])) {
            foreach($config['annotationNamespaces'] as $annotationNamespace) {
                AnnotationRegistry::registerAutoloadNamespace(
                        $annotationNamespace['namespace']
                        , isset($annotationNamespace['includePath']) ? $annotationNamespace['includePath'] : null
                );
            }

        }
    }
}

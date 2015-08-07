<?php

namespace Profiler\Db;

use Zend\Db\Adapter\AdapterAbstractServiceFactory;
use Zend\ServiceManager\ServiceLocatorInterface;
//use BjyProfiler\Db\Adapter\ProfilingAdapter;
//use BjyProfiler\Db\Adapter\LoggingProfiler;
//use BjyProfiler\Db\Adapter\Profiler;

class ProfilerAdapterAbstractServiceFactory extends AdapterAbstractServiceFactory
{
    /**
     * Create a DB adapter
     *
     * @param  ServiceLocatorInterface $services
     * @param  string $name
     * @param  string $requestedName
     * @return Adapter
     */
    public function createServiceWithName(ServiceLocatorInterface $services, $name, $requestedName)
    {
        $config = $this->getConfig($services);
        $adapter = new \BjyProfiler\Db\Adapter\ProfilingAdapter($config[$requestedName]);

        $adapter->setProfiler(new \BjyProfiler\Db\Profiler\Profiler());
        if (isset($config[$requestedName]['options']) && is_array($config[$requestedName]['options'])) {
            $options = $config[$requestedName]['options'];
        } else {
            $options = array();
        }
        $adapter->injectProfilingStatementPrototype($options);
        return $adapter;
    }
}


<?php

namespace Profiler\Mapper;

use MongoClient;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class ProfileMapperFactory
 *
 * @author Antoine Bon
 */
class ProfileMapperFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $options = $serviceLocator->get('Profiler\Config');
        $request = $serviceLocator->get('Request');

        $mongoClient = new MongoClient($options->getDbServer());

        $profileMapper = new ProfileMapper($mongoClient, $options->getDatabase(), $options->getCollection(), array('w' => 0));
        $profileMapper->setHostname($request->getServer('HTTP_HOST') . $request->getBaseUrl());

        return $profileMapper;
    }

}

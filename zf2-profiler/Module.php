<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/Exam for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Profiler;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\Mvc\MvcEvent;
use Zend\EventManager\EventManager;
use Zend\Stdlib\ArrayUtils;

class Module implements AutoloaderProviderInterface
{
    public function getAutoloaderConfig()
    {
        return array(
            /* 'Zend\Loader\ClassMapAutoloader' => array( */
            /*     __DIR__ . '/autoload_classmap.php', */
            /* ), */
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    // if we're in a namespace deeper than one level we need to fix the \ in the path
                    __NAMESPACE__ => __DIR__ . '/src/' . str_replace('\\', '/' , __NAMESPACE__),
                ),
            ),
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getServiceConfig()
    {
        return array(
            'invokables' => array(
                'Profiler\ProfilerListener' => 'Profiler\Listener\ProfilerListener',
            ),
            'factories' => array(
                'Profiler\ProfileMapper' => 'Profiler\Mapper\ProfileMapperFactory',
                'Profiler\Config' => function ($sm) {
                    $config = $sm->get('Config');
                    $config = isset($config['profiler']) ? $config['profiler'] : null;
                    return new Options($config, $sm->get('ZendDeveloperTools\Config'));
                },
                'Profiler\ProfilerService' => function ($sm) {
                    return new Service\ProfilerService(
                        $sm->get('Profiler\ProfileMapper'),
                        $sm->get('Profiler\Config')
                    );
                },
            ),
        );
    }

    public function onBootstrap(MvcEvent $event)
    {
        $application = $event->getApplication();
        $em = $application->getEventManager();
        $sm = $application->getServiceManager();
        $sem = $em->getSharedManager();

        $options = $sm->get('Profiler\Config');
        if ($options->isProfilerEnabled()) {
            $sem->attach('profiler', $sm->get('Profiler\ProfilerListener'), null);
        }
    }
}

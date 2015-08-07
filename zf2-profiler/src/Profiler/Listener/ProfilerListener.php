<?php

namespace Profiler\Listener;

use Zend\Mvc\MvcEvent;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZendDeveloperTools\ProfilerEvent;
use ZendDeveloperTools\Profiler;

/**
 * Description of ProfilerListener
 *
 * @author abon
 */
class ProfilerListener implements ListenerAggregateInterface
{
    /**
     * @var array
     */
    protected $listeners = array();

    /**
     * {@inheritdoc}
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(
            ProfilerEvent::EVENT_COLLECTED,
            array($this, 'onCollected'),
            Profiler::PRIORITY_EVENT_COLLECTOR
        );
    }

    /**
     * {@inheritdoc}
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
    }

    /**
     * ProfilerEvent::EVENT_COLLECTED event callback.
     *
     * @param ProfilerEvent $event
     */
    public function onCollected(ProfilerEvent $event)
    {
        $profiler   = $event->getProfiler();
        $report     = $event->getReport();
        // Log every collected profile except for profiler route
        $routeMatch = $event->getApplication()->getMvcEvent()->getRouteMatch();;
        if ($routeMatch && !in_array($routeMatch->getMatchedRouteName(), array('profiler', 'profiler-static'))) {
            $event->getApplication()->getServiceManager()->get('Profiler\ProfilerService')->logCollectedProfilesIntoLogFile($profiler, $report);
        }
    }

}


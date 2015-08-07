<?php

namespace Profiler\Service;

use Profiler\Paginator\Adapter\MongoCursor;
use Zend\Paginator\Paginator;

class ProfilerService
{
    protected $mapper;

    protected $oOptions;

    public function __construct(
        $mapper,
        \Profiler\Options $oOptions
    ) {
        $this->mapper = $mapper;
        $this->oOptions = $oOptions;
    }

    /**
     * Writes collected profiles into a new json log file
     */
    public function logCollectedProfilesIntoLogFile($profiler, $report)
    {
        $collectors = $this->oOptions->getCollectors();
        $profile    = array();
        $run        = microtime(true);

        $profile['report'] = array(
            'ip'     => $report->getIp(),
            'uri'    => $report->getUri(),
            'method' => $report->getMethod(),
            'token'  => $report->getToken(),
            'time'   => $report->getTime(),
            'run'    => $run,
        );

        foreach ($collectors as $name => $factory) {
            try {
                $collectorInstance = $report->getCollector($name);
                $profile[$name] = $this->collectorToArray($name, $collectorInstance);
                $profile[$name]['run'] = $run;
            } catch (RuntimeException $e) {
            }
        }

        $this->mapper->save($profile);
    }

    public function fetchAllProfiles()
    {
        return $this->mapper->fetchAll();
    }

    public function fetchAllProfilesByLogTime()
    {
        return $this->mapper->fetchAllByLogTime();
    }

    public function fetchAllProfilesByDbTime()
    {
        return $this->mapper->fetchAllByDbTime();
    }

    public function fetchAllProfilesByExecutionTime()
    {
        return $this->mapper->fetchAllByExecutionTime();
    }

    public function fetchAllProfilesByMemoryUsage()
    {
        return $this->mapper->fetchAllByMemoryUsage();
    }

    public function getProfilesPaginator($iPage = 1, $iCount = null)
    {
        $oPaginator = new Paginator(new MongoCursor($this->mapper->fetchAllIds()));
        $oPaginator->setItemCountPerPage(($iCount) ? $iCount : $this->oOptions->getProfilesCountPerPage());
        $oPaginator->setCurrentPageNumber($iPage);
        return $oPaginator;
    }

    public function setPaginationSettings($aPaginationOptions)
    {
        $iPageItemCount = isset($aPaginationOptions['count']) ? $aPaginationOptions['count'] : $this->oOptions->getProfilesCountPerPage();

        $this->mapper->setLimit($iPageItemCount);

        if (isset($aPaginationOptions['page'])) {
            $this->mapper->setSkip($iPageItemCount * ($aPaginationOptions['page'] - 1));
        }
    }

	public function setFilteringOptions($aFilteringOptions)
	{
		if (isset($aFilteringOptions['text'])) {
			$this->mapper->setTextFilter($aFilteringOptions['text']);
		}
	}

    /**
     * Convert a collector into an array for json log output
     *
     * @param String $name
     * @param $collector
     */
    protected function collectorToArray($name, \ZendDeveloperTools\Collector\CollectorInterface $collector) {
        $return = array();
        if (!$collector) {
            return $return;
        }
        if ($name === 'db') {
            $profiler = $collector->getProfiler();
            if (!$profiler) {
                return $return;
            }
            $return = array(
                'count' => $collector->getQueryCount(),
                'time' => $collector->getQueryTime(),
                'queries' => array(),
            );
            foreach ($profiler->getQueryProfiles() as $profile) {
                $query = $profile->toArray();
                unset($query['stack']);
                $return['queries'][] = $query;
            }
        } else if ($name === 'time') {
            if ($collector->hasEventTimes()) {
                $return['events'] = $collector->getApplicationEventTimes();
                $return['total'] = $collector->getExecutionTime();
            }
        } else if ($name === 'memory') {
            if ($collector->hasEventMemory()) {
                $return['events'] = $collector->getApplicationEventMemory();
                $return['total'] = $collector->getMemory();
            }
        } else if ($name === 'request') {
            $return = array(
                'controller' => $collector->getFullControllerName(false),
                'method' => $collector->getMethod(),
                'route' => $collector->getRouteName(),
                'templates' => $collector->getViews(),
            );
        }
        return $return;
    }

}

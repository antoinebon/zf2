<?php
/**
 * Zend Developer Tools for Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendDeveloperTools for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Profiler;

use Zend\Stdlib\AbstractOptions;

class Options extends AbstractOptions
{
    protected $zdtOptions;

    public function __construct($config, \ZendDeveloperTools\Options $zdtOptions)
    {
        $this->zdtOptions = $zdtOptions;
        return parent::__construct($config);
    }

    /**
     * If a method is not define in the class then call it on the zdtOptions object
     */
    public function __call($name, $arguments)
    {
        return $this->zdtOptions->{$name}($arguments);
    }

    /**
     * @var array
     */
    protected $profiler = array(
        'enabled' => false,
        'graphs' => array(
            'db' => 'Database Requests',
            'time' => 'Execution Time',
            'memory' => 'Memory Usage',
        ),
        'paginator' => array(
            'itemCountPerPage' => 40,
        ),
        'mongodb' => array(
            'server' => 'mongodb://localhost:27017',
            'database' => 'log',
            'collection' => 'profiler',
        ),
        'portalHomeRouteParams' => array(
            'dashboard', // the route name
            array('action' => 'index'), // the route params
            array(), // the route options
        )
    );

    public function setProfiler(array $options)
    {
		$this->profiler = array_merge($this->profiler, $options);
    }

    /**
     * Is the performance profiling enabled?
     *
     * @return bool
     */
    public function isProfilerEnabled()
    {
        return $this->profiler['enabled'];
    }

    /**
     * Is the performance profiling enabled?
     *
     * @return bool
     */
    public function getGraphNames()
    {
        return $this->profiler['graphs'];
    }

    public function getProfilesCountPerPage()
    {
        return $this->profiler['paginator']['itemCountPerPage'];
    }

    public function getDbServer()
    {
        return $this->profiler['mongodb']['server'];
    }

    public function getDatabase()
    {
        return $this->profiler['mongodb']['database'];
    }

    public function getCollection()
    {
        return $this->profiler['mongodb']['collection'];
    }

    public function getPortalHomeRouteParams()
    {
        return $this->profiler['portalHomeRouteParams'];
    }
}

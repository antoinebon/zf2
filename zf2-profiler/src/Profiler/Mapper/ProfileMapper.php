<?php

namespace Profiler\Mapper;

use DateTime;
use Mongo;
use MongoClient;
use MongoDate;
use Traversable;
use Zend\Log\Exception;
use Zend\Log\Formatter\FormatterInterface;
use Zend\Stdlib\ArrayUtils;

/**
 * Class ProfileMapper
 * @author Antoine Bon
 */
class ProfileMapper
{
    /**
     * MongoCollection instance
     *
     * @var MongoCollection
     */
    protected $mongoCollection;

    /**
     * Options used for MongoCollection::save()
     *
     * @var array
     */
    protected $saveOptions;

    /**
     * Hostname associated to each profile
     *
     * @var string
     */
    protected $hostname = 'all';

    /**
     * Find skip setting
     *
     * @var integer
     */
    protected $skip = 0;

    /**
     * Find limit setting
     *
     * @var integer
     */
    protected $limit = 0;

    /**
     * Find text filter
     *
     * @var integer
     */
    protected $textFilter = false;

    /**
     * Constructor
     *
     * @param Mongo|MongoClient|array|Traversable $mongo
     * @param string|MongoDB $database
     * @param string $collection
     * @param array $saveOptions
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($mongo, $database = null, $collection = null, array $saveOptions = array())
    {
        if ($mongo instanceof Traversable) {
            // Configuration may be multi-dimensional due to save options
            $mongo = ArrayUtils::iteratorToArray($mongo);
        }
        if (is_array($mongo)) {
            $saveOptions = isset($mongo['save_options']) ? $mongo['save_options'] : array();
            $collection  = isset($mongo['collection']) ? $mongo['collection'] : null;
            $database    = isset($mongo['database']) ? $mongo['database'] : null;
            $mongo       = isset($mongo['mongo']) ? $mongo['mongo'] : null;
        }

        if (null === $collection) {
            throw new Exception\InvalidArgumentException(
                    'The collection parameter cannot be empty'
            );
        }

        if (null === $database) {
            throw new Exception\InvalidArgumentException(
                    'The database parameter cannot be empty'
            );
        }

        if (!($mongo instanceof MongoClient || $mongo instanceof Mongo)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Parameter of type %s is invalid; must be MongoClient or Mongo',
                (is_object($mongo) ? get_class($mongo) : gettype($mongo))
            ));
        }

        $this->mongoCollection = $mongo->selectCollection($database, $collection);
		// Set write concern
		$this->mongoCollection->setWriteConcern(0);

        $this->saveOptions     = $saveOptions;
    }

    public function setHostname($hostname)
    {
        $this->hostname = $hostname;
    }

    /**
     * Write a profile to the mongo database
     *
     * @param array $profile Profiler Collected Data
     * @return void
     * @throws Exception\RuntimeException
     */
    public function save(array $profile)
    {
        if (null === $this->mongoCollection) {
            throw new Exception\RuntimeException('MongoCollection must be defined');
        }

        if ($this->hostname !== 'all') {
            $profile['report']['hostname'] = $this->hostname;
        }

        $this->mongoCollection->save($profile, $this->saveOptions);
    }

    public function fetchAll()
    {
        $search = $this->mongoCollection->find($this->getDefaultFindOptions());
        if ($this->skip) {
            $search->skip($this->skip);
        }
        if ($this->limit) {
            $search->limit($this->limit);
        }
        return $search;
    }

    public function fetchAllByLogTime()
    {
        return $this->fetchAll()->sort(array('report.run' => -1));
    }

    public function fetchAllByDbTime()
    {
        return $this->fetchAll()->sort(array('db.time' => -1));
    }

    public function fetchAllByExecutionTime()
    {
        return $this->fetchAll()->sort(array('time.total' => -1));
    }

    public function fetchAllByMemoryUsage()
    {
        return $this->fetchAll()->sort(array('memory.total' => -1));
    }

    public function fetchAllIds()
    {
        return $this->mongoCollection->find($this->getDefaultFindOptions(), array('_id' => 1));
    }

    public function setSkip($iSkip)
    {
        $this->skip = $iSkip;
    }

    public function setLimit($iLimit)
    {
        $this->limit = $iLimit;
    }

    public function setTextFilter($sText)
    {
        $this->textFilter = $sText;
    }

    protected function getDefaultFindOptions()
    {
		$options = array('report.hostname' => $this->hostname);
		if ($this->textFilter) {
			$options['$text'] = array('$search' => $this->textFilter);
		}
		return $options;
    }
}


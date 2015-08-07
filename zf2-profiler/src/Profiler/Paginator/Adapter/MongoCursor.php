<?php

namespace Profiler\Paginator\Adapter;

use Zend\Paginator\Adapter\Iterator;

/**
 * Class MongoCursor
 * @author Antoine Bon
 */
class MongoCursor extends Iterator implements \Countable
{
    /**
     * Constructor.
     *
     * @param  \Iterator $iterator Iterator to paginate
     * @throws \Zend\Paginator\Adapter\Exception\InvalidArgumentException
     */
    public function __construct(\MongoCursor $iterator)
    {
        $this->iterator = $iterator;
        $this->count = $iterator->count();
    }
}

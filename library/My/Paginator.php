<?php

class My_Paginator implements Countable, IteratorAggregate
{

    /**
     * @var Zend_Paginator_Adapter_Interface
     */
    protected $_adapter;

    protected $_limit = 0;

    protected $_offset = 0;

    protected $_totalItems;

    /**
     * @param Zend_Paginator_Adapter_Interface $adapter
     */
    public function __construct(Zend_Paginator_Adapter_Interface $adapter)
    {
        $this->_adapter = $adapter;
    }

    /**
     * @return mixed|Traversable
     */
    public function getIterator()
    {
        $items = $this->getItems();
        if (!$items instanceof Traversable) {
            $items = new ArrayIterator($items);
        }
        return $items;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->_adapter);
    }

    /**
     * @return mixed
     */
    public function getItems()
    {
        return $this->_adapter->getItems($this->_offset, $this->_limit);
    }

    /**
     * @return int
     */
    public function getTotalItems()
    {
        if (null === $this->_totalItems) {
            $this->_totalItems = intval($this->_adapter->count());
            if ($this->_totalItems < 0) {
                $this->_totalItems = 0;
            }
        }
        return $this->_totalItems;
    }

    /**
     * @return bool|int
     */
    public function getPreviousOffset()
    {
        $previous = false;
        if ($this->_offset > 0) {
            $previous = $this->normalizeOffset($this->_offset - $this->_limit);
        }
        return $previous;
    }

    /**
     * @return bool|int
     */
    public function getNextOffset()
    {
        $next = false;
        if ($this->getTotalItems() > $this->_offset + $this->_limit) {
            $next = $this->normalizeOffset($this->_offset + $this->_limit);
        }
        return $next;
    }

    /**
     * @param $limit
     * @return $this
     */
    public function setLimit($limit)
    {
        $this->_limit = intval($limit);
        return $this;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->_limit;
    }

    /**
     * @param int $offset
     * @return $this
     */
    public function setOffset($offset)
    {
        $this->_offset = $offset;
        return $this;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->_offset;
    }

    /**
     * @param int $page
     * @return $this
     */
    public function setPage($page)
    {
        $page = $this->normalizePage($page);
        $this->_offset = (intval($page) - 1) * $this->_limit;
        return $this;
    }

    /**
     * @return int
     */
    public function getPage()
    {
        if (!$this->_limit) {
            return 1;
        }
        return ceil($this->_offset / $this->_limit) + 1;
    }

    /**
     * @param int $page
     * @return int
     */
    public function normalizePage($page)
    {
        $page = intval($page);
        if ($page < 1) {
            $page = 1;
        }
        return $page;
    }

    /**
     * @param int $limit
     * @return int
     */
    public function normalizeLimit($limit)
    {
        $limit = intval($limit);
        if ($limit < 0) {
            $limit = 0;
        }
        return $limit;
    }

    /**
     * @param int $offset
     * @return int
     */
    public function normalizeOffset($offset)
    {
        $offset = intval($offset);
        if ($offset < 0) {
            $offset = 0;
        }
        if ($offset > 0 && $this->getTotalItems() > $offset + $this->_limit) {
            $offset = $this->getTotalItems() - $this->_limit; // show one full page
        }
        return $offset;
    }

}
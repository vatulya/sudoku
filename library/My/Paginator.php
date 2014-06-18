<?php

class My_Paginator implements Countable, IteratorAggregate
{

    /**
     * @var Zend_Paginator_Adapter_Interface
     */
    protected $_adapter;

    /**
     * @var mixed
     */
    protected $_source;

    protected $_limit = 0;

    protected $_offset = 0;

    protected $_totalItems;

    protected $_fullLastPage = false;

    public function __construct($source)
    {
        $this->_adapter = $this->initAdapter($source);
        $this->_source = $source;
    }

    /**
     * @param mixed $source
     * @return Zend_Paginator_Adapter_Interface|null
     */
    public function initAdapter($source)
    {
        switch (true) {
            case $source instanceof Zend_Db_Select:
                $adapter = new Zend_Paginator_Adapter_DbSelect($source);
                break;
            case is_array($source):
                $adapter = new Zend_Paginator_Adapter_Array($source);
                break;
            default:
                $adapter = null;
                break;
        }
        return $adapter;
    }

    /**
     * @return Zend_Paginator_Adapter_Interface
     */
    public function getAdapter()
    {
        return $this->_adapter;
    }

    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->_source;
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
        return $this->getTotalItems();
    }

    /**
     * @return mixed
     */
    public function getItems()
    {
        return $this->_adapter->getItems($this->getOffset(), $this->getLimit());
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
        if ($this->getOffset() > 0) {
            $previous = $this->normalizeOffset($this->getOffset() - $this->getLimit());
        }
        return $previous;
    }

    /**
     * @return bool|int
     */
    public function getNextOffset()
    {
        $next = false;
        if ($this->getTotalItems() > $this->getOffset() + $this->getLimit()) {
            $next = $this->normalizeOffset($this->getOffset() + $this->getLimit());
        }
        return $next;
    }

    /**
     * @param $limit
     * @return $this
     */
    public function setLimit($limit)
    {
        $this->_limit = $this->normalizeLimit($limit);
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
        $this->_offset = $this->normalizeOffset($offset);
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
    public function setCurrentPage($page)
    {
        $page = $this->normalizePage($page);
        $this->setOffset(($page - 1) * $this->getLimit());
        return $this;
    }

    /**
     * @return int
     */
    public function getCurrentPage()
    {
        if (!$this->getLimit()) {
            return 1;
        }
        return (int)ceil($this->getOffset() / $this->getLimit()) + 1;
    }

    /**
     * @return int
     */
    public function getTotalPages()
    {
        if (!$this->getLimit() || !$this->getTotalItems()) {
            return 1;
        }
        return (int)ceil($this->getTotalItems() / $this->getLimit());
    }

    /**
     * Example:
     * [
     *     ['page' => 1, 'offset' =  0, 'isCurrent' = false],
     *     ['page' => 2, 'offset' = 10, 'isCurrent' = true ],
     *     ['page' => 3, 'offset' = 20, 'isCurrent' = false],
     * ]
     *
     * @return array
     */
    public function getPages()
    {
        $pages = [];
        $totalPages = $this->getTotalPages();
        for ($i = 1; $totalPages >= $i; $i++) {
            $pages[] = [
                'page'      => $i,
                'offset'    => $this->normalizeOffset(($i - 1) * $this->getLimit()),
                'isCurrent' => $i == $this->getCurrentPage(),
            ];
        }
        return $pages;
    }

    /**
     * @param boolean $fullLastPage
     * @return $this
     */
    public function setFullLastPage($fullLastPage)
    {
        $this->_fullLastPage = (bool)$fullLastPage;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getFullLastPage()
    {
        return $this->_fullLastPage;
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
        if ($offset > 0 && ($offset + $this->getLimit()) > $this->getTotalItems()) {
            if ($this->getFullLastPage()) {
                $offset = $this->getTotalItems() - $this->getLimit(); // show one full page
            } else {
                if ($offset > $this->getTotalItems()) {
                    $offset = $this->getTotalItems() - 1; // absolutely wrong offset. show only one element
                }
            }
        }
        return $offset;
    }

}
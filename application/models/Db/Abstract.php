<?php

abstract class Application_Model_Db_Abstract
{

    const TABLE_NAME = '';

    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;

    public function __construct()
    {
        $this->_db = Zend_Db_Table::getDefaultAdapter();
    }

    /**
     * @param array $parameters
     * @return array
     */
    public function getOne(array $parameters = [], array $order = [])
    {
        $select = $this->_db->select()->from(static::TABLE_NAME);
        foreach ($parameters as $field => $value) {
            $select->where($field . ' = ?', $value);
        }
        if (!empty($order)) {
            $select->order($order);
        }
        $select->limit(1);
        $result = $this->_db->fetchRow($select);
        return $result;
    }

    /**
     * @param array $parameters
     * @param array $order
     * @return array
     */
    public function getAll(array $parameters = [], array $order = [])
    {
        $select = $this->_db->select()->from(static::TABLE_NAME);
        foreach ($parameters as $field => $value) {
            $expression = is_array($value) ? ' IN (?)' : ' = ?';
            $select->where($field . $expression, $value);
        }
        if (!empty($order)) {
            $select->order($order);
        }
        $result = $this->_db->fetchAll($select);
        return $result;
    }

    public function getNow()
    {
        return (new \DateTime('NOW', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    }

    /**
     * @param array $data
     * @return int
     */
    abstract public function insert(array $data);

    /**
     * @param $id
     * @param array $data
     * @return bool
     */
    abstract public function update($id, array $data);

    /**
     * @param $id
     * @return bool
     */
    abstract public function delete($id);

}
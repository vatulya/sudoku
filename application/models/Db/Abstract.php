<?php

abstract class Application_Model_Db_Abstract
{

    protected $_db;

    public function __construct()
    {
        $this->_db = Zend_Db_Table::getDefaultAdapter();
    }

}
<?php

class Application_Model_Database
{

    protected $_db;

    public function __construct()
    {
        $this->_db = Zend_Db_Table::getDefaultAdapter();
    }

}
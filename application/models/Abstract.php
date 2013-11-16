<?php

abstract class Application_Model_Abstract extends Application_Model_Database
{

    protected $_modelDb;

    protected function _getDatabase()
    {
        return $this->_db;
    }

}
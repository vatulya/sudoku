<?php

abstract class Application_Model_Abstract
{

    protected $modelDb;

    protected function initModelDb()
    {
        if (is_string($this->modelDb)) {
            $class = 'Application_Model_Db_' . $this->modelDb;
            $this->modelDb = new $class();
        }
        return $this;
    }

    /**
     * @return Application_Model_Db_Abstract
     */
    protected function getModelDb()
    {
        if (is_string($this->modelDb)) {
            $this->initModelDb();
        }
        return $this->modelDb;
    }

}
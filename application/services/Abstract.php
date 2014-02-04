<?php

abstract class Application_Service_Abstract
{

    const DB_MODEL_NAME = '';

    const DB_MODEL_NAME_TEMPLATE = 'Application_Model_Db_%s';

    private static $_instances = array();

    /**
     * @var Application_Model_Db_Abstract
     */
    protected $_modelDb;

    /**
     * @return $this
     */
    public static function getInstance()
    {
        $class = get_called_class();
        if (!isset(Application_Service_Abstract::$_instances[$class])) {
            Application_Service_Abstract::$_instances[$class] = new $class();
        }
        return Application_Service_Abstract::$_instances[$class];
    }

    protected function __construct()
    {
    }

    /**
     * @return $this
     */
    protected function initModelDb()
    {
        if ($model = static::DB_MODEL_NAME) {
            $model = sprintf(static::DB_MODEL_NAME_TEMPLATE, $model);
            $this->_modelDb = new $model();
        }
        return $this;
    }

    /**
     * @return Application_Model_Db_Abstract
     */
    protected function getModelDb()
    {
        if (is_null($this->_modelDb)) {
            $this->initModelDb();
        }
        return $this->_modelDb;
    }

}
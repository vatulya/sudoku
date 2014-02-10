<?php

abstract class Application_Service_Abstract
{

    const DB_MODEL_NAME = '';

    const DB_MODEL_NAME_TEMPLATE = 'Application_Model_Db_%s';

    private static $instances = array();

    /**
     * @var Application_Model_Db_Abstract
     */
    protected $modelDb;

    /**
     * @return $this
     */
    public static function getInstance()
    {
        $class = get_called_class();
        if (!isset(Application_Service_Abstract::$instances[$class])) {
            Application_Service_Abstract::$instances[$class] = new $class();
        }
        return Application_Service_Abstract::$instances[$class];
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
            $this->modelDb = new $model();
        }
        return $this;
    }

    /**
     * @return Application_Model_Db_Abstract
     */
    public function getModelDb()
    {
        if (is_null($this->modelDb)) {
            $this->initModelDb();
        }
        return $this->modelDb;
    }

}
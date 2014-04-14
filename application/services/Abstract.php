<?php

abstract class Application_Service_Abstract
{

    private static $instances = [];

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
        if (is_string($this->modelDb)) {
            $model = 'Application_Model_Db_' . $this->modelDb;
            $this->modelDb = new $model();
        }
        return $this;
    }

    /**
     * @return Application_Model_Db_Abstract
     */
    public function getModelDb()
    {
        if (is_string($this->modelDb)) {
            $this->initModelDb();
        }
        return $this->modelDb;
    }

}
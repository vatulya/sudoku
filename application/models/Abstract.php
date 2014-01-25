<?php

abstract class Application_Model_Abstract
{

    const DB_MODEL_NAME = '';

    const DB_MODEL_NAME_TEMPLATE = 'Application_Model_Db_%s';

    private static $_instances = array();

    protected $_modelDb;

    public static function getInstance()
    {
        $class = get_called_class();
        if (!isset(Application_Model_Abstract::$_instances[$class])) {
            Application_Model_Abstract::$_instances[$class] = new $class();
            Application_Model_Abstract::$_instances[$class]->init();
        }
        return Application_Model_Abstract::$_instances[$class];
    }

    /**
     * You can create new object, but you MUST call init() after that
     */
    public function __construct()
    {
    }

    protected function init()
    {
        $class = get_called_class();
        if ($model = $class::DB_MODEL_NAME) {
            $model = sprintf(self::DB_MODEL_NAME_TEMPLATE, $model);
            $this->_modelDb = new $model();
        }
    }

}
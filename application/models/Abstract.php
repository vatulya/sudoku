<?php

abstract class Application_Model_Abstract
{

    protected static $modelDb;

    protected static function initModelDb()
    {
        if (is_string(static::$modelDb)) {
            $class = 'Application_Model_Db_' . static::$modelDb;
            static::$modelDb = new $class();
        }
    }

    /**
     * @return Application_Model_Db_Abstract
     */
    protected static function getModelDb()
    {
        if (is_string(static::$modelDb)) {
            static::initModelDb();
        }
        return static::$modelDb;
    }

}
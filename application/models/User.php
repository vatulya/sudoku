<?php

class Application_Model_User extends Application_Model_Abstract
{

    const DB_MODEL_NAME = 'Users';

    /**
     * @var Application_Model_Db_Users
     */
    protected $_modelDb;

    public function getById($id)
    {
        return $this->_modelDb->getUserById($id);
    }

}

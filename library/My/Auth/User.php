<?php

class My_Auth_User
{

    /**
     * @var My_Auth_User
     */
    protected static $_instance;

    /**
     * @return My_Auth_User
     */
    public static function getInstance()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    protected function __construct()
    {
    }

    /**
     * @param array $loginData
     * @return array
     */
    public function login(array $loginData)
    {
        $adapter = new My_Auth_Adapter($loginData);
        $auth = Zend_Auth::getInstance();
        $result = $auth->authenticate($adapter);
        if ($result->isValid()) {
            $user = $result->getIdentity();
            $data = [
                'user_id' => $user['id'],
                'session_id' => session_id(),
                'ip' => Zend_Controller_Front::getInstance()->getRequest()->getServer('REMOTE_ADDR'),
            ];
            (new Application_Model_Db_User_Sessions())->insert($data);
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function logout()
    {
        $auth = Zend_Auth::getInstance();
        $auth->clearIdentity();
        return true;
    }

    /**
     * @return array
     */
    public function getCurrentUser()
    {
        $auth = Zend_Auth::getInstance();
        $user = $auth->hasIdentity() ? $auth->getIdentity() : [];
        return $user;
    }

}
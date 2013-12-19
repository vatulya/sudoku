<?php

class Application_Model_Auth extends Application_Model_Abstract
{

    const DB_MODEL_NAME = 'Users';

    const ROLE_GUEST = 0;
    const ROLE_USER  = 20;

    public function login($email, $password)
    {
        $adapter = new Application_Model_AuthAdapter($email, $password);
        $auth = Zend_Auth::getInstance();
        $result = $auth->authenticate($adapter);
        if ($result->isValid()) {
            return $result->getIdentity();
        }
        return false;
    }

    public function logout()
    {
        $auth = Zend_Auth::getInstance();
        $auth->clearIdentity();
        return true;
    }

    public function getCurrentUser()
    {
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            return $auth->getIdentity();
        }
        return null;
    }

    public function _changePassword($userId, $newPassword)
    {
        $newPasswordEncoded = Application_Model_AuthAdapter::encodePassword($newPassword);
        $result = $this->_modelDb->savePassword($userId, $newPasswordEncoded);
        return $result;
    }

    static public function getRole($asString = false)
    {
        $stringAliases = array(
            self::ROLE_GUEST => 'GUEST',
            self::ROLE_USER  => 'USER',
        );
        $auth = Zend_Auth::getInstance();
        $role = self::ROLE_GUEST;
        if ($auth->hasIdentity()) {
            $user = $auth->getIdentity();
            $role = $user['role'];
        }
        if ($asString) {
            $role = $stringAliases[$role];
        }
        return $role;
    }

    static public function getAllowedRoles()
    {
        $roles = array(
            self::ROLE_GUEST => 'Guest',
            self::ROLE_USER  => 'User',
        );
        return $roles;
    }

}
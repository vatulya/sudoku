<?php

class Application_Model_Auth extends Application_Model_Abstract
{

    const DB_MODEL_NAME = 'Users';

    const ROLE_GUEST = 0;
    const ROLE_USER  = 20;

    /**
     * @param string $loginEmail
     * @param string $password
     * @return bool
     */
    public function login($loginEmail, $password)
    {
        $errors = array();
        if (empty($loginEmail)) {
            $errors[] = array(
                'name'  => 'login-email',
                'title' => 'Login or Email',
                'text'  => 'Please enter Login or Email',
            );
        }
        if (empty($password)) {
            $errors[] = array(
                'name'  => 'password',
                'title' => 'Password',
                'text'  => 'Please enter Password',
            );
        }
        if (empty($errors)) {
            $adapter = new Application_Model_AuthAdapter($loginEmail, $password);
            $auth = Zend_Auth::getInstance();
            $result = $auth->authenticate($adapter);
            if (!$result->isValid()) {
                $errors[] = array(
                    'name' => 'loginForm',
                    'title' => 'Login form',
                    'text' => 'Wrong Login data. No such user in database',
                );
            }
        }
        return $errors;
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
        return self::ROLE_GUEST;

        // TODO: finish it

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
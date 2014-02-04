<?php

class My_Auth_User
{

    /**
     * @var My_Auth_User
     */
    protected static $_instance;

    const ROLE_GUEST = 0;
    const ROLE_USER  = 20;

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
     * @param string $loginEmail
     * @param string $password
     * @return array
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
            $adapter = new My_Auth_Adapter_Main($loginEmail, $password);
            $auth = Zend_Auth::getInstance();
            $result = $auth->authenticate($adapter);
            if (!$result->isValid()) {
                $errors[] = array(
                    'name'  => 'loginForm',
                    'title' => 'Login form',
                    'text'  => 'Wrong Login data. No such user in database',
                );
            }
        }
        return $errors;
    }

    /**
     * @param array $user
     * @return array
     */
    public function loginOther(array $user)
    {
        $errors = array();
        $adapter = new My_Auth_Adapter_Other($user);
        $auth = Zend_Auth::getInstance();
        $result = $auth->authenticate($adapter);
        if (!$result->isValid()) {
            $errors[] = array(
                'name'  => 'LoginError',
                'title' => 'Login error',
                'text'  => 'Wrong Login data',
            );
        }
        return $errors;
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
        $user = $auth->hasIdentity() ? $auth->getIdentity() : array();
        return $user;
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
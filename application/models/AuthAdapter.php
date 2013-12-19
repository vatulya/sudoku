<?php

class Application_Model_AuthAdapter implements Zend_Auth_Adapter_Interface
{

    protected $_email;
    protected $_password; // SHA1

    public function __construct($email, $password)
    {
        $this->_email = $email;
        $this->_password = Application_Model_AuthAdapter::encodePassword($password);
    }

    public function authenticate()
    {
        $users = new Application_Model_Db_Users();
        $user = $users->withHiddenFields()->getUserByEmail($this->_email);
        if ($user['password'] === $this->_password) {
            $user = $users->hideFields($user);
            return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $user);
        }
        return new Zend_Auth_Result(Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID, null);
    }

    static public function encodePassword($password)
    {
        return sha1($password);
    }

}
<?php

class Application_Model_AuthAdapter implements Zend_Auth_Adapter_Interface
{

    protected $_loginEmail;
    protected $_password; // SHA1

    public function __construct($loginEmail, $password)
    {
        $this->_loginEmail = $loginEmail;
        $this->_password = Application_Model_AuthAdapter::encodePassword($password);
    }

    public function authenticate()
    {
        $users = new Application_Model_Db_Users();
        $emailValidator = new Zend_Validate_EmailAddress();
        if ($emailValidator->isValid($this->_loginEmail)) {
            $user = $users->withHiddenFields()->getUserByEmail($this->_loginEmail);
        } else {
            $user = $users->withHiddenFields()->getUserByLogin($this->_loginEmail);
        }
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
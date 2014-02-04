<?php

class My_Auth_Adapter_Main implements Zend_Auth_Adapter_Interface
{

    /**
     * @var string
     */
    protected $loginOrEmail;

    /**
     * @var string
     */
    protected $password; // SHA1

    /**
     * @param string $loginOrEmail
     * @param string $password
     */
    public function __construct($loginOrEmail, $password)
    {
        $this->loginOrEmail = $loginOrEmail;
        $this->password = self::encodePassword($password);
    }

    /**
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        $users = new Application_Model_Db_Users();
        $emailValidator = new Zend_Validate_EmailAddress();
        if ($emailValidator->isValid($this->loginOrEmail)) {
            $user = $users->withHiddenFields()->getByEmail($this->loginOrEmail);
        } else {
            $user = $users->withHiddenFields()->getByLogin($this->loginOrEmail);
        }
        if ($user['password'] === $this->password) {
            $user = $users->hideFields($user);
            return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $user);
        }
        return new Zend_Auth_Result(Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID, null);
    }

    /**
     * @param string $password
     * @return string
     */
    static public function encodePassword($password)
    {
        return sha1($password);
    }

}
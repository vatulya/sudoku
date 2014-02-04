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
        $usersDbModel = new Application_Model_Db_Users();
        $emailValidator = new Zend_Validate_EmailAddress();
        $user = array();
        if ($emailValidator->isValid($this->loginOrEmail)) {
            $user = $usersDbModel->getOne(array('email' => $this->loginOrEmail, 'password' => $this->password));
        }
        if (empty($user)) {
            $user = $usersDbModel->getOne(array('login' => $this->loginOrEmail, 'password' => $this->password));
        }
        if (!empty($user)) {
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
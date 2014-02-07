<?php

class My_Auth_Adapter implements Zend_Auth_Adapter_Interface
{

    /**
     * @var array
     */
    protected $loginData;

    /**
     * @param array $loginData
     */
    public function __construct(array $loginData)
    {
        $this->loginData = $loginData;
    }

    /**
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        $serviceUser = Application_Service_User::getInstance();
        $user = $serviceUser->getByData($this->loginData);
        if (!empty($user)) {
            return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $user);
        }
        return new Zend_Auth_Result(Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID, null);
    }

}
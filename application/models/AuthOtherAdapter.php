<?php

class Application_Model_AuthOtherAdapter implements Zend_Auth_Adapter_Interface
{

    protected $_user;

    public function __construct(array $user)
    {
        $this->_user = $user;
    }

    public function authenticate()
    {
        if (isset($this->_user['network'], $this->_user['network_id'])) {
            $userOtherModel = new Application_Model_Db_UsersOther();
            $user = $userOtherModel->getUserByNetworkAndId($this->_user['network'], $this->_user['network_id']);
            if (!$user) {
                $userId = $userOtherModel->insert($this->_user);
                if (!$userId) {
                    throw new Exception('Something wrong!');
                }
                $user = $userOtherModel->getUserById($userId);
            }
            if (!$user) {
                throw new Exception('Something wrong!');
            }
            return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $user);
        }
        return new Zend_Auth_Result(Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID, null);
    }

}
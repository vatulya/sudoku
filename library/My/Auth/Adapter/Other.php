<?php

class My_Auth_Adapter_Other implements Zend_Auth_Adapter_Interface
{

    /**
     * @var array
     */
    protected $user;

    /**
     * @param array $user
     */
    public function __construct(array $user)
    {
        $this->user = $user;
    }

    /**
     * @return Zend_Auth_Result
     * @throws Exception
     */
    public function authenticate()
    {
        if (isset($this->user['network'], $this->user['network_id'])) {
            $userOtherModel = new Application_Model_Db_UsersOther();
            $user = $userOtherModel->getByNetworkAndId($this->user['network'], $this->user['network_id']);
            if (!$user) {
                $userId = $userOtherModel->insert($this->user);
                if (!$userId) {
                    throw new Exception('Something wrong!');
                }
                $user = $userOtherModel->getById($userId);
            }
            if (!$user) {
                throw new Exception('Something wrong!');
            }
            return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $user);
        }
        return new Zend_Auth_Result(Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID, null);
    }

}